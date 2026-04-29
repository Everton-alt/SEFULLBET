<?php
// 1. Iniciar a sessão e configurar erros
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Carregar a conexão
require_once 'config.php'; 

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = $_POST['login'] ?? '';
    $senha_input = $_POST['senha'] ?? '';

    if (!empty($login_input) && !empty($senha_input)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ? OR email = ?");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha_input, $user['senha'])) {
                
                // VERIFICAÇÃO DE STATUS (Escopo Mestre)
                if ($user['status_aprovacao'] !== 'Ativo') {
                    $erro = "Sua conta está aguardando aprovação do administrador.";
                } else {
                    // Salva na sessão
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['nome'] = $user['nome'];
                    $_SESSION['perfil'] = $user['perfil'];
                    
                    // REDIRECIONAMENTO CORRIGIDO PARA dashb.php
                    header("Location: Dashboard.php");
                    exit();
                }
            } else {
                $erro = "Login ou senha incorretos.";
            }
        } catch (PDOException $e) {
            $erro = "Erro de conexão: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sefullbet</title>
    <style>
        body { background: #080a0f; color: white; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: #12151c; padding: 40px; border-radius: 15px; border: 1px solid #262c3a; width: 100%; max-width: 350px; text-align: center; box-shadow: 0 0 20px rgba(0,255,136,0.1); }
        h2 { color: #00ff88; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #262c3a; background: #080a0f; color: white; box-sizing: border-box; outline: none; }
        input:focus { border-color: #00ff88; }
        button { width: 100%; padding: 12px; border: none; border-radius: 8px; background: #00ff88; color: #000; font-weight: bold; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        button:hover { background: #00cc6e; box-shadow: 0 0 15px rgba(0, 255, 136, 0.4); }
        .error { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.85rem; border: 1px solid rgba(255, 77, 77, 0.3); }
        .links { margin-top: 20px; font-size: 0.8rem; }
        .links a { color: #00ff88; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Sefullbet</h2>
        
        <?php if ($erro): ?>
            <div class="error"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="login" placeholder="E-mail ou Usuário" required>
            <input type="password" name="senha" placeholder="Sua Senha" required>
            <button type="submit">Entrar no Sistema</button>
        </form>

        <div class="links">
            Não tem conta? <a href="cadastro.php">Cadastre-se</a>
        </div>
    </div>
</body>
</html>
