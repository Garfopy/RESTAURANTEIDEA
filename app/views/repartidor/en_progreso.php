<?php
include ROOT_PATH . '/app/views/components/header_repartidor.php';
// $entregaId passed from controller
?>
<div class="rep-page">
  <div style="margin-bottom:16px">
    <a href="<?= BASE_URL ?>repartidor/detalle/<?= $entrega['id'] ?>" style="font-size:.875rem;color:#94A3B8;text-decoration:none">← Detalle</a>
  </div>

  <div style="font-weight:700;font-size:1.1rem;margin-bottom:16px">Completar entrega</div>

  <!-- Nombre receptor -->
  <div class="rep-card" style="margin-bottom:12px">
    <label style="font-size:.75rem;color:#94A3B8;display:block;margin-bottom:6px">Nombre de quien recibe *</label>
    <input type="text" id="receptorNombre" placeholder="Ej. María García"
           style="width:100%;background:#2D3348;border:1px solid #374151;color:#F1F5F9;padding:10px;border-radius:8px;font-size:.875rem;box-sizing:border-box">
  </div>

  <!-- Firma digital -->
  <div class="rep-card" style="margin-bottom:12px">
    <div style="font-size:.75rem;color:#94A3B8;margin-bottom:8px">Firma del receptor *</div>
    <canvas id="firmaCanvas" width="100%" height="160"
            style="background:#2D3348;border-radius:8px;touch-action:none;width:100%;display:block;border:1px dashed #374151"></canvas>
    <div style="display:flex;gap:8px;margin-top:8px">
      <button onclick="limpiarFirma()" style="flex:1;padding:8px;background:#374151;color:#94A3B8;border:none;border-radius:6px;font-size:.75rem;cursor:pointer">
        🗑️ Limpiar
      </button>
    </div>
  </div>

  <!-- Foto evidencia -->
  <div class="rep-card" style="margin-bottom:16px">
    <div style="font-size:.75rem;color:#94A3B8;margin-bottom:8px">Foto de evidencia (opcional)</div>
    <div id="fotoPreview" style="display:none;margin-bottom:8px">
      <img id="fotoImg" style="width:100%;border-radius:8px;max-height:200px;object-fit:cover">
    </div>
    <label style="display:block;background:#2D3348;border:1px dashed #374151;border-radius:8px;padding:20px;text-align:center;cursor:pointer">
      <div style="font-size:1.5rem;margin-bottom:4px">📷</div>
      <div style="font-size:.75rem;color:#94A3B8">Tomar foto o seleccionar archivo</div>
      <input type="file" accept="image/*" capture="environment" style="display:none" onchange="mostrarFoto(this)">
    </label>
  </div>

  <!-- Botón confirmar -->
  <button id="btnConfirmar" onclick="confirmarEntrega(<?= $entrega['id'] ?>)"
          class="rep-btn-primary" style="width:100%;padding:14px;border-radius:10px;font-size:.875rem">
    ✅ Confirmar entrega
  </button>
</div>

<script>
// Signature pad
const canvas = document.getElementById('firmaCanvas');
const ctx = canvas.getContext('2d');
canvas.width = canvas.offsetWidth;
canvas.height = 160;
ctx.strokeStyle = '#F1F5F9';
ctx.lineWidth = 2.5;
ctx.lineCap = 'round';

let drawing = false, firmaSigned = false;

function getPos(e) {
  const rect = canvas.getBoundingClientRect();
  if (e.touches) return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
  return { x: e.clientX - rect.left, y: e.clientY - rect.top };
}

canvas.addEventListener('mousedown', e => { drawing = true; ctx.beginPath(); const p = getPos(e); ctx.moveTo(p.x, p.y); });
canvas.addEventListener('mousemove', e => { if (!drawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); firmaSigned = true; });
canvas.addEventListener('mouseup', () => drawing = false);
canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; ctx.beginPath(); const p = getPos(e); ctx.moveTo(p.x, p.y); }, { passive: false });
canvas.addEventListener('touchmove', e => { e.preventDefault(); if (!drawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); firmaSigned = true; }, { passive: false });
canvas.addEventListener('touchend', () => drawing = false);

function limpiarFirma() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  firmaSigned = false;
}

let fotoDataUrl = null;
function mostrarFoto(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    fotoDataUrl = e.target.result;
    document.getElementById('fotoImg').src = fotoDataUrl;
    document.getElementById('fotoPreview').style.display = 'block';
  };
  reader.readAsDataURL(input.files[0]);
}

function confirmarEntrega(detalleId) {
  const receptor = document.getElementById('receptorNombre').value.trim();
  if (!receptor) { alert('Ingresa el nombre de quien recibe'); return; }
  if (!firmaSigned) { alert('Se requiere la firma del receptor'); return; }

  const btn = document.getElementById('btnConfirmar');
  btn.disabled = true;
  btn.textContent = 'Guardando...';

  const firmaData = canvas.toDataURL('image/png');
  fetch('<?= BASE_URL ?>repartidor/completarEntrega', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      detalle_id: detalleId,
      receptor_nombre: receptor,
      firma_data: firmaData,
      foto_data: fotoDataUrl || ''
    })
  }).then(r => r.json()).then(d => {
    if (d.ok) {
      window.location = '<?= BASE_URL ?>repartidor/entregas';
    } else {
      btn.disabled = false;
      btn.textContent = '✅ Confirmar entrega';
      alert('Error al guardar. Intenta de nuevo.');
    }
  });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer_repartidor.php'; ?>
