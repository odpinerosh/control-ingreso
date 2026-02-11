(function () {
  const cedulaEl = document.getElementById('cedula');
  const msgEl = document.getElementById('msg');

  if (!cedulaEl || !msgEl) return;

  const sede = (cedulaEl.dataset.sede || '').trim();
  const k = (cedulaEl.dataset.k || '').trim();

  function showMsg(text, ok = true) {
    msgEl.style.display = 'block';
    msgEl.className = 'alert ' + (ok ? 'ok' : 'err');
    msgEl.textContent = text;
  }

  cedulaEl.addEventListener('input', () => {
    cedulaEl.value = cedulaEl.value.replace(/\D/g, '');
  });

  async function marcar(tipo) {
    const cedula = cedulaEl.value.trim();
    if (!cedula || cedula.length < 5) {
      showMsg('Digite una cédula válida.', false);
      return;
    }

    document.querySelectorAll('button[data-t]').forEach(b => (b.disabled = true));

    try {
      const apiUrl = new URL("api/marcar.php", window.location.href).toString();
      const r = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cedula, tipo, sede, k })
      });

      const data = await r.json().catch(() => ({ ok: false, msg: 'Respuesta inválida' }));
      showMsg(data.msg || (data.ok ? 'OK' : 'Error'), !!data.ok);

      if (data.ok) {
        cedulaEl.value = '';
        cedulaEl.focus();
      }
    } catch (e) {
      showMsg('No se pudo conectar al servidor.', false);
    } finally {
      document.querySelectorAll('button[data-t]').forEach(b => (b.disabled = false));
    }
  }

  document.querySelectorAll('button[data-t]').forEach(btn => {
    btn.addEventListener('click', () => marcar(btn.dataset.t));
  });

  cedulaEl.focus();
})();
