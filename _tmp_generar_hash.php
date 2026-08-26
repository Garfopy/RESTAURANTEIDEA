<?php
// TEMPORAL — sube solo este archivo a cafeuteq/, ábrelo en el navegador, copia el hash
// y BÓRRALO del servidor inmediatamente después de usarlo (no debe quedar público).
$password = $_GET['pw'] ?? 'Admin1234!';
echo '<pre>';
echo "Password: " . htmlspecialchars($password) . "\n";
echo "Hash bcrypt: " . password_hash($password, PASSWORD_BCRYPT) . "\n";
echo '</pre>';
echo '<p style="color:red;font-weight:bold">Borra este archivo del servidor ahora que ya tienes el hash.</p>';
