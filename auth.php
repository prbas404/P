<?php
session_start();
require_once 'connection.php';

require_once 'auth.php';

function obtenerCarrito() {
    // Si el carrito no existe en la sesión, inicialízalo
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }
    // Retorna el array del carrito de la sesión
    return $_SESSION['carrito'];
}
// CÓDIGO CORREGIDO PARA registrarUsuario en auth.php
// CÓDIGO FINAL DE registrarUsuario EN auth.php
function registrarUsuario($nombre, $email, $password, $rol = 'cliente') {
    global $pdo;
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 🚨 Esta es la clave: 5 PLACEHOLDERS '?'
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, ?)");
    
    // 🚨 5 VALORES en el execute: $nombre, $email, $hashedPassword, $rol, y el '1' para activo
    if ($stmt->execute([$nombre, $email, $hashedPassword, $rol, 1])) { 
        return ['success' => true, 'message' => 'Usuario registrado exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Error al registrar usuario en la base de datos'];
    }
}

// Función para iniciar sesión
// Función para iniciar sesión (CÓDIGO CORREGIDO)
function iniciarSesion($email, $password) {
    global $pdo;

    // Asegúrate de usar FETCH_ASSOC si es necesario, aunque PDO por defecto es numérico y asociativo a la vez.
    // Vamos a asegurar el FETCH_ASSOC para mayor claridad si no está configurado globalmente.
    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC); // <-- Opcional, pero buena práctica

    if ($usuario && password_verify($password, $usuario['password'])) {
        // 1. Establecer variables de sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_rol'] = $usuario['rol'];
        
        // 2. RETORNAR EL OBJETO 'user' PARA EL JAVASCRIPT
        return [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user' => [ // <--- ¡ESTE CAMPO ES VITAL!
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'rol' => $usuario['rol']
            ]
        ];
    } else {
        return ['success' => false, 'message' => 'Credenciales incorrectas'];
    }
}

// Función para cerrar sesión
function cerrarSesion() {
    session_destroy();
    return ['success' => true, 'message' => 'Sesión cerrada'];
}

// Función para verificar si el usuario está autenticado
function estaAutenticado() {
    return isset($_SESSION['usuario_id']);
}

// Función para verificar rol de administrador
function esAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

// API endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'register':
                $result = registrarUsuario($data['nombre'], $data['email'], $data['password']);
                echo json_encode($result);
                break;
            case 'login':
                $result = iniciarSesion($data['email'], $data['password']);
                echo json_encode($result);
                break;
            case 'logout':
                $result = cerrarSesion();
                echo json_encode($result);
                break;
        }
    }
}


// Lógica para peticiones GET (Verificar estado de autenticación)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check') {
    header('Content-Type: application/json');
    if (estaAutenticado()) {
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['usuario_id'],
                'nombre' => $_SESSION['usuario_nombre'],
                'email' => $_SESSION['usuario_email'],
                'rol' => $_SESSION['usuario_rol']
            ]
        ]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
    exit;
}
?>
