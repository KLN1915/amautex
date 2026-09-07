USE `amautex`;

-- ------------------------------------------------------------
--  1) Superadmin: dueño del sistema, sin academia asignada.
--     Login: super / super123     (cambiar al primer ingreso)
-- ------------------------------------------------------------
INSERT INTO `usuarios` (`academia_id`, `nombre`, `usuario`, `correo`, `clave`, `rol`, `activo`)
VALUES (NULL, 'Superadministrador', 'super', NULL,
        '$2y$10$xBEm7Q2tNZz2jh5bEAH7v.kgY4/xDpaOKXda.8j5Z8XriDvukUSnK',
        'superadmin', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- ------------------------------------------------------------
--  2) Academia de ejemplo con su administrador.
--     Login: admin / admin123
-- ------------------------------------------------------------
INSERT INTO `academias`
    (`nombre`, `codigo`, `correo`, `telefono`, `plan`, `estado`,
     `limite_usuarios`, `limite_alumnos`, `inicio_plan`, `fin_plan`)
VALUES
    ('Academia Demo', 'demo', 'contacto@demo.pe', '999999999', 'prueba', 'activa',
     10, 200, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

INSERT INTO `usuarios` (`academia_id`, `nombre`, `usuario`, `correo`, `clave`, `rol`, `activo`)
SELECT a.id, 'Administrador Demo', 'admin', 'admin@demo.pe',
       '$2y$10$bCfhJGBa3IllOxVwwfz9OOqcYVMeHIAE.UKGBHYn2TznuyzAnDXR.',
       'admin', 1
  FROM `academias` a
 WHERE a.codigo = 'demo'
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);
