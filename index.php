<?php
/**
 * index.php
 * ---------
 * Interfaz principal del kiosko de Control de Ingreso.
 * Se accede con parámetros: ?sede=BOYACA&k=BOYACA-9f4c2a1b3d
 */

// ── Leer parámetros GET ───────────────────────────────────────────────────────
$sede = isset($_GET['sede']) ? strtoupper(trim($_GET['sede'])) : '';
$k    = isset($_GET['k'])    ? trim($_GET['k'])                : '';

// Validación mínima en el front (la seguridad real está en api/marcar.php)
if ($sede === '' || $k === '') {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="es"><body style="font-family:sans-serif;padding:2rem">';
    echo '<h2>Acceso inválido</h2>';
    echo '<p>URL incorrecta. Contacte a soporte.</p>';
    echo '</body></html>';
    exit;
}

// Nombre legible para mostrar en pantalla (solo para UI)
$sede_display = htmlspecialchars($sede, ENT_QUOTES, 'UTF-8');
$k_display    = htmlspecialchars($k,    ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Ingreso · <?= $sede_display ?></title>
    <link rel="stylesheet" href="css/kiosko.css">
</head>
<body>

<div class="kiosko-wrapper">
    <div class="kiosko-card">

        <!-- Header -->
        <header class="kiosko-header">
            <div class="kiosko-logo">✦</div>
            <h1>Control de Ingreso</h1>
            <p class="kiosko-sede">SEDE: <?= $sede_display ?></p>
        </header>

        <!-- Formulario -->
        <main class="kiosko-body">
            <div class="field-group">
                <label for="cedula">Número de cédula</label>
                <input
                    type="text"
                    id="cedula"
                    name="cedula"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    placeholder="Digite su cédula"
                    autocomplete="off"
                    autofocus
                    maxlength="20"
                >
            </div>

            <!-- Banner de resultado (oculto por defecto) -->
            <div id="banner" class="banner banner--hidden" role="alert" aria-live="polite"></div>

            <!-- Botones de marcación -->
            <div class="botones-grid">
                <button class="btn btn--entrada"   data-tipo="ENTRADA"          type="button">
                    <span class="btn-icon">▶</span> Entrada
                </button>
                <button class="btn btn--almuerzo"  data-tipo="SALIDA_ALMUERZO"  type="button">
                    <span class="btn-icon">⏸</span> Salida almuerzo
                </button>
                <button class="btn btn--regreso"   data-tipo="REGRESO_ALMUERZO" type="button">
                    <span class="btn-icon">↩</span> Regreso almuerzo
                </button>
                <button class="btn btn--salida"    data-tipo="SALIDA"           type="button">
                    <span class="btn-icon">■</span> Salida
                </button>
            </div>
        </main>

        <footer class="kiosko-footer">
            <small>Sistema interno · solo red corporativa</small>
        </footer>

    </div><!-- /.kiosko-card -->
</div><!-- /.kiosko-wrapper -->

<!-- Pasar datos de sede/token al JS de forma segura -->
<script>
    // Datos de la sesión inyectados desde PHP
    window.KIOSKO = {
        sede: "<?= $sede_display ?>",
        k:    "<?= $k_display ?>"
    };
</script>
<script src="js/kiosko.js"></script>

</body>
</html>
