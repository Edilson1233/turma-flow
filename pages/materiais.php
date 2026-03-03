<?php
// ============================================================
//  pages/materiais.php — Repositório de Materiais de Apoio
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
//  AÇÃO: Adicionar material (POST — só admin)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($isAdmin)) {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar') {
        $titulo    = trim($_POST['titulo']    ?? '');
        $url       = trim($_POST['url']       ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $tipo      = $_POST['tipo'] ?? 'Outro';

        $tiposValidos = ['Drive', 'YouTube', 'PDF', 'Outro'];
        if (!in_array($tipo, $tiposValidos)) $tipo = 'Outro';

        $erros = [];
        if (empty($titulo)) $erros[] = 'O título é obrigatório.';
        if (empty($url))    $erros[] = 'O link é obrigatório.';
        if (!filter_var($url, FILTER_VALIDATE_URL)) $erros[] = 'O link não parece válido.';

        if (empty($erros)) {
            $user   = getCurrentUser();
            $stmt   = $db->prepare("INSERT INTO materiais (turma_id, titulo, url, descricao, tipo, criado_por) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$turma_id, $titulo, $url, $descricao ?: null, $tipo, $user['id']]);
            setFlash('sucesso', "Material \"$titulo\" adicionado com sucesso!");
            header("Location: materiais.php?turma_id=$turma_id"); exit;
        }
        // Se houver erros, continua a mostrar o formulário com erros
    }

    if ($acao === 'excluir') {
        $excluir_id = filter_input(INPUT_POST, 'excluir_id', FILTER_VALIDATE_INT);
        if ($excluir_id) {
            $stmt = $db->prepare("DELETE FROM materiais WHERE id = ? AND turma_id = ?");
            $stmt->execute([$excluir_id, $turma_id]);
            setFlash('sucesso', 'Material removido.');
        }
        header("Location: materiais.php?turma_id=$turma_id"); exit;
    }
}

// Busca todos os materiais agrupados por tipo
$stmt = $db->prepare("
    SELECT m.*, u.nome AS adicionado_por_nome
    FROM materiais m
    JOIN users u ON m.criado_por = u.id
    WHERE m.turma_id = ?
    ORDER BY m.tipo ASC, m.criado_em DESC
");
$stmt->execute([$turma_id]);
$todosMateriais = $stmt->fetchAll();

// Agrupa por tipo para exibição mais organizada
$materiaisPorTipo = [];
foreach ($todosMateriais as $m) {
    $materiaisPorTipo[$m['tipo']][] = $m;
}

$iconesTipo = [
    'Drive'   => '📂',
    'YouTube' => '▶️',
    'PDF'     => '📄',
    'Outro'   => '🔗',
];

$pageTitulo = 'Materiais';
$activeNav  = 'materiais';

require_once __DIR__ . '/../includes/layout_header.php';
?>

<main class="container">

    <!-- === FORMULÁRIO DE ADICIONAR (só admin) === -->
    <?php if ($isAdmin): ?>
    <section class="card-secao">
        <div class="section-header">
            <h2>➕ Adicionar Material</h2>
            <button class="btn btn-secondary btn-sm" onclick="toggleForm('form-material')">
                Mostrar/Ocultar
            </button>
        </div>
        <div id="form-material">
            <?php if (!empty($erros ?? [])): ?>
                <div class="flash flash-erro">
                    <?php foreach ($erros as $e): ?><p>⚠️ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="materiais.php?turma_id=<?= $turma_id ?>" class="form-material">
                <input type="hidden" name="acao" value="adicionar">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Título <span class="obrigatorio">*</span></label>
                        <input type="text" name="titulo" required
                               value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>"
                               placeholder="Slides Aula 3 — Derivadas">
                    </div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="tipo">
                            <?php foreach ($iconesTipo as $t => $ico): ?>
                                <option value="<?= $t ?>" <?= ($_POST['tipo'] ?? '') === $t ? 'selected' : '' ?>>
                                    <?= $ico ?> <?= $t ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Link <span class="obrigatorio">*</span></label>
                    <input type="url" name="url" required
                           value="<?= htmlspecialchars($_POST['url'] ?? '') ?>"
                           placeholder="https://drive.google.com/file/...">
                </div>

                <div class="form-group">
                    <label>Nota (opcional)</label>
                    <input type="text" name="descricao"
                           value="<?= htmlspecialchars($_POST['descricao'] ?? '') ?>"
                           placeholder="Ex: Cobre os capítulos 3 e 4 do livro">
                </div>

                <button type="submit" class="btn btn-primary">💾 Guardar Material</button>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <!-- === LISTA DE MATERIAIS === -->
    <section class="card-secao">
        <h2>📁 Materiais de Apoio</h2>

        <?php if (empty($todosMateriais)): ?>
            <div class="empty-state">
                <p style="font-size:2.5rem">📭</p>
                <h3>Sem materiais ainda</h3>
                <?php if ($isAdmin): ?>
                    <p>Adiciona links do Google Drive, YouTube, PDFs e mais.</p>
                <?php else: ?>
                    <p>O chefe da turma ainda não adicionou materiais.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <?php foreach ($materiaisPorTipo as $tipo => $lista): ?>
                <div class="materiais-grupo">
                    <h3 class="materiais-grupo-titulo">
                        <?= $iconesTipo[$tipo] ?? '🔗' ?> <?= htmlspecialchars($tipo) ?>
                        <span class="materiais-count"><?= count($lista) ?></span>
                    </h3>
                    <div class="materiais-grid">
                        <?php foreach ($lista as $m): ?>
                            <div class="material-card">
                                <div class="material-card-corpo">
                                    <a href="<?= htmlspecialchars($m['url']) ?>"
                                       target="_blank" rel="noopener noreferrer"
                                       class="material-titulo">
                                        <?= htmlspecialchars($m['titulo']) ?>
                                        <span class="material-ext-icon">↗</span>
                                    </a>
                                    <?php if ($m['descricao']): ?>
                                        <p class="material-descricao"><?= htmlspecialchars($m['descricao']) ?></p>
                                    <?php endif; ?>
                                    <span class="material-meta">
                                        Adicionado por <?= htmlspecialchars($m['adicionado_por_nome']) ?>
                                        · <?= date('d/m/Y', strtotime($m['criado_em'])) ?>
                                    </span>
                                </div>
                                <?php if ($isAdmin): ?>
                                    <div class="material-card-acoes">
                                        <form method="POST" action="materiais.php?turma_id=<?= $turma_id ?>"
                                              onsubmit="return confirm('Remover este material?')">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="excluir_id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="btn-mini btn-excluir" title="Remover">🗑️</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

</main>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
