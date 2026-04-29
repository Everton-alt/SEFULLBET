<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $senha = $_POST['senha'];

    // Busca o usuário e todos os dados do novo escopo
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        // Grava os dados essenciais na sessão
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nome'] = $user['nome'];
        $_SESSION['perfil'] = $user['perfil'];
        $_SESSION['status'] = $user['status_aprovacao'];

        // Redirecionamento baseado no perfil (Escopo 1)
        if ($user['perfil'] == 'Admin' || $user['perfil'] == 'Supervisor') {
            header("Location: dashboard_admin.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        header("Location: login.php?erro=1");
        exit();
    }
}
