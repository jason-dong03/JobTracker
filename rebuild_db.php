<?php
require_once 'config/db.php';
$db = get_db();

echo "Starting database rebuild...\n";

// 1. Disable foreign key checks so we can drop tables in any order
$db->query("SET FOREIGN_KEY_CHECKS = 0");

// 2. Get all tables and drop them
$result = $db->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $table = $row[0];
    $db->query("DROP TABLE IF EXISTS $table");
    echo "Dropped table: $table\n";
}

// 3. Re-enable foreign key checks
$db->query("SET FOREIGN_KEY_CHECKS = 1");

// 4. Read and execute schema.sql
$sql = file_get_contents('db/schema.sql');

// Remove the "CREATE DATABASE" and "USE" lines if they cause permission errors
$sql = preg_replace('/CREATE DATABASE IF NOT EXISTS.*;/i', '', $sql);
$sql = preg_replace('/USE .*;/i', '', $sql);

if ($db->multi_query($sql)) {
    do {
        // flush multi_queries
    } while ($db->next_result());
    echo "Successfully recreated tables from schema.sql!\n";
} else {
    echo "Error recreating tables: " . $db->error . "\n";
}

echo "Rebuild complete!\n";
