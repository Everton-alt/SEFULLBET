<?php
// Habilita exibição de erros para debug total
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    include 'config.php';
} catch (Exception $e) {
    die("Erro ao carregar o config.php: " . $e->getMessage());
}

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'] ?? '';
    $senha = $_POST['senha'] ?? '';

    try {
        // Busca o usuário
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            // Grava os dados na sessão
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['perfil'] = $user['perfil'];
            $_SESSION['status'] = $user['status_aprovacao'];

            // Redirecionamento (Escopo Mestre)
            if (in_array($user['perfil'], ['Admin', 'Supervisor'])) {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            // Se o login falhar mas a conexão estiver OK
            header("Location: login.php?erro=1");
            exit();
        }

    } catch (PDOException $e) {
        // Se der erro no banco de dados, vai mostrar EXATAMENTE o motivo aqui
        die("ERRO DE BANCO DE DADOS: " . $e->getMessage());
    }
} else {
    header("Location: login.php");
    exit();
}
