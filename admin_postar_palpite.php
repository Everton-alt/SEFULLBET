<?php
include 'config.php';
session_start();

// Trava de Segurança: Conforme Item 4 do Escopo
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['Admin', 'Supervisor'])) {
    die("Acesso negado.");
}

// Lógica de Publicação (Item 2.B)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo']; // Grátis ou VIP
    $liga = $_POST['liga'];
    $jogo = $_POST['jogo'];
    $entrada = $_POST['entrada'];
    $odd = $_POST['odd'];
    $criado_por = $_SESSION['usuario_id'];

    $stmt = $pdo->prepare("INSERT INTO palpites (tipo, liga, jogo, entrada, odd, criado_por) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$tipo, $liga, $jogo, $entrada, $odd, $criado_por]);
    $sucesso = "Palpite publicado com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Postar Palpite | Sefullbet ADM</title>
    <style>
        :root { --primary: #00ff88; --bg: #080a0f; --card: #12151c; --border: #262c3a; }
        body { background: var(--bg); color: white; font-family: sans-serif; padding: 40px; }
        .form-container { background: var(--card); border: 1px solid var(--border); padding: 25px; border-radius: 15px; max-width: 500px; margin: auto; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; background: #1a1e26; border: 1px solid var(--border); color: white; border-radius: 8px; box-sizing: border-box; }
        .btn-postar { background: var(--primary); color: #000; width: 100%; padding: 15px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1rem; }
        .msg { color: var(--primary); text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2 style="text-align: center; color: var(--primary);">NOVA TIP (PALPITE)</h2>
        <?php if(isset($sucesso)) echo "<p class='msg'>$sucesso</p>"; ?>
        
        <form method="POST">
            <label>Tipo de Acesso:</label>
            <select name="tipo" required>
                <option value="Grátis">Grátis (Visível para todos)</option>
                <option value="VIP">VIP (Com cadeado para Grátis)</option>
            </select>

            <input type="text" name="liga" placeholder="Ex: Premier League" required>
            <input type="text" name="jogo" placeholder="Ex: Arsenal vs Liverpool" required>
            <input type="text" name="entrada" placeholder="Ex: Over 2.5 Gols" required>
            <input type="number" step="0.01" name="odd" placeholder="Ex: 1.85" required>

            <button type="submit" class="btn-postar">PUBLICAR PALPITE</button>
        </form>
        <br>
        <a href="dashboard.php" style="color: #a0aec0; text-decoration: none; display: block; text-align: center;">← Voltar</a>
    </div>

</body>
</html>
