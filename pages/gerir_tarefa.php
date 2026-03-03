<?php
// ============================================================
//  pages/gerir_tarefa.php — Formulário Adicionar/Editar Tarefas
//  ACESSO RESTRITO: Apenas admins da turma
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$turma_id = filter_input(INPUT_GET, 'turma_id', FILTER_VALIDATE_INT);
if (!$turma_id) { header('Location: dashboard.php'); exit; }

if (!isAdminInTurma($turma_id)) {
    setFlash('erro', 'Só o chefe da turma pode gerir as tarefas.');
    header("Location: tarefas.php?turma_id=$turma_id"); exit;
}

$db   = getDB();
$acao = $_GET['acao'] ?? 'nova';
$id   = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user = getCurrentUser();

// Busca info da turma
$stmt = $db->prepare("SELECT id, nome, codigo FROM turmas WHERE id = ?");
$stmt->execute([$turma_id]);
$turma = $stmt->fetch();
if (!$turma) { header('Location: dashboard.php'); exit; }

// ============================================================
//  AÇÃO: Editar — carrega dados existentes
// ============================================================
$tarefa = null;
if ($acao === 'editar' && $id) {
    $stmt = $db->prepare("SELECT * FROM tarefas WHERE id = ? AND turma_id = ?");
    $stmt->execute([$id, $turma_id]);
    $tarefa = $stmt->fetch();
    if (!$tarefa) {
        setFlash('erro', 'Tarefa não encontrada.');
        header("Location: tarefas.php?turma_id=$turma_id"); exit;
    }
}

