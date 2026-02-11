<?php
$sede = strtoupper(trim($_GET['sede'] ?? ''));
$k    = trim($_GET['k'] ?? '');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Control de ingreso</title>
  <link rel="stylesheet" href="css/styles.css?v=1">
</head>
<body>
  <div class="card">
    <div class="meta">
    <h1>Control de ingreso</h1>

    <?php if ($sede === '' || $k === ''): ?>
      <div class="alert err">
        Acceso inválido. Use el <b>acceso directo</b> de su sede (favoritos / ícono).
      </div>
      <p class="muted small">
        Soporte: verifique IP en <b>/control-ingreso/ip.php</b>.
      </p>
    <?php else: ?>
      <div class="meta">
        <div><b>Sede:</b> <?= htmlspecialchars($sede) ?></div>
        <div class="small">* Solo red corporativa</div>
      </div>
    </div>

      <label for="cedula">Cédula</label>
      <input
        id="cedula"
        inputmode="numeric"
        placeholder="Digite su cédula y marque"
        maxlength="20"
        autocomplete="off"
        data-sede="<?= htmlspecialchars($sede) ?>"
        data-k="<?= htmlspecialchars($k) ?>"
        />


      <div class="grid">
        <button data-t="ENTRADA">Entrada</button>
        <button data-t="SALIDA_ALMUERZO">Salida almuerzo</button>
        <button data-t="REGRESO_ALMUERZO">Regreso almuerzo</button>
        <button data-t="SALIDA">Salida</button>
      </div>

      <div id="msg" class="alert" style="display:none;"></div>
      <div class="hint">Tip: después de marcar, el campo queda listo para la siguiente persona.</div>

      <script src="js/script.js?v=1"></script>
    <?php endif; ?>
  </div>
</body>
</html>
