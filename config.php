<?php
// Exibir erros para sabermos o que está acontecendo (Só para teste)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Dados exatos da sua imagem
$host   = 'dpg-d7mgjiapmmbs73c01b2g-a.oregon-postgres.render.com'; 
$port   = '5432';
$dbname = 'sefullbet';
$user   = 'sefullbetdb_2yej_user';
$pass   = 'lynqHfnkYApwjPoUczKWYeqiFXUuNYKK';

try {
    // O segredo no Render muitas vezes é o sslmode=require
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5 // Tempo limite de 5 segundos
    ]);

    // Se chegar aqui, a conexão funcionou!
} catch (PDOException $e) {
    // Se falhar, vai mostrar o erro REAL na tela
    die("ERRO DE CONEXÃO REAL: " . $e->getMessage());
}
?>
