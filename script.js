// Variables globales
let currentUser = null;
let cart = [];
let products = [];
let categories = [];

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    checkAuthStatus();
    loadCategories();
    loadProducts();
    setupEventListeners();
});

// Verificar estado de autenticaciónt
function checkAuthStatus() {
    fetch('auth.php?action=check')
        .then(response => response.json())
        .then(data => {
            if (data.authenticated) {
                currentUser = data.user;
                updateUIForAuthenticatedUser();
            } else {
                updateUIForGuest();
            }
        })
        .catch(error => {
            console.error('Error checking auth status:', error);
            updateUIForGuest();
        });
}

// Actualizar UI para usuario autenticado
function updateUIForAuthenticatedUser() {
    document.getElementById('login-btn').style.display = 'none';
    document.getElementById('register-btn').style.display = 'none';
    document.getElementById('logout-btn').style.display = 'inline-block';
    document.getElementById('user-info').textContent = `Hola, ${currentUser.nombre}`;

    if (currentUser.rol === 'admin') {
        document.getElementById('admin-btn').style.display = 'inline-block';
    }

    loadCart();
}

// Actualizar UI para invitado
function updateUIForGuest() {
    document.getElementById('login-btn').style.display = 'inline-block';
    document.getElementById('register-btn').style.display = 'inline-block';
    document.getElementById('logout-btn').style.display = 'none';
    document.getElementById('admin-btn').style.display = 'none';
    document.getElementById('user-info').textContent = '';
}

// Configurar event listeners
function setupEventListeners() {
    // Formularios de autenticación
    document.getElementById('login-form').addEventListener('submit', handleLogin);
    document.getElementById('register-form').addEventListener('submit', handleRegister);

    // Filtros de productos
    document.getElementById('categoria-filter').addEventListener('change', filterProducts);
    document.getElementById('search-input').addEventListener('input', filterProducts);

    // Checkout
    document.getElementById('checkout-btn').addEventListener('click', handleCheckout);
}

// Manejar login
function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    fetch('auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'login',
            email: email,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentUser = data.user;
            updateUIForAuthenticatedUser();
            showSection('productos');
            showMessage('Inicio de sesión exitoso', 'success');
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al iniciar sesión', 'error');
    });
}

// Manejar registro
function handleRegister(e) {
    e.preventDefault();
    const nombre = document.getElementById('register-name').value;
    const email = document.getElementById('register-email').value;
    const password = document.getElementById('register-password').value;

    fetch('auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'register',
            nombre: nombre,
            email: email,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSection('login');
            showMessage('Registro exitoso. Ahora puedes iniciar sesión.', 'success');
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al registrarse', 'error');
    });
}

