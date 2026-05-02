// logistica_mapa.js — Logistics map with Leaflet.js

const LogisticaMapa = {
  map: null,
  markers: [],

  init(containerId, center = [20.5888, -100.3899], zoom = 11) {
    this.map = L.map(containerId, { zoomControl: true }).setView(center, zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
      maxZoom: 19,
    }).addTo(this.map);
    return this;
  },

  // Plot delivery stops on the map
  plotParadas(paradas) {
    this.clearMarkers();
    const latlngs = [];
    const colores = {
      pendiente: '#F59E0B',
      en_ruta:   '#3B82F6',
      entregado: '#10B981',
      incidente: '#EF4444',
    };

    paradas.forEach((p, i) => {
      if (!p.lat || !p.lng) return;
      const color  = colores[p.estado] || '#6B7280';
      const icon   = L.divIcon({
        html: `<div style="background:${color};color:#fff;font-size:11px;font-weight:800;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3)">${i + 1}</div>`,
        iconSize: [28, 28], iconAnchor: [14, 14], className: ''
      });
      const marker = L.marker([p.lat, p.lng], { icon })
        .addTo(this.map)
        .bindPopup(`
          <strong>${p.empresa_nombre || ''}</strong><br>
          ${p.sucursal_nombre || ''}<br>
          <span style="color:#6B7280;font-size:11px">${p.direccion || ''}</span><br>
          <a href="${window.BASE_URL || '/'}repartidor/detalle/${p.ruta_detalle_id}" style="color:#C8102E;font-size:12px">Ver detalle →</a>
        `);
      this.markers.push(marker);
      latlngs.push([p.lat, p.lng]);
    });

    // Draw route line
    if (latlngs.length > 1) {
      L.polyline(latlngs, { color: '#C8102E', weight: 3, dashArray: '8,6', opacity: .75 }).addTo(this.map);
    }
    if (latlngs.length) this.map.fitBounds(latlngs, { padding: [40, 40] });
  },

  clearMarkers() {
    this.markers.forEach(m => this.map.removeLayer(m));
    this.markers = [];
  },

  // Add depot marker (warehouse)
  addDepot(lat, lng, label = 'Bodega') {
    const icon = L.divIcon({
      html: `<div style="background:#1A1D23;color:#fff;font-size:10px;font-weight:700;padding:4px 8px;border-radius:6px;white-space:nowrap;box-shadow:0 2px 6px rgba(0,0,0,.3)">🏭 ${label}</div>`,
      iconAnchor: [0, 0], className: ''
    });
    L.marker([lat, lng], { icon }).addTo(this.map);
  }
};
