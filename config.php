<?php
/**
 * CONFIGURAÇÃO DE BANCO DE DADOS - SEFULLBET (VERSÃO EXTERNA)
 */

// O segredo está no final do host: .oregon-postgres.render.com
$host   = 'dpg-d7mgjiapmmbs73c01b2g-a.oregon-postgres.render.com'; 
$port   = '5432';
$dbname = 'sefullbet';
$user   = 'sefullbetdb_2yej_user';
$pass   = 'lynqHfnkYApwjPoUczKWYeqiFXUuNYKK';

try {
    // Montagem do DSN com SSL obrigatório
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass);
    
    // Configurações extras de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Isso vai nos mostrar o erro exato se falhar de novo
    echo "Falha na conexão: " . $e->getMessage();
    exit;
}
?>
