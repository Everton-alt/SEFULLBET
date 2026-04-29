<?php
/**
 * CONFIGURAÇÃO DE BANCO DE DADOS - SEFULLBET
 * Dados extraídos conforme as credenciais do Render
 */

$host   = 'dpg-d7mgjiapmmbs73c01b2g-a.oregon-postgres.render.com'; 
$port   = '5432';
$dbname = 'sefullbet';
$user   = 'sefullbetdb_2yej_user';
$pass   = 'lynqHfnkYApwjPoUczKWYeqiFXUuNYKK';

try {
    // DSN específico para PostgreSQL com SSL obrigatório (exigência do Render)
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Conexão bem-sucedida
} catch (PDOException $e) {
    // Se houver erro, exibe a mensagem real para diagnóstico
    die("Erro de Conexão Sefullbet: " . $e->getMessage());
}
?>
