<?php
// Configurações de Administrador e Segurança
define('ADMIN_EMAIL', 'admin@sefullbet.com');
define('ADMIN_PASSWORD', 'Bobba123');
define('JWT_SECRET', 'sefullbet-chave-secreta-2026-ultra-segura');

// Credenciais extraídas da sua DATABASE_URL do Render
$host     = 'dpg-d7mgjiapmmbs73c01b2g-a.oregon-postgres.render.com'; // Host interno/externo do Render
$port     = '5432';
$dbname   = 'sefullbet';
$user     = 'sefullbet_user';
$password = 'kmWspOQXsHqx4hMI5DLnbvCXj0jJt9Vs';

try {
    // String de conexão PDO para PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erro
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Retorna dados como array associativo
        PDO::ATTR_EMULATE_PREPARES => false, // Segurança extra contra SQL Injection
    ]);

} catch (PDOException $e) {
    // Log de erro (em produção, o ideal é não exibir detalhes ao usuário)
    die("Erro crítico de conexão: Verifique as credenciais do banco de dados.");
}
?>
