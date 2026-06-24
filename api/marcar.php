<?php
/**
 * api/marcar.php
 * --------------
 * Endpoint que recibe marcaciones de los kioskos.
 * Acepta solo POST con JSON. Responde siempre JSON.
 *
 * Flujo:
 *   1) Validar IP permitida (doble seguridad: .htaccess + PHP)
 *   2) Leer y validar JSON del body
 *   3) Validar cédula numérica
 *   4) Validar tipo de marcación
 *   5) Buscar sede en BD
 *   6) Comparar hash SHA-256 del token
 *   7) Insertar marcación
 *   8) Responder JSON
 */

// ── Cabeceras ─────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
// Evitar caché en el cliente
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// ── Solo aceptar POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

// ── Cargar configuración ──────────────────────────────────────────────────────
// config.php está un nivel arriba de api/
$config_path = __DIR__ . '/../config.php';
if (!file_exists($config_path)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error de configuración del servidor']);
    exit;
}
require $config_path;
// Ahora disponibles: DB_HOST, DB_NAME, DB_USER, DB_PASS, $ALLOW_IPS

// ── 1) Validar IP ─────────────────────────────────────────────────────────────
$ip_cliente = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($ip_cliente, $ALLOW_IPS, true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Acceso no autorizado']);
    exit;
}

// ── 2) Leer JSON del body ─────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Petición inválida']);
    exit;
}

$cedula = isset($data['cedula']) ? trim((string)$data['cedula']) : '';
$tipo   = isset($data['tipo'])   ? strtoupper(trim((string)$data['tipo'])) : '';
$sede   = isset($data['sede'])   ? strtoupper(trim((string)$data['sede'])) : '';
$k      = isset($data['k'])      ? trim((string)$data['k']) : '';

// ── 3) Validar cédula ─────────────────────────────────────────────────────────
if ($cedula === '' || !ctype_digit($cedula) || strlen($cedula) > 20) {
    echo json_encode(['ok' => false, 'msg' => 'Cédula inválida']);
    exit;
}

// ── 4) Validar tipo ───────────────────────────────────────────────────────────
$tipos_permitidos = ['ENTRADA', 'SALIDA_ALMUERZO', 'REGRESO_ALMUERZO', 'SALIDA'];
if (!in_array($tipo, $tipos_permitidos, true)) {
    echo json_encode(['ok' => false, 'msg' => 'Tipo de marcación inválido']);
    exit;
}

// ── 5 y 6) Buscar sede y validar token ───────────────────────────────────────
if ($sede === '' || $k === '') {
    echo json_encode(['ok' => false, 'msg' => 'Acceso inválido']);
    exit;
}

// Conectar a MySQL (PDO con charset utf8mb4)
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    // No exponer detalle del error al cliente
    echo json_encode(['ok' => false, 'msg' => 'Error de conexión a base de datos']);
    exit;
}

// Buscar sede activa
$stmt = $pdo->prepare('SELECT id, nombre, token_hash FROM sedes WHERE codigo = :codigo AND activa = 1 LIMIT 1');
$stmt->execute([':codigo' => $sede]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['ok' => false, 'msg' => 'Sede no encontrada o inactiva']);
    exit;
}

// Comparar SHA-256 del token recibido con el hash almacenado
$token_hash_calculado = hash('sha256', $k);

if (!hash_equals($row['token_hash'], $token_hash_calculado)) {
    echo json_encode(['ok' => false, 'msg' => 'Acceso inválido']);
    exit;
}

// ── 7) Insertar marcación ─────────────────────────────────────────────────────
$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);

try {
    $ins = $pdo->prepare(
        'INSERT INTO marcaciones (cedula, tipo, sede, fecha_hora, ip_publica, user_agent)
         VALUES (:cedula, :tipo, :sede, NOW(), :ip, :ua)'
    );
    $ins->execute([
        ':cedula' => $cedula,
        ':tipo'   => $tipo,
        ':sede'   => $sede,
        ':ip'     => $ip_cliente,
        ':ua'     => $user_agent,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar la marcación']);
    exit;
}

// ── 8) Responder éxito ────────────────────────────────────────────────────────
echo json_encode([
    'ok'  => true,
    'msg' => 'Marcación registrada en ' . $row['nombre'],
]);
