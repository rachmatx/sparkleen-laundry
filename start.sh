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
            \$host = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: getenv('database.default.hostname') ?: 'localhost';
            \$port = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: getenv('database.default.port') ?: '3306';
            \$user = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: getenv('database.default.username') ?: 'root';
            \$pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: getenv('database.default.password') ?: '';
            \$conn = @new mysqli(\$host, \$user, \$pass, null, (int)\$port);
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

    # Seed master tables (idempotent check is inside the seeder)
    echo "[INFO] Running MasterSeeder..."
    php spark db:seed MasterSeeder 2>&1 || echo "[WARNING] MasterSeeder failed"

    # Seed Admin user (idempotent)
    echo "[INFO] Ensuring default Admin user exists..."
    php spark db:seed AdminSeeder 2>&1 || echo "[WARNING] AdminSeeder failed"
) &

# Start Apache
echo "[INFO] Starting Apache on port $PORT..."

# FORCE cleanup of any conflicting MPMs that might have been injected at runtime
rm -f /etc/apache2/mods-enabled/mpm_event.load
rm -f /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load
rm -f /etc/apache2/mods-enabled/mpm_worker.conf

exec apache2-foreground
