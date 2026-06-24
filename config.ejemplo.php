<?php
/**
 * config.ejemplo.php
 * ------------------
 * COPIAR este archivo como config.php en producción y completar los valores reales.
 * NUNCA subir config.php al repositorio Git.
 *
 * Instrucciones: ver docs/OPERACION.md
 */

// ── Base de datos ─────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');           // Generalmente localhost en cPanel
define('DB_NAME', 'nombre_base_datos');   // Ej: solucio1_control
define('DB_USER', 'usuario_db');          // Usuario MySQL de cPanel
define('DB_PASS', 'contraseña_segura');   // Contraseña MySQL

// ── IPs públicas autorizadas ──────────────────────────────────────────────────
// Agregar aquí todas las IPs públicas desde las que se permite acceso.
// En Colombia muchas sedes salen por NAT de Bogotá, validar con ip.php.
$ALLOW_IPS = [
    '186.84.155.242',
    '186.84.155.244',
    // Agregar más IPs según sea necesario:
    // '200.xxx.xxx.xxx',
];
