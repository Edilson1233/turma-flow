<?php
// ============================================================
//  config/db.php — Conexão Database + Configurações Gerais
// ============================================================

// --- 1. CONFIGURAÇÕES DE AMBIENTE ---
define('IS_DEV_MODE', true);

// --- 2. CONFIGURAÇÕES DE URL ---
define('BASE_URL', 'http://localhost/turma-flow'); // CORRIGIDO: URL completa para redirecionamentos

// --- 3. CREDENCIAIS DO BANCO DE DADOS ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'turma_mvp');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Cria e retorna uma conexão PDO com o banco de dados.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (IS_DEV_MODE) {
                die("ERRO FATAL DE CONEXÃO (Dev Mode): <br>" . $e->getMessage());
            } else {
                error_log("Erro de BD: " . $e->getMessage());
                die("O sistema está temporariamente indisponível.");
            }
        }
    }

    return $pdo;
}
?>