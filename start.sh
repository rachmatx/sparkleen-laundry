#!/bin/bash
set -e

echo "========================================="
echo " Sparkleen Laundry - Startup Script"
echo "========================================="

# Set default PORT if not provided by Railway
export PORT=${PORT:-8080}
echo "[INFO] Server will listen on port: $PORT"

# Run database operations in the background so they don't block Apache from starting
# This prevents the 60s healthcheck timeout if the DB is slow to initialize
(
    # Wait for database to be ready (Railway MySQL might take a moment)
    echo "[INFO] Waiting for database connection..."
    MAX_RETRIES=30
    RETRY_COUNT=0

    while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
        if php -r "
            \$host = getenv('database.default.hostname') ?: 'localhost';
            \$port = getenv('database.default.port') ?: '3306';
            \$conn = @new mysqli(\$host, 
                getenv('database.default.username') ?: 'root',
                getenv('database.default.password') ?: '',
                null,
                (int)\$port
            );
            if (\$conn->connect_error) { exit(1); }
            \$conn->close();
            exit(0);
        " 2>/dev/null; then
            echo "[INFO] Database connection successful!"
            break
        fi
        
        RETRY_COUNT=$((RETRY_COUNT + 1))
        echo "[INFO] Waiting for database... attempt $RETRY_COUNT/$MAX_RETRIES"
        sleep 2
    done

    if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
        echo "[WARNING] Could not connect to database after $MAX_RETRIES attempts."
        echo "[WARNING] Starting server anyway — migrations may fail."
    fi

    # Run database migrations
    echo "[INFO] Running database migrations..."
    php spark migrate --all 2>&1 || echo "[WARNING] Migration failed or already up-to-date"

    # Seed master tables (only if empty — idempotent check)
    echo "[INFO] Checking master tables..."
    php -r "
        require 'vendor/autoload.php';
        \$app = \CodeIgniter\Config\Services::codeigniter();
        \$app->initialize();
        \$db = \Config\Database::connect();
        try {
            \$count = \$db->table('tb_master_status_transaksi')->countAllResults();
            if (\$count == 0) {
                echo 'Master tables empty — running MasterSeeder...' . PHP_EOL;
                \$seeder = \Config\Database::seeder();
                \$seeder->call('MasterSeeder');
                echo 'MasterSeeder completed.' . PHP_EOL;
            } else {
                echo 'Master tables already populated (' . \$count . ' rows). Skipping seed.' . PHP_EOL;
            }
        } catch (Exception \$e) {
            echo 'Seed check failed: ' . \$e->getMessage() . PHP_EOL;
        }
    " 2>&1 || echo "[WARNING] Seeder check failed"
) &

# Start Apache
echo "[INFO] Starting Apache on port $PORT..."

# FORCE cleanup of any conflicting MPMs that might have been injected at runtime
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf

exec apache2-foreground
