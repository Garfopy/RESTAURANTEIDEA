// precios.js — Tiered pricing calculator (client-side)

class PrecioEscalonado {
  constructor(rangos, precioBase) {
    this.rangos     = rangos || [];
    this.precioBase = parseFloat(precioBase) || 0;
  }

  getPrecio(cantidad) {
    cantidad = parseFloat(cantidad) || 0;
    let precio = this.precioBase;
    for (const r of this.rangos) {
      const min = parseFloat(r.rango_min) || 0;
      const max = r.rango_max ? parseFloat(r.rango_max) : Infinity;
      if (cantidad >= min && cantidad <= max) {
        precio = parseFloat(r.precio_por_unidad);
        break;
      }
    }
    return precio;
  }

  getSubtotal(cantidad) {
    return this.getPrecio(cantidad) * (parseFloat(cantidad) || 0);
  }

  // Highlight the active tier row in the pricing table
  highlightRow(cantidad) {
    document.querySelectorAll('.precio-row').forEach((tr, i) => {
      tr.style.background = '';
      const r = this.rangos[i];
      if (!r) return;
      const min = parseFloat(r.rango_min) || 0;
      const max = r.rango_max ? parseFloat(r.rango_max) : Infinity;
      if (cantidad >= min && cantidad <= max) {
        tr.style.background = '#FEF2F2';
        tr.style.borderRadius = '6px';
      }
    });
  }

  // Format as MXN currency string
  static fmt(n) {
    return '$' + parseFloat(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
}
