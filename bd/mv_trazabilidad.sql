-- =====================================================================
-- Sistema de trazabilidad de materiales y órdenes de producción
-- M & V Technology Textil S.A.C. · Modelo físico (MySQL / MariaDB)
-- 14 tablas · 18 llaves foráneas
-- Equivale a database/migrations/2026_11_02_000001_create_trazabilidad_tables.php
-- =====================================================================

CREATE DATABASE IF NOT EXISTS mv_trazabilidad CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE mv_trazabilidad;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS estados_orden, movimientos, asignaciones, materiales_orden, ordenes, pedidos,
  detalle_compra, compras, materiales_producto, productos, materiales, costureros, proveedores, usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Usuarios y su rol (RN-10)
CREATE TABLE usuarios (
  id_usuario      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(100) NOT NULL,
  correo          VARCHAR(120) NOT NULL,
  contrasena      VARCHAR(255) NOT NULL,           -- hash bcrypt, nunca la clave
  rol             ENUM('ADMINISTRADOR','CONTROL_MATERIALES','ALMACEN','VENTAS','CONSULTA') NOT NULL,
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  remember_token  VARCHAR(100) NULL,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB;

-- 2. Proveedores
CREATE TABLE proveedores (
  id_proveedor  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  razon_social  VARCHAR(150) NOT NULL,
  ruc           CHAR(11) NULL,
  telefono      VARCHAR(20) NULL,
  rubro         VARCHAR(80) NULL,
  PRIMARY KEY (id_proveedor),
  UNIQUE KEY uq_proveedores_ruc (ruc)
) ENGINE=InnoDB;

-- 3. Costureros (costureras externas)
CREATE TABLE costureros (
  id_costurero  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(100) NOT NULL,
  zona          VARCHAR(60) NULL,
  telefono      VARCHAR(20) NULL,
  PRIMARY KEY (id_costurero)
) ENGINE=InnoDB;

-- 4. Materiales con stock actual, mínimo y costo promedio
CREATE TABLE materiales (
  id_material     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(120) NOT NULL,
  tipo            ENUM('TELA','INSUMO','CONSUMIBLE','EMPAQUE') NOT NULL,
  unidad          VARCHAR(20) NOT NULL,
  stock_actual    DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock_minimo    DECIMAL(10,2) NOT NULL DEFAULT 0,
  costo_promedio  DECIMAL(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_material),
  UNIQUE KEY uq_materiales_nombre (nombre)
) ENGINE=InnoDB;

-- 5. Productos
CREATE TABLE productos (
  id_producto   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(100) NOT NULL,
  talla_tamano  VARCHAR(40) NULL,
  PRIMARY KEY (id_producto)
) ENGINE=InnoDB;

-- 6. Receta: cuánto material lleva una unidad de producto
CREATE TABLE materiales_producto (
  id_material_producto  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_producto           INT UNSIGNED NOT NULL,
  id_material           INT UNSIGNED NOT NULL,
  consumo_unitario      DECIMAL(8,3) NOT NULL,
  PRIMARY KEY (id_material_producto),
  CONSTRAINT fk_mp_producto FOREIGN KEY (id_producto) REFERENCES productos (id_producto),   -- FK 1
  CONSTRAINT fk_mp_material FOREIGN KEY (id_material) REFERENCES materiales (id_material)   -- FK 2
) ENGINE=InnoDB;

-- 7. Compras
CREATE TABLE compras (
  id_compra     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_proveedor  INT UNSIGNED NOT NULL,
  id_usuario    INT UNSIGNED NOT NULL,
  fecha         DATE NOT NULL,
  total         DECIMAL(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_compra),
  CONSTRAINT fk_compras_proveedor FOREIGN KEY (id_proveedor) REFERENCES proveedores (id_proveedor),  -- FK 3
  CONSTRAINT fk_compras_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)             -- FK 4
) ENGINE=InnoDB;

-- 8. Detalle de compra (lo rechazado por falla no entra al stock, RN-09)
CREATE TABLE detalle_compra (
  id_detalle          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_compra           INT UNSIGNED NOT NULL,
  id_material         INT UNSIGNED NOT NULL,
  cantidad            DECIMAL(10,2) NOT NULL,
  cantidad_rechazada  DECIMAL(10,2) NOT NULL DEFAULT 0,
  precio_unitario     DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id_detalle),
  CONSTRAINT fk_dc_compra FOREIGN KEY (id_compra) REFERENCES compras (id_compra),          -- FK 5
  CONSTRAINT fk_dc_material FOREIGN KEY (id_material) REFERENCES materiales (id_material)  -- FK 6
) ENGINE=InnoDB;

