// repartidor.js — Delivery app utilities (signature pad, photo capture)

const SignaturePad = {
  canvas: null,
  ctx: null,
  drawing: false,
  signed: false,

  init(canvasId) {
    this.canvas = document.getElementById(canvasId);
    if (!this.canvas) return;
    this.ctx = this.canvas.getContext('2d');
    this.canvas.width  = this.canvas.offsetWidth;
    this.canvas.height = this.canvas.offsetHeight || 160;
    this.ctx.strokeStyle = '#F1F5F9';
    this.ctx.lineWidth   = 2.5;
    this.ctx.lineCap     = 'round';

    const getPos = e => {
      const r = this.canvas.getBoundingClientRect();
      if (e.touches) return { x: e.touches[0].clientX - r.left, y: e.touches[0].clientY - r.top };
      return { x: e.clientX - r.left, y: e.clientY - r.top };
    };

    this.canvas.addEventListener('mousedown', e => { this.drawing = true; this.ctx.beginPath(); const p = getPos(e); this.ctx.moveTo(p.x, p.y); });
    this.canvas.addEventListener('mousemove', e => { if (!this.drawing) return; const p = getPos(e); this.ctx.lineTo(p.x, p.y); this.ctx.stroke(); this.signed = true; });
    this.canvas.addEventListener('mouseup',   () => this.drawing = false);
    this.canvas.addEventListener('touchstart', e => { e.preventDefault(); this.drawing = true; this.ctx.beginPath(); const p = getPos(e); this.ctx.moveTo(p.x, p.y); }, { passive: false });
    this.canvas.addEventListener('touchmove',  e => { e.preventDefault(); if (!this.drawing) return; const p = getPos(e); this.ctx.lineTo(p.x, p.y); this.ctx.stroke(); this.signed = true; }, { passive: false });
    this.canvas.addEventListener('touchend',   () => this.drawing = false);
  },

  clear() {
    if (!this.ctx) return;
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    this.signed = false;
  },

  getDataUrl() {
    return this.canvas ? this.canvas.toDataURL('image/png') : null;
  },

  isEmpty() { return !this.signed; }
};

// Photo preview helper
function initPhotoCapture(inputId, previewContainerId, previewImgId) {
  const input = document.getElementById(inputId);
  if (!input) return;

  let dataUrl = null;

  input.addEventListener('change', () => {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
      dataUrl = e.target.result;
      const container = document.getElementById(previewContainerId);
      const img       = document.getElementById(previewImgId);
      if (img)       img.src = dataUrl;
      if (container) container.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  });

  return { getDataUrl: () => dataUrl };
}
