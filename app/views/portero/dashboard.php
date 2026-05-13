<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portero — <?= htmlspecialchars($restaurante['nombre'] ?? '') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { background: #111827; color: #F9FAFB; font-family: system-ui, sans-serif; min-height: 100vh; }
    .topbar { background: #1F2937; border-bottom: 1px solid #374151; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; }
    .result-box { border-radius: 16px; padding: 28px; text-align: center; font-size: 1.4rem; font-weight: 700; margin-top: 16px; display: none; }
    .result-ok  { background: #064E3B; border: 2px solid #10B981; color: #6EE7B7; }
    .result-err { background: #7F1D1D; border: 2px solid #EF4444; color: #FCA5A5; }
  </style>
</head>
<body>
<div class="topbar">
  <div style="font-weight:700">🚪 Portero — <?= htmlspecialchars($restaurante['nombre'] ?? '') ?></div>
  <a href="<?= BASE_URL ?>auth/login" style="color:#9CA3AF;font-size:.8rem">Salir</a>
</div>

<div style="padding:28px;max-width:480px;margin:0 auto">

  <!-- Scanner manual -->
  <div style="background:#1F2937;border-radius:16px;padding:24px;margin-bottom:20px">
    <div style="font-weight:600;font-size:1rem;margin-bottom:14px">Verificar código de visita</div>
    <form id="formVerificar" style="display:flex;gap:10px">
      <input type="text" id="qrInput" placeholder="Escanear o ingresar código QR..."
        style="flex:1;padding:12px 14px;border:1px solid #374151;border-radius:10px;background:#111827;color:#F9FAFB;font-size:1rem"
        autofocus>
      <button type="submit"
        style="padding:10px 18px;background:#C8102E;color:#fff;border:none;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer">
        Verificar
      </button>
    </form>
    <div id="resultBox" class="result-box"></div>
  </div>

  <!-- Cámara QR (opcional) -->
  <div style="background:#1F2937;border-radius:16px;padding:24px;margin-bottom:20px">
    <div style="font-weight:600;margin-bottom:12px">Escanear QR con cámara</div>
    <video id="video" style="width:100%;border-radius:10px;display:none;max-height:280px;object-fit:cover"></video>
    <canvas id="canvas" style="display:none"></canvas>
    <button id="btnCam"
      style="width:100%;padding:10px;background:#374151;color:#F9FAFB;border:none;border-radius:10px;cursor:pointer;font-size:.9rem">
      📷 Activar cámara
    </button>
  </div>

  <!-- Registrar entrada -->
  <div style="background:#1F2937;border-radius:16px;padding:24px">
    <div style="font-weight:600;margin-bottom:14px">Registrar entrada</div>
    <form id="formEntrada">
      <div style="margin-bottom:10px">
        <input type="text" name="nombre" placeholder="Nombre del comensal (opcional)"
          style="width:100%;padding:10px 12px;border:1px solid #374151;border-radius:8px;background:#111827;color:#F9FAFB;font-size:.9rem">
      </div>
      <div style="margin-bottom:10px">
        <input type="tel" name="telefono" placeholder="Teléfono (opcional)"
          style="width:100%;padding:10px 12px;border:1px solid #374151;border-radius:8px;background:#111827;color:#F9FAFB;font-size:.9rem">
      </div>
      <button type="submit"
        style="width:100%;padding:10px;background:#10B981;color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer">
        Registrar entrada ▶
      </button>
    </form>
    <div id="entradaResult" style="margin-top:10px;font-size:.875rem;color:#6EE7B7;display:none"></div>
  </div>
</div>

<script src="https://unpkg.com/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
const baseUrl = '<?= BASE_URL ?>';
const video   = document.getElementById('video');
const canvas  = document.getElementById('canvas');
const ctx     = canvas.getContext('2d');

document.getElementById('formVerificar').addEventListener('submit', async e => {
  e.preventDefault();
  const qr  = document.getElementById('qrInput').value.trim();
  if (!qr) return;
  await verificar(qr);
});

async function verificar(qr) {
  const fd = new FormData();
  fd.append('qr_code', qr);
  const res  = await fetch(baseUrl + 'rest-portero/verificar', { method: 'POST', body: fd });
  const data = await res.json();
  const box  = document.getElementById('resultBox');
  box.textContent = data.mensaje || (data.ok ? 'OK' : 'Error');
  box.className   = 'result-box ' + (data.pagado ? 'result-ok' : 'result-err');
  box.style.display = 'block';
  document.getElementById('qrInput').value = '';
}

document.getElementById('formEntrada').addEventListener('submit', async e => {
  e.preventDefault();
  const fd  = new FormData(e.target);
  const res = await fetch(baseUrl + 'rest-portero/registrarEntrada', { method: 'POST', body: fd });
  const data = await res.json();
  const el  = document.getElementById('entradaResult');
  el.textContent = data.ok ? '✅ Entrada registrada. QR: ' + (data.qr_code || '') : '❌ Error';
  el.style.display = 'block';
  e.target.reset();
});

// Cámara QR
let scanning = false;
document.getElementById('btnCam').addEventListener('click', () => {
  if (scanning) { scanning = false; video.style.display='none'; document.getElementById('btnCam').textContent='📷 Activar cámara'; return; }
  navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(stream => {
    video.srcObject = stream;
    video.style.display = 'block';
    video.play();
    scanning = true;
    document.getElementById('btnCam').textContent = '⏹ Detener cámara';
    requestAnimationFrame(scan);
  }).catch(() => alert('No se pudo acceder a la cámara.'));
});

function scan() {
  if (!scanning) return;
  if (video.readyState === video.HAVE_ENOUGH_DATA) {
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const img  = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(img.data, img.width, img.height);
    if (code) { verificar(code.data); }
  }
  if (scanning) requestAnimationFrame(scan);
}
</script>
</body>
</html>
