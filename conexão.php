<?php
// conexao.php
$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    // Extrai as informações da URL do Render
    $url = parse_url($dbUrl);
    
    $host = $url["host"];
    $port = $url["port"];
    $user = $url["user"];
    $pass = $url["pass"];
    $dbname = ltrim($url["path"], '/');

    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Importante para o Render:
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // echo "Conectado com sucesso!";
    } catch (PDOException $e) {
        die("Erro na conexão: " . $e->getMessage());
    }
} else {
    die("Variável DATABASE_URL não configurada.");
}
?>
