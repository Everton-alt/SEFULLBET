<?php
// 1. CONFIGURAÇÕES DE SESSÃO (Executar antes de iniciar a sessão)
$tempo_sessao = 30 * 24 * 60 * 60; // 30 dias

// Verifica se já existe uma sessão ativa e a interrompe para aplicar as novas configurações
if (session_status() !== PHP_SESSION_NONE) {
    session_write_close();
}

// Agora sim, definimos as configurações com a sessão fechada
ini_set('session.gc_maxlifetime', $tempo_sessao);
ini_set('session.cookie_lifetime', $tempo_sessao);

session_set_cookie_params([
    'lifetime' => $tempo_sessao,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Inicia a sessão com as novas regras
session_start();

// 2. EXIBIÇÃO DE ERROS
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 3. DADOS DE CONEXÃO
$host   = 'dpg-d7mgjiapmmbs73c01b2g-a.oregon-postgres.render.com'; 
$port   = '5432';
$dbname = 'sefullbet';
$user   = 'sefullbetdb_2yej_user';
$pass   = 'lynqHfnkYApwjPoUczKWYeqiFXUuNYKK';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10 
    ]);
} catch (PDOException $e) {
    die("ERRO DE CONEXÃO: " . $e->getMessage());
}
