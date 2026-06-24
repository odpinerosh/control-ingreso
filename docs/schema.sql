-- ============================================================
-- SISTEMA DE CONTROL DE INGRESO - COOPERATIVA
-- Schema de base de datos
-- ============================================================

-- Tabla de sedes
CREATE TABLE IF NOT EXISTS `sedes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo`      VARCHAR(50)  NOT NULL COMMENT 'Ej: BOYACA, CALI',
  `nombre`      VARCHAR(100) NOT NULL COMMENT 'Nombre legible de la sede',
  `token_hash`  CHAR(64)     NOT NULL COMMENT 'SHA-256 del token secreto de la sede',
  `activa`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de marcaciones
CREATE TABLE IF NOT EXISTS `marcaciones` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cedula`      VARCHAR(20)  NOT NULL COMMENT 'Número de cédula del trabajador',
  `tipo`        ENUM(
                  'ENTRADA',
                  'SALIDA_ALMUERZO',
                  'REGRESO_ALMUERZO',
                  'SALIDA'
                ) NOT NULL,
  `sede`        VARCHAR(50)  NOT NULL COMMENT 'Código de la sede',
  `fecha_hora`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_publica`  VARCHAR(45)  NOT NULL COMMENT 'IP pública desde donde se marcó',
  `user_agent`  VARCHAR(300) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_cedula`    (`cedula`),
  KEY `idx_sede`      (`sede`),
  KEY `idx_fecha`     (`fecha_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DE EJEMPLO - insertar sedes (ajustar token_hash real)
-- Para calcular SHA-256 del token ver docs/OPERACION.md
-- ============================================================

-- Ejemplo: token plano = BOYACA-9f4c2a1b3d
-- SHA-256  = 846c13a5c8e361b5c9cf388ba7a329360d08db7879f8107288b4aeb443413faf
INSERT INTO `sedes` (`codigo`, `nombre`, `token_hash`, `activa`) VALUES
('ATLANTICO',          'Atlántico',           'HASH_AQUI', 1),
('CALI',               'Cali',                'HASH_AQUI', 1),
('POPAYAN',            'Popayán',             'HASH_AQUI', 1),
('GIRARDOT',           'Girardot',            'HASH_AQUI', 1),
('CALDAS',             'Caldas',              'HASH_AQUI', 1),
('META',               'Meta',                'HASH_AQUI', 1),
('FUSA',               'Fusagasugá',          'HASH_AQUI', 1),
('HUILA',              'Huila',               'HASH_AQUI', 1),
('NORTE_SANTANDER',    'Norte de Santander',  'HASH_AQUI', 1),
('CARTAGENA',          'Cartagena',           'HASH_AQUI', 1),
('BOYACA',             'Boyacá',              '846c13a5c8e361b5c9cf388ba7a329360d08db7879f8107288b4aeb443413faf', 1),
('PEREIRA',            'Pereira',             'HASH_AQUI', 1),
('GUAJIRA',            'La Guajira',          'HASH_AQUI', 1),
('NARINO',             'Nariño',              'HASH_AQUI', 1),
('CAQUETA',            'Caquetá',             'HASH_AQUI', 1),
('YOPAL',              'Yopal',               'HASH_AQUI', 1),
('BUCARAMANGA',        'Bucaramanga',         'HASH_AQUI', 1),
('BOGOTA',             'Bogotá',              'HASH_AQUI', 1);
