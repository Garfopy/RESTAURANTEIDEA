<?php
include ROOT_PATH . '/app/views/components/header_repartidor.php';
$flash = $this->getFlash() ?? null;
?>
<div class="rep-page">
  <div style="font-weight:700;font-size:1.1rem;margin-bottom:16px;padding-top:12px">Mi perfil</div>

  <?php if (!empty($flash)): ?>
  <div style="background:#064E3B;border-radius:8px;padding:10px 14px;font-size:.8rem;color:#10B981;margin-bottom:12px"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <!-- Avatar -->
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px">
    <div style="width:56px;height:56px;border-radius:50%;background:#C8102E;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:1.4rem">
      <?= strtoupper(substr($usuario['nombre'],0,1)) ?>
    </div>
    <div>
      <div style="font-weight:700"><?= htmlspecialchars($usuario['nombre']) ?></div>
      <div style="font-size:.75rem;color:#94A3B8"><?= htmlspecialchars($usuario['email']) ?></div>
      <?php if (!empty($chofer['placa'])): ?>
      <div style="font-size:.75rem;color:#64748B">🚛 <?= htmlspecialchars($chofer['marca'] . ' ' . $chofer['modelo'] . ' · ' . $chofer['placa']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Editar perfil -->
  <div class="rep-card" style="margin-bottom:14px">
    <div style="font-weight:700;font-size:.875rem;margin-bottom:12px">Editar datos</div>
    <form method="POST" action="<?= BASE_URL ?>repartidor/perfil">
      <div style="display:flex;flex-direction:column;gap:10px">
        <div>
          <label style="font-size:.75rem;color:#94A3B8;display:block;margin-bottom:4px">Nombre</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>"
                 style="width:100%;background:#2D3348;border:1px solid #374151;color:#F1F5F9;padding:10px;border-radius:8px;font-size:.875rem;box-sizing:border-box" required>
        </div>
        <div>
          <label style="font-size:.75rem;color:#94A3B8;display:block;margin-bottom:4px">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>"
                 style="width:100%;background:#2D3348;border:1px solid #374151;color:#F1F5F9;padding:10px;border-radius:8px;font-size:.875rem;box-sizing:border-box" required>
        </div>
      </div>
      <button type="submit" class="rep-btn-primary" style="width:100%;padding:12px;border-radius:8px;margin-top:12px;font-size:.875rem">
        Guardar cambios
      </button>
    </form>
  </div>

  <a href="<?= BASE_URL ?>auth/logout" style="display:block;background:#2D3348;color:#EF4444;text-align:center;padding:12px;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
    Cerrar sesión
  </a>
</div>

<nav class="rep-bottom-nav">
  <a href="<?= BASE_URL ?>repartidor/inicio" class="rep-nav-item">🏠<span>Inicio</span></a>
  <a href="<?= BASE_URL ?>repartidor/entregas" class="rep-nav-item">📦<span>Entregas</span></a>
  <a href="<?= BASE_URL ?>repartidor/mapa" class="rep-nav-item">🗺️<span>Mapa</span></a>
  <a href="<?= BASE_URL ?>repartidor/historial" class="rep-nav-item">📋<span>Historial</span></a>
  <a href="<?= BASE_URL ?>repartidor/perfil" class="rep-nav-item active">👤<span>Perfil</span></a>
</nav>

<?php include ROOT_PATH . '/app/views/components/footer_repartidor.php'; ?>
