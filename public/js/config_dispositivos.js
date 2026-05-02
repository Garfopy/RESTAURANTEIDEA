// config_dispositivos.js — IoT device management (HikVision + Shelly)

const Dispositivos = {
  baseUrl: window.BASE_URL || '/',

  // Open the modal for adding/editing a device
  openModal(tipo, id = null) {
    const modal = document.getElementById('modalDispositivo');
    const title = document.getElementById('modalTitle');
    const form  = document.getElementById('formDispositivo');
    if (!modal || !form) return;

    form.reset();
    document.getElementById('dispTipo').value = tipo;
    document.getElementById('dispId').value   = id || '';

    // Show/hide fields based on tipo
    document.querySelectorAll('.field-hikvision').forEach(el => el.style.display = tipo === 'hikvision' ? 'block' : 'none');
    document.querySelectorAll('.field-shelly').forEach(el => el.style.display    = tipo === 'shelly'    ? 'block' : 'none');

    if (id) {
      title.textContent = 'Editar dispositivo';
      this.loadDevice(tipo, id);
    } else {
      title.textContent = `Agregar dispositivo ${tipo === 'hikvision' ? 'HikVision' : 'Shelly'}`;
    }

    modal.classList.add('active');
  },

  closeModal() {
    const modal = document.getElementById('modalDispositivo');
    if (modal) modal.classList.remove('active');
  },

  loadDevice(tipo, id) {
    fetch(`${this.baseUrl}config/getDispositivo?tipo=${tipo}&id=${id}`)
      .then(r => r.json())
      .then(d => {
        if (!d.ok) return;
        const dev = d.dispositivo;
        Object.keys(dev).forEach(key => {
          const el = document.getElementById('disp_' + key);
          if (el) el.value = dev[key];
        });
      });
  },

  save() {
    const form = document.getElementById('formDispositivo');
    if (!form) return;
    const data = {};
    new FormData(form).forEach((v, k) => data[k] = v);

    fetch(`${this.baseUrl}config/guardarDispositivo`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    }).then(r => r.json()).then(d => {
      if (d.ok) {
        showToast('Dispositivo guardado', 'success');
        this.closeModal();
        setTimeout(() => location.reload(), 700);
      } else {
        showToast(d.error || 'Error al guardar', 'error');
      }
    });
  },

  delete(tipo, id) {
    if (!confirm('¿Eliminar este dispositivo?')) return;
    fetch(`${this.baseUrl}config/eliminarDispositivo`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ tipo, id })
    }).then(r => r.json()).then(d => {
      if (d.ok) {
        showToast('Dispositivo eliminado', 'success');
        const row = document.getElementById(`device-row-${tipo}-${id}`);
        if (row) row.remove();
      } else {
        showToast(d.error || 'Error al eliminar', 'error');
      }
    });
  },

  // Toggle Shelly relay
  toggleShelly(id) {
    fetch(`${this.baseUrl}config/toggleShelly/${id}`, { method: 'POST' })
      .then(r => r.json())
      .then(d => {
        if (d.ok) {
          const badge = document.getElementById(`shelly-estado-${id}`);
          if (badge) { badge.textContent = d.estado === 'on' ? '● ON' : '● OFF'; badge.style.color = d.estado === 'on' ? '#10B981' : '#EF4444'; }
          showToast(d.estado === 'on' ? 'Encendido' : 'Apagado', 'success');
        }
      });
  }
};

// Close modal on overlay click
document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('modalDispositivo');
  if (overlay) {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) Dispositivos.closeModal();
    });
  }
});
