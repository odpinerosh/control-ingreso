/**
 * js/kiosko.js
 * ------------
 * Lógica del kiosko de Control de Ingreso.
 * Vanilla JS puro, sin dependencias.
 *
 * Requisito: window.KIOSKO debe estar definido por index.php antes de
 * cargar este script, con la forma:
 *   { sede: "BOYACA", k: "BOYACA-9f4c2a1b3d" }
 */

(function () {
    'use strict';

    // ── Referencia a elementos del DOM ────────────────────────────────────────
    var cedulaInput = document.getElementById('cedula');
    var banner      = document.getElementById('banner');
    var botones     = document.querySelectorAll('.btn[data-tipo]');

    // ── URL de la API (robusta: relativa desde la ubicación actual) ───────────
    // Sube un nivel desde /control-ingreso/ hasta la raíz del subpath y añade api/marcar.php
    var apiUrl = new URL('api/marcar.php', window.location.href).toString();

    // ── Anti doble clic: bandera global ──────────────────────────────────────
    window.__busyMark = false;

    // ── Etiquetas legibles por tipo ───────────────────────────────────────────
    var ETIQUETAS = {
        'ENTRADA':          'entrada',
        'SALIDA_ALMUERZO':  'salida almuerzo',
        'REGRESO_ALMUERZO': 'regreso almuerzo',
        'SALIDA':           'salida',
    };

    // ── Función auxiliar: formatear hora HH:MM:SS ─────────────────────────────
    function horaActual() {
        var ahora = new Date();
        var hh = String(ahora.getHours()).padStart(2, '0');
        var mm = String(ahora.getMinutes()).padStart(2, '0');
        var ss = String(ahora.getSeconds()).padStart(2, '0');
        return hh + ':' + mm + ':' + ss;
    }

    // ── Mostrar banner ────────────────────────────────────────────────────────
    function mostrarBanner(tipo, texto) {
        banner.className = 'banner';
        banner.classList.add(tipo === 'ok' ? 'banner--ok' : 'banner--error');
        banner.textContent = texto;
        banner.removeAttribute('hidden');
    }

    function ocultarBanner() {
        banner.className = 'banner banner--hidden';
        banner.textContent = '';
    }

    // ── Habilitar / deshabilitar botones ──────────────────────────────────────
    function setBotonesDisabled(estado) {
        botones.forEach(function (btn) {
            btn.disabled = estado;
        });
    }

    // ── Función principal: enviar marcación ───────────────────────────────────
    function marcar(tipo) {
        // Anti doble clic
        if (window.__busyMark) return;

        var cedula = cedulaInput.value.trim();

        // Validación básica de cédula en el front (la real está en la API)
        if (cedula === '' || !/^\d+$/.test(cedula)) {
            mostrarBanner('error', '⚠ Ingrese un número de cédula válido');
            cedulaInput.focus();
            return;
        }

        // Activar bandera y deshabilitar botones
        window.__busyMark = true;
        setBotonesDisabled(true);
        ocultarBanner();

        // Construir payload
        var payload = {
            cedula: cedula,
            tipo:   tipo,
            sede:   window.KIOSKO.sede,
            k:      window.KIOSKO.k
        };

        // Enviar a la API
        fetch(apiUrl, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        })
        .then(function (response) {
            // Intentar parsear JSON aunque el status no sea 200
            return response.json().then(function (data) {
                return { status: response.status, data: data };
            });
        })
        .then(function (result) {
            if (result.data && result.data.ok) {
                // ── Éxito ──────────────────────────────────────────────────
                var etiqueta = ETIQUETAS[tipo] || tipo.toLowerCase();
                var hora     = horaActual();
                mostrarBanner('ok', '✔ Marcación registrada · ' + etiqueta + ' · ' + hora);

                // Limpiar campo y devolver foco
                cedulaInput.value = '';
                cedulaInput.focus();
            } else {
                // ── Error de negocio devuelto por la API ───────────────────
                var msg = (result.data && result.data.msg) ? result.data.msg : 'Error al registrar';
                mostrarBanner('error', '✘ ' + msg);
                cedulaInput.focus();
            }
        })
        .catch(function (err) {
            // ── Error de red o parse ───────────────────────────────────────
            console.error('Error de red:', err);
            mostrarBanner('error', '✘ Error de conexión. Intente nuevamente.');
            cedulaInput.focus();
        })
        .finally(function () {
            // Liberar siempre, haya éxito o error
            window.__busyMark = false;
            setBotonesDisabled(false);
        });
    }

    // ── Asignar eventos a los botones ─────────────────────────────────────────
    botones.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tipo = btn.getAttribute('data-tipo');
            if (tipo) marcar(tipo);
        });
    });

    // ── Enter en el campo de cédula dispara "Entrada" por defecto ─────────────
    cedulaInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            // Buscar el botón de Entrada y simular clic
            var btnEntrada = document.querySelector('.btn[data-tipo="ENTRADA"]');
            if (btnEntrada) btnEntrada.click();
        }
    });

    // ── Foco inicial ──────────────────────────────────────────────────────────
    cedulaInput.focus();

})();
