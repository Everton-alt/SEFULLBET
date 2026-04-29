<?php
// 1. Conexão com o banco de dados
// Certifica-te de que o caminho para o db.php está correto
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 2. Captura e limpeza de dados básicos
    $nome  = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $login = trim($_POST['login']);
    $senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];

    // 3. Validações de Segurança
    
    // Verificar se as senhas coincidem
    if ($senha !== $confirma_senha) {
        die("Erro: As senhas digitadas não são iguais.");
    }

    // Verificar se o e-mail tem um formato válido
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Erro: O e-mail informado não é válido.");
    }

    // 4. Criptografia da Senha (Segurança Máxima 🛡️)
    // Transformamos a senha num código que ninguém consegue ler
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        // 5. Preparar a inserção no PostgreSQL
        // Usamos "Prepared Statements" para evitar ataques de SQL Injection
        $sql = "INSERT INTO usuarios (nome, email, login, senha) VALUES (:nome, :email, :login, :senha)";
        $stmt = $pdo->prepare($sql);

        // Vinculamos os valores aos parâmetros da consulta
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':senha', $senha_hash);

        // 6. Executar e dar feedback
        if ($stmt->execute()) {
            echo "Cadastro realizado com sucesso! Agora já podes fazer login.";
            // Podes redirecionar o usuário após 3 segundos:
            // header("Refresh: 3; url=index.html");
        }

    } catch (PDOException $e) {
        // Caso o e-mail ou login já existam (se houver restrição UNIQUE no banco)
        if ($e->getCode() == 23505) {
            die("Erro: Este usuário ou e-mail já está registado.");
        } else {
            die("Erro ao salvar no banco: " . $e->getMessage());
        }
    }
} else {
    // Se alguém tentar aceder ao arquivo diretamente sem o formulário
    header("Location: cadastro.html");
    exit;
}
