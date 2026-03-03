<?php
// ============================================================
//  pages/erro.php — Página de Erro Amigável
// ============================================================

$codigo    = filter_input(INPUT_GET, 'codigo', FILTER_VALIDATE_INT) ?: 404;
$mensagens = [
    403 => ['Acesso Negado',           '🔒', 'Não tens permissão para aceder a esta página.'],
    404 => ['Página Não Encontrada',   '🔍', 'A página que procuras não existe ou foi movida.'],
    500 => ['Erro do Servidor',        '⚙️',  'Algo correu mal do nosso lado. Tenta mais tarde.'],
];

[$titulo, $icone, $descricao] = $mensagens[$codigo] ?? $mensagens[404];
http_response_code($codigo);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $codigo ?> — <?= $titulo ?> · TurmaApp</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="body-auth">
<div class="auth-wrapper">
    <div class="auth-card" style="text-align:center">
        <div style="font-size:4rem; margin-bottom:0.5rem"><?= $icone ?></div>
        <h1 style="font-size:3rem; color:#424242; margin-bottom:0.3rem; font-weight:900"><?= $codigo ?></h1>
        <h2 style="margin-bottom:0.8rem"><?= $titulo ?></h2>
        <p style="color:var(--cor-texto-suave); margin-bottom:1.5rem"><?= $descricao ?></p>
        <a href="../pages/dashboard.php" class="btn btn-primary">Ir para o Dashboard</a>
        &nbsp;
        <a href="../pages/login.php" class="btn btn-secondary">Login</a>
    </div>
</div>
</body>
</html>
