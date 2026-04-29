<?php
// 1. Configurações de erro e conexão
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Caminho corrigido para a raiz
require_once 'config.php'; 

$mensagem = "";
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome  = $_POST['nome'] ?? '';
    $login = $_POST['login'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? 'Grátis'; // VIP ou Platinum conforme escolha

    if (!empty($nome) && !empty($login) && !empty($senha)) {
        try {
            // Verifica se o login já existe
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE login = ? OR email = ?");
            $check->execute([$login, $email]);
            
            if ($check->rowCount() > 0) {
                $mensagem = "Este usuário ou e-mail já está cadastrado.";
            } else {
                // Criptografa a senha
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                // Insere conforme Escopo: Status sempre 'Aguardando Aprovação' para novos
                $sql = "INSERT INTO usuarios (nome, login, email, senha, perfil, status_aprovacao, saldo_creditos) 
                        VALUES (?, ?, ?, ?, ?, 'Aguardando Aprovação', 0)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nome, $login, $email, $senhaHash, $perfil]);

                $sucesso = true;
                $mensagem = "Cadastro realizado! Aguarde a aprovação do administrador para acessar.";
            }
        } catch (PDOException $e) {
            // Aqui matamos o erro de conexão/banco
            $mensagem = "Erro no banco de dados: " . $e->getMessage();
        }
    } else {
        $mensagem = "Preencha todos os campos obrigatórios.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - Sefullbet</title>
    <style>
        body { background: #080a0f; color: white; font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #12151c; padding: 30px; border-radius: 15px; border: 1px solid #262c3a; width: 100%; max-width: 400px; }
        h2 { color: #00ff88; text-align: center; margin-bottom: 25px; }
        input, select { width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #262c3a; background: #080a0f; color: white; box-sizing: border-box; }
        button { width: 100%; padding: 12px; border: none; border-radius: 8px; background: #00ff88; color: #000; font-weight: bold; cursor: pointer; }
        .msg { padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-size: 0.9rem; }
        .error { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; }
        .success { background: rgba(0, 255, 136, 0.1); color: #00ff88; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Criar Conta Sefullbet</h2>
        
        <?php if ($mensagem): ?>
            <div class="msg <?php echo $sucesso ? 'success' : 'error'; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <?php if (!$sucesso): ?>
        <form method="POST">
            <input type="text" name="nome" placeholder="Nome Completo" required>
            <input type="text" name="login" placeholder="Usuário (Login)" required>
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="senha" placeholder="Senha" required>
            
            <label style="display:block; margin-bottom: 5px; font-size: 0.8rem; color: #a0aec0;">Escolha seu Plano:</label>
            <select name="perfil">
                <option value="Grátis">Grátis (Testes)</option>
                <option value="VIP">VIP (30 Créditos)</option>
                <option value="Platinum">Platinum (Ilimitado)</option>
            </select>

            <button type="submit">SOLICITAR ACESSO</button>
        </form>
        <?php else: ?>
            <a href="login.php" style="color: #00ff88; display: block; text-align: center; text-decoration: none;">Ir para Login</a>
        <?php insert_id; endif; ?>
    </div>
</body>
</html>
