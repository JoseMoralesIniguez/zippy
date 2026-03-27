-- ======================================================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS PARA "ZIPPY" (Autodocumentado)
-- ======================================================================

-- Crea la base de datos si no existe y la selecciona para trabajar en ella.
CREATE DATABASE IF NOT EXISTS zippy;
USE zippy;

-- ==========================================
-- 1. USUARIOS, ROLES Y CUENTAS BANCARIAS
-- ==========================================

-- Tabla: Usuarios
-- Propósito: Es el corazón del sistema. Aquí viven TODOS los que usan la plataforma, 
-- diferenciados por su "rol". Esto evita tener tablas separadas para clientes y repartidores.
CREATE TABLE Usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY, -- Identificador único del usuario.
    nombre VARCHAR(100) NOT NULL,              -- Nombre completo.
    telefono VARCHAR(20) NOT NULL,             -- Teléfono de contacto (vital para entregas).
    email VARCHAR(100) NOT NULL UNIQUE,        -- Correo para login (UNIQUE evita duplicados).
    password_hash VARCHAR(255) NOT NULL,       -- Contraseña encriptada por seguridad.
    rol ENUM('Cliente', 'Repartidor', 'AdminRestaurante', 'SuperAdmin') DEFAULT 'Cliente', -- Define qué puede hacer en la app.
    saldo_wallet DECIMAL(10, 2) DEFAULT 0.00,  -- Dinero a favor del usuario (por reembolsos) listo para usarse.
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Cuándo creó su cuenta.
);

-- Tabla: Cuentas_Bancarias
-- Propósito: Guarda a dónde se le va a depositar el dinero a los negocios y repartidores.
CREATE TABLE Cuentas_Bancarias (
    id_cuenta INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,                   -- Dueño de la cuenta bancaria.
    banco VARCHAR(100) NOT NULL,               -- Nombre de la institución (Ej. BBVA).
    titular_cuenta VARCHAR(150) NOT NULL,      -- Nombre oficial registrado en el banco.
    numero_cuenta VARCHAR(50) NOT NULL,        -- Los 18 dígitos de la CLABE o número de tarjeta.
    tipo_cuenta ENUM('CLABE', 'Tarjeta de Débito', 'Cuenta de Ahorros') DEFAULT 'CLABE',
    token_pasarela VARCHAR(255) DEFAULT NULL,  -- ID seguro si usas Stripe/MercadoPago (para no tocar la tarjeta).
    cuenta_principal BOOLEAN DEFAULT TRUE,     -- Indica si es la cuenta preferida para recibir pagos.
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
);

-- Tabla: Restaurantes
-- Propósito: Catálogo de todos los negocios afiliados a Zippy.
CREATE TABLE Restaurantes (
    id_restaurante INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,              -- Nombre comercial del negocio.
    direccion VARCHAR(255) NOT NULL,           -- Dirección escrita para el cliente.
    latitud DECIMAL(10, 8) NOT NULL,           -- Coordenada GPS precisa (vital para el mapa).
    longitud DECIMAL(11, 8) NOT NULL,          -- Coordenada GPS precisa (vital para el mapa).
    abierto BOOLEAN DEFAULT TRUE,              -- Botón de emergencia para que el local "cierre" si está saturado.
    porcentaje_comision DECIMAL(5, 2) DEFAULT 20.00, -- Lo que Zippy le cobra de comisión (Ej. 20.00%).
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: Administradores_Restaurante (Tabla Pivote)
-- Propósito: Conecta a un usuario con rol 'AdminRestaurante' con su negocio físico. 
-- Permite que un solo dueño administre varias sucursales.
CREATE TABLE Administradores_Restaurante (
    id_usuario INT NOT NULL,                   -- El ID del dueño o gerente.
    id_restaurante INT NOT NULL,               -- El ID del local que administra.
    PRIMARY KEY (id_usuario, id_restaurante),  -- Llave compuesta para evitar duplicidades.
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_restaurante) REFERENCES Restaurantes(id_restaurante) ON DELETE CASCADE
);

-- Tabla: Datos_Repartidor
-- Propósito: Extensión técnica de la tabla Usuarios exclusiva para los que reparten.
CREATE TABLE Datos_Repartidor (
    id_usuario INT PRIMARY KEY,                -- El ID del repartidor (vinculado a Usuarios).
    tipo_vehiculo ENUM('Moto', 'Bicicleta', 'Auto') NOT NULL, -- Define qué tan rápido puede ir.
    latitud_actual DECIMAL(10, 8),             -- Su ubicación GPS actual (se actualiza cada pocos segundos).
    longitud_actual DECIMAL(11, 8),            -- Su ubicación GPS actual.
    estatus_conexion ENUM('Disponible', 'En Camino', 'Ocupado', 'Desconectado') DEFAULT 'Desconectado', -- Para saber si se le pueden mandar pedidos.
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
);

-- ==========================================
-- 2. INVENTARIO Y MENÚ
-- ==========================================

