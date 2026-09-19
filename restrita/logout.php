<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
$u = usuarioAtual();
if ($u) {
    registrarAuditoria((int)$u['id'], 'LOGOUT', 'autenticacao', null);
}
logoutLocal();
header('Location: login.php');
exit;
