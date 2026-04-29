<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'] ?? '';
    $senha = $_POST['senha'] ?? '';

    try {
        // 1. Busca o usuário pelo login ou e-mail
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            
            // 2. Verifica se a conta está ativa
            if ($user['status_aprovacao'] !== 'Ativo') {
                header("Location: login.php?erro=pendente");
                exit();
            }

            // 3. GRAVA A SESSÃO (Essencial para o dashb.php te reconhecer)
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['perfil'] = $user['perfil'];

            // 4. REDIRECIONA PARA O NOME CORRETO DO SEU ARQUIVO
            header("Location: dashb.php"); 
            exit();

        } else {
            // Senha ou usuário incorretos
            header("Location: login.php?erro=1");
            exit();
        }
    } catch (PDOException $e) {
        die("Erro no banco: " . $e->getMessage());
    }
}
