<?php
// ============================================================
//  setup.php - Configuracao inicial local
//
//  Como usar:
//  1. Garante que o Apache e o MySQL estao ativos no XAMPP.
//  2. Acede a http://localhost/turma-flow/setup.php.
//  3. Depois de confirmar que o login funciona, remove este ficheiro.
// ============================================================

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    die('Acesso negado. Este script so pode ser executado a partir de localhost.');
}

require_once __DIR__ . '/includes/db.php';

$log = [];
$erros = [];

try {
    $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $db = new PDO($dsn, DB_USER, DB_PASS, $options);

    $dbName = str_replace('`', '``', DB_NAME);
    $db->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE `{$dbName}`");

    $db->exec("CREATE TABLE IF NOT EXISTS turmas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        codigo VARCHAR(10) NOT NULL UNIQUE,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS turma_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        turma_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
        entrou_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_turma_user (turma_id, user_id),
        FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS horario_aulas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        turma_id INT UNSIGNED NOT NULL,
        disciplina VARCHAR(100) NOT NULL,
        professor VARCHAR(100),
        sala VARCHAR(50),
        dia_semana TINYINT NOT NULL,
        hora_inicio TIME NOT NULL,
        hora_fim TIME NOT NULL,
        cor_hex VARCHAR(7) DEFAULT '#4A90D9',
        FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS tarefas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        turma_id INT UNSIGNED NOT NULL,
        titulo VARCHAR(150) NOT NULL,
        descricao TEXT,
        data_entrega DATETIME NOT NULL,
        tipo ENUM('TPC', 'Teste', 'Exame', 'Projeto') NOT NULL DEFAULT 'TPC',
        criado_por INT UNSIGNED NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
        FOREIGN KEY (criado_por) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS materiais (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        turma_id INT UNSIGNED NOT NULL,
        titulo VARCHAR(150) NOT NULL,
        url TEXT NOT NULL,
        descricao VARCHAR(255),
        tipo ENUM('Drive', 'YouTube', 'PDF', 'Outro') NOT NULL DEFAULT 'Outro',
        criado_por INT UNSIGNED NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
        FOREIGN KEY (criado_por) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        INDEX idx_password_resets_user_created (user_id, created_at),
        INDEX idx_password_resets_token_active (token_hash, used_at, expires_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $log[] = ['ok', 'Base de dados e tabelas criadas/verificadas com sucesso.'];

    $hashAdmin = password_hash('123456', PASSWORD_BCRYPT);
    $hashAluno = password_hash('123456', PASSWORD_BCRYPT);

    $stmt = $db->prepare("
        INSERT INTO users (nome, email, password)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE nome = VALUES(nome), password = VALUES(password)
    ");
    $stmt->execute(['Maria Admin', 'maria@uni.pt', $hashAdmin]);
    $stmt->execute(['Joao Aluno', 'joao@uni.pt', $hashAluno]);

    $log[] = ['ok', 'Utilizador admin criado/atualizado: maria@uni.pt / 123456'];
    $log[] = ['ok', 'Utilizador aluno criado/atualizado: joao@uni.pt / 123456'];

    $stmt = $db->prepare("SELECT id FROM turmas WHERE codigo = '#ENG24'");
    $stmt->execute();
    $turmaExistente = $stmt->fetch();

    if (!$turmaExistente) {
        $stmt = $db->prepare("INSERT INTO turmas (nome, codigo) VALUES (?, ?)");
        $stmt->execute(['Engenharia Informatica - 2024/25', '#ENG24']);
        $log[] = ['ok', 'Turma de exemplo criada: #ENG24'];
    } else {
        $log[] = ['info', 'Turma #ENG24 ja existe; foi mantida.'];
    }

    $stmtUser = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmtUser->execute(['maria@uni.pt']);
    $idMaria = (int) $stmtUser->fetchColumn();

    $stmtUser->execute(['joao@uni.pt']);
    $idJoao = (int) $stmtUser->fetchColumn();

    $stmtTurma = $db->query("SELECT id FROM turmas WHERE codigo = '#ENG24'");
    $idTurma = (int) $stmtTurma->fetchColumn();

    $stmtLink = $db->prepare("INSERT IGNORE INTO turma_users (turma_id, user_id, role) VALUES (?, ?, ?)");
    $stmtLink->execute([$idTurma, $idMaria, 'admin']);
    $stmtLink->execute([$idTurma, $idJoao, 'student']);

    $log[] = ['ok', 'Maria definida como admin e Joao como aluno na turma #ENG24.'];

    $stmtCount = $db->prepare("SELECT COUNT(*) FROM horario_aulas WHERE turma_id = ?");
    $stmtCount->execute([$idTurma]);

    if ((int) $stmtCount->fetchColumn() === 0) {
        $aulas = [
            [$idTurma, 'Calculo II', 'Prof. Santos', 'Sala 101', 1, '08:00', '10:00', '#E74C3C'],
            [$idTurma, 'Programacao Web', 'Prof. Nunes', 'Lab 3B', 1, '14:00', '16:00', '#3498DB'],
            [$idTurma, 'Fisica', 'Prof. Lima', 'Sala 202', 2, '10:00', '12:00', '#2ECC71'],
            [$idTurma, 'Algebra Linear', 'Prof. Costa', 'Sala 101', 3, '08:00', '10:00', '#9B59B6'],
            [$idTurma, 'Programacao Web', 'Prof. Nunes', 'Lab 3B', 4, '14:00', '16:00', '#3498DB'],
            [$idTurma, 'Calculo II', 'Prof. Santos', 'Sala 101', 5, '08:00', '10:00', '#E74C3C'],
        ];

        $stmtAula = $db->prepare("
            INSERT INTO horario_aulas (turma_id, disciplina, professor, sala, dia_semana, hora_inicio, hora_fim, cor_hex)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($aulas as $aula) {
            $stmtAula->execute($aula);
        }

        $log[] = ['ok', '6 aulas de exemplo adicionadas ao horario.'];
    } else {
        $log[] = ['info', 'Horario ja tem dados; foi mantido.'];
    }

    $stmtCount = $db->prepare("SELECT COUNT(*) FROM tarefas WHERE turma_id = ?");
    $stmtCount->execute([$idTurma]);

    if ((int) $stmtCount->fetchColumn() === 0) {
        $stmtTarefa = $db->prepare("
            INSERT INTO tarefas (turma_id, titulo, descricao, data_entrega, tipo, criado_por)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtTarefa->execute([$idTurma, 'TPC 3 - Limites e Continuidade', 'Exercicios 5 a 12 da ficha 3.', date('Y-m-d H:i:s', strtotime('+5 days')), 'TPC', $idMaria]);
        $stmtTarefa->execute([$idTurma, 'Teste Intercalar de Fisica', 'Capitulos 1, 2 e 3.', date('Y-m-d H:i:s', strtotime('+12 days')), 'Teste', $idMaria]);
        $stmtTarefa->execute([$idTurma, 'Projeto Final de Programacao Web', 'Ver enunciado no Drive da turma.', date('Y-m-d H:i:s', strtotime('+30 days')), 'Projeto', $idMaria]);

        $log[] = ['ok', '3 tarefas de exemplo adicionadas.'];
    } else {
        $log[] = ['info', 'Tarefas ja existem; foram mantidas.'];
    }

    $stmtCount = $db->prepare("SELECT COUNT(*) FROM materiais WHERE turma_id = ?");
    $stmtCount->execute([$idTurma]);

    if ((int) $stmtCount->fetchColumn() === 0) {
        $stmtMaterial = $db->prepare("
            INSERT INTO materiais (turma_id, titulo, url, descricao, tipo, criado_por)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtMaterial->execute([$idTurma, 'Slides Aula 1 - Introducao ao Calculo', 'https://drive.google.com', 'Limites e derivadas basicas', 'Drive', $idMaria]);
        $stmtMaterial->execute([$idTurma, 'Video: Regra da Cadeia Explicada', 'https://youtube.com', '12 minutos, muito claro', 'YouTube', $idMaria]);
        $stmtMaterial->execute([$idTurma, 'Formulario de Fisica para o Teste', 'https://drive.google.com', 'Permitido no teste', 'PDF', $idMaria]);

        $log[] = ['ok', '3 materiais de exemplo adicionados.'];
    } else {
        $log[] = ['info', 'Materiais ja existem; foram mantidos.'];
    }
} catch (Throwable $e) {
    $erros[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - TurmaFlow</title>
    <style>
        body { font-family: "Segoe UI", sans-serif; background: #f0f3f7; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; }
        .card { background: white; border-radius: 12px; padding: 2rem; max-width: 680px; width: 100%; box-shadow: 0 8px 32px rgba(0,0,0,0.12); }
        h1 { color: #2c3e50; margin-bottom: 0.3rem; }
        .subtitle { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 1.5rem; }
        .log-item { display: flex; gap: 0.7rem; align-items: flex-start; padding: 0.5rem 0; border-bottom: 1px solid #f0f3f7; font-size: 0.9rem; }
        .ico { font-weight: 700; flex-shrink: 0; }
        .ok { color: #27ae60; }
        .info { color: #2980b9; }
        .box-credenciais { background: #eafaf1; border: 2px solid #27ae60; border-radius: 8px; padding: 1.2rem; margin: 1.5rem 0; }
        .box-credenciais h3 { color: #1d6a3a; margin: 0 0 0.8rem; }
        .credencial { display: flex; gap: 1rem; font-family: monospace; font-size: 0.95rem; margin: 0.3rem 0; flex-wrap: wrap; }
        .credencial .label { color: #7f8c8d; min-width: 80px; }
        .credencial .valor { font-weight: 700; color: #2c3e50; }
        .box-aviso { background: #fef9e7; border: 2px solid #f39c12; border-radius: 8px; padding: 1rem; margin-top: 1rem; font-size: 0.9rem; color: #7d6608; }
        .btn { display: inline-block; padding: 0.7rem 1.5rem; background: #4a90d9; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 1.2rem; }
        .btn:hover { background: #2c6fab; }
        .box-erro { background: #fdedec; border: 2px solid #e74c3c; border-radius: 8px; padding: 1rem; color: #922b21; font-family: monospace; font-size: 0.85rem; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>TurmaFlow - Setup</h1>
    <p class="subtitle">Configuracao inicial da base de dados <strong><?= htmlspecialchars(DB_NAME) ?></strong></p>

    <?php if (!empty($erros)): ?>
        <div class="box-erro">
            <strong>Erro durante o setup:</strong><br>
            <?php foreach ($erros as $erro): ?>
                <?= htmlspecialchars($erro) ?><br>
            <?php endforeach; ?>
            <br>Verifica as credenciais em <code>includes/db.php</code>.
        </div>
    <?php else: ?>
        <?php foreach ($log as [$tipo, $msg]): ?>
            <div class="log-item">
                <span class="ico <?= htmlspecialchars($tipo) ?>"><?= $tipo === 'ok' ? 'OK' : 'INFO' ?></span>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
        <?php endforeach; ?>

        <div class="box-credenciais">
            <h3>Credenciais de teste</h3>
            <div class="credencial"><span class="label">Admin:</span> <span class="valor">maria@uni.pt</span> <span class="valor">123456</span></div>
            <div class="credencial"><span class="label">Aluno:</span> <span class="valor">joao@uni.pt</span> <span class="valor">123456</span></div>
            <div class="credencial"><span class="label">Codigo:</span> <span class="valor">#ENG24</span></div>
        </div>

        <div class="box-aviso">
            <strong>Seguranca:</strong> depois de confirmar que o login funciona, remove o ficheiro <code>setup.php</code> do servidor.
        </div>

        <a href="<?= htmlspecialchars(BASE_URL) ?>/pages/login.php" class="btn">Ir para o Login</a>
    <?php endif; ?>
</div>
</body>
</html>
