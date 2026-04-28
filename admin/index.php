<?php
require_once '../config/db.php';
require_once '../modules/auth_logic.php';
verificarLogin();
verificarAcessoAdm('Supervisor');

// Resumo para os Cards
$totalUsers = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$pendentes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE status_aprovacao = 'Aguardando Aprovação'")->fetchColumn();
$exclusoes = $pdo->query("SELECT COUNT(*) FROM palpites WHERE exclusao_pendente = TRUE")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link rel="stylesheet" href="../public/css/style.css">
    <title>Sefullbet - Admin</title>
</head>
<body class="admin-panel">
    <nav class="sidebar">
        <h2>Sefullbet ADM</h2>
        <a href="modules/usuarios.php">Gestão de Usuários</a>
        <a href="modules/importar.php">Importar Planilha</a>
        <a href="modules/pendentes.php">Aprovações & Pendentes (<?php echo $exclusoes; ?>)</a>
        <a href="../public/dashboard.php">Voltar ao Site</a>
    </nav>
    <main>
        <h1>Painel Geral</h1>
        <div class="stats-grid">
            <div class="card">Total Usuários: <?php echo $totalUsers; ?></div>
            <div class="card warning">Aprovações Pendentes: <?php echo $pendentes; ?></div>
        </div>
    </main>
</body>
</html>