<?php
// conexao.php - Versão Sefullbet Pro
$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    // Extrai as informações da URL do Render
    $url = parse_url($dbUrl);
    
    $host = $url["host"];
    $port = $url["port"] ?? 5432; // Define 5432 como padrão se não houver na URL
    $user = $url["user"];
    $pass = $url["pass"];
    $dbname = ltrim($url["path"], '/');

    try {
        // Adicionado: charset=utf8 para evitar erros de acentos nas Tips
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--client_encoding=UTF8'";
        
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Adicionado: Mantém a conexão persistente para maior velocidade no Analisador
            PDO::ATTR_PERSISTENT => true,
            // Evita conversão de tipos numéricos (importante para odds e créditos)
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Conexão bem-sucedida
    } catch (PDOException $e) {
        // Em produção, você pode trocar o die por um log silencioso
        die("Erro técnico na conexão: " . $e->getMessage());
    }
} else {
    die("Configuração Crítica: DATABASE_URL ausente no ambiente Render.");
}
?>
