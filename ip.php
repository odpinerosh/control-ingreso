<?php
/**
 * ip.php
 * ------
 * Herramienta de diagnóstico: muestra la IP pública desde la que se accede.
 * Útil para saber qué IP agregar en .htaccess y config.php.
 *
 * Uso: abrir https://solucionescooptraiss.com/control-ingreso/ip.php
 * desde la sede que se quiere autorizar.
 *
 * NOTA: Este archivo NO está protegido por .htaccess para que sea accesible
 * antes de autorizar la IP. Eliminar o proteger en producción si se desea.
 */

$ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

// También revisar cabeceras de proxy (Cloudflare, balanceadores, etc.)
$forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
$real      = $_SERVER['HTTP_X_REAL_IP']       ?? '';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Diagnóstico de IP ===\n\n";
echo "REMOTE_ADDR          : {$ip}\n";
echo "HTTP_X_FORWARDED_FOR : {$forwarded}\n";
echo "HTTP_X_REAL_IP       : {$real}\n";
echo "\nAgrega la IP correcta en .htaccess y en \$ALLOW_IPS de config.php\n";
