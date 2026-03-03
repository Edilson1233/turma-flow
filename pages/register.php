<?php
// ============================================================
//  pages/register.php — Criar Conta + Criar ou Entrar numa Turma
//  Este é o fluxo de entrada mais importante do sistema.
// ============================================================

require_once __DIR__ . '/../includes/auth.php';

if (getCurrentUser()) {
    header('Location: dashboard.php');
    exit;
}

$erros    = [];
$valores  = []; // Mantém os valores dos campos após erro

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Recolhe e sanitiza os dados ---
    $nome       = trim($_POST['nome']       ?? '');
    $email      = trim($_POST['email']      ?? '');
    $password   = trim($_POST['password']   ?? '');
    $confirmar  = trim($_POST['confirmar']  ?? '');
    $acao_turma = $_POST['acao_turma']      ?? ''; // 'criar' ou 'entrar'
    $nome_turma = trim($_POST['nome_turma'] ?? '');
    $codigo     = strtoupper(trim($_POST['codigo'] ?? ''));

    $valores = compact('nome', 'email', 'acao_turma', 'nome_turma', 'codigo');

    // --- Validação ---
    if (empty($nome))          $erros[] = 'O nome é obrigatório.';
    if (empty($email))         $erros[] = 'O email é obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
    if (strlen($password) < 6) $erros[] = 'A password deve ter pelo menos 6 caracteres.';
    if ($password !== $confirmar) $erros[] = 'As passwords não coincidem.';
    if (!in_array($acao_turma, ['criar', 'entrar'])) $erros[] = 'Escolha uma opção de turma.';
    if ($acao_turma === 'criar' && empty($nome_turma)) $erros[] = 'O nome da turma é obrigatório.';
    if ($acao_turma === 'entrar' && empty($codigo))    $erros[] = 'O código de convite é obrigatório.';

    if (empty($erros)) {
        $db = getDB();

        // Verifica se o email já está registado
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erros[] = 'Este email já está registado. Tenta fazer login.';
        }
    }

    if (empty($erros)) {
        $db = getDB();

        // --- Tudo certo: começa a transação ---
        // Uma transação garante que ou TUDO é guardado, ou NADA (evita dados inconsistentes)
        $db->beginTransaction();

        try {
            // 1. Cria o utilizador
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (nome, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $email, $hash]);
            $user_id = $db->lastInsertId();

            if ($acao_turma === 'criar') {
                // 2a. Cria nova turma com código único
                $codigo_novo = gerarCodigoTurma();
                $stmt = $db->prepare("INSERT INTO turmas (nome, codigo) VALUES (?, ?)");
                $stmt->execute([$nome_turma, $codigo_novo]);
                $turma_id = $db->lastInsertId();

                // 3a. Liga o utilizador à turma como ADMIN
                $stmt = $db->prepare("INSERT INTO turma_users (turma_id, user_id, role) VALUES (?, ?, 'admin')");
                $stmt->execute([$turma_id, $user_id]);

                $db->commit();

                // Faz login automático
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user_id;
                $_SESSION['user_nome']  = $nome;
                $_SESSION['user_email'] = $email;

                setFlash('sucesso', "Turma criada! O código de convite é: <strong>{$codigo_novo}</strong> — partilha com os teus colegas.");
                header('Location: dashboard.php');
                exit;

            } else {
                // 2b. Procura a turma pelo código
                $stmt = $db->prepare("SELECT id, nome FROM turmas WHERE codigo = ?");
                $stmt->execute([$codigo]);
                $turma = $stmt->fetch();

                if (!$turma) {
                    $db->rollBack();
                    $erros[] = "Código de convite '{$codigo}' não encontrado. Verifica se está correto.";
                } else {
                    // 3b. Liga o utilizador à turma como STUDENT
                    $stmt = $db->prepare("INSERT INTO turma_users (turma_id, user_id, role) VALUES (?, ?, 'student')");
                    $stmt->execute([$turma['id'], $user_id]);

                    $db->commit();

                    // Faz login automático
                    session_regenerate_id(true);
                    $_SESSION['user_id']    = $user_id;
                    $_SESSION['user_nome']  = $nome;
                    $_SESSION['user_email'] = $email;

                    setFlash('sucesso', "Bem-vindo à turma <strong>{$turma['nome']}</strong>!");
                    header('Location: dashboard.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            $db->rollBack();
            $erros[] = 'Erro ao criar conta. Por favor tente novamente.';
            error_log("Erro no registo: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta — TurmaApp</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="body-auth">

<div class="auth-wrapper">
    <div class="auth-card auth-card-wide">

        <div class="auth-logo">
            <span class="auth-logo-icon">🎓</span>
            <h1>Criar Conta</h1>
            <p>Regista-te e organiza a tua turma</p>
        </div>

        <?php if (!empty($erros)): ?>
            <div class="flash flash-erro">
                <?php foreach ($erros as $e): ?>
                    <p>⚠️ <?= $e /* Pode conter HTML intencional em erros de código */ ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="auth-form" id="form-registo">

            <!-- === SECÇÃO 1: Dados Pessoais === -->
            <fieldset class="form-fieldset">
                <legend>👤 Os teus dados</legend>

                <div class="form-group">
                    <label for="nome">Nome completo</label>
                    <input type="text" id="nome" name="nome"
                           value="<?= htmlspecialchars($valores['nome'] ?? '') ?>"
                           placeholder="Edílson Paulo" required autofocus>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?= htmlspecialchars($valores['email'] ?? '') ?>"
                               placeholder="edilson123@gmail.com" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password <small>(mín. 6 caracteres)</small></label>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••" required minlength="6">
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmar">Confirmar password</label>
                    <input type="password" id="confirmar" name="confirmar"
                           placeholder="Repete a password" required>
                </div>
            </fieldset>

            <!-- === SECÇÃO 2: Turma === -->
            <fieldset class="form-fieldset">
                <legend>🏫 A tua turma</legend>

                <div class="opcao-turma-tabs">
                    <label class="opcao-tab <?= ($valores['acao_turma'] ?? '') !== 'entrar' ? 'opcao-tab-active' : '' ?>">
                        <input type="radio" name="acao_turma" value="criar"
                               <?= ($valores['acao_turma'] ?? 'criar') === 'criar' ? 'checked' : '' ?>>
                        <span class="opcao-tab-icon">✨</span>
                        <span><strong>Criar nova turma</strong><br><small>Serei o Chefe (Admin)</small></span>
                    </label>
                    <label class="opcao-tab <?= ($valores['acao_turma'] ?? '') === 'entrar' ? 'opcao-tab-active' : '' ?>">
                        <input type="radio" name="acao_turma" value="entrar"
                               <?= ($valores['acao_turma'] ?? '') === 'entrar' ? 'checked' : '' ?>>
                        <span class="opcao-tab-icon">🔗</span>
                        <span><strong>Entrar em turma</strong><br><small>Tenho um código de convite</small></span>
                    </label>
                </div>

                <!-- Campo: Nome da turma (visível quando "criar") -->
                <div class="form-group" id="campo-criar"
                     style="<?= ($valores['acao_turma'] ?? '') === 'entrar' ? 'display:none' : '' ?>">
                    <label for="nome_turma">Nome da turma</label>
                    <input type="text" id="nome_turma" name="nome_turma"
                           value="<?= htmlspecialchars($valores['nome_turma'] ?? '') ?>"
                           placeholder="Engenharia Informática — 2024/25">
                </div>

                <!-- Campo: Código de convite (visível quando "entrar") -->
                <div class="form-group" id="campo-entrar"
                     style="<?= ($valores['acao_turma'] ?? '') !== 'entrar' ? 'display:none' : '' ?>">
                    <label for="codigo">Código de convite</label>
                    <input type="text" id="codigo" name="codigo"
                           value="<?= htmlspecialchars($valores['codigo'] ?? '') ?>"
                           placeholder="#ENG24" maxlength="10"
                           style="text-transform:uppercase; font-family: monospace; font-size: 1.2rem; letter-spacing: 2px;">
                </div>

            </fieldset>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem">
                Criar Conta e Entrar →
            </button>
        </form>

        <div class="auth-switch">
            <p>Já tens conta?</p>
            <a href="login.php" class="btn btn-secondary btn-full">Fazer Login</a>
        </div>

    </div>
</div>

<script>
// Mostra/esconde os campos consoante a opção selecionada
document.querySelectorAll('input[name="acao_turma"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('campo-criar').style.display  = this.value === 'criar'  ? '' : 'none';
        document.getElementById('campo-entrar').style.display = this.value === 'entrar' ? '' : 'none';

        document.querySelectorAll('.opcao-tab').forEach(t => t.classList.remove('opcao-tab-active'));
        this.closest('.opcao-tab').classList.add('opcao-tab-active');
    });
});

// Código sempre em maiúsculas
const campoCode = document.getElementById('codigo');
if (campoCode) campoCode.addEventListener('input', e => e.target.value = e.target.value.toUpperCase());
</script>
</body>
</html>
