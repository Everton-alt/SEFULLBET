<?php
// 1. Configurações de Administrador e Segurança (Mantidas)
define('ADMIN_EMAIL', 'admin@sefullbet.com');
define('ADMIN_PASSWORD', 'Bobba123');
define('JWT_SECRET', 'sefullbet-chave-secreta-2026-ultra-segura');

// 2. Credenciais ATUALIZADAS conforme sua foto do Render
$host     = 'dpg-d7mgjiapmmbs73c01b2g-a.oregon-postgres.render.com'; 
$port     = '5432';
$dbname   = 'sefullbet';
$user     = 'sefullbetdb_2yej_user'; // Corrigido conforme a foto
$password = 'lynqHfnkYApwjPoUczKWYeqiFXUuNYKK'; // Corrigido conforme a foto

try {
    // 3. String de conexão com SSL OBRIGATÓRIO para o Render
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

} catch (PDOException $e) {
    // 4. MUDANÇA CRUCIAL: Agora ele vai mostrar o erro REAL se falhar
    die("ERRO DE CONEXÃO REAL: " . $e->getMessage());
}
?>