-- Tabla: Categorias
-- Propósito: Organizar el menú del restaurante (Ej. "Bebidas", "Postres").
CREATE TABLE Categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    id_restaurante INT NOT NULL,               -- A qué restaurante pertenece esta sección.
    nombre_categoria VARCHAR(50) NOT NULL,     -- El nombre de la sección.
    FOREIGN KEY (id_restaurante) REFERENCES Restaurantes(id_restaurante) ON DELETE CASCADE
);

-- Tabla: Productos
-- Propósito: Los artículos individuales que el cliente puede meter al carrito.
CREATE TABLE Productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,                 -- En qué sección del menú aparece.
    nombre VARCHAR(100) NOT NULL,              -- Nombre del platillo.
    descripcion TEXT,                          -- Qué ingredientes trae.
    precio DECIMAL(10, 2) NOT NULL,            -- Precio de venta al público (DECIMAL para exactitud financiera).
    disponible BOOLEAN DEFAULT TRUE,           -- Por si se les acaba un ingrediente temporalmente.
    FOREIGN KEY (id_categoria) REFERENCES Categorias(id_categoria) ON DELETE CASCADE
);

-- ==========================================
-- 3. TRANSACCIONES Y OPERACIÓN (EL PEDIDO)
-- ==========================================

-- Tabla: Pedidos
-- Propósito: El encabezado general de la orden. Conecta al cliente, restaurante y repartidor en un solo viaje.
CREATE TABLE Pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,                   -- Quién lo pidió.
    id_restaurante INT NOT NULL,               -- Dónde se prepara.
    id_repartidor INT DEFAULT NULL,            -- Quién lo lleva (NULL al principio hasta que alguien acepte).
    estado_pedido ENUM('Pendiente', 'Preparando', 'Listo', 'En Camino', 'Entregado', 'Cancelado') DEFAULT 'Pendiente', -- El flujo de la app.
    subtotal DECIMAL(10, 2) NOT NULL,          -- Costo puro de la comida.
    costo_envio DECIMAL(10, 2) NOT NULL,       -- Lo que se cobra por llevarlo.
    total DECIMAL(10, 2) NOT NULL,             -- Subtotal + envío.
    metodo_pago ENUM('Efectivo', 'Tarjeta', 'Billetera Digital') NOT NULL, -- Cómo va a pagar el cliente.
    direccion_entrega_lat DECIMAL(10, 8) NOT NULL, -- Coordenada destino.
    direccion_entrega_lng DECIMAL(11, 8) NOT NULL, -- Coordenada destino.
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Cuándo se hizo la orden.
    FOREIGN KEY (id_cliente) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (id_restaurante) REFERENCES Restaurantes(id_restaurante),
    FOREIGN KEY (id_repartidor) REFERENCES Usuarios(id_usuario)
);

-- Tabla: Detalle_Pedido
-- Propósito: Las líneas del ticket. Qué compró exactamente dentro de esa orden.
CREATE TABLE Detalle_Pedido (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,                    -- A qué orden general pertenece.
    id_producto INT NOT NULL,                  -- Qué platillo es.
    cantidad INT NOT NULL,                     -- Cuántos pidió.
    precio_unitario DECIMAL(10, 2) NOT NULL,   -- Se guarda histórico para que no afecte si el precio cambia mañana.
    instrucciones_especiales VARCHAR(255),     -- Ej. "Sin cebolla".
    FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES Productos(id_producto)
);

-- ==========================================
-- 4. EVIDENCIAS, RESEÑAS Y RECLAMOS
-- ==========================================

-- Tabla: Evidencias_Pedido
-- Propósito: Respaldo fotográfico para protegerse de fraudes.
CREATE TABLE Evidencias_Pedido (
    id_evidencia INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,                    -- Orden a la que pertenece la foto.
    url_foto VARCHAR(500) NOT NULL,            -- Link del servidor en la nube donde vive la imagen.
    subido_por ENUM('Restaurante', 'Repartidor') NOT NULL, -- Quién tomó la foto (al empaquetar o al entregar).
    comentario_evidencia VARCHAR(255),         -- Ej. "Se dejó en la puerta como pidió el cliente".
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido) ON DELETE CASCADE
);

-- Tabla: Resenas
-- Propósito: Calificaciones de 1 a 5 estrellas para medir la calidad.
CREATE TABLE Resenas (
    id_resena INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL UNIQUE,             -- UNIQUE asegura que solo se califique una vez por pedido.
    id_cliente INT NOT NULL,                   -- Quién califica.
    calificacion_restaurante TINYINT CHECK (calificacion_restaurante BETWEEN 1 AND 5), -- Estrellas para la comida.
    comentario_restaurante TEXT,               -- Opinión escrita sobre la comida.
    calificacion_repartidor TINYINT CHECK (calificacion_repartidor BETWEEN 1 AND 5),   -- Estrellas para el viaje.
    comentario_repartidor TEXT,                -- Opinión escrita sobre el servicio de entrega.
    fecha_resena TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
);

