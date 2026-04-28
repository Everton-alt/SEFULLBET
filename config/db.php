<?php
// config/db.php

// Pega a URL completa do banco de dados das Environment Variables do Render
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    // Extrai os componentes (postgresql://sefullbet_user:kmWspOQXsHqx4hMI5DLnbvCXj0jJt9Vs@dpg-d7mgjiapmmbs73c01b2g-a/sefullbet)
    $dbConfig = parse_url($databaseUrl);

    $host = $dbConfig['host'];
    $user = $dbConfig['user'];
    $pass = $dbConfig['pass'];
    $db   = ltrim($dbConfig['path'], '/');
    $port = $dbConfig['port'] ?? 5432;

    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
} else {
    // Fallback caso a variável não seja encontrada (ajuda no debug local)
    die("Erro crítico: A variável DATABASE_URL não foi detectada no ambiente Render.");
}

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Falha na conexão com o banco de dados Sefullbet: " . $e->getMessage());
}
?>