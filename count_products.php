<?php
require_once 'connection.php';

try {
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM productos');
    $result = $stmt->fetch();
    echo 'Total productos en BD: ' . $result['total'] . PHP_EOL;

    $stmt = $pdo->query('SELECT COUNT(*) as total FROM productos WHERE activo = 1');
    $result = $stmt->fetch();
    echo 'Total productos activos: ' . $result['total'] . PHP_EOL;

    // Mostrar algunos productos para verificar
    $stmt = $pdo->query('SELECT nombre, stock, activo FROM productos LIMIT 15');
    $productos = $stmt->fetchAll();
    echo 'Primeros 15 productos:' . PHP_EOL;
    foreach ($productos as $p) {
        echo '- ' . $p['nombre'] . ' (Stock: ' . $p['stock'] . ', Activo: ' . ($p['activo'] ? 'Sí' : 'No') . ')' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
