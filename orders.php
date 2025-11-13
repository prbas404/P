<?php
session_start();
require_once 'connection.php';
require_once 'auth.php';
// Función para crear orden desde carrito
$carrito = obtenerCarrito(); // 🚨 Sin el $usuarioId
if (empty($carrito)) {
    return ['success' => false, 'message' => 'El carrito está vacío'];
}


function crearOrden($usuarioId) {
    global $pdo;

    // Obtener items del carrito
    $carrito = obtenerCarrito(); 
    if (empty($carrito)) {
        return ['success' => false, 'message' => 'El carrito está vacío'];
    }

    // Calcular total
    $total = 0;
    foreach ($carrito as $item) {
        $total += $item['subtotal'];
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // Crear orden
        $stmt = $pdo->prepare("INSERT INTO ordenes (usuario_id, total) VALUES (?, ?)");
        $stmt->execute([$usuarioId, $total]);
        $ordenId = $pdo->lastInsertId();

        // Agregar detalles de la orden
        foreach ($carrito as $item) {
            $stmt = $pdo->prepare("INSERT INTO orden_detalles (orden_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ordenId, $item['producto_id'], $item['cantidad'], $item['precio'], $item['subtotal']]);

            // Actualizar stock
            $stmt = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['cantidad'], $item['producto_id']]);
        }

        // Vaciar carrito
        $stmt = $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);

        $pdo->commit();
        return ['success' => true, 'message' => 'Orden creada exitosamente', 'orden_id' => $ordenId];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error al crear la orden: ' . $e->getMessage()];
    }
}

// Función para obtener órdenes del usuario
function obtenerOrdenesUsuario($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(od.id) as total_productos
        FROM ordenes o
        LEFT JOIN orden_detalles od ON o.id = od.orden_id
        WHERE o.usuario_id = ?
        GROUP BY o.id
        ORDER BY o.fecha_creacion DESC
    ");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll();
}

// Función para obtener detalles de una orden
function obtenerDetallesOrden($ordenId, $usuarioId) {
    global $pdo;
    // Verificar que la orden pertenece al usuario
    $stmt = $pdo->prepare("SELECT id FROM ordenes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$ordenId, $usuarioId]);
    if (!$stmt->fetch()) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT od.*, p.nombre, p.imagen
        FROM orden_detalles od
        JOIN productos p ON od.producto_id = p.id
        WHERE od.orden_id = ?
    ");
    $stmt->execute([$ordenId]);
    return $stmt->fetchAll();
}

// Función para obtener todas las órdenes (admin)
function obtenerTodasOrdenes() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT o.*, u.nombre as usuario_nombre, COUNT(od.id) as total_productos
        FROM ordenes o
        JOIN usuarios u ON o.usuario_id = u.id
        LEFT JOIN orden_detalles od ON o.id = od.orden_id
        GROUP BY o.id
        ORDER BY o.fecha_creacion DESC
    ");
    return $stmt->fetchAll();
}

// Función para actualizar estado de orden (admin)
function actualizarEstadoOrden($ordenId, $estado) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE ordenes SET estado = ? WHERE id = ?");
    return $stmt->execute([$estado, $ordenId]);
}

// Función para obtener reporte de ventas por producto
function reporteVentasProducto($fechaInicio = null, $fechaFin = null) {
    global $pdo;

    $where = "";
    $params = [];

    if ($fechaInicio && $fechaFin) {
        $where = "WHERE o.fecha_creacion BETWEEN ? AND ?";
        $params = [$fechaInicio, $fechaFin];
    }

    $stmt = $pdo->prepare("
        SELECT p.nombre, SUM(od.cantidad) as total_vendido, SUM(od.subtotal) as total_ventas
        FROM orden_detalles od
        JOIN productos p ON od.producto_id = p.id
        JOIN ordenes o ON od.orden_id = o.id
        $where
        GROUP BY p.id, p.nombre
        ORDER BY total_ventas DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Función para obtener reporte de ventas por período
function reporteVentasPeriodo($fechaInicio = null, $fechaFin = null) {
    global $pdo;

    $where = "";
    $params = [];

    if ($fechaInicio && $fechaFin) {
        $where = "WHERE fecha_creacion BETWEEN ? AND ?";
        $params = [$fechaInicio, $fechaFin];
    }

    $stmt = $pdo->prepare("
        SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total_ordenes, SUM(total) as total_ventas
        FROM ordenes
        $where
        GROUP BY DATE(fecha_creacion)
        ORDER BY fecha DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    session_start();

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'ordenes_usuario':
                if (!isset($_SESSION['usuario_id'])) {
                    echo json_encode(['error' => 'No autenticado']);
                    exit;
                }
                echo json_encode(obtenerOrdenesUsuario($_SESSION['usuario_id']));
                break;
            case 'detalles_orden':
                if (!isset($_SESSION['usuario_id']) || !isset($_GET['orden_id'])) {
                    echo json_encode(['error' => 'Parámetros inválidos']);
                    exit;
                }
                $detalles = obtenerDetallesOrden($_GET['orden_id'], $_SESSION['usuario_id']);
                echo json_encode($detalles);
                break;
            case 'todas_ordenes':
                if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
                    echo json_encode(['error' => 'Acceso denegado']);
                    exit;
                }
                echo json_encode(obtenerTodasOrdenes());
                break;
            case 'reporte_ventas_producto':
                if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
                    echo json_encode(['error' => 'Acceso denegado']);
                    exit;
                }
                $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
                $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
                echo json_encode(reporteVentasProducto($fechaInicio, $fechaFin));
                break;
            case 'reporte_ventas_periodo':
                if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
                    echo json_encode(['error' => 'Acceso denegado']);
                    exit;
                }
                $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
                $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
                echo json_encode(reporteVentasPeriodo($fechaInicio, $fechaFin));
                break;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'crear_orden':
                $result = crearOrden($_SESSION['usuario_id']);
                echo json_encode($result);
                break;
            case 'actualizar_estado':
                if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
                    echo json_encode(['error' => 'Acceso denegado']);
                    exit;
                }
                $result = actualizarEstadoOrden($data['orden_id'], $data['estado']);
                echo json_encode(['success' => $result, 'message' => $result ? 'Estado actualizado' : 'Error al actualizar']);
                break;
        }
    }
}
?>
