<?php
// ============================================================
//  index.php — Ponto de entrada da aplicação
//  Redireciona para o dashboard se logado, senão para o login.
// ============================================================

require_once __DIR__ . '/includes/auth.php';

if (getCurrentUser()) {
    header('Location: pages/dashboard.php');
} else {
    header('Location: pages/login.php');
}
exit;
