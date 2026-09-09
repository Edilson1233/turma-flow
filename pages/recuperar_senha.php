<?php
require_once __DIR__ . '/../includes/auth.php';

if (getCurrentUser()) {
    header('Location: dashboard.php');
    exit;
}

$erros = [];
$pedidoEnviado = false;
$emailValor = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$reset = $token !== '' ? getPasswordResetByToken($token) : null;
$devResetUrl = null;
$linkInvalido = $token !== '' && !$reset && $_SERVER['REQUEST_METHOD'] !== 'POST';

function isLocalResetRequest(): bool {
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $isLocalHost = (bool) preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host);

    return $isLocalHost && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada. Atualiza a página e tenta novamente.';
    } elseif ($acao === 'pedir_link') {
        $emailValor = strtolower(trim($_POST['email'] ?? ''));

        if (!filter_var($emailValor, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Introduz um email válido.';
        }

        if (empty($erros)) {
            try {
                $user = findUserByEmail($emailValor);

                if ($user && canCreatePasswordReset((int) $user['id'])) {
                    $novoToken = createPasswordResetToken((int) $user['id']);
                    $emailEnviado = sendPasswordResetEmail($user['email'], $user['nome'], $novoToken);

                    if (!$emailEnviado) {
                        error_log('Falha ao enviar email de recuperação para user_id=' . $user['id']);
                    }

                    if (defined('IS_DEV_MODE') && IS_DEV_MODE && isLocalResetRequest()) {
                        $devResetUrl = BASE_URL . '/pages/recuperar_senha.php?token=' . urlencode($novoToken);
                    }
                }

                $pedidoEnviado = true;
            } catch (Exception $e) {
                error_log('Erro ao criar recuperação de password: ' . $e->getMessage());
                $pedidoEnviado = true;
            }
        }
    } elseif ($acao === 'alterar_password') {
        $token = trim($_POST['token'] ?? '');
        $reset = getPasswordResetByToken($token);
        $password = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if (!$reset) {
            $erros[] = 'Este link é inválido ou expirou. Pede um novo link de recuperação.';
        } elseif (strlen($password) < 8) {
            $erros[] = 'A nova password deve ter pelo menos 8 caracteres.';
        } elseif ($password !== $confirmar) {
            $erros[] = 'As passwords não coincidem.';
        } elseif (password_verify($password, $reset['password'])) {
            $erros[] = 'Escolhe uma password diferente da atual.';
        }

        if (empty($erros) && $reset) {
            try {
                updateUserPasswordFromReset((int) $reset['user_id'], $password, (int) $reset['id']);
                session_regenerate_id(true);
                setFlash('sucesso', 'Password alterada com sucesso. Já podes fazer login.');

                header('Location: login.php');
                exit;
            } catch (Exception $e) {
                error_log('Erro ao alterar password por recuperação: ' . $e->getMessage());
                $erros[] = 'Não foi possível alterar a password agora. Tenta novamente.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar password — TurmaFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="body-auth">

<div class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-logo">
            <span class="auth-logo-icon">🎓</span>
            <h1>Recuperar password</h1>
            <p>Protege a tua conta com um link temporário</p>
        </div>

        <?php if ($linkInvalido): ?>
            <div class="flash flash-erro">
                <p>Este link é inválido ou expirou. Pede um novo link de recuperação.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($erros)): ?>
            <div class="flash flash-erro">
                <?php foreach ($erros as $erro): ?>
                    <p>⚠️ <?= htmlspecialchars($erro) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($pedidoEnviado): ?>
            <div class="flash flash-sucesso">
                <p>Se esse email existir, enviaremos um link para alterar a password. O link expira em 30 minutos.</p>
            </div>
        <?php endif; ?>

        <?php if ($devResetUrl): ?>
            <div class="flash flash-info">
                <p><strong>Modo desenvolvimento:</strong> o envio de email pode não estar configurado neste XAMPP.</p>
                <p><a href="<?= htmlspecialchars($devResetUrl, ENT_QUOTES) ?>">Abrir link de recuperação</a></p>
            </div>
        <?php endif; ?>

        <?php if ($reset): ?>
            <form method="POST" action="recuperar_senha.php" class="auth-form">
                <input type="hidden" name="acao" value="alterar_password">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">

                <div class="form-group">
                    <label for="password">Nova password</label>
                    <input type="password" id="password" name="password"
                           placeholder="Mínimo de 8 caracteres"
                           minlength="8" required autofocus>
                </div>

                <div class="form-group">
                    <label for="confirmar">Confirmar nova password</label>
                    <input type="password" id="confirmar" name="confirmar"
                           placeholder="Repete a nova password"
                           minlength="8" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    Alterar password
                </button>
            </form>
        <?php else: ?>
            <form method="POST" action="recuperar_senha.php" class="auth-form">
                <input type="hidden" name="acao" value="pedir_link">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES) ?>">

                <div class="form-group">
                    <label for="email">Email da conta</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($emailValor, ENT_QUOTES) ?>"
                           placeholder="teu@email.com"
                           required autofocus>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    Enviar link seguro
                </button>
            </form>
        <?php endif; ?>

        <div class="auth-switch">
            <a href="login.php" class="btn btn-secondary btn-full">Voltar ao login</a>
        </div>

    </div>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>