-- 9. Pedidos del cliente
CREATE TABLE pedidos (
  id_pedido         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente           VARCHAR(120) NOT NULL,
  telefono_cliente  VARCHAR(20) NULL,
  id_producto       INT UNSIGNED NOT NULL,
  especificaciones  TEXT NULL,
  cantidad          INT NOT NULL,
  fecha_entrega     DATE NOT NULL,
  estado            ENUM('REGISTRADO','EN_PRODUCCION','ENTREGADO','ANULADO') NOT NULL DEFAULT 'REGISTRADO',
  PRIMARY KEY (id_pedido),
  CONSTRAINT fk_pedidos_producto FOREIGN KEY (id_producto) REFERENCES productos (id_producto)  -- FK 7
) ENGINE=InnoDB;

-- 10. Órdenes de producción
CREATE TABLE ordenes (
  id_orden      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pedido     INT UNSIGNED NOT NULL,
  fecha_inicio  DATE NOT NULL,
  fecha_limite  DATE NOT NULL,
  estado        ENUM('PENDIENTE','EN_CORTE','EN_CONFECCION','EN_ESTAMPADO','TERMINADO','ENTREGADO','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
  costo_total   DECIMAL(10,2) NOT NULL DEFAULT 0,  -- se recalcula después de cada movimiento
  PRIMARY KEY (id_orden),
  CONSTRAINT fk_ordenes_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos (id_pedido)  -- FK 8
) ENGINE=InnoDB;

-- 11. Material requerido y entregado por orden
CREATE TABLE materiales_orden (
  id_material_orden   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_orden            INT UNSIGNED NOT NULL,
  id_material         INT UNSIGNED NOT NULL,
  cantidad_requerida  DECIMAL(10,2) NOT NULL DEFAULT 0,
  cantidad_entregada  DECIMAL(10,2) NOT NULL DEFAULT 0,  -- neto: entregado - devuelto
  PRIMARY KEY (id_material_orden),
  UNIQUE KEY uq_mo_orden_material (id_orden, id_material),
  CONSTRAINT fk_mo_orden FOREIGN KEY (id_orden) REFERENCES ordenes (id_orden),             -- FK 9
  CONSTRAINT fk_mo_material FOREIGN KEY (id_material) REFERENCES materiales (id_material)  -- FK 10
) ENGINE=InnoDB;

-- 12. Asignación de la orden a una costurera y su máquina
CREATE TABLE asignaciones (
  id_asignacion    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_orden         INT UNSIGNED NOT NULL,
  id_costurero     INT UNSIGNED NOT NULL,
  maquina          VARCHAR(60) NULL,
  cantidad_piezas  INT NOT NULL,
  fecha            DATE NOT NULL,
  PRIMARY KEY (id_asignacion),
  CONSTRAINT fk_asig_orden FOREIGN KEY (id_orden) REFERENCES ordenes (id_orden),                -- FK 11
  CONSTRAINT fk_asig_costurero FOREIGN KEY (id_costurero) REFERENCES costureros (id_costurero)  -- FK 12
) ENGINE=InnoDB;

-- 13. Movimientos: el kardex. Nunca se borran; un error se anula con un movimiento contrario (RN-02)
CREATE TABLE movimientos (
  id_movimiento          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_material            INT UNSIGNED NOT NULL,
  id_orden               INT UNSIGNED NULL,
  id_usuario             INT UNSIGNED NOT NULL,
  tipo                   ENUM('ENTRADA','SALIDA_ORDEN','DEVOLUCION','MERMA','AJUSTE','ANULACION') NOT NULL,
  cantidad               DECIMAL(10,2) NOT NULL,
  efecto                 DECIMAL(10,2) NOT NULL DEFAULT 0,  -- + entra, - sale, 0 merma en taller
  saldo                  DECIMAL(10,2) NOT NULL,
  motivo                 VARCHAR(150) NULL,
  anulado                TINYINT(1) NOT NULL DEFAULT 0,
  id_movimiento_anulado  INT UNSIGNED NULL,
  fecha                  DATETIME NOT NULL,
  PRIMARY KEY (id_movimiento),
  CONSTRAINT fk_mov_material FOREIGN KEY (id_material) REFERENCES materiales (id_material),                  -- FK 13
  CONSTRAINT fk_mov_orden FOREIGN KEY (id_orden) REFERENCES ordenes (id_orden),                             -- FK 14
  CONSTRAINT fk_mov_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario),                      -- FK 15
  CONSTRAINT fk_mov_anulado FOREIGN KEY (id_movimiento_anulado) REFERENCES movimientos (id_movimiento)      -- FK 16
) ENGINE=InnoDB;

-- 14. Historial de estados de cada orden
CREATE TABLE estados_orden (
  id_estado    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_orden     INT UNSIGNED NOT NULL,
  id_usuario   INT UNSIGNED NOT NULL,
  estado       VARCHAR(20) NOT NULL,
  fecha        DATETIME NOT NULL,
  observacion  VARCHAR(200) NULL,
  PRIMARY KEY (id_estado),
  CONSTRAINT fk_eo_orden FOREIGN KEY (id_orden) REFERENCES ordenes (id_orden),        -- FK 17
  CONSTRAINT fk_eo_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)  -- FK 18
) ENGINE=InnoDB;