-- Tabla: Reclamos_Producto
-- Propósito: Gestión de disputas y reembolsos parciales (Ej. "Faltó mi refresco").
CREATE TABLE Reclamos_Producto (
    id_reclamo INT AUTO_INCREMENT PRIMARY KEY,
    id_detalle INT NOT NULL,                   -- Apunta al artículo específico que falló, no a toda la orden.
    motivo ENUM('Faltante', 'Mal Estado', 'Equivocado', 'Calidad Inaceptable', 'Otro') NOT NULL, -- Tipificación del error.
    comentario_cliente TEXT NOT NULL,          -- Queja del cliente.
    url_evidencia_cliente VARCHAR(500),        -- Foto que sube el cliente demostrando el error.
    estado_resolucion ENUM('Pendiente', 'En Revisión', 'Reembolso Aprobado', 'Rechazado') DEFAULT 'Pendiente', -- Flujo de soporte.
    metodo_reembolso ENUM('Billetera Virtual', 'Tarjeta Original') DEFAULT NULL, -- A dónde quiso su dinero.
    monto_solicitado DECIMAL(10, 2) NOT NULL,  -- Cuánto pide de regreso.
    monto_reembolsado DECIMAL(10, 2) DEFAULT 0.00, -- Cuánto le autorizó Soporte.
    fecha_reclamo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_detalle) REFERENCES Detalle_Pedido(id_detalle) ON DELETE CASCADE
);

-- Tabla: Transacciones_Wallet
-- Propósito: El "Libro Mayor" financiero. Registra cada centavo que entra o sale del saldo virtual de un usuario.
CREATE TABLE Transacciones_Wallet (
    id_transaccion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,                   -- De quién es el dinero.
    tipo_movimiento ENUM('Abono por Reembolso', 'Cargo por Compra', 'Recarga Manual', 'Retiro a Banco') NOT NULL, -- Concepto contable.
    monto DECIMAL(10, 2) NOT NULL,             -- Cantidad monetaria del movimiento.
    id_pedido_relacionado INT DEFAULT NULL,    -- Si gastó su saldo, en qué orden fue.
    id_reclamo_relacionado INT DEFAULT NULL,   -- Si ganó saldo, de qué reclamo vino.
    descripcion VARCHAR(255),                  -- Concepto libre para el estado de cuenta.
    fecha_transaccion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_pedido_relacionado) REFERENCES Pedidos(id_pedido) ON DELETE SET NULL,
    FOREIGN KEY (id_reclamo_relacionado) REFERENCES Reclamos_Producto(id_reclamo) ON DELETE SET NULL
);

-- ==========================================
-- 5. FINANZAS Y LIQUIDACIONES (EL NEGOCIO)
-- ==========================================

-- Tabla: Liquidaciones_Restaurantes
-- Propósito: Los "Recibos de Pago" semanales que agrupan las ganancias de los negocios.
CREATE TABLE Liquidaciones_Restaurantes (
    id_liquidacion INT AUTO_INCREMENT PRIMARY KEY,
    id_restaurante INT NOT NULL,               -- A quién se le debe.
    fecha_inicio DATE NOT NULL,                -- Inicio del periodo de corte.
    fecha_fin DATE NOT NULL,                   -- Fin del periodo de corte.
    monto_total DECIMAL(10, 2) NOT NULL,       -- Total a transferir.
    estatus ENUM('Pendiente', 'Procesando', 'Pagado', 'Rechazado_Por_Banco') DEFAULT 'Pendiente', -- Control interno de pagos.
    metodo_pago VARCHAR(50),                   -- Vía por la que se mandó (Ej. SPEI).
    referencia_bancaria VARCHAR(100),          -- Folio del banco para comprobación fiscal.
    fecha_pago TIMESTAMP NULL,                 -- Día exacto en que cayó el dinero.
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_restaurante) REFERENCES Restaurantes(id_restaurante)
);

-- Tabla: Liquidaciones_Repartidores
-- Propósito: Los "Recibos de Pago" semanales para los motociclistas.
CREATE TABLE Liquidaciones_Repartidores (
    id_liquidacion INT AUTO_INCREMENT PRIMARY KEY,
    id_repartidor INT NOT NULL,                -- El repartidor a pagar.
    fecha_inicio DATE NOT NULL,                -- Periodo.
    fecha_fin DATE NOT NULL,                   -- Periodo.
    monto_total DECIMAL(10, 2) NOT NULL,       -- Total a depositarle.
    estatus ENUM('Pendiente', 'Procesando', 'Pagado', 'Rechazado_Por_Banco') DEFAULT 'Pendiente',
    metodo_pago VARCHAR(50), 
    referencia_bancaria VARCHAR(100), 
    fecha_pago TIMESTAMP NULL, 
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_repartidor) REFERENCES Usuarios(id_usuario)
);

-- Tabla: Finanzas_Pedido
-- Propósito: El corte de caja microscópico por pedido. Aquí calculas la rentabilidad de tu empresa orden por orden.
CREATE TABLE Finanzas_Pedido (
    id_finanza INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL UNIQUE,             -- Asegura que solo haya