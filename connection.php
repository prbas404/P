<?php
// Conexión a la base de datos usando PDO
$host = 'localhost';
$dbname = 'plus'; // Cambiar según tu configuración
$username = 'root'; // Cambiar según tu configuración
$password = ''; // Cambiar según tu configuración

try {
    // connection.php
$pdo = new PDO("mysql:host=$host;port=33060;dbname=$dbname;charset=utf8", $username, $password);;
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
