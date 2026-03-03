<?php
// ============================================================
//  pages/gerir_horario.php — Formulário Adicionar/Editar Aulas
//  ACESSO RESTRITO: Apenas admins da turma
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$turma_id = filter_input(INPUT_GET, 'turma_id', FILTER_VALIDATE_INT);
if (!$turma_id) { header('Location: dashboard.php'); exit; }

// Dupla verificação: verifica role no banco (não só na sessão)
if (!isAdminInTurma($turma_id)) {
    setFlash('erro', 'Só o chefe da turma pode gerir o horário.');
    header("Location: horario.php?turma_id=$turma_id"); exit;
}

$db   = getDB();
$acao = $_GET['acao'] ?? 'nova';
$id   = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Busca info da turma
$stmt = $db->prepare("SELECT id, nome, codigo FROM turmas WHERE id = ?");
$stmt->execute([$turma_id]);
$turma = $stmt->fetch();
if (!$turma) { header('Location: dashboard.php'); exit; }

// ============================================================
//  AÇÃO: Excluir (via GET com confirmação em JS)
// ============================================================
if ($acao === 'excluir' && $id) {
    $stmt = $db->prepare("DELETE FROM horario_aulas WHERE id = ? AND turma_id = ?");
    $stmt->execute([$id, $turma_id]);
    setFlash('sucesso', 'Aula removida do horário.');
    header("Location: horario.php?turma_id=$turma_id"); exit;
}

// ============================================================
//  AÇÃO: Editar — carrega dados existentes
// ============================================================
$aula = null;
if ($acao === 'editar' && $id) {
    $stmt = $db->prepare("SELECT * FROM horario_aulas WHERE id = ? AND turma_id = ?");
    $stmt->execute([$id, $turma_id]);
    $aula = $stmt->fetch();
    if (!$aula) {
        setFlash('erro', 'Aula não encontrada.');
        header("Location: horario.php?turma_id=$turma_id"); exit;
    }
}

// ============================================================
//  PROCESSAR FORMULÁRIO (POST)
// ============================================================
$erros  = [];
$campos = []; // Valores a pré-preencher no formulário após erro

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'disciplina'  => trim($_POST['disciplina']  ?? ''),
        'professor'   => trim($_POST['professor']   ?? ''),
        'sala'        => trim($_POST['sala']        ?? ''),
        'dia_semana'  => (int)($_POST['dia_semana'] ?? 0),
        'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
        'hora_fim'    => trim($_POST['hora_fim']    ?? ''),
        'cor_hex'     => trim($_POST['cor_hex']     ?? '#4A90D9'),
    ];

    // Validação
    if (empty($campos['disciplina']))              $erros[] = 'O nome da disciplina é obrigatório.';
    if ($campos['dia_semana'] < 1 || $campos['dia_semana'] > 5) $erros[] = 'Dia da semana inválido.';
    if (empty($campos['hora_inicio']))             $erros[] = 'A hora de início é obrigatória.';
    if (empty($campos['hora_fim']))                $erros[] = 'A hora de fim é obrigatória.';
    if ($campos['hora_inicio'] >= $campos['hora_fim']) $erros[] = 'A hora de início deve ser anterior à hora de fim.';

    // Valida o formato da cor hex
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $campos['cor_hex'])) {
        $campos['cor_hex'] = '#4A90D9';
    }

    if (empty($erros)) {
        $editandoId = (int)($_POST['editar_id'] ?? 0);

        if ($editandoId > 0) {
            // ATUALIZAR aula existente
            $stmt = $db->prepare("
                UPDATE horario_aulas
                SET disciplina=?, professor=?, sala=?, dia_semana=?, hora_inicio=?, hora_fim=?, cor_hex=?
                WHERE id=? AND turma_id=?
            ");
            $stmt->execute([
                $campos['disciplina'], $campos['professor'] ?: null, $campos['sala'] ?: null,
                $campos['dia_semana'], $campos['hora_inicio'], $campos['hora_fim'], $campos['cor_hex'],
                $editandoId, $turma_id
            ]);
            setFlash('sucesso', "Aula \"" . $campos['disciplina'] . "\" atualizada.");
        } else {
            // INSERIR nova aula
            $stmt = $db->prepare("
                INSERT INTO horario_aulas (turma_id, disciplina, professor, sala, dia_semana, hora_inicio, hora_fim, cor_hex)
                VALUES (?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $turma_id, $campos['disciplina'], $campos['professor'] ?: null, $campos['sala'] ?: null,
                $campos['dia_semana'], $campos['hora_inicio'], $campos['hora_fim'], $campos['cor_hex']
            ]);
            setFlash('sucesso', "Aula \"" . $campos['disciplina'] . "\" adicionada ao horário!");
        }

        header("Location: horario.php?turma_id=$turma_id"); exit;
    }
}

