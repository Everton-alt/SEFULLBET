<?php
// 1. Iniciar a sessão e configurar erros
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. CAMINHO CORRIGIDO: Carregar a conexão
// Se o seu arquivo se chama config.php e está na raiz, use assim:
require_once 'config.php'; 

// 3. LÓGICA DE PROCESSAMENTO
$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = $_POST['login'] ?? '';
    $senha_input = $_POST['senha'] ?? '';

    if (!empty($login_input) && !empty($senha_input)) {
        try {
            // Buscamos o usuário pelo login ou email
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ? OR email = ?");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha_input, $user['senha'])) {
                // Salvamos os dados essenciais na sessão conforme o Escopo Mestre
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome'];
                $_SESSION['perfil'] = $user['perfil'];
                $_SESSION['status'] = $user['status_aprovacao'];
                
                // Redireciona para a Dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $erro = "Login ou senha incorretos.";
            }
        } catch (PDOException $e) {
            // Se o erro persistir, ele vai mostrar aqui o detalhe real do banco
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
        .login-card { background: #12151c; padding: 40px; border-radius: 15px; border: 1px solid #262c3a; width: 100%; max-width: 350px; text-align: center; }
        h2 { color: #00ff88; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #262c3a; background: #080a0f; color: white; box-sizing: border-box; }
        button { width: 100%; padding: 12px; border: none; border-radius: 8px; background: #00ff88; color: #000; font-weight: bold; cursor: pointer; text-transform: uppercase; }
        button:hover { background: #00cc6e; }
        .error { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem; }
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
    </div>
</body>
</html>
