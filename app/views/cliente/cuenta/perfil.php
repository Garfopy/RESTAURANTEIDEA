<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
?>
<h1 style="font-size:1.1rem;font-weight:700;margin-bottom:20px">Mi cuenta</h1>

<?php if (!empty($flash)): ?>
<div class="toast <?= $flash['type'] ?>" style="margin-bottom:12px;position:relative;max-width:100%"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<!-- Avatar + nombre -->
<?php
  $cliNombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido_paterno'] ?? '') .
                            (!empty($usuario['apellido_materno']) ? ' ' . $usuario['apellido_materno'] : ''));
  $cliInicial = strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1, 'UTF-8'));
?>
<div style="display:flex;align-items:center;gap:14px;margin-bottom:20px">
  <div style="width:56px;height:56px;border-radius:50%;background:#C8102E;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:1.4rem;overflow:hidden">
    <?php if (!empty($usuario['avatar'])): ?>
    <img src="<?= BASE_URL . htmlspecialchars($usuario['avatar']) ?>" style="width:100%;height:100%;object-fit:cover" alt="">
    <?php else: ?>
    <?= $cliInicial ?>
    <?php endif; ?>
  </div>
  <div>
    <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($cliNombreCompleto) ?></div>
    <div style="font-size:.75rem;color:#6B7280"><?= htmlspecialchars($usuario['email']) ?></div>
    <div style="font-size:.75rem;color:#9CA3AF"><?= htmlspecialchars($empresa['razon_social'] ?? '') ?></div>
  </div>
</div>

<!-- Datos empresa -->
<?php if ($empresa): ?>
<div class="card" style="margin-bottom:14px">
  <div style="font-weight:700;font-size:.875rem;margin-bottom:10px">Mi empresa</div>
  <div style="font-size:.8rem;color:#374151;display:flex;flex-direction:column;gap:6px">
    <div><span style="color:#6B7280">Razón social:</span> <?= htmlspecialchars($empresa['razon_social']) ?></div>
    <div><span style="color:#6B7280">RFC:</span> <?= htmlspecialchars($empresa['rfc']) ?></div>
    <?php if ($empresa['email']): ?>
    <div><span style="color:#6B7280">Email:</span> <?= htmlspecialchars($empresa['email']) ?></div>
    <?php endif; ?>
    <?php if ($empresa['telefono']): ?>
    <div><span style="color:#6B7280">Teléfono:</span> <?= htmlspecialchars($empresa['telefono']) ?></div>
    <?php endif; ?>
    <?php if ($empresa['credito_activo']): ?>
    <div style="margin-top:6px;padding:8px;background:#EFF6FF;border-radius:6px">
      <span style="color:#1D4ED8;font-weight:600">💳 Crédito activo</span>
      <span style="color:#6B7280;font-size:.75rem"> · Límite: $<?= number_format($empresa['limite_credito'],0,'.', ',') ?> · Saldo: $<?= number_format($empresa['saldo_credito'],0,'.', ',') ?></span>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- Editar perfil -->
<div class="card" style="margin-bottom:14px">
  <div style="font-weight:700;font-size:.875rem;margin-bottom:14px">Editar perfil</div>
  <form method="POST" action="<?= BASE_URL ?>cuenta/guardarPerfil">
    <div style="display:flex;flex-direction:column;gap:12px">
      <div>
        <label class="form-label">Nombre(s)</label>
        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
      </div>
      <div style="display:flex;gap:10px">
        <div style="flex:1">
          <label class="form-label">Apellido paterno</label>
          <input type="text" name="apellido_paterno" class="form-control" value="<?= htmlspecialchars($usuario['apellido_paterno'] ?? '') ?>" required>
        </div>
        <div style="flex:1">
          <label class="form-label">Apellido materno</label>
          <input type="text" name="apellido_materno" class="form-control" value="<?= htmlspecialchars($usuario['apellido_materno'] ?? '') ?>">
        </div>
      </div>
      <div>
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" required>
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-block" style="margin-top:14px">Guardar cambios</button>
  </form>
</div>

<!-- Cambiar contraseña -->
<div class="card" style="margin-bottom:20px">
  <div style="font-weight:700;font-size:.875rem;margin-bottom:14px">Cambiar contraseña</div>
  <form method="POST" action="<?= BASE_URL ?>cuenta/cambiarPassword">
    <div style="display:flex;flex-direction:column;gap:12px">
      <div>
        <label class="form-label">Contraseña actual</label>
        <input type="password" name="password_actual" class="form-control" required>
      </div>
      <div>
        <label class="form-label">Nueva contraseña</label>
        <input type="password" name="password_nueva" class="form-control" minlength="6" required>
      </div>
      <div>
        <label class="form-label">Confirmar nueva contraseña</label>
        <input type="password" name="password_confirm" class="form-control" minlength="6" required>
      </div>
    </div>
    <button type="submit" class="btn btn-secondary btn-block" style="margin-top:14px">Cambiar contraseña</button>
  </form>
</div>

<a href="<?= BASE_URL ?>auth/logout" class="btn btn-secondary btn-block">Cerrar sesión</a>

<!-- Bottom nav mobile -->
<nav class="bottom-nav hide-desktop">
  <a href="<?= BASE_URL ?>carrito/inicio" class="bottom-nav-item">🏠 <span>Inicio</span></a>
  <a href="<?= BASE_URL ?>producto/catalogo" class="bottom-nav-item">📦 <span>Catálogo</span></a>
  <a href="<?= BASE_URL ?>pedido/index" class="bottom-nav-item">📋 <span>Pedidos</span></a>
  <a href="<?= BASE_URL ?>carrito/index" class="bottom-nav-item">🛒 <span>Carrito</span></a>
  <a href="<?= BASE_URL ?>cuenta/perfil" class="bottom-nav-item active">👤 <span>Cuenta</span></a>
</nav>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