// Cerrar sesión
function logout() {
    fetch('auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'logout'
        })
    })
    .then(response => response.json())
    .then(data => {
        currentUser = null;
        cart = [];
        updateUIForGuest();
        updateCartCount();
        showSection('productos');
        showMessage('Sesión cerrada', 'info');
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Cargar categorías
function loadCategories() {
    fetch('products.php?action=categorias')
        .then(response => response.json())
        .then(data => {
            categories = data;
            const filter = document.getElementById('categoria-filter');
            filter.innerHTML = '<option value="">Todas las categorías</option>';
            data.forEach(cat => {
                filter.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
            });
        })
        .catch(error => {
            console.error('Error loading categories:', error);
        });
}

// Cargar productos
function loadProducts() {
    fetch('products.php?action=productos')
        .then(response => response.json())
        .then(data => {
            products = data;
            displayProducts(data);
        })
        .catch(error => {
            console.error('Error loading products:', error);
        });
}

// Mostrar productos
function displayProducts(productos) {
    const grid = document.getElementById('productos-grid');
    grid.innerHTML = '';

    productos.forEach(producto => {
        const card = document.createElement('div');
        card.className = 'producto-card fade-in';
        card.innerHTML = `
            <div class="producto-imagen">
                ${producto.imagen ? `<img src="${producto.imagen}" alt="${producto.nombre}">` : '📦'}
            </div>
            <div class="producto-info">
                <h3 class="producto-nombre">${producto.nombre}</h3>
                <p class="producto-descripcion">${producto.descripcion}</p>
                <p class="producto-precio">Bs. ${parseFloat(producto.precio).toFixed(2)}</p>
                <p class="producto-stock">Stock: ${producto.stock}</p>
                <div class="producto-acciones">
                    <input type="number" class="cantidad-input" value="1" min="1" max="${producto.stock}">
                    <button class="btn-primary" onclick="addToCart(${producto.id}, this.previousElementSibling.value)">Agregar al Carrito</button>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

// Filtrar productos
function filterProducts() {
    const categoriaId = document.getElementById('categoria-filter').value;
    const searchTerm = document.getElementById('search-input').value.toLowerCase();

    let filtered = products;

    if (categoriaId) {
        filtered = filtered.filter(p => p.categoria_id == categoriaId);
    }

    if (searchTerm) {
        filtered = filtered.filter(p =>
            p.nombre.toLowerCase().includes(searchTerm) ||
            p.descripcion.toLowerCase().includes(searchTerm)
        );
    }

    displayProducts(filtered);
}

// Agregar al carrito
function addToCart(productoId, cantidad) {
    if (!currentUser) {
        showMessage('Debes iniciar sesión para agregar productos al carrito', 'error');
        showSection('login');
        return;
    }

    fetch('products.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'agregar_carrito',
            producto_id: productoId,
            cantidad: parseInt(cantidad)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart();
            showMessage('Producto agregado al carrito', 'success');
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al agregar al carrito', 'error');
    });
}

// Cargar carrito
function loadCart() {
    if (!currentUser) return;

    fetch('products.php?action=carrito')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                cart = [];
            } else {
                cart = data;
            }
            updateCartCount();
            displayCart();
        })
        .catch(error => {
            console.error('Error loading cart:', error);
            cart = [];
            updateCartCount();
        });
}

// Actualizar contador del carrito
function updateCartCount() {
    const count = cart.reduce((total, item) => total + item.cantidad, 0);
    document.getElementById('cart-count').textContent = count;
}

// Mostrar carrito
function displayCart() {
    const container = document.getElementById('carrito-items');
    const totalElement = document.getElementById('total-amount');

    if (cart.length === 0) {
        container.innerHTML = '<p>Tu carrito está vacío</p>';
        totalElement.textContent = '0.00';
        return;
    }

    let total = 0;
    container.innerHTML = '';

    cart.forEach(item => {
        total += parseFloat(item.subtotal);
        const itemDiv = document.createElement('div');
        itemDiv.className = 'carrito-item';
        itemDiv.innerHTML = `
            <div class="carrito-item-info">
                <h4 class="carrito-item-nombre">${item.nombre}</h4>
                <p>Cantidad: ${item.cantidad} x Bs. ${parseFloat(item.precio).toFixed(2)}</p>
            </div>
            <div>
                <p class="carrito-item-precio">Bs. ${parseFloat(item.subtotal).toFixed(2)}</p>
                <button class="btn-secondary" onclick="updateCartItem(${item.producto_id}, ${item.cantidad - 1})">-</button>
                <button class="btn-secondary" onclick="updateCartItem(${item.producto_id}, ${item.cantidad + 1})">+</button>
                <button class="btn-secondary" onclick="removeFromCart(${item.producto_id})">Eliminar</button>
            </div>
        `;
        container.appendChild(itemDiv);
    });

    totalElement.textContent = total.toFixed(2);
}

// Actualizar item del carrito
function updateCartItem(productoId, cantidad) {
    if (cantidad <= 0) {
        removeFromCart(productoId);
        return;
    }

    fetch('products.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'actualizar_carrito',
            producto_id: productoId,
            cantidad: cantidad
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al actualizar carrito', 'error');
    });
}

// Eliminar del carrito
function removeFromCart(productoId) {
    updateCartItem(productoId, 0);
}

// Manejar checkout
function handleCheckout() {
    if (cart.length === 0) {
        showMessage('Tu carrito está vacío', 'error');
        return;
    }

    // Mostrar confirmación personalizada en lugar de alert
    showConfirmDialog('¿Estás seguro de que quieres realizar el pedido?', function(confirmed) {
        if (confirmed) {
            fetch('orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'crear_orden'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cart = [];
                    updateCartCount();
                    displayCart();
                    showSection('ordenes');
                    loadOrders();
                    showMessage('Pedido realizado exitosamente', 'success');
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error al realizar el pedido', 'error');
            });
        }
    });
}

// Cargar órdenes
function loadOrders() {
    if (!currentUser) return;

    fetch('orders.php?action=ordenes_usuario')
        .then(response => response.json())
        .then(data => {
            displayOrders(data);
        })
        .catch(error => {
            console.error('Error loading orders:', error);
        });
}

// Mostrar órdenes
function displayOrders(ordenes) {
    const container = document.getElementById('ordenes-list');

    if (ordenes.length === 0) {
        container.innerHTML = '<p>No tienes órdenes aún</p>';
        return;
    }

    container.innerHTML = '';
    ordenes.forEach(orden => {
        const card = document.createElement('div');
        card.className = 'orden-card';
        card.innerHTML = `
            <div class="orden-header">
                <span class="orden-id">Orden #${orden.id}</span>
                <span class="orden-estado ${orden.estado}">${orden.estado}</span>
            </div>
            <p>Fecha: ${new Date(orden.fecha_creacion).toLocaleDateString()}</p>
            <p>Total: Bs. ${parseFloat(orden.total).toFixed(2)}</p>
            <p>Productos: ${orden.total_productos}</p>
            <button class="btn-secondary" onclick="viewOrderDetails(${orden.id})">Ver Detalles</button>
        `;
        container.appendChild(card);
    });
}

// Ver detalles de orden
function viewOrderDetails(ordenId) {
    fetch(`orders.php?action=detalles_orden&orden_id=${ordenId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showMessage('Error al cargar detalles', 'error');
                return;
            }

            let details = `Detalles de la Orden #${ordenId}:\n\n`;
            data.forEach(item => {
                details += `${item.nombre} - Cantidad: ${item.cantidad} - Precio: Bs. ${parseFloat(item.precio_unitario).toFixed(2)} - Subtotal: Bs. ${parseFloat(item.subtotal).toFixed(2)}\n`;
            });

            alert(details);
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error al cargar detalles', 'error');
        });
}

// Mostrar sección
function showSection(sectionName) {
    // Ocultar todas las secciones
    const sections = document.querySelectorAll('main > section');
    sections.forEach(section => {
        section.style.display = 'none';
    });

    // Mostrar la sección seleccionada
    const targetSection = document.getElementById(sectionName + '-section');
    if (targetSection) {
        targetSection.style.display = 'block';
    }

    // Actualizar navegación activa
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.classList.remove('active');
    });

    const activeLink = document.querySelector(`[onclick="showSection('${sectionName}')"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }

    // Cargar contenido específico
    if (sectionName === 'ordenes') {
        loadOrders();
    } else if (sectionName === 'admin') {
        loadAdminDashboard();
    }
}

// Toggle menú hamburguesa
function toggleMenu() {
    const navMenu = document.getElementById('nav-menu');
    navMenu.classList.toggle('active');
}

// Mostrar mensaje
function showMessage(message, type) {
    // Crear elemento de mensaje
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;

    // Insertar al inicio del main
    const main = document.querySelector('main');
    main.insertBefore(messageDiv, main.firstChild);

    // Remover después de 5 segundos
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// Funciones de admin
function showAdminTab(tabName) {
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    const content = document.getElementById('admin-content');

    switch (tabName) {
        case 'dashboard':
            loadAdminDashboard();
            break;
        case 'productos':
            loadAdminProducts();
            break;
        case 'categorias':
            loadAdminCategories();
            break;
        case 'ordenes':
            loadAdminOrders();
            break;
        case 'reportes':
            loadAdminReports();
            break;
    }
}

function loadAdminDashboard() {
    fetch('admin.php?action=estadisticas')
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('admin-content');
            content.innerHTML = `
                <h3>Dashboard</h3>
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <span class="stat-value">${data.total_productos}</span>
                        <span class="stat-label">Productos</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${data.total_categorias}</span>
                        <span class="stat-label">Categorías</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${data.total_usuarios}</span>
                        <span class="stat-label">Usuarios</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${data.total_ordenes}</span>
                        <span class="stat-label">Órdenes</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">Bs. ${parseFloat(data.ventas_totales).toFixed(2)}</span>
                        <span class="stat-label">Ventas Totales</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${data.ordenes_pendientes}</span>
                        <span class="stat-label">Órdenes Pendientes</span>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error loading dashboard:', error);
        });
}

