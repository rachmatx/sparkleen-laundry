#!/bin/bash
set -euo pipefail
repo_root=$(cd "$(dirname "$0")/../.." && pwd)
scratch_dir=$(mktemp -d)
trap '/bin/rm -rf "$scratch_dir"' EXIT
mkdir "$scratch_dir/bin"
export STARTUP_TEST_LOG="$scratch_dir/calls.log"
cat > "$scratch_dir/bin/php" <<'STUB'
#!/bin/bash
printf 'php %s\n' "$*" >> "$STARTUP_TEST_LOG"
exit "${STARTUP_TEST_MIGRATION_STATUS:-0}"
STUB
cat > "$scratch_dir/bin/apache2-foreground" <<'STUB'
#!/bin/bash
printf 'apache\n' >> "$STARTUP_TEST_LOG"
STUB
cat > "$scratch_dir/bin/rm" <<'STUB'
#!/bin/bash
exit 0
STUB
chmod +x "$scratch_dir/bin/"*
export PATH="$scratch_dir/bin:$PATH"

RUN_DATABASE_MIGRATIONS=0 bash "$repo_root/start.sh" > /dev/null
[ "$(cat "$STARTUP_TEST_LOG")" = 'apache' ]
echo 'PASS: default startup does not migrate or seed'

: > "$STARTUP_TEST_LOG"
RUN_DATABASE_MIGRATIONS=1 bash "$repo_root/start.sh" > /dev/null
[ "$(cat "$STARTUP_TEST_LOG")" = $'php spark migrate --all\napache' ]
echo 'PASS: explicit migration finishes before Apache starts'

: > "$STARTUP_TEST_LOG"
if RUN_DATABASE_MIGRATIONS=1 STARTUP_TEST_MIGRATION_STATUS=9 bash "$repo_root/start.sh" > /dev/null; then
    echo 'FAIL: migration error was ignored' >&2
    exit 1
fi
[ "$(cat "$STARTUP_TEST_LOG")" = 'php spark migrate --all' ]
echo 'PASS: migration failure prevents Apache startup'
# Restore rm for the cleanup trap.
export PATH="${PATH#*:}"
