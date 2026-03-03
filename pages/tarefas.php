<?php
// ============================================================
//  pages/tarefas.php — Tarefas da Turma (TPCs, Testes, Exames)
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$turma_id = filter_input(INPUT_GET, 'turma_id', FILTER_VALIDATE_INT);
if (!$turma_id) {
    setFlash('erro', 'Turma não especificada.');
    header('Location: dashboard.php'); exit;
}

$meuRole = getUserRoleInTurma($turma_id);
if (!$meuRole) {
    setFlash('erro', 'Não tens acesso a esta turma.');
    header('Location: dashboard.php'); exit;
}
$isAdmin = ($meuRole === 'admin');

$db = getDB();

// Busca info da turma
$stmt = $db->prepare("SELECT id, nome, codigo FROM turmas WHERE id = ?");
$stmt->execute([$turma_id]);
$turma = $stmt->fetch();
if (!$turma) { header('Location: dashboard.php'); exit; }

// ============================================================
//  AÇÃO: Excluir tarefa (só admin, via POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    if (!$isAdmin) { setFlash('erro', 'Sem permissão.'); header("Location: tarefas.php?turma_id=$turma_id"); exit; }

    $excluir_id = filter_input(INPUT_POST, 'excluir_id', FILTER_VALIDATE_INT);
    if ($excluir_id) {
        // A condição AND turma_id = ? garante que o admin só apaga da SUA turma
        $stmt = $db->prepare("DELETE FROM tarefas WHERE id = ? AND turma_id = ?");
        $stmt->execute([$excluir_id, $turma_id]);
        setFlash('sucesso', 'Tarefa removida com sucesso.');
    }
    header("Location: tarefas.php?turma_id=$turma_id"); exit;
}

// ============================================================
//  BUSCAR TAREFAS (futuras primeiro, passadas no fim)
// ============================================================
$stmt = $db->prepare("
    SELECT t.*, u.nome AS criado_por_nome
    FROM tarefas t
    JOIN users u ON t.criado_por = u.id
    WHERE t.turma_id = ?
    ORDER BY
        CASE WHEN t.data_entrega >= NOW() THEN 0 ELSE 1 END,
        t.data_entrega ASC
");
$stmt->execute([$turma_id]);
$tarefas = $stmt->fetchAll();

$iconesTipo = ['TPC' => '📝', 'Teste' => '📋', 'Exame' => '🎓', 'Projeto' => '🔧'];

function horasAteEntrega(string $data): float {
    $diff = (new DateTime($data))->diff(new DateTime());
    return $diff->invert ? ($diff->days * 24 + $diff->h) : -1;
}

$pageTitulo = 'Tarefas';
$activeNav  = 'tarefas';

require_once __DIR__ . '/../includes/layout_header.php';
?>

<main class="container">
    <section class="card-secao">

        <div class="section-header">
            <h2>📋 Tarefas e Avaliações</h2>
            <?php if ($isAdmin): ?>
                <a href="gerir_tarefa.php?turma_id=<?= $turma_id ?>&acao=nova"
                   class="btn btn-primary">+ Adicionar Tarefa</a>
            <?php endif; ?>
        </div>


        <?php if (empty($tarefas)): ?>
            <div class="empty-state">
                <p style="font-size:2.5rem">🎉</p>
                <h3>Sem tarefas registadas</h3>
                <?php if ($isAdmin): ?>
                    <p>Adiciona a primeira tarefa da turma.</p>
                    <a href="gerir_tarefa.php?turma_id=<?= $turma_id ?>&acao=nova"
                       class="btn btn-primary" style="margin-top:1rem">+ Primeira Tarefa</a>
                <?php else: ?>
                    <p>O chefe da turma ainda não adicionou tarefas.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <ul class="lista-tarefas">
                <?php foreach ($tarefas as $tarefa):
                    $passada = strtotime($tarefa['data_entrega']) < time();
                    $horas   = $passada ? -1 : horasAteEntrega($tarefa['data_entrega']);
                    $isCritica = !$passada && $horas <= 24;
                    $isUrgente = !$passada && $horas <= 48 && $horas > 24;
                    $icone = $iconesTipo[$tarefa['tipo']] ?? '📌';

                    $classe = '';
                    if ($passada)    $classe = 'tarefa-passada';
                    elseif($isCritica) $classe = 'tarefa-critica';
                    elseif($isUrgente) $classe = 'tarefa-urgente';
                ?>
                    <li class="tarefa-item <?= $classe ?>">
                        <div class="tarefa-header">
                            <span class="tarefa-badge tarefa-badge-<?= strtolower($tarefa['tipo']) ?>">
                                <?= $icone ?> <?= htmlspecialchars($tarefa['tipo']) ?>
                            </span>
                            <strong class="tarefa-titulo"><?= htmlspecialchars($tarefa['titulo']) ?></strong>
                            <?php if ($passada): ?>
                                <span class="badge badge-passada">✅ Entregue</span>
                            <?php elseif ($isCritica): ?>
                                <span class="badge badge-critico">⚠️ Menos de 24h!</span>
                            <?php elseif ($isUrgente): ?>
                                <span class="badge badge-urgente">🔴 Urgente</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($tarefa['descricao']): ?>
                            <p class="tarefa-descricao"><?= nl2br(htmlspecialchars($tarefa['descricao'])) ?></p>
                        <?php endif; ?>

                        <div class="tarefa-footer">
                            <div class="tarefa-meta">
                                <span class="tarefa-data">
                                    📅 <?= date('d/m/Y \à\s H:i', strtotime($tarefa['data_entrega'])) ?>
                                </span>
                                <span class="tarefa-autor">Adicionado por <?= htmlspecialchars($tarefa['criado_por_nome']) ?></span>
                            </div>
                            <?php if ($isAdmin): ?>
                                <div class="tarefa-acoes">
                                    <a href="gerir_tarefa.php?turma_id=<?= $turma_id ?>&acao=editar&id=<?= $tarefa['id'] ?>"
                                       class="btn btn-secondary btn-sm">✏️ Editar</a>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Tens a certeza que queres excluir esta tarefa?')">
                                        <input type="hidden" name="excluir_id" value="<?= $tarefa['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑️ Excluir</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
