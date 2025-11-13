<?php
session_start();
require_once 'connection.php';

// Función para agregar producto
function agregarProducto($nombre, $descripcion, $precio, $stock, $categoriaId, $imagen = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id, imagen) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$nombre, $descripcion, $precio, $stock, $categoriaId, $imagen]);
}

// Función para actualizar producto
function actualizarProducto($id, $nombre, $descripcion, $precio, $stock, $categoriaId, $imagen = null) {
    global $pdo;
    if ($imagen) {
        $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, categoria_id = ?, imagen = ? WHERE id = ?");
        return $stmt->execute([$nombre, $descripcion, $precio, $stock, $categoriaId, $imagen, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, categoria_id = ? WHERE id = ?");
        return $stmt->execute([$nombre, $descripcion, $precio, $stock, $categoriaId, $id]);
    }
}

// Función para eliminar producto (desactivar)
function eliminarProducto($id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

// Función para agregar categoría
function agregarCategoria($nombre, $descripcion) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
    return $stmt->execute([$nombre, $descripcion]);
}

// Función para actualizar categoría
function actualizarCategoria($id, $nombre, $descripcion) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?");
    return $stmt->execute([$nombre, $descripcion, $id]);
}

// Función para eliminar categoría (desactivar)
function eliminarCategoria($id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE categorias SET activo = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

// Función para obtener estadísticas del dashboard
function obtenerEstadisticas() {
    global $pdo;

    $stats = [];

    // Total productos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
    $stats['total_productos'] = $stmt->fetch()['total'];

    // Total categorías
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias WHERE activo = 1");
    $stats['total_categorias'] = $stmt->fetch()['total'];

    // Total usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
    $stats['total_usuarios'] = $stmt->fetch()['total'];

    // Total órdenes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ordenes");
    $stats['total_ordenes'] = $stmt->fetch()['total'];

    // Ventas totales
    $stmt = $pdo->query("SELECT SUM(total) as total FROM ordenes WHERE estado != 'cancelado'");
    $stats['ventas_totales'] = $stmt->fetch()['total'] ?? 0;

    // Órdenes pendientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ordenes WHERE estado = 'pendiente'");
    $stats['ordenes_pendientes'] = $stmt->fetch()['total'];

    return $stats;
}

// API endpoints para admin
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    session_start();
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'estadisticas':
                echo json_encode(obtenerEstadisticas());
                break;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action'])) {
        $result = false;
        $message = '';

        switch ($data['action']) {
            case 'agregar_producto':
                $result = agregarProducto($data['nombre'], $data['descripcion'], $data['precio'], $data['stock'], $data['categoria_id'], $data['imagen'] ?? null);
                $message = $result ? 'Producto agregado' : 'Error al agregar producto';
                break;
            case 'actualizar_producto':
                $result = actualizarProducto($data['id'], $data['nombre'], $data['descripcion'], $data['precio'], $data['stock'], $data['categoria_id'], $data['imagen'] ?? null);
                $message = $result ? 'Producto actualizado' : 'Error al actualizar producto';
                break;
            case 'eliminar_producto':
                $result = eliminarProducto($data['id']);
                $message = $result ? 'Producto eliminado' : 'Error al eliminar producto';
                break;
            case 'agregar_categoria':
                $result = agregarCategoria($data['nombre'], $data['descripcion']);
                $message = $result ? 'Categoría agregada' : 'Error al agregar categoría';
                break;
            case 'actualizar_categoria':
                $result = actualizarCategoria($data['id'], $data['nombre'], $data['descripcion']);
                $message = $result ? 'Categoría actualizada' : 'Error al actualizar categoría';
                break;
            case 'eliminar_categoria':
                $result = eliminarCategoria($data['id']);
                $message = $result ? 'Categoría eliminada' : 'Error al eliminar categoría';
                break;
        }

        echo json_encode(['success' => $result, 'message' => $message]);
    }
}
?>
