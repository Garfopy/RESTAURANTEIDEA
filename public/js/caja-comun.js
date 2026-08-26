/* ============================================================
   caja-comun.js — utilidades compartidas por las pantallas del POS.
   ============================================================ */
(function () {
  'use strict';

  const CAJA = window.CAJA || {};
  const BASE = CAJA.base || '/';

  /** POST JSON con CSRF. Devuelve siempre {ok, ...} y nunca lanza por red. */
  async function postJson(ruta, datos) {
    try {
      const r = await fetch(BASE + ruta, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-Token': CAJA.csrf || ''
        },
        body: JSON.stringify(Object.assign({ _csrf: CAJA.csrf || '' }, datos || {}))
      });
      const data = await r.json().catch(() => ({}));
      if (data.redirect && !data.ok) {
        window.location.href = data.redirect;
        return { ok: false, error: data.error || '' };
      }
      return data;
    } catch (e) {
      return { ok: false, error: 'Sin conexión con el servidor. Revisa la red.' };
    }
  }

  async function getJson(ruta) {
    try {
      const r = await fetch(BASE + ruta, { headers: { 'Accept': 'application/json' } });
      const data = await r.json().catch(() => ({}));
      if (data.redirect && !data.ok) { window.location.href = data.redirect; }
      return data;
    } catch (e) {
      return { ok: false, error: 'Sin conexión con el servidor.' };
    }
  }

  const pesos = (n) => '$' + (Number(n) || 0).toLocaleString('es-MX', {
    minimumFractionDigits: 2, maximumFractionDigits: 2
  });

  function abrirModal(id)  { const m = document.getElementById(id); if (m) m.hidden = false; }
  function cerrarModal(id) { const m = document.getElementById(id); if (m) m.hidden = true; }

  /** Abre con [data-modal], cierra con [data-cerrar], con el fondo o con Esc. */
  document.addEventListener('click', (e) => {
    if (e.target.classList && e.target.classList.contains('modal')) e.target.hidden = true;

    const cerrar = e.target.closest('[data-cerrar]');
    if (cerrar) cerrarModal(cerrar.dataset.cerrar);

    const abrir = e.target.closest('[data-modal]');
    if (abrir && !abrir.disabled) abrirModal(abrir.dataset.modal);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal:not([hidden])').forEach(m => { m.hidden = true; });
    }
    // Ctrl+L bloquea la pantalla sin cerrar el turno.
    if (e.key === 'l' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); bloquear(); }
  });

  async function bloquear() {
    const r = await postJson('rest-caja/bloquear', {});
    if (r.redirect) window.location.href = r.redirect;
  }

  document.querySelectorAll('[data-accion="bloquear"]').forEach(b => {
    b.addEventListener('click', bloquear);
  });

  /** Sonido corto para avisar de un pedido nuevo, sin archivos externos. */
  function beep() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gan = ctx.createGain();
      osc.connect(gan); gan.connect(ctx.destination);
      osc.frequency.value = 880;
      gan.gain.setValueAtTime(0.0001, ctx.currentTime);
      gan.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + 0.01);
      gan.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
      osc.start(); osc.stop(ctx.currentTime + 0.36);
    } catch (e) { /* el navegador puede exigir un toque previo: no pasa nada */ }
  }

  window.Caja = { postJson, getJson, pesos, abrirModal, cerrarModal, beep };
})();
