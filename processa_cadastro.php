<?php
/**
 * PROCESSA CADASTRO - SEFULLBET
 * Este arquivo recebe os dados via POST e insere no Banco de Dados
 */

// 1. Configurações de erro e conexão
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php'; // Usa o config que configuramos com o host externo

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Captura e limpa os dados recebidos
    $nome  = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? 'Grátis';

    // Validação básica
    if (!$nome || !$login || !$email || !$senha) {
        die("Erro: Por favor, preencha todos os campos obrigatórios.");
    }

    try {
        // 2. Verifica se o login ou e-mail já existem para não duplicar
        $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE login = ? OR email = ?");
        $stmt_check->execute([$login, $email]);

        if ($stmt_check->rowCount() > 0) {
            die("Erro: Este usuário ou e-mail já está cadastrado no sistema.");
        }

        // 3. Criptografia da senha (Segurança)
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // 4. Preparação da Query de Inserção (Batendo com as colunas do seu print)
        // Definimos: status_aprovacao = 'Aguardando Aprovação' e saldo_creditos = 0
        $sql = "INSERT INTO usuarios (
                    nome, 
                    login, 
                    email, 
                    senha, 
                    perfil, 
                    status_aprovacao, 
                    saldo_creditos,
                    creditos,
                    termos_aceitos,
                    pendente_exclusao
                ) VALUES (?, ?, ?, ?, ?, 'Aguardando Aprovação', 0, 0, true, false)";

        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute([
            $nome, 
            $login, 
            $email, 
            $senha_hash, 
            $perfil
        ]);

        if ($res) {
            // Sucesso: Redireciona para uma página de aviso ou login com mensagem
            echo "<script>
                    alert('Cadastro realizado com sucesso! Aguarde a aprovação do administrador.');
                    window.location.href = 'login.php';
                  </script>";
        }

    } catch (PDOException $e) {
        // Caso ocorra erro de banco (ex: coluna faltando)
        die("Erro Crítico ao Salvar no Banco: " . $e->getMessage());
    }

} else {
    // Se tentarem acessar o arquivo direto sem POST, manda de volta pro cadastro
    header("Location: cadastro.php");
    exit();
}
