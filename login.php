<?php
// 1. Iniciar a sessão e configurar erros
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Carregar a conexão
require_once 'config.php'; 

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Captura e limpa espaços extras
    $login_input = isset($_POST['login']) ? trim($_POST['login']) : '';
    $senha_input = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if (!empty($login_input) && !empty($senha_input)) {
        try {
            // Busca o usuário pelo login ou e-mail
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ? OR email = ?");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch();

            // Verifica se o usuário existe e se a senha (hash) é compatível
            if ($user && password_verify($senha_input, $user['senha'])) {
                
                // VERIFICAÇÃO DE STATUS (Conforme o Escopo Mestre)
                if ($user['status_aprovacao'] !== 'Ativo') {
                    $erro = "Sua conta está: " . $user['status_aprovacao'] . ". Aguarde a liberação do Admin.";
                } else {
                    // Define as variáveis de sessão
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['nome']       = $user['nome'];
                    $_SESSION['perfil']     = $user['perfil'];
                    
                    // REDIRECIONAMENTO MANTIDO PARA dashboard.php
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $erro = "Usuário ou senha inválidos.";
            }
        } catch (PDOException $e) {
            $erro = "Erro técnico. Por favor, tente novamente mais tarde.";
        }
    } else {
        $erro = "Preencha todos os campos.";
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
        body { background: #080a0f; color: white; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: #12151c; padding: 40px; border-radius: 15px; border: 1px solid #262c3a; width: 100%; max-width: 350px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h2 { color: #00ff88; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 2px; font-weight: 800; }
        input { width: 100%; padding: 14px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #262c3a; background: #080a0f; color: white; box-sizing: border-box; outline: none; transition: 0.3s; }
        input:focus { border-color: #00ff88; }
        button { width: 100%; padding: 14px; border: none; border-radius: 8px; background: #00ff88; color: #000; font-weight: bold; cursor: pointer; text-transform: uppercase; transition: 0.3s; margin-top: 10px; }
        button:hover { background: #00cc6e; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 255, 136, 0.3); }
        .error { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid rgba(255, 77, 77, 0.3); }
        .links { margin-top: 25px; font-size: 0.85rem; color: #a0aec0; }
        .links a { color: #00ff88; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Sefullbet</h2>
        
        <?php if ($erro): ?>
            <div class="error"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="login" placeholder="Usuário ou E-mail" required>
            <input type="password" name="senha" placeholder="Sua Senha" required>
            <button type="submit">Entrar no Sistema</button>
        </form>

        <div class="links">
            Não tem conta? <a href="cadastro.php">Registrar Agora</a>
        </div>
    </div>
</body>
</html>