function loadAdminProducts() {
    const content = document.getElementById('admin-content');
    content.innerHTML = `
        <h3>Gestión de Productos</h3>
        <button class="btn-primary" onclick="showProductForm()">Agregar Producto</button>
        <div id="products-list"></div>
    `;
    loadProductsList();
}

function loadProductsList() {
    fetch('products.php?action=productos')
        .then(response => response.json())
        .then(data => {
            const list = document.getElementById('products-list');
            list.innerHTML = `
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Categoría</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(p => `
                            <tr>
                                <td>${p.id}</td>
                                <td>${p.nombre}</td>
                                <td>Bs. ${parseFloat(p.precio).toFixed(2)}</td>
                                <td>${p.stock}</td>
                                <td>${p.categoria_nombre}</td>
                                <td>
                                    <button class="btn-secondary" onclick="editProduct(${p.id})">Editar</button>
                                    <button class="btn-secondary" onclick="deleteProduct(${p.id})">Eliminar</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        })
        .catch(error => {
            console.error('Error loading products:', error);
        });
}

function loadAdminCategories() {
    const content = document.getElementById('admin-content');
    content.innerHTML = `
        <h3>Gestión de Categorías</h3>
        <button class="btn-primary" onclick="showCategoryForm()">Agregar Categoría</button>
        <div id="categories-list"></div>
    `;
    loadCategoriesList();
}

