<?php
// Configurações do Banco de Dados no Render
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // Extrai os dados da URL do Render (postgres://user:pass@host:port/dbname)
    $db_config = parse_url($database_url);

    $host = $db_config['host'];
    $port = isset($db_config['port']) ? $db_config['port'] : '5432'; // Se não achar a porta, usa 5432
    $user = $db_config['user'];
    $pass = $db_config['pass'];
    $dbname = ltrim($db_config['path'], '/');

    try {
        // DSN formatado corretamente para PostgreSQL
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
} else {
    die("Variável DATABASE_URL não encontrada. Verifique as configurações no Render.");
}
?>
