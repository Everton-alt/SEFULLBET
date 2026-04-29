<?php
// Arquivo de Configuração Central - Sefullbet
// Este arquivo conecta seu PHP ao PostgreSQL no Render

// Pegamos a URL do Banco de Dados das Variáveis de Ambiente do Render
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    // O Render fornece a URL no formato postgresql://sefullbet_user:kmWspOQXsHqx4hMI5DLnbvCXj0jJt9Vs@dpg-d7mgjiapmmbs73c01b2g-a/sefullbet
    // Vamos converter para o formato DSN que o PHP (PDO) entende
    $url = parse_url($databaseUrl);

    $host = $url['host'];
    $port = $url['port'];
    $db   = ltrim($url['path'], '/');
    $user = $url['user'];
    $pass = $url['pass'];

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
} else {
    // Caso você esteja testando localmente sem Docker
    die("Erro: DATABASE_URL não encontrada.");
}

try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
?>
