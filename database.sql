-- Base de datos para la plataforma web de pedidos en línea de PLUS Micromercado

CREATE DATABASE IF NOT EXISTS plus;
USE plus;

-- Tabla de usuarios (Administrador, Cliente)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de categorías de productos
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    categoria_id INT,
    imagen VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabla de carrito de compras (temporal)
CREATE TABLE carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_carrito (usuario_id, producto_id)
);

-- Tabla de órdenes de compra
CREATE TABLE ordenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabla de detalles de órdenes
CREATE TABLE orden_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (orden_id) REFERENCES ordenes(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Insertar datos de ejemplo
-- Usuario administrador
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@plus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); -- password: password

-- Usuario cliente
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Cliente Ejemplo', 'cliente@plus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente'); -- password: password

-- Categorías
INSERT INTO categorias (nombre, descripcion) VALUES
('Alimentos', 'Productos alimenticios básicos'),
('Bebidas', 'Refrescos, jugos y bebidas'),
('Limpieza', 'Productos de limpieza del hogar'),
('Higiene Personal', 'Artículos de higiene y cuidado personal'),
('Frutas y Verduras', 'Frutas frescas, verduras y hortalizas'),
('Carnes', 'Carnes rojas, blancas y procesadas'),
('Lácteos', 'Leche, quesos y productos lácteos'),
('Panadería', 'Panes, pasteles y productos de panadería'),
('Snacks', 'Galletas, chocolates y aperitivos');

