<?php

namespace App\Services;

class BackupService
{
    /**
     * Generate a full SQL dump of the current database.
     * Includes CREATE TABLE statements and INSERT data for all tables.
     *
     * @return string Complete SQL dump
     */
    public function generateSqlDump(): string
    {
        $db = \Config\Database::connect();
        $tables = $db->listTables();

        $sqlDump = "-- Sparkleen Laundry Database Backup\n";
        $sqlDump .= "-- Generated on " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- Host: " . $db->hostname . "\n";
        $sqlDump .= "-- Database: " . $db->database . "\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Dapatkan skema tabel (CREATE TABLE)
            $query = $db->query("SHOW CREATE TABLE `" . $table . "`");
            $row = $query->getRowArray();

            $sqlDump .= "-- Table structure for table `$table`\n";
            $sqlDump .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
            $sqlDump .= $row['Create Table'] . ";\n\n";

            // Dapatkan isi data tabel
            $rows = $db->table($table)->get()->getResultArray();
            if (!empty($rows)) {
                $sqlDump .= "-- Dumping data for table `$table`\n";
                foreach ($rows as $r) {
                    $keys = array_keys($r);
                    $escaped_values = [];
                    foreach ($r as $val) {
                        if ($val === null) {
                            $escaped_values[] = "NULL";
                        } else {
                            $escaped_values[] = $db->escape($val);
                        }
                    }
                    $sqlDump .= "INSERT INTO `" . $table . "` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escaped_values) . ");\n";
                }
                $sqlDump .= "\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sqlDump;
    }
}
