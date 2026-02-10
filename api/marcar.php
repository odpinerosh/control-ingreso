<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

// 1) Validar IP corporativa
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, $ALLOW_IPS, true)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'msg'=>'Acceso denegado: fuera de red corporativa.']);
  exit;
}

// 2) Leer JSON
$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];

$cedula = preg_replace('/\D+/', '', (string)($body['cedula'] ?? ''));
$tipo   = (string)($body['tipo'] ?? '');
$sede   = strtoupper(trim((string)($body['sede'] ?? '')));
$k      = trim((string)($body['k'] ?? ''));

// 3) Validaciones
if ($cedula === '' || strlen($cedula) < 5 || strlen($cedula) > 20) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Cédula inválida.']);
  exit;
}

$tipos = ['ENTRADA','SALIDA_ALMUERZO','REGRESO_ALMUERZO','SALIDA'];
if (!in_array($tipo, $tipos, true)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Tipo inválido.']);
  exit;
}

if ($sede === '' || $k === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Sede/Token faltante.']);
  exit;
}

// 4) Hash del token (no guardamos token en claro)
$khash = hash('sha256', $k);
$ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

// 5) DB
try {
  $pdo = new PDO(
    "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8",
    DB_USER, DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
  );

  // 5.1 Validar sede + token
  $st = $pdo->prepare("SELECT sede_id, nombre FROM sedes WHERE codigo=? AND token_hash=? AND activa=1 LIMIT 1");
  $st->execute([$sede, $khash]);
  $sedeRow = $st->fetch();

  if (!$sedeRow) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'Acceso denegado: QR inválido.']);
    exit;
  }

  // 5.2 Anti doble-clic (mismo tipo para misma cédula en 60s)
  $st2 = $pdo->prepare("
    SELECT marcacion_id
    FROM marcaciones
    WHERE cedula=? AND tipo=? AND sede_id=? AND fecha_hora >= (NOW() - INTERVAL 60 SECOND)
    LIMIT 1
  ");
  $st2->execute([$cedula, $tipo, $sedeRow['sede_id']]);
  if ($st2->fetch()) {
    echo json_encode(['ok'=>true,'msg'=>'Marcación ya registrada (evité duplicado por doble clic).']);
    exit;
  }

  // 5.3 Insert
  $ins = $pdo->prepare("
    INSERT INTO marcaciones (cedula, tipo, sede_id, ip_origen, user_agent)
    VALUES (?, ?, ?, ?, ?)
  ");
  $ins->execute([$cedula, $tipo, $sedeRow['sede_id'], $ip, $ua]);

  echo json_encode(['ok'=>true,'msg'=>"Marcación registrada en {$sedeRow['nombre']}"]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error interno al registrar.']);
}
