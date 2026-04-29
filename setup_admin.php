<?php
require_once 'config.php';

try {
    // 1. Limpa qualquer tentativa mal sucedida
    $pdo->exec("DELETE FROM usuarios WHERE login = 'admin'");

    // 2. Prepara o INSERT completo
    // A senha será: admin123
    $nome  = 'Administrador Sefullbet';
    $login = 'admin';
    $email = 'admin@sefullbet.com';
    $senha = password_hash('admin123', PASSWORD_DEFAULT);
    $perfil = 'Admin';
    $status = 'Ativo';
    $saldo  = 1000;

    $sql = "INSERT INTO usuarios (nome, login, email, senha, perfil, status_aprovacao, saldo_creditos) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nome, $login, $email, $senha, $perfil, $status, $saldo]);

    echo "<h1>✅ Sucesso!</h1>";
    echo "<p>Usuário <b>admin</b> criado com a senha <b>admin123</b></p>";
    echo "<a href='login.php'>Ir para o Login</a>";

} catch (PDOException $e) {
    echo "<h1>❌ Erro ao criar:</h1> " . $e->getMessage();
}
?>
