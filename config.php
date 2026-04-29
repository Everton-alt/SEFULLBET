<?php
// Captura a URL do Render
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // Decompõe a URL: postgresql://sefullbetdb_2yej_user:lynqHfnkYApwjPoUczKWYeqiFXUuNYKK@dpg-d7mgjiapmmbs73c01b2g-a.oregon-postgres.render.com/sefullbet
    $db_config = parse_url($database_url);

    $host = $db_config['host'];
    // Se a porta não existir na URL, o PHP usa a padrão 5432
    $port = isset($db_config['port']) ? $db_config['port'] : '5432';
    $user = $db_config['user'];
    $pass = $db_config['pass'];
    // Remove a barra "/" do início do caminho para pegar o nome do banco
    $dbname = ltrim($db_config['path'], '/');

    try {
        // Monta o DSN (Data Source Name) de forma limpa
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Importante para garantir estabilidade no Render
            PDO::ATTR_PERSISTENT => true 
        ]);

    } catch (PDOException $e) {
        // Se der erro, mostra uma mensagem limpa
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
} else {
    die("Erro Crítico: A variável DATABASE_URL não foi encontrada no ambiente.");
}
?>
