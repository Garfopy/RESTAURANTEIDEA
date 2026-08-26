/* ============================================================
   caja-print.js — PrintBridge

   El POS nunca habla con una impresora: habla con esta capa, que elige
   adaptador en tiempo de ejecución. Hoy solo está implementado `browser`;
   `qz` y `desktop` quedan declarados con su contrato para que el día que
   se integren (QZ Tray o la app de escritorio) no haya que tocar el POS.

   Contrato del payload: GET rest-caja/ticketPayload/{id}
   Ver plan-web-cajero-ui.md §4.
   ============================================================ */
(function () {
  'use strict';

  const BASE = (window.CAJA && window.CAJA.base) || '/';

  const PrintBridge = {
    /** @returns {'desktop'|'qz'|'browser'} */
    adaptador() {
      if (window.cajaDesktop && typeof window.cajaDesktop.imprimir === 'function') return 'desktop';
      if (window.qz && window.qz.websocket) return 'qz';
      return 'browser';
    },

    /**
     * Imprime el ticket de un pedido.
     * @param {number} pedidoId
     * @param {{reimpresion?: boolean, ancho?: '58'|'80'}} opciones
     */
    async imprimirTicket(pedidoId, opciones) {
      const op = opciones || {};
      const modo = this.adaptador();

      if (modo === 'desktop') {
        const datos = await this.payload(pedidoId, op);
        return window.cajaDesktop.imprimir(datos);
      }

      if (modo === 'qz') {
        // PENDIENTE (v2): conectar por websocket a QZ Tray y mandar ESC/POS
        // crudo armado desde el mismo payload. Mientras tanto, se imprime
        // por navegador para no dejar al cajero sin ticket.
        console.info('[caja] QZ Tray detectado pero el adaptador todavía no está implementado.');
      }

      return this.imprimirEnNavegador(pedidoId, op);
    },

    /** Adaptador implementado: imprime la vista térmica en un iframe oculto. */
    imprimirEnNavegador(pedidoId, op) {
      return new Promise((resolve) => {
        const params = new URLSearchParams();
        if (op.ancho) params.set('w', op.ancho);
        if (op.reimpresion) params.set('reimpresion', '1');

        const viejo = document.getElementById('caja-print-frame');
        if (viejo) viejo.remove();

        const frame = document.createElement('iframe');
        frame.id = 'caja-print-frame';
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';
        frame.src = BASE + 'rest-caja/ticket/' + pedidoId + (params.toString() ? '?' + params : '');

        frame.onload = () => {
          try {
            frame.contentWindow.focus();
            frame.contentWindow.print();
          } catch (e) {
            // Si el navegador bloquea la impresión desde el iframe, se abre
            // el ticket en una pestaña: peor experiencia, pero nunca deja
            // al cajero sin poder entregar el comprobante.
            window.open(frame.src, '_blank');
          }
          resolve(true);
        };

        document.body.appendChild(frame);
      });
    },

    async payload(pedidoId, op) {
      const params = new URLSearchParams();
      if (op && op.ancho) params.set('w', op.ancho);
      if (op && op.reimpresion) params.set('reimpresion', '1');

      const r = await fetch(BASE + 'rest-caja/ticketPayload/' + pedidoId + (params.toString() ? '?' + params : ''),
                            { headers: { 'Accept': 'application/json' } });
      const data = await r.json();
      return data.ticket;
    },

    /** Cajón de dinero: solo existe con QZ Tray o app de escritorio (v2). */
    async abrirCajon() {
      const modo = this.adaptador();
      if (modo === 'desktop' && window.cajaDesktop.abrirCajon) {
        return window.cajaDesktop.abrirCajon();
      }
      console.info('[caja] La apertura de cajón necesita QZ Tray o la app de escritorio.');
      return false;
    }
  };

  window.PrintBridge = PrintBridge;
})();