function loadCategoriesList() {
    fetch('products.php?action=categorias')
        .then(response => response.json())
        .then(data => {
            const list = document.getElementById('categories-list');
            list.innerHTML = `
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(c => `
                            <tr>
                                <td>${c.id}</td>
                                <td>${c.nombre}</td>
                                <td>${c.descripcion}</td>
                                <td>
                                    <button class="btn-secondary" onclick="editCategory(${c.id})">Editar</button>
                                    <button class="btn-secondary" onclick="deleteCategory(${c.id})">Eliminar</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        })
        .catch(error => {
            console.error('Error loading categories:', error);
        });
}

function loadAdminOrders() {
    fetch('orders.php?action=todas_ordenes')
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('admin-content');
            content.innerHTML = `
                <h3>Gestión de Órdenes</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Productos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(o => `
                            <tr>
                                <td>${o.id}</td>
                                <td>${o.usuario_nombre}</td>
                                <td>Bs. ${parseFloat(o.total).toFixed(2)}</td>
                                <td>
                                    <select onchange="updateOrderStatus(${o.id}, this.value)">
                                        <option value="pendiente" ${o.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                                        <option value="procesando" ${o.estado === 'procesando' ? 'selected' : ''}>Procesando</option>
                                        <option value="enviado" ${o.estado === 'enviado' ? 'selected' : ''}>Enviado</option>
                                        <option value="entregado" ${o.estado === 'entregado' ? 'selected' : ''}>Entregado</option>
                                        <option value="cancelado" ${o.estado === 'cancelado' ? 'selected' : ''}>Cancelado</option>
                                    </select>
                                </td>
                                <td>${new Date(o.fecha_creacion).toLocaleDateString()}</td>
                                <td>${o.total_productos}</td>
                                <td>
                                    <button class="btn-secondary" onclick="viewOrderDetails(${o.id})">Ver Detalles</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        })
        .catch(error => {
            console.error('Error loading orders:', error);
        });
}

