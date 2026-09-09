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
//  FUNCOES DE CSRF
// ============================================================
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================
//  FUNCOES DE RECUPERACAO DE PASSWORD
// ============================================================
function ensurePasswordResetsTable(): void {
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            INDEX idx_password_resets_user_created (user_id, created_at),
            INDEX idx_password_resets_token_active (token_hash, used_at, expires_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
}

function findUserByEmail(string $email): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, nome, email, password FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function canCreatePasswordReset(int $userId): bool {
    ensurePasswordResetsTable();

    $db = getDB();
    $stmt = $db->prepare("
        SELECT created_at
        FROM password_resets
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $lastCreatedAt = $stmt->fetchColumn();

    if (!$lastCreatedAt) {
        return true;
    }

    return strtotime((string) $lastCreatedAt) <= time() - 60;
}

function createPasswordResetToken(int $userId): string {
    ensurePasswordResetsTable();

    $db = getDB();
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 30 * 60);
    $ipAddress = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            UPDATE password_resets
            SET used_at = NOW()
            WHERE user_id = ? AND used_at IS NULL
        ");
        $stmt->execute([$userId]);

        $stmt = $db->prepare("
            INSERT INTO password_resets (user_id, token_hash, expires_at, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $tokenHash, $expiresAt, $ipAddress, $userAgent]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    return $token;
}

function getPasswordResetByToken(string $token): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
        return null;
    }

    ensurePasswordResetsTable();

    $db = getDB();
    $stmt = $db->prepare("
        SELECT pr.id, pr.user_id, pr.expires_at, u.nome, u.email, u.password
        FROM password_resets pr
        JOIN users u ON u.id = pr.user_id
        WHERE pr.token_hash = ?
          AND pr.used_at IS NULL
          AND pr.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([hash('sha256', $token)]);
    $reset = $stmt->fetch();

    return $reset ?: null;
}

function markPasswordResetUsed(int $resetId): void {
    $db = getDB();
    $stmt = $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
    $stmt->execute([$resetId]);
}

function updateUserPasswordFromReset(int $userId, string $password, int $resetId): void {
    $db = getDB();
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);

        markPasswordResetUsed($resetId);

        $stmt = $db->prepare("
            UPDATE password_resets
            SET used_at = NOW()
            WHERE user_id = ? AND used_at IS NULL
        ");
        $stmt->execute([$userId]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function sendPasswordResetEmail(string $email, string $nome, string $token): bool {
    $resetUrl = BASE_URL . '/pages/recuperar_senha.php?token=' . urlencode($token);
    $subject = 'Recuperar password - TurmaFlow';
    $message = "Olá {$nome},\n\n";
    $message .= "Recebemos um pedido para alterar a password da tua conta TurmaFlow.\n";
    $message .= "Usa este link nos próximos 30 minutos:\n{$resetUrl}\n\n";
    $message .= "Se não foste tu, podes ignorar este email.\n";
    $headers = [
        'From: TurmaFlow <no-reply@turmaflow.local>',
        'Reply-To: no-reply@turmaflow.local',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion(),
    ];

    return @mail($email, $subject, $message, implode("\r\n", $headers));
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
