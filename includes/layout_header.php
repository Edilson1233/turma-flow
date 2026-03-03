<?php
// ============================================================
//  includes/layout_header.php
//  Cabeçalho HTML partilhado por todas as páginas de turma.
//  Variáveis esperadas antes de incluir:
//    $pageTitulo  — título da página (ex: "Horário")
//    $turma       — array com 'nome' e 'codigo'
//    $turma_id    — int
//    $isAdmin     — bool
//    $activeNav   — string: 'horario' | 'tarefas' | 'materiais'
// ============================================================
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitulo ?? 'TurmaApp') ?> — TurmaApp</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="app-header">
    <div class="header-info">
        <a href="dashboard.php" class="header-back" title="Voltar às turmas">←</a>
        <div>
            <h1><?= htmlspecialchars($turma['nome'] ?? '') ?></h1>
            <span class="codigo-turma">Código de convite: <strong><?= htmlspecialchars($turma['codigo'] ?? '') ?></strong></span>
        </div>
    </div>
    <nav>
        <a href="horario.php?turma_id=<?= $turma_id ?>"
           class="<?= ($activeNav ?? '') === 'horario'   ? 'nav-active' : '' ?>">📅 Horário</a>
        <a href="tarefas.php?turma_id=<?= $turma_id ?>"
           class="<?= ($activeNav ?? '') === 'tarefas'   ? 'nav-active' : '' ?>">📋 Tarefas</a>
        <a href="materiais.php?turma_id=<?= $turma_id ?>"
           class="<?= ($activeNav ?? '') === 'materiais' ? 'nav-active' : '' ?>">📁 Materiais</a>
        <?php if ($isAdmin ?? false): ?>
            <span class="badge-role badge-admin">👑 Admin</span>
        <?php else: ?>
            <span class="badge-role badge-student">🎓 Aluno</span>
        <?php endif; ?>
        <a href="../pages/logout.php" class="nav-logout">Sair</a>
    </nav>
</header>

<?php
// Exibe flash message se existir
$flash = getFlash();
if ($flash): ?>
    <div class="flash flash-<?= htmlspecialchars($flash['tipo']) ?>">
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
<?php endif; ?>