// Pré-preenche com dados da aula se estiver a editar
if ($aula && empty($campos)) {
    $campos = [
        'disciplina'  => $aula['disciplina'],
        'professor'   => $aula['professor']  ?? '',
        'sala'        => $aula['sala']        ?? '',
        'dia_semana'  => $aula['dia_semana'],
        'hora_inicio' => substr($aula['hora_inicio'], 0, 5),
        'hora_fim'    => substr($aula['hora_fim'],    0, 5),
        'cor_hex'     => $aula['cor_hex'],
    ];
}

$diasNomes = [1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira'];
$coresSugeridas = ['#E74C3C','#E67E22','#F1C40F','#2ECC71','#1ABC9C','#3498DB','#9B59B6','#34495E','#EC407A','#26C6DA'];

$tituloPagina = $acao === 'editar' ? 'Editar Aula' : 'Nova Aula';
$isAdmin      = true;

// Para o layout_header funcionar
$pageTitulo = $tituloPagina;
$activeNav  = 'horario';

require_once __DIR__ . '/../includes/layout_header.php';
?>

<main class="container">
    <section class="card-secao" style="max-width:640px">

        <div class="section-header">
            <h2><?= $acao === 'editar' ? '✏️ Editar Aula' : '➕ Nova Aula' ?></h2>
            <a href="horario.php?turma_id=<?= $turma_id ?>" class="btn btn-secondary btn-sm">← Voltar</a>
        </div>

        <?php if (!empty($erros)): ?>
            <div class="flash flash-erro">
                <?php foreach ($erros as $e): ?><p>⚠️ <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="gerir_horario.php?turma_id=<?= $turma_id ?>" class="form-gerir">

            <!-- ID da aula para saber se é edição ou criação -->
            <?php if ($acao === 'editar' && $aula): ?>
                <input type="hidden" name="editar_id" value="<?= $aula['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Disciplina <span class="obrigatorio">*</span></label>
                <input type="text" name="disciplina" required autofocus
                       value="<?= htmlspecialchars($campos['disciplina'] ?? '') ?>"
                       placeholder="Ex: Cálculo II">
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Professor <small>(opcional)</small></label>
                    <input type="text" name="professor"
                           value="<?= htmlspecialchars($campos['professor'] ?? '') ?>"
                           placeholder="Prof. Santos">
                </div>
                <div class="form-group">
                    <label>Sala <small>(opcional)</small></label>
                    <input type="text" name="sala"
                           value="<?= htmlspecialchars($campos['sala'] ?? '') ?>"
                           placeholder="Lab 3B">
                </div>
            </div>

            <div class="form-group">
                <label>Dia da Semana <span class="obrigatorio">*</span></label>
                <select name="dia_semana" required>
                    <option value="">— Seleciona o dia —</option>
                    <?php foreach ($diasNomes as $num => $nome): ?>
                        <option value="<?= $num ?>"
                            <?= ($campos['dia_semana'] ?? 0) == $num ? 'selected' : '' ?>>
                            <?= $nome ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Hora de Início <span class="obrigatorio">*</span></label>
                    <input type="time" name="hora_inicio" required
                           value="<?= htmlspecialchars($campos['hora_inicio'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Hora de Fim <span class="obrigatorio">*</span></label>
                    <input type="time" name="hora_fim" required
                           value="<?= htmlspecialchars($campos['hora_fim'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Cor na Grade</label>
                <div class="cor-picker">
                    <?php foreach ($coresSugeridas as $cor): ?>
                        <label class="cor-opcao" title="<?= $cor ?>">
                            <input type="radio" name="cor_hex" value="<?= $cor ?>"
                                   <?= ($campos['cor_hex'] ?? '#3498DB') === $cor ? 'checked' : '' ?>>
                            <span class="cor-circulo" style="background:<?= $cor ?>"></span>
                        </label>
                    <?php endforeach; ?>
                    <div class="cor-custom-wrap">
                        <label>Outra:</label>
                        <input type="color" name="cor_hex_custom" id="cor-custom"
                               value="<?= htmlspecialchars($campos['cor_hex'] ?? '#4A90D9') ?>"
                               onchange="document.querySelectorAll('input[name=cor_hex]').forEach(r=>r.checked=false); this.form.cor_hex.value = this.value">
                    </div>
                </div>
                <p class="form-hint">Esta cor aparecerá na célula do horário semanal.</p>
            </div>

            <div class="form-acoes">
                <a href="horario.php?turma_id=<?= $turma_id ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <?= $acao === 'editar' ? '💾 Guardar Alterações' : '➕ Adicionar Aula' ?>
                </button>
            </div>
        </form>

    </section>
</main>

<script>
// Sincroniza o color picker com os radios
document.querySelectorAll('input[name="cor_hex"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('cor-custom').value = this.value;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
