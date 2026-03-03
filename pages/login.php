<?php
// ============================================================
//  pages/login.php — Página de Login
// ============================================================

// 1. MODO DETETIVE ON (Para ver erros em vez de tela branca)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. CORREÇÃO CRÍTICA DE CAMINHO
// O login está em 'pages/', o auth está em 'includes/'.
// O '/../' faz o código subir um nível para encontrar a pasta certa.
require_once __DIR__ . '/../includes/auth.php';

// Se já estiver logado, vai direto para o dashboard
if (getCurrentUser()) {
    header('Location: dashboard.php');
    exit;
}

$erros = [];
$emailValor = ''; // Para manter o email no campo após erro

// Processa o formulário quando submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email))    $erros[] = 'O email é obrigatório.';
    if (empty($password)) $erros[] = 'A password é obrigatória.';

    if (empty($erros)) {
        // Tenta fazer login
        if (loginUser($email, $password)) {
            // Sucesso!
            // Verifica se a sessão user_nome existe antes de usar
            $nome = $_SESSION['user_nome'] ?? 'Estudante';
            setFlash('sucesso', 'Bem-vindo de volta, ' . htmlspecialchars($nome) . '!');
            
            // Redireciona para o dashboard (que está na mesma pasta 'pages')
            header('Location: dashboard.php');
            exit;
        } else {
            $erros[] = 'Email ou password incorretos.';
            $emailValor = $email;
        }
    } else {
        $emailValor = $email;
    }
}

// Pega mensagens flash (se existirem)
$flash = function_exists('getFlash') ? getFlash() : null;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login — TurmaFlow</title>
    
    <link rel="stylesheet" href="../assets/css/style.css">
    
</head>
<body class="body-auth">

<div class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-logo">
            <span class="auth-logo-icon">🎓</span>
            <h1 style="font-size: 1.5rem; margin: 10px 0;">TurmaFlow</h1>
            <p style="font-size: 0.9rem;">Organiza a tua turma universitária</p>
        </div>

        <?php if ($flash): ?>
            <div class="flash flash-<?= $flash['tipo'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
        <?php endif; ?>

        <?php if (!empty($erros)): ?>
            <div class="flash flash-erro">
                <?php foreach ($erros as $e): ?>
                    <p>⚠️ <?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($emailValor) ?>"
                       placeholder="edilson@teste.com"
                       required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="A tua password"
                       required>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Entrar →
            </button>
        </form>

        <div class="auth-switch">
            <p style="margin-top: 20px; font-size: 0.9rem;">Ainda não tens conta?</p>
            <a href="register.php" class="btn btn-secondary btn-full">
                Criar conta grátis
            </a>
        </div>

    </div>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>