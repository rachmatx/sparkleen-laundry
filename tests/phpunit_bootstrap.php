<?php
declare(strict_types=1);

/**
 * Custom PHPUnit bootstrap for laundry_app.
 * Loads the CI4 framework bootstrap, then ensures the test database
 * is in a clean state so DatabaseTestTrait's regress/migrate flow works.
 *
 * Without this, prior test runs can leave a partially-applied migrations
 * table (orphan history rows with no underlying tables), which makes
 * regress() call down() on migrations that have nothing to drop.
 */

require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

$conn = @new mysqli('localhost', 'root', '', 'laundry_db_test');
if ($conn && !$conn->connect_error) {
    $res = $conn->query('SHOW TABLES');
    if ($res) {
        $conn->query('SET FOREIGN_KEY_CHECKS=0');
        while ($row = $res->fetch_array()) {
            $conn->query("DROP TABLE IF EXISTS `{$row[0]}`");
        }
        $conn->query('SET FOREIGN_KEY_CHECKS=1');
    }
    $conn->close();
}

service('routes')->loadRoutes();
