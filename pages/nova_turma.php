<?php
// pages/nova_turma.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// 1. Segurança
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

$erro = '';

function gerarCodigoTurmaLocal() {
    return '#' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 5));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_turma = trim($_POST['nome_turma'] ?? '');

    if (empty($nome_turma)) {
        $erro = "O nome da turma é obrigatório.";
    } else {
        try {
            $db = getDB();
            $db->beginTransaction();

            // Gera código único
            $stmt = $db->prepare("SELECT id FROM turmas WHERE codigo = ?");
            do {
                $codigo_novo = gerarCodigoTurmaLocal();
                $stmt->execute([$codigo_novo]);
            } while ($stmt->fetch());

            // Cria Turma
            $stmt = $db->prepare("INSERT INTO turmas (nome, codigo) VALUES (?, ?)");
            $stmt->execute([$nome_turma, $codigo_novo]);
            $turma_id = $db->lastInsertId();

            // Associa Admin
            $user_id = $_SESSION['user_id'];
            $stmt = $db->prepare("INSERT INTO turma_users (turma_id, user_id, role) VALUES (?, ?, 'admin')");
            $stmt->execute([$turma_id, $user_id]);

            $db->commit();
            
            // Atualiza sessão
            $_SESSION['turma_id'] = $turma_id;
            header('Location: dashboard.php');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $erro = "Erro ao criar turma: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Nova Turma - TurmaApp</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Reset básico para garantir consistência */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { 
            background: linear-gradient(135deg, #1a3a5c 0%, var(--cor-primaria) 100%); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; /* Permite rolar se a tela for pequena */
            padding: 20px;     /* Espaço nas bordas em celulares */
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .card { 
            background: white; 
            padding: 2rem; 
            border-radius: 12px; /* Cantos mais arredondados estilo app moderno */
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); 
            width: 100%; 
            max-width: 400px; 
        }

        h2 { 
            margin-bottom: 0.5rem; 
            text-align: center; 
            color: #333;
        }

        p.subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        input[type="text"] { 
            width: 100%; 
            padding: 12px; 
            margin-bottom: 20px; 
            border: 2px solid #eee; 
            border-radius: 8px; 
            font-size: 16px; /* Evita zoom automático no iPhone */
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            border-color: #007bff;
            outline: none;
        }

        .btn { 
            display: block; 
            width: 100%; 
            padding: 14px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 1rem; 
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-primary { 
            background: #007bff; 
            color: white; 
            margin-bottom: 10px;
        }
        .btn-primary:active { transform: scale(0.98); }

        .btn-secondary { 
            background: #e2e6ea; 
            color: #495057; 
        }

        .erro { 
            color: #721c24; 
            background-color: #f8d7da; 
            border: 1px solid #f5c6cb; 
            padding: 10px; 
            border-radius: 8px; 
            margin-bottom: 15px; 
            font-size: 0.9rem;
            text-align: center;
        }

        /* Ajustes específicos para telas muito pequenas */
        @media (max-width: 400px) {
            .card { padding: 1.5rem; }
            h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="card">
        <h2>✨ Nova Turma</h2>
        <p class="subtitle">Crie um espaço para organizar os horários e tarefas da sua classe.</p>
        
        <?php if ($erro): ?>
            <div class="erro">⚠️ <?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="nome_turma">Nome da Turma</label>
            <input type="text" id="nome_turma" name="nome_turma" placeholder="Ex: Engenharia Informática 2º Ano" required>
            
            <button type="submit" class="btn btn-primary">Criar Turma</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

</body>
</html>