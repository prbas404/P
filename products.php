<?php
require_once 'connection.php';

// Función para obtener todos los productos
function obtenerProductos() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.activo = 1 ORDER BY p.nombre");
    return $stmt->fetchAll();
}

// Función para obtener producto por ID
function obtenerProducto($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.id = ? AND p.activo = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Función para obtener categorías
function obtenerCategorias() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre");
    return $stmt->fetchAll();
}

// Función para agregar producto al carrito
function agregarAlCarrito($usuarioId, $productoId, $cantidad) {
    global $pdo;

    // Verificar stock disponible
    $producto = obtenerProducto($productoId);
    if (!$producto || $producto['stock'] < $cantidad) {
        return ['success' => false, 'message' => 'Stock insuficiente'];
    }

    // Verificar si ya existe en el carrito
    $stmt = $pdo->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?");
    $stmt->execute([$usuarioId, $productoId]);
    $item = $stmt->fetch();

    if ($item) {
        // Actualizar cantidad
        $nuevaCantidad = $item['cantidad'] + $cantidad;
        if ($nuevaCantidad > $producto['stock']) {
            return ['success' => false, 'message' => 'Stock insuficiente para la cantidad solicitada'];
        }
        $stmt = $pdo->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
        $stmt->execute([$nuevaCantidad, $item['id']]);
    } else {
        // Insertar nuevo item
        $stmt = $pdo->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)");
        $stmt->execute([$usuarioId, $productoId, $cantidad]);
    }

    return ['success' => true, 'message' => 'Producto agregado al carrito'];
}

// Función para obtener carrito del usuario
function obtenerCarrito($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, p.nombre, p.precio, p.stock, (c.cantidad * p.precio) as subtotal
        FROM carrito c
        JOIN productos p ON c.producto_id = p.id
        WHERE c.usuario_id = ?
        ORDER BY c.fecha_agregado DESC
    ");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll();
}

// Función para actualizar cantidad en carrito
function actualizarCarrito($usuarioId, $productoId, $cantidad) {
    global $pdo;

    if ($cantidad <= 0) {
        // Eliminar del carrito
        $stmt = $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?");
        $stmt->execute([$usuarioId, $productoId]);
        return ['success' => true, 'message' => 'Producto eliminado del carrito'];
    }

    // Verificar stock
    $producto = obtenerProducto($productoId);
    if ($cantidad > $producto['stock']) {
        return ['success' => false, 'message' => 'Stock insuficiente'];
    }

    $stmt = $pdo->prepare("UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?");
    $stmt->execute([$cantidad, $usuarioId, $productoId]);

    return ['success' => true, 'message' => 'Carrito actualizado'];
}

// Función para vaciar carrito
function vaciarCarrito($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    return ['success' => true, 'message' => 'Carrito vaciado'];
}

// API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'productos':
                echo json_encode(obtenerProductos());
                break;
            case 'categorias':
                echo json_encode(obtenerCategorias());
                break;
            case 'carrito':
                session_start();
                if (!isset($_SESSION['usuario_id'])) {
                    echo json_encode(['error' => 'No autenticado']);
                    exit;
                }
                echo json_encode(obtenerCarrito($_SESSION['usuario_id']));
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
            case 'agregar_carrito':
                $result = agregarAlCarrito($_SESSION['usuario_id'], $data['producto_id'], $data['cantidad']);
                echo json_encode($result);
                break;
            case 'actualizar_carrito':
                $result = actualizarCarrito($_SESSION['usuario_id'], $data['producto_id'], $data['cantidad']);
                echo json_encode($result);
                break;
            case 'vaciar_carrito':
                $result = vaciarCarrito($_SESSION['usuario_id']);
                echo json_encode($result);
                break;
        }
    }
}
?>
