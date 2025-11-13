<?php
require_once 'connection.php';

try {
    // Leer el archivo SQL
    $sql = file_get_contents('database.sql');

    // Ejecutar el SQL
    $pdo->exec($sql);

    echo "Database setup completed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
