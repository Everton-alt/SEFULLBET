<?php
// Inicia a sessão para manter o usuário conectado
session_start();

// 1. Inclui a conexão com o banco e as constantes de Admin
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Captura os dados do formulário de login (popup do Index)
    $login_input = trim($_POST['login']);
    $senha_input = $_POST['senha'];

    try {
        // 2. Busca o usuário no banco de dados pelo Login ou E-mail
        $stmt = $pdo->prepare("SELECT id, nome, email, login, senha, plano FROM usuarios WHERE login = :login OR email = :login");
        $stmt->execute(['login' => $login_input]);
        $user = $stmt->fetch();

        // 3. Verificação de Credenciais
        if ($user && password_verify($senha_input, $user['senha'])) {
            
            // Login bem-sucedido! Criamos as variáveis de sessão:
            $_SESSION['usuario_id']    = $user['id'];
            $_SESSION['usuario_nome']  = $user['nome'];
            $_SESSION['usuario_plano'] = $user['plano'];

            // 4. Verificação Especial: É o Administrador?
            // Compara com as informações que você passou no db.php
            if ($user['email'] === ADMIN_EMAIL && $senha_input === ADMIN_PASSWORD) {
                $_SESSION['is_admin'] = true;
                header("Location: admin_dashboard.php"); // Redireciona para área master
                exit;
            }

            // Se for usuário comum, vai para o Dashboard padrão
            header("Location: dashboard.php");
            exit;

        } else {
            // Se as credenciais estiverem erradas
            echo "<script>
                    alert('Usuário ou senha incorretos.');
                    window.location.href = 'index.html';
                  </script>";
        }

    } catch (PDOException $e) {
        die("Erro no sistema: " . $e->getMessage());
    }

} else {
    // Se tentar acessar o arquivo diretamente sem postar o formulário
    header("Location: index.html");
    exit;
}