function updateOrderStatus(orderId, status) {
    fetch('orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'actualizar_estado',
            orden_id: orderId,
            estado: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Estado actualizado', 'success');
        } else {
            showMessage('Error al actualizar estado', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error al actualizar estado', 'error');
    });
}

function loadAdminReports() {
    const content = document.getElementById('admin-content');
    content.innerHTML = `
        <h3>Reportes</h3>
        <div>
            <h4>Reporte de Ventas por Producto</h4>
            <button class="btn-primary" onclick="generateProductReport()">Generar Reporte</button>
            <div id="product-report"></div>
        </div>
        <div>
            <h4>Reporte de Ventas por Período</h4>
            <button class="btn-primary" onclick="generatePeriodReport()">Generar Reporte</button>
            <div id="period-report"></div>
        </div>
    `;
}

function generateProductReport() {
    fetch('orders.php?action=reporte_ventas_producto')
        .then(response => response.json())
        .then(data => {
            const report = document.getElementById('product-report');
            report.innerHTML = `
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Total Vendido</th>
                            <th>Total Ventas (Bs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(r => `
                            <tr>
                                <td>${r.nombre}</td>
                                <td>${r.total_vendido}</td>
                                <td>Bs. ${parseFloat(r.total_ventas).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        })
        .catch(error => {
            console.error('Error generating report:', error);
        });
}

function generatePeriodReport() {
    fetch('orders.php?action=reporte_ventas_periodo')
        .then(response => response.json())
        .then(data => {
            const report = document.getElementById('period-report');
            report.innerHTML = `
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Total Órdenes</th>
                            <th>Total Ventas (Bs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(r => `
                            <tr>
                                <td>${new Date(r.fecha).toLocaleDateString()}</td>
                                <td>${r.total_ordenes}</td>
                                <td>Bs. ${parseFloat(r.total_ventas).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        })
        .catch(error => {
            console.error('Error generating report:', error);
        });
}

// Mostrar diálogo de confirmación personalizado
function showConfirmDialog(message, callback) {
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    `;

    // Crear diálogo
    const dialog = document.createElement('div');
    dialog.className = 'confirm-dialog';
    dialog.style.cssText = `
        background-color: #2c2c2c;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        max-width: 400px;
        width: 90%;
        text-align: center;
        color: #e0e0e0;
    `;

    dialog.innerHTML = `
        <h3 style="color: #dc143c; margin-bottom: 1rem;">Confirmar Pedido</h3>
        <p style="margin-bottom: 1.5rem;">${message}</p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button id="confirm-yes" style="background-color: #dc143c; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer;">Sí, confirmar</button>
            <button id="confirm-no" style="background-color: #555; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer;">Cancelar</button>
        </div>
    `;

    overlay.appendChild(dialog);
    document.body.appendChild(overlay);

    // Event listeners
    document.getElementById('confirm-yes').addEventListener('click', function() {
        document.body.removeChild(overlay);
        callback(true);
    });

    document.getElementById('confirm-no').addEventListener('click', function() {
        document.body.removeChild(overlay);
        callback(false);
    });

    // Cerrar al hacer clic en overlay
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            document.body.removeChild(overlay);
            callback(false);
        }
    });
}

// Funciones auxiliares para formularios (simplificadas)
function showProductForm(product = null) {
    // Implementación simplificada - en producción usar modales
    const form = product ?
        `Editar producto ${product.nombre}` :
        'Agregar nuevo producto';
    alert('Formulario de producto: ' + form);
}

function showCategoryForm(category = null) {
    const form = category ?
        `Editar categoría ${category.nombre}` :
        'Agregar nueva categoría';
    alert('Formulario de categoría: ' + form);
}
