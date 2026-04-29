<?php
require_once 'config.php';
$mensagem = ""; $erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $login = trim($_POST['login']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $plano = $_POST['plano']; // Grátis, VIP ou Platinum

    // Regra de Status Inicial do Escopo
    $status = ($plano == 'Grátis') ? 'Ativo' : 'Aguardando Aprovação';
    $creditos = ($plano == 'Grátis') ? 1 : (($plano == 'VIP') ? 30 : 9999);

    try {
        $sql = "INSERT INTO usuarios (nome, login, email, senha, perfil, plano_escolhido, status_aprovacao, saldo_creditos) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $login, $email, $senha, $plano, $plano, $status, $creditos]);
        
        $mensagem = ($status == 'Ativo') ? "Conta Ativa! Pode logar." : "Cadastro recebido! Aguarde aprovação VIP.";
    } catch (PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sefullbet - Cadastro</title>
    <style>
        body { background: #080a0f; color: white; font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: #12151c; padding: 30px; border-radius: 15px; border: 1px solid #262c3a; width: 350px; }
        input, select, button { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid #262c3a; background: #080a0f; color: white; }
        button { background: #00ff88; color: black; font-weight: bold; cursor: pointer; border: none; }
        .success { color: #00ff88; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="text-align:center; color:#00ff88;">SEFULLBET</h2>
        <?php if($mensagem) echo "<p class='success'>$mensagem</p>"; ?>
        <form method="POST">
            <input type="text" name="nome" placeholder="Nome" required>
            <input type="text" name="login" placeholder="Login" required>
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <label>Escolha seu Plano:</label>
            <select name="plano">
                <option value="Grátis">Grátis (1 Crédito)</option>
                <option value="VIP">VIP (30 Créditos)</option>
                <option value="Platinum">Platinum (Ilimitado)</option>
            </select>
            <button type="submit">CADASTRAR</button>
        </form>
    </div>
</body>
</html>
