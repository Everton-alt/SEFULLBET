<?php
include 'conexao.php'; // Ajustado para o seu arquivo
session_start();

// Trava de Segurança (Item 4 do Escopo)
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['Admin', 'Supervisor'])) {
    die("Acesso negado.");
}

// Lógica para marcar Resultado (Green/Red)
if (isset($_GET['id']) && isset($_GET['resultado'])) {
    $id = $_GET['id'];
    $res = $_GET['resultado']; // 'Green' ou 'Red'

    $stmt = $pdo->prepare("UPDATE palpites SET resultado = ? WHERE id = ?");
    $stmt->execute([$res, $id]);
    header("Location: admin_vitorias.php?status=atualizado");
}

// Busca apenas palpites que ainda estão "Pendente"
$stmt = $pdo->query("SELECT * FROM palpites WHERE resultado = 'Pendente' AND pendente_exclusao = FALSE ORDER BY criado_at DESC");
$pendentes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Módulo de Vitórias | Sefullbet</title>
    <style>
        :root { --primary: #00ff88; --bg: #080a0f; --card: #12151c; --danger: #ff4d4d; }
        body { background: var(--bg); color: white; font-family: sans-serif; padding: 30px; }
        .grid-vitorias { display: grid; gap: 15px; }
        .card-palpite { background: var(--card); border: 1px solid #262c3a; padding: 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; }
        .btn-green { background: var(--primary); color: #000; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-right: 10px; }
        .btn-red { background: var(--danger); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .info-jogo { font-size: 1.1rem; }
        .badge-tipo { color: var(--primary); font-size: 0.8rem; border: 1px solid var(--primary); padding: 2px 5px; border-radius: 4px; }
    </style>
</head>
<body>

    <h1 style="color: var(--primary);">🏆 Gestão de Resultados</h1>
    <p>Marque os Greens e Reds para atualizar a assertividade do sistema.</p>

    <div class="grid-vitorias">
        <?php if (empty($pendentes)): ?>
            <p>Nenhum palpite pendente para conferência.</p>
        <?php endif; ?>

        <?php foreach ($pendentes as $p): ?>
            <div class="card-palpite">
                <div class="info-jogo">
                    <span class="badge-tipo"><?php echo $p['tipo']; ?></span> <br>
                    <strong><?php echo $p['jogo']; ?></strong> <br>
                    <small style="color: #a0aec0;"><?php echo $p['liga']; ?> | Entrou em: <?php echo $p['entrada']; ?> (@<?php echo $p['odd']; ?>)</small>
                </div>
                <div>
                    <a href="?id=<?php echo $p['id']; ?>&resultado=Green" class="btn-green">GREEN</a>
                    <a href="?id=<?php echo $p['id']; ?>&resultado=Red" class="btn-red">RED</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <br><br>
    <a href="dashboard.php" style="color: #a0aec0; text-decoration: none;">← Voltar para Dashboard</a>

</body>
</html>
