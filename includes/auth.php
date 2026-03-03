<?php
// ============================================================
//  includes/auth.php — Funções de autenticação
// ============================================================

// 1. Inclui a conexão (que tem a constante BASE_URL)
// Como auth.php e db.php estão na mesma pasta, usamos __DIR__
require_once __DIR__ . '/db.php';

// 2. Inicia a sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
//  FUNÇÃO: requireLogin()
//  CORREÇÃO: Agora usa BASE_URL para não dar erro 404
// ============================================================
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        // Redireciona usando o endereço completo do site
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit; 
    }
}

// ============================================================
//  FUNÇÃO: getCurrentUser()
// ============================================================
function getCurrentUser(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    // Verifica se as chaves existem para evitar "Undefined index"
    return [
        'id'    => $_SESSION['user_id'] ?? 0,
        'nome'  => $_SESSION['user_nome'] ?? 'Utilizador',
        'email' => $_SESSION['user_email'] ?? '',
    ];
}

// ============================================================
//  FUNÇÃO: getUserRoleInTurma()
// ============================================================
function getUserRoleInTurma(int $turma_id): ?string {
    $user = getCurrentUser();
    if (!$user) return null;

    $db = getDB();
    $stmt = $db->prepare("
        SELECT role
        FROM turma_users
        WHERE turma_id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$turma_id, $user['id']]);
    $row = $stmt->fetch();

    return $row ? $row['role'] : null;
}

// ============================================================
//  FUNÇÃO: isAdminInTurma()
// ============================================================
function isAdminInTurma(int $turma_id): bool {
    return getUserRoleInTurma($turma_id) === 'admin';
}

// ============================================================
//  FUNÇÃO: loginUser()
// ============================================================
function loginUser(string $email, string $password): bool {
    $db = getDB();

    $stmt = $db->prepare("SELECT id, nome, email, password FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_nome']  = $user['nome'];
        $_SESSION['user_email'] = $user['email'];

        return true;
    }

    return false;
}

// ============================================================
//  FUNÇÃO: logoutUser()
//  CORREÇÃO: Agora usa BASE_URL
// ============================================================
function logoutUser(): void {
    session_unset();
    session_destroy();
    // Redireciona usando o endereço completo
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

// ============================================================
//  FUNÇÕES DE MENSAGENS FLASH
// ============================================================
function setFlash(string $tipo, string $mensagem): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'msg' => $mensagem];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================================
//  FUNÇÃO: gerarCodigoTurma()
// ============================================================
function gerarCodigoTurma(): string {
    $db = getDB();
    do {
        $codigo = '#' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $stmt = $db->prepare("SELECT id FROM turmas WHERE codigo = ?");
        $stmt->execute([$codigo]);
    } while ($stmt->fetch());

    return $codigo;
}
?>