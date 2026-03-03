<?php
// ============================================================
//  pages/horario.php — Grade Visual do Horário Semanal
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

// Busca todas as aulas ordenadas
$stmt = $db->prepare("
    SELECT id, disciplina, professor, sala, dia_semana,
           hora_inicio, hora_fim, cor_hex
    FROM horario_aulas
    WHERE turma_id = ?
    ORDER BY dia_semana ASC, hora_inicio ASC
");
$stmt->execute([$turma_id]);
$todasAulas = $stmt->fetchAll();

// ============================================================
//  Transforma a lista plana numa grade 2D indexada por [dia][hora]
//  $grade[1]['08:00'] = array com dados da aula
// ============================================================
$grade      = [];
$todasHoras = [];

foreach ($todasAulas as $aula) {
    $dia  = $aula['dia_semana'];
    $hora = substr($aula['hora_inicio'], 0, 5);
    $grade[$dia][$hora] = $aula;
    $todasHoras[$hora]  = true;
}
ksort($todasHoras);

$diasNomes = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta'];

$pageTitulo = 'Horário';
$activeNav  = 'horario';

require_once __DIR__ . '/../includes/layout_header.php';
?>

<main class="container">
    <section class="card-secao">

        <div class="section-header">
            <h2>📅 Horário Semanal</h2>
            <?php if ($isAdmin): ?>
                <a href="gerir_horario.php?turma_id=<?= $turma_id ?>&acao=nova"
                   class="btn btn-primary">+ Adicionar Aula</a>
            <?php endif; ?>
        </div>

        <?php if (empty($todasHoras)): ?>
            <div class="empty-state">
                <p style="font-size:2.5rem">📭</p>
                <h3>Sem aulas no horário ainda</h3>
                <?php if ($isAdmin): ?>
                    <p>Começa a preencher o horário da turma.</p>
                    <a href="gerir_horario.php?turma_id=<?= $turma_id ?>&acao=nova"
                       class="btn btn-primary" style="margin-top:1rem">+ Primeira Aula</a>
                <?php else: ?>
                    <p>O chefe da turma ainda não adicionou o horário.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>

        <div class="tabela-wrapper">
            <table class="tabela-horario">
                <thead>
                    <tr>
                        <th class="th-hora">⏰</th>
                        <?php foreach ($diasNomes as $numDia => $nomeDia): ?>
                            <th class="th-dia"><?= $nomeDia ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_keys($todasHoras) as $hora): ?>
                        <tr>
                            <td class="td-hora"><?= htmlspecialchars($hora) ?></td>

                            <?php foreach ($diasNomes as $numDia => $nomeDia):
                                $aula = $grade[$numDia][$hora] ?? null;
                            ?>
                                <td class="td-aula <?= $aula ? 'tem-aula' : 'sem-aula' ?>"
                                    <?= $aula ? 'style="background-color:' . htmlspecialchars($aula['cor_hex']) . ';"' : '' ?>>

                                    <?php if ($aula): ?>
                                        <div class="aula-card">
                                            <strong class="aula-nome"><?= htmlspecialchars($aula['disciplina']) ?></strong>
                                            <?php if ($aula['professor']): ?>
                                                <span class="aula-info">👤 <?= htmlspecialchars($aula['professor']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($aula['sala']): ?>
                                                <span class="aula-info">📍 <?= htmlspecialchars($aula['sala']) ?></span>
                                            <?php endif; ?>
                                            <span class="aula-horas"><?= substr($aula['hora_inicio'],0,5) ?> – <?= substr($aula['hora_fim'],0,5) ?></span>
                                            <?php if ($isAdmin): ?>
                                                <div class="aula-acoes">
                                                    <a href="gerir_horario.php?turma_id=<?= $turma_id ?>&acao=editar&id=<?= $aula['id'] ?>"
                                                       class="btn-mini btn-editar" title="Editar">✏️</a>
                                                    <a href="gerir_horario.php?turma_id=<?= $turma_id ?>&acao=excluir&id=<?= $aula['id'] ?>"
                                                       class="btn-mini btn-excluir" title="Excluir"
                                                       onclick="return confirm('Excluir esta aula?')">🗑️</a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="celula-livre">–</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
