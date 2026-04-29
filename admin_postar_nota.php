<?php
include 'conexao.php';
session_start();

// Segurança: Admin e Supervisor (Escopo Item 4)
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['Admin', 'Supervisor'])) {
    die("Acesso negado.");
}

// Lógica de Postagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conteudo = $_POST['conteudo'];
    $cor = $_POST['cor_destaque']; // Ex: #00ff88 (Neon)
    $prioridade = $_POST['prioridade'];

    $stmt = $pdo->prepare("INSERT INTO notas (conteudo, cor_destaque, prioridade) VALUES (?, ?, ?)");
    $stmt->execute([$conteudo, $cor, $prioridade]);
    $msg = "Nota publicada com sucesso!";
}

// Busca notas existentes para gestão
$notas = $pdo->query("SELECT * FROM notas WHERE pendente_exclusao = FALSE ORDER BY prioridade DESC, criado_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Postar Avisos | Sefullbet ADM</title>
    <style>
        :root { --primary: #00ff88; --bg: #080a0f; --card: #12151c; }
        body { background: var(--bg); color: white; font-family: sans-serif; padding: 30px; }
        .form-nota { background: var(--card); padding: 20px; border-radius: 10px; border: 1px solid #262c3a; margin-bottom: 30px; }
        textarea { width: 100%; background: #1a1e26; border: 1px solid #262c3a; color: white; padding: 10px; border-radius: 5px; height: 100px; }
        .btn-postar { background: var(--primary); border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .nota-item { background: #1a1e26; padding: 10px; margin-bottom: 5px; border-left: 4px solid var(--primary); display: flex; justify-content: space-between; }
    </style>
</head>
<body>

    <h1>📢 Gerenciar Avisos (Carrossel)</h1>

    <div class="form-nota">
        <form method="POST">
            <label>Conteúdo da Nota:</label><br>
            <textarea name="conteudo" placeholder="Digite aqui o aviso ou estratégia..." required></textarea>
            
            <div style="margin-top: 10px;">
                <label>Cor do Destaque:</label>
                <input type="color" name="cor_destaque" value="#00ff88">
                
                <label style="margin-left: 20px;">Prioridade (0-10):</label>
                <input type="number" name="prioridade" value="0" min="0" max="10">
            </div>

            <button type="submit" class="btn-postar">PUBLICAR NO CARROSSEL</button>
        </form>
    </div>

    <h3>Avisos Ativos:</h3>
    <?php foreach ($notas as $n): ?>
        <div class="nota-item">
            <span><?php echo $n['conteudo']; ?></span>
            <a href="admin_acoes_geral.php?tabela=notas&id=<?php echo $n['id']; ?>&acao=excluir" style="color: #ff4d4d; text-decoration: none;">[X]</a>
        </div>
    <?php endforeach; ?>

    <br>
    <a href="dashboard.php" style="color: #a0aec0;">← Voltar</a>
</body>
</html>
