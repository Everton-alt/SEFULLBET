<?php
// 1. CONFIGURAÇÕES DE SESSÃO (Para parar de deslogar)
$tempo_sessao = 30 * 24 * 60 * 60; // 30 dias em segundos

// Define o tempo que a sessão dura no servidor
ini_set('session.gc_maxlifetime', $tempo_sessao);

// Define o tempo que o cookie dura no navegador do usuário
ini_set('session.cookie_lifetime', $tempo_sessao);

// Configurações extras de segurança e estabilidade para o cookie
session_set_cookie_params([
    'lifetime' => $tempo_sessao,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Inicia a sessão se ela ainda não existir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. EXIBIÇÃO DE ERROS
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 3. DADOS DE CONEXÃO (RENDER)
$host   = 'dpg-d7mgjiapmmbs73c01b2g-a.oregon-postgres.render.com'; 
$port   = '5432';
$dbname = 'sefullbet';
$user   = 'sefullbetdb_2yej_user';
$pass   = 'lynqHfnkYApwjPoUczKWYeqiFXUuNYKK';

try {
    // O DSN para PostgreSQL no Render com SSL obrigatório
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10 // Aumentei um pouco o timeout para evitar quedas em redes instáveis
    ]);

} catch (PDOException $e) {
    die("ERRO DE CONEXÃO REAL: " . $e->getMessage());
}
?>
