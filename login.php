<?php
require_once 'config/db.php';
require_once 'modules/auth_logic.php';

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = $_POST['login'];
    $senha_input = $_POST['senha'];

    // Busca o usuário no banco
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ? OR email = ?");
    $stmt->execute([$login_input, $login_input]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha_input, $user['senha'])) {
        // Verifica status para VIP/Platinum (Grátis entra direto)
        if ($user['status_aprovacao'] == 'Ativo' || $user['perfil'] == 'Grátis') {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['nome'];
            $_SESSION['usuario_perfil'] = $user['perfil'];
            $_SESSION['usuario_creditos'] = $user['creditos'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $erro = "Sua conta (".$user['perfil'].") ainda aguarda aprovação do Admin.";
        }
    } else {
        // Validação extra: Verifica se é o Admin configurado no Render
        if ($login_input == getenv('ADMIN_EMAIL') && $senha_input == getenv('ADMIN_PASSWORD')) {
             $erro = "Conta Admin encontrada nas variáveis, mas precisa ser criada no Banco SQL primeiro.";
        } else {
             $erro = "Login ou senha incorretos.";
        }
    }
}
?>
<!-- O HTML do login deve enviar os campos 'login' e 'senha' via POST -->