-- Productos de ejemplo
INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id) VALUES
('Arroz 1kg', 'Arroz blanco de primera calidad', 15.50, 100, 1),
('Leche 1L', 'Leche entera pasteurizada', 8.00, 50, 1),
('Coca Cola 2L', 'Refresco de cola', 12.00, 30, 2),
('Jabón en polvo 1kg', 'Detergente para ropa', 25.00, 20, 3),
('Shampoo 400ml', 'Shampoo para cabello normal', 18.00, 15, 4),
('Pan integral', 'Pan de trigo integral', 5.50, 40, 1),
('Agua mineral 1.5L', 'Agua embotellada', 3.00, 80, 2),
('Desodorante', 'Desodorante en aerosol', 22.00, 25, 4),
-- Más productos para Alimentos
('Fideos 500g', 'Fideos de trigo para sopa', 6.00, 60, 1),
('Aceite de girasol 1L', 'Aceite vegetal para cocinar', 18.00, 40, 1),
('Azúcar 1kg', 'Azúcar refinada', 12.00, 70, 1),
('Sal 500g', 'Sal marina fina', 4.00, 90, 1),
('Harina de trigo 1kg', 'Harina para hornear', 10.00, 50, 1),
-- Más productos para Bebidas
('Sprite 2L', 'Refresco de lima-limón', 11.00, 35, 2),
('Jugo de naranja 1L', 'Jugo natural de naranja', 14.00, 25, 2),
('Cerveza nacional 355ml', 'Cerveza rubia nacional', 8.50, 100, 2),
('Té helado 2L', 'Té negro helado', 10.00, 30, 2),
-- Más productos para Limpieza
('Detergente líquido 1L', 'Detergente para lavar platos', 20.00, 25, 3),
('Cloro 1L', 'Desinfectante para superficies', 7.00, 40, 3),
('Suavizante 1L', 'Suavizante para ropa', 15.00, 20, 3),
('Limpiavidrios 500ml', 'Limpia vidrios y superficies', 12.00, 30, 3),
-- Más productos para Higiene Personal
('Jabón de tocador', 'Jabón antibacterial', 5.00, 80, 4),
('Pasta dental 100g', 'Pasta dental con flúor', 8.00, 60, 4),
('Papel higiénico 4 rollos', 'Papel higiénico suave', 18.00, 45, 4),
('Champú anticaspa 400ml', 'Champú para cabello con caspa', 20.00, 25, 4),
-- Frutas y Verduras
('Manzanas rojas 1kg', 'Manzanas frescas rojas', 12.00, 30, 5),
('Plátanos 1kg', 'Plátanos maduros', 8.00, 50, 5),
('Tomates 1kg', 'Tomates rojos frescos', 10.00, 40, 5),
('Cebollas 1kg', 'Cebollas blancas', 6.00, 60, 5),
('Papas 2kg', 'Papas para freír o cocer', 15.00, 35, 5),
('Zanahorias 1kg', 'Zanahorias frescas', 7.00, 45, 5),
-- Carnes
('Pollo entero 1kg', 'Pollo fresco entero', 25.00, 20, 6),
('Carne de res molida 500g', 'Carne molida para hamburguesas', 30.00, 15, 6),
('Pescado fresco 500g', 'Filete de pescado blanco', 35.00, 10, 6),
('Salchichas 500g', 'Salchichas de cerdo', 18.00, 25, 6),
-- Lácteos
('Queso cheddar 200g', 'Queso cheddar madurado', 22.00, 20, 7),
('Yogurt natural 1kg', 'Yogurt natural sin azúcar', 12.00, 30, 7),
('Mantequilla 200g', 'Mantequilla con sal', 15.00, 25, 7),
('Leche descremada 1L', 'Leche baja en grasas', 9.00, 40, 7),
-- Panadería
('Pan blanco 500g', 'Pan blanco para sandwiches', 6.00, 50, 8),
('Croissants 4 unidades', 'Croissants de mantequilla', 12.00, 20, 8),
('Torta de chocolate', 'Torta de chocolate con cobertura', 45.00, 5, 8),
-- Snacks
('Galletas de chocolate', 'Galletas con chips de chocolate', 10.00, 40, 9),
('Papas fritas 150g', 'Papas fritas saladas', 8.00, 60, 9),
('Chocolate con leche 100g', 'Barra de chocolate con leche', 6.00, 70, 9),
('Maní salado 200g', 'Maní tostado y salado', 12.00, 35, 9),
-- Más productos para Alimentos
('Café molido 250g', 'Café tostado y molido', 28.00, 30, 1),
('Té negro 100 bolsas', 'Té negro en bolsas', 16.00, 40, 1),
('Miel pura 500g', 'Miel de abeja natural', 35.00, 20, 1),
('Vinagre 500ml', 'Vinagre de vino blanco', 8.00, 50, 1),
('Salsa de tomate 400g', 'Salsa de tomate natural', 7.00, 60, 1),
('Mayonesa 475g', 'Mayonesa clásica', 18.00, 35, 1),
('Mostaza 200g', 'Mostaza amarilla', 9.00, 45, 1),
('Ketchup 400g', 'Salsa de tomate ketchup', 10.00, 50, 1),
-- Más productos para Bebidas
('Cerveza importada 330ml', 'Cerveza lager importada', 12.00, 80, 2),
('Vino tinto 750ml', 'Vino tinto reserva', 45.00, 15, 2),
('Refresco de naranja 2L', 'Refresco de naranja natural', 11.00, 40, 2),
('Agua con gas 1.5L', 'Agua mineral con gas', 4.00, 70, 2),
('Energizante 250ml', 'Bebida energética', 15.00, 60, 2),
-- Más productos para Limpieza
('Jabón líquido para manos', 'Jabón antibacterial líquido', 12.00, 40, 3),
('Desinfectante en spray 500ml', 'Desinfectante multiusos', 18.00, 30, 3),
('Papel absorbente 2 rollos', 'Papel absorbente para cocina', 14.00, 50, 3),
('Bolsas de basura 30L x10', 'Bolsas de basura resistentes', 8.00, 60, 3),
('Cera para pisos 1L', 'Cera abrillantadora', 22.00, 25, 3),
-- Más productos para Higiene Personal
('Crema dental blanqueadora', 'Crema dental para dientes blancos', 12.00, 40, 4),
('Enjuague bucal 500ml', 'Enjuague bucal antibacterial', 16.00, 35, 4),
('Jabón íntimo 200ml', 'Jabón para higiene íntima', 14.00, 45, 4),
('Talco para bebés 200g', 'Talco suave para bebés', 10.00, 50, 4),
('Crema hidratante 400ml', 'Crema corporal hidratante', 25.00, 30, 4),
('Protector solar SPF 50', 'Protector solar facial y corporal', 35.00, 20, 4),
-- Más productos para Frutas y Verduras
('Naranjas 1kg', 'Naranjas dulces frescas', 9.00, 40, 5),
('Limones 500g', 'Limones frescos', 5.00, 60, 5),
('Lechuga romana', 'Lechuga fresca para ensaladas', 4.00, 70, 5),
('Espinacas 300g', 'Espinacas frescas', 6.00, 50, 5),
('Brócoli 500g', 'Brócoli fresco', 8.00, 45, 5),
('Zucchini 500g', 'Calabacín verde', 7.00, 55, 5),
('Pimientos rojos 500g', 'Pimientos rojos dulces', 10.00, 40, 5),
-- Más productos para Carnes
('Chuletas de cerdo 1kg', 'Chuletas de cerdo frescas', 28.00, 20, 6),
('Pechuga de pollo 1kg', 'Pechuga de pollo sin hueso', 22.00, 25, 6),
('Costillas de res 1kg', 'Costillas para asar', 35.00, 15, 6),
('Hígado de res 500g', 'Hígado de res fresco', 18.00, 30, 6),
('Mortadela 500g', 'Mortadela italiana', 20.00, 25, 6),
-- Más productos para Lácteos
('Queso mozzarella 200g', 'Queso mozzarella fresco', 18.00, 30, 7),
('Crema de leche 200ml', 'Crema de leche para cocinar', 8.00, 40, 7),
('Leche condensada 400g', 'Leche condensada azucarada', 12.00, 35, 7),
('Yogurt griego 1kg', 'Yogurt griego natural', 15.00, 25, 7),
('Manteca 200g', 'Manteca vegetal', 10.00, 45, 7),
-- Más productos para Panadería
('Facturas surtidas 6 unidades', 'Medialunas y facturas variadas', 18.00, 20, 8),
('Bizcochos 500g', 'Bizcochos dulces', 12.00, 30, 8),
('Galletas de agua 200g', 'Galletas de agua tradicionales', 8.00, 40, 8),
('Tarta de manzana', 'Tarta casera de manzana', 35.00, 10, 8),
-- Más productos para Snacks
('Chicles sin azúcar x20', 'Chicles de menta sin azúcar', 5.00, 80, 9),
('Barritas energéticas x5', 'Barritas de cereales y frutas', 15.00, 50, 9),
('Mix de frutos secos 300g', 'Mezcla de nueces y almendras', 28.00, 25, 9),
('Gomitas de frutas 200g', 'Gomitas surtidas de frutas', 9.00, 60, 9),
('Palomitas de maíz 100g', 'Palomitas listas para comer', 6.00, 70, 9),
-- Productos adicionales variados
('Huevos docena', 'Huevos frescos de granja', 16.00, 50, 1),
('Mermelada de frutilla 250g', 'Mermelada artesanal', 14.00, 40, 1),
('Sopa instantánea x6', 'Sopas instantáneas variadas', 12.00, 60, 1),
('Helado de vainilla 1L', 'Helado cremoso de vainilla', 20.00, 30, 7),
('Cereal de maíz 500g', 'Cereal para desayuno', 18.00, 35, 1),
('Gaseosa light 2L', 'Refresco dietético', 10.00, 45, 2),
('Ambientador automático', 'Ambientador con refill', 25.00, 20, 3),
('Repelente de insectos 200ml', 'Repelente en spray', 22.00, 30, 4),
('Champú para bebés 300ml', 'Champú suave para bebés', 16.00, 40, 4),
('Toallas femeninas x20', 'Toallas higiénicas normales', 12.00, 55, 4),
('Pañales para adultos x10', 'Pañales absorbentes', 35.00, 15, 4),
('Cerveza sin alcohol 330ml', 'Cerveza sin alcohol', 9.00, 40, 2),
('Vino blanco 750ml', 'Vino blanco semiseco', 40.00, 20, 2),
('Whisky 750ml', 'Whisky escocés', 120.00, 10, 2),
('Ron blanco 750ml', 'Ron ligero', 85.00, 15, 2),
('Vodka 750ml', 'Vodka premium', 95.00, 12, 2),
('Fernet 750ml', 'Aperitivo italiano', 75.00, 18, 2),
('Chicles sin azúcar x20', 'Chicles de menta sin azúcar', 5.00, 80, 9),
('Barritas energéticas x5', 'Barritas de cereales y frutas', 15.00, 50, 9),
('Mix de frutos secos 300g', 'Mezcla de nueces y almendras', 28.00, 25, 9),
('Gomitas de frutas 200g', 'Gomitas surtidas de frutas', 9.00, 60, 9),
('Palomitas de maíz 100g', 'Palomitas listas para comer', 6.00, 70, 9);
