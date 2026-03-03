<?php
// ============================================================
//  pages/dashboard.php — Painel Principal do Utilizador
//  Lista todas as turmas às quais o utilizador pertence.
//  Também permite entrar em mais turmas via código.
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$db   = getDB();

// ============================================================
//  AÇÃO: Entrar em nova turma via código (formulário no dashboard)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    if ($_POST['acao'] === 'entrar_turma') {
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));

        if (empty($codigo)) {
            setFlash('erro', 'Introduz um código de convite.');
        } else {
            // Procura a turma
            $stmt = $db->prepare("SELECT id, nome FROM turmas WHERE codigo = ?");
            $stmt->execute([$codigo]);
            $turmaEncontrada = $stmt->fetch();

            if (!$turmaEncontrada) {
                setFlash('erro', "Código '{$codigo}' não encontrado. Verifica se está correto.");
            } else {
                // Verifica se o utilizador já pertence a esta turma
                $stmt = $db->prepare("SELECT id FROM turma_users WHERE turma_id = ? AND user_id = ?");
                $stmt->execute([$turmaEncontrada['id'], $user['id']]);

                if ($stmt->fetch()) {
                    setFlash('info', "Já pertences à turma <strong>{$turmaEncontrada['nome']}</strong>.");
                } else {
                    $stmt = $db->prepare("INSERT INTO turma_users (turma_id, user_id, role) VALUES (?, ?, 'student')");
                    $stmt->execute([$turmaEncontrada['id'], $user['id']]);
                    setFlash('sucesso', "Entraste na turma <strong>{$turmaEncontrada['nome']}</strong>!");
                }
            }
        }
        header('Location: dashboard.php');
        exit;
    }
}

// ============================================================
//  BUSCAR TURMAS DO UTILIZADOR
//  JOIN entre turmas e turma_users para pegar nome + role
// ============================================================
$stmt = $db->prepare("
    SELECT
        t.id,
        t.nome,
        t.codigo,
        t.criado_em,
        tu.role,
        -- Conta quantas tarefas existem com entrega futura
        (SELECT COUNT(*) FROM tarefas ta WHERE ta.turma_id = t.id AND ta.data_entrega >= NOW()) AS tarefas_pendentes,
        -- Conta membros da turma
        (SELECT COUNT(*) FROM turma_users tu2 WHERE tu2.turma_id = t.id) AS total_membros
    FROM turmas t
    JOIN turma_users tu ON tu.turma_id = t.id
    WHERE tu.user_id = ?
    ORDER BY tu.role ASC, t.nome ASC
");
$stmt->execute([$user['id']]);
$minhasTurmas = $stmt->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — TurmaFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="app-header">
    <div class="header-info">
        <span class="auth-logo-icon" style="font-size:1.5rem">🎓</span>
        <div>
            <h1>TurmaApp</h1>
            <span class="codigo-turma">Olá, <?= htmlspecialchars($user['nome']) ?>!</span>
        </div>
    </div>
    <nav>
        <a href="/pages/logout.php" class="nav-logout">Sair</a>
    </nav>
</header>

<?php if ($flash): ?>
    <div class="flash flash-<?= htmlspecialchars($flash['tipo']) ?>">
        <?= $flash['msg'] /* pode conter HTML intencional */ ?>
    </div>
<?php endif; ?>

<main class="container">

    <!-- === LISTA DE TURMAS === -->
    <section class="dashboard-section">
        <div class="section-header">
            <h2>📚 As Minhas Turmas</h2>
            <a href="nova_turma.php" class="btn btn-primary">+ Criar Nova Turma</a>
        </div>

        <?php if (empty($minhasTurmas)): ?>
            <div class="empty-state-card">
                <p style="font-size:3rem">🏫</p>
                <h3>Ainda não pertences a nenhuma turma</h3>
                <p>Cria uma nova turma ou entra numa existente com um código de convite.</p>
                <div style="display:flex; gap:1rem; justify-content:center; margin-top:1rem; flex-wrap:wrap">
                    <a href="register.php?acao=criar" class="btn btn-primary">✨ Criar Turma</a>
                </div>
            </div>
        <?php else: ?>
            <div class="turmas-grid">
                <?php foreach ($minhasTurmas as $t): ?>
                    <div class="turma-card <?= $t['role'] === 'admin' ? 'turma-card-admin' : '' ?>">

                        <div class="turma-card-header">
                            <?php if ($t['role'] === 'admin'): ?>
                                <span class="badge-role badge-admin">👑 Admin</span>
                            <?php else: ?>
                                <span class="badge-role badge-student">🎓 Aluno</span>
                            <?php endif; ?>
                            <span class="turma-codigo"><?= htmlspecialchars($t['codigo']) ?></span>
                        </div>

                        <h3 class="turma-nome"><?= htmlspecialchars($t['nome']) ?></h3>

                        <div class="turma-stats">
                            <span title="Membros">👥 <?= $t['total_membros'] ?> membro<?= $t['total_membros'] != 1 ? 's' : '' ?></span>
                            <span title="Tarefas pendentes">
                                <?php if ($t['tarefas_pendentes'] > 0): ?>
                                    <strong style="color:var(--cor-urgente)">📋 <?= $t['tarefas_pendentes'] ?> tarefa<?= $t['tarefas_pendentes'] != 1 ? 's' : '' ?></strong>
                                <?php else: ?>
                                    📋 Sem tarefas
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="turma-card-acoes">
                            <a href="horario.php?turma_id=<?= $t['id'] ?>"   class="btn btn-sm btn-secondary">📅 Horário</a>
                            <a href="tarefas.php?turma_id=<?= $t['id'] ?>"   class="btn btn-sm btn-secondary">📋 Tarefas</a>
                            <a href="materiais.php?turma_id=<?= $t['id'] ?>" class="btn btn-sm btn-secondary">📁 Materiais</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- === ENTRAR EM TURMA EXISTENTE === -->
    <section class="dashboard-section">
        <h2>🔗 Entrar em Turma com Código</h2>
        <p style="color:var(--cor-texto-suave); margin-bottom:1rem">
            O teu colega tem um código como <code>#ENG24</code>? Cola-o aqui.
        </p>
        <form method="POST" action="dashboard.php" class="form-inline-codigo">
            <input type="hidden" name="acao" value="entrar_turma">
            <input type="text" name="codigo" placeholder="#ENG24"
                   maxlength="10" required
                   style="text-transform:uppercase; font-family:monospace; font-size:1.1rem; letter-spacing:2px; width:160px;">
            <button type="submit" class="btn btn-primary">Entrar →</button>
        </form>
    </section>

</main>

<footer class="app-footer">
    <p>TurmaFlow - Todos os direitos reservados &copy; <?= date('Y') ?></p>
</footer>

<script src="../assets/js/app.js"></script>
</body>
</html>