// ============================================================
//  PROCESSAR FORMULÁRIO (POST)
// ============================================================
$erros  = [];
$campos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'titulo'       => trim($_POST['titulo']       ?? ''),
        'descricao'    => trim($_POST['descricao']    ?? ''),
        'data_entrega' => trim($_POST['data_entrega'] ?? ''),
        'hora_entrega' => trim($_POST['hora_entrega'] ?? '23:59'),
        'tipo'         => $_POST['tipo'] ?? 'TPC',
    ];

    $tiposValidos = ['TPC', 'Teste', 'Exame', 'Projeto'];
    if (!in_array($campos['tipo'], $tiposValidos)) $campos['tipo'] = 'TPC';

    // Validação
    if (empty($campos['titulo']))       $erros[] = 'O título é obrigatório.';
    if (empty($campos['data_entrega'])) $erros[] = 'A data de entrega é obrigatória.';

    $dataHoraStr = '';
    if (!empty($campos['data_entrega'])) {
        $dataHoraStr = $campos['data_entrega'] . ' ' . ($campos['hora_entrega'] ?: '23:59') . ':00';
        if (!strtotime($dataHoraStr)) $erros[] = 'Data de entrega inválida.';
    }

    if (empty($erros)) {
        $editandoId = (int)($_POST['editar_id'] ?? 0);

        if ($editandoId > 0) {
            $stmt = $db->prepare("
                UPDATE tarefas
                SET titulo=?, descricao=?, data_entrega=?, tipo=?
                WHERE id=? AND turma_id=?
            ");
            $stmt->execute([
                $campos['titulo'], $campos['descricao'] ?: null, $dataHoraStr, $campos['tipo'],
                $editandoId, $turma_id
            ]);
            setFlash('sucesso', "Tarefa \"" . $campos['titulo'] . "\" atualizada.");
        } else {
            $stmt = $db->prepare("
                INSERT INTO tarefas (turma_id, titulo, descricao, data_entrega, tipo, criado_por)
                VALUES (?,?,?,?,?,?)
            ");
            $stmt->execute([
                $turma_id, $campos['titulo'], $campos['descricao'] ?: null,
                $dataHoraStr, $campos['tipo'], $user['id']
            ]);
            setFlash('sucesso', "Tarefa \"" . $campos['titulo'] . "\" adicionada!");
        }

        header("Location: tarefas.php?turma_id=$turma_id"); exit;
    }
}

// Pré-preenche com dados se estiver a editar
if ($tarefa && empty($campos)) {
    $dt = new DateTime($tarefa['data_entrega']);
    $campos = [
        'titulo'       => $tarefa['titulo'],
        'descricao'    => $tarefa['descricao'] ?? '',
        'data_entrega' => $dt->format('Y-m-d'),
        'hora_entrega' => $dt->format('H:i'),
        'tipo'         => $tarefa['tipo'],
    ];
}

$tiposInfo = [
    'TPC'     => ['icone' => '📝', 'label' => 'TPC / Trabalho'],
    'Teste'   => ['icone' => '📋', 'label' => 'Teste'],
    'Exame'   => ['icone' => '🎓', 'label' => 'Exame'],
    'Projeto' => ['icone' => '🔧', 'label' => 'Projeto'],
];

$isAdmin    = true;
$pageTitulo = $acao === 'editar' ? 'Editar Tarefa' : 'Nova Tarefa';
$activeNav  = 'tarefas';

require_once __DIR__ . '/../includes/layout_header.php';
?>

<main class="container">
    <section class="card-secao" style="max-width:640px">

        <div class="section-header">
            <h2><?= $acao === 'editar' ? '✏️ Editar Tarefa' : '➕ Nova Tarefa' ?></h2>
            <a href="tarefas.php?turma_id=<?= $turma_id ?>" class="btn btn-secondary btn-sm">← Voltar</a>
        </div>

        <?php if (!empty($erros)): ?>
            <div class="flash flash-erro">
                <?php foreach ($erros as $e): ?><p>⚠️ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="gerir_tarefa.php?turma_id=<?= $turma_id ?>" class="form-gerir">

            <?php if ($acao === 'editar' && $tarefa): ?>
                <input type="hidden" name="editar_id" value="<?= $tarefa['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Título <span class="obrigatorio">*</span></label>
                <input type="text" name="titulo" required autofocus
                       value="<?= htmlspecialchars($campos['titulo'] ?? '') ?>"
                       placeholder="Ex: Trabalho de Investigação — Capítulo 5">
            </div>

            <div class="form-group">
                <label>Tipo de Avaliação <span class="obrigatorio">*</span></label>
                <div class="tipo-grid">
                    <?php foreach ($tiposInfo as $val => $info): ?>
                        <label class="tipo-opcao <?= ($campos['tipo'] ?? 'TPC') === $val ? 'tipo-opcao-active' : '' ?>">
                            <input type="radio" name="tipo" value="<?= $val ?>"
                                   <?= ($campos['tipo'] ?? 'TPC') === $val ? 'checked' : '' ?>>
                            <span class="tipo-icon"><?= $info['icone'] ?></span>
                            <span><?= $info['label'] ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Data de Entrega <span class="obrigatorio">*</span></label>
                    <input type="date" name="data_entrega" required
                           value="<?= htmlspecialchars($campos['data_entrega'] ?? '') ?>"
                           min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Hora</label>
                    <input type="time" name="hora_entrega"
                           value="<?= htmlspecialchars($campos['hora_entrega'] ?? '23:59') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Descrição <small>(opcional)</small></label>
                <textarea name="descricao" rows="4"
                          placeholder="Detalhes sobre a tarefa: o que entregar, onde submeter, formato..."><?= htmlspecialchars($campos['descricao'] ?? '') ?></textarea>
            </div>

            <div class="form-acoes">
                <a href="tarefas.php?turma_id=<?= $turma_id ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <?= $acao === 'editar' ? '💾 Guardar Alterações' : '➕ Adicionar Tarefa' ?>
                </button>
            </div>
        </form>

    </section>
</main>

<script>
// Atualiza estilo visual dos tipos ao selecionar
document.querySelectorAll('input[name="tipo"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.tipo-opcao').forEach(l => l.classList.remove('tipo-opcao-active'));
        this.closest('.tipo-opcao').classList.add('tipo-opcao-active');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
