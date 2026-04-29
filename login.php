<?php
// 1. PRIMEIRA COISA: Iniciar a sessão e configurar erros
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. SEGUNDA COISA: Carregar a conexão com o banco
// Usamos o caminho direto já que o arquivo está na raiz
require_once 'config/db.php';

// 3. LÓGICA DE PROCESSAMENTO (O que acontece quando clicam em "Entrar")
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
                // Se os dados estão certos, salvamos na sessão e mandamos para o dashboard
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $erro = "Login ou senha incorretos.";
            }
        } catch (PDOException $e) {
            $erro = "Erro no banco: " . $e->getMessage();
        }
    }
}

// 4. FECHAMENTO DO PHP: Agora o código sai do "modo programação" 
// e entra no "modo visual" (HTML)
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Sefullbet</title>
</head>
<body>
    <h2>Entrar no Sefullbet</h2>
    
    <?php if ($erro): ?>
        <p style="color: red;"><?php echo $erro; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="login" placeholder="E-mail ou Usuário" required><br><br>
        <input type="password" name="senha" placeholder="Sua Senha" required><br><br>
        <button type="submit">Entrar</button>
    </form>
</body>
</html>
