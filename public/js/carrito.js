// carrito.js — Multi-branch cart logic

const Carrito = {
  baseUrl: window.BASE_URL || '/',

  agregar(productoId, sucursales) {
    return postJSON(this.baseUrl + 'carrito/agregar', { producto_id: productoId, sucursales });
  },

  actualizar(productoId, sucursalId, cantidad) {
    return postJSON(this.baseUrl + 'carrito/actualizar', {
      producto_id: productoId,
      sucursal_id: sucursalId,
      cantidad: parseFloat(cantidad) || 0
    });
  },

  eliminar(productoId) {
    return postJSON(this.baseUrl + 'carrito/eliminar', { producto_id: productoId });
  },

  // Recalculate a row after any quantity change
  recalcularFila(productoId) {
    const inputs = document.querySelectorAll(`input[data-producto="${productoId}"]`);
    let total = 0;
    inputs.forEach(i => total += parseFloat(i.value) || 0);
    if (total <= 0) {
      ['total', 'precio', 'sub'].forEach(k => {
        const el = document.getElementById(`${k}-${productoId}`);
        if (el) el.textContent = k === 'total' ? '0 kg' : '$0.00';
      });
      return;
    }

    fetch(`${this.baseUrl}api/precioEscalonado?producto_id=${productoId}&cantidad=${total}`)
      .then(r => r.json())
      .then(d => {
        const elTotal = document.getElementById(`total-${productoId}`);
        const elPrecio = document.getElementById(`precio-${productoId}`);
        const elSub    = document.getElementById(`sub-${productoId}`);
        if (elTotal)  elTotal.textContent  = total + ' kg';
        if (elPrecio) elPrecio.textContent = '$' + parseFloat(d.precio).toFixed(2);
        if (elSub)    elSub.textContent    = '$' + parseFloat(d.subtotal).toLocaleString('es-MX');
        Carrito.recalcularGrandTotal();
      });
  },

  recalcularGrandTotal() {
    let grand = 0;
    document.querySelectorAll('[id^="sub-"]').forEach(el => {
      grand += parseFloat(el.textContent.replace(/[$,]/g, '')) || 0;
    });
    const elGrand = document.getElementById('grandTotal');
    if (elGrand) elGrand.textContent = '$' + grand.toLocaleString('es-MX', { maximumFractionDigits: 0 });
  }
};

// Global handler for onchange in cart table inputs
function actualizarCantidad(productoId, sucursalId, valor) {
  Carrito.actualizar(productoId, sucursalId, valor)
    .then(d => { if (d.ok) Carrito.recalcularFila(productoId); });
}

function eliminarItem(productoId) {
  if (!confirm('¿Quitar este producto del carrito?')) return;
  Carrito.eliminar(productoId).then(d => { if (d.ok) location.reload(); });
}
