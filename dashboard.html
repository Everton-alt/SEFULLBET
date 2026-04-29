<?php
// 1. Iniciar a sessão e verificar se o usuário está logado
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit;
}

// 2. Conectar ao banco e buscar os dados do usuário
require_once 'config/db.php';
$stmt = $pdo->prepare("SELECT nome, login, creditos, plano FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $_SESSION['usuario_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel SeFull Bet</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dash-container { display: flex; min-height: 100vh; }
        
        /* Sidebar Neon */
        .sidebar { width: 260px; background: #0b0e14; border-right: 1px solid var(--border); padding: 30px 20px; }
        .nav-link { display: flex; align-items: center; gap: 15px; color: var(--text-dim); text-decoration: none; padding: 15px; border-radius: 12px; margin-bottom: 10px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: rgba(0, 255, 136, 0.1); color: var(--primary); }

        /* Área Principal */
        .main-content { flex: 1; padding: 40px; background: #080a0f; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-top: 30px; }
        .stat-card { background: var(--card-bg); padding: 30px; border-radius: 20px; border: 1px solid var(--border); }
        .stat-card h3 { color: var(--text-dim); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
        .stat-value { font-size: 2.2rem; font-weight: 900; color: #fff; margin-top: 10px; }
    </style>
</head>
<body>

<div class="dash-container">
    <aside class="sidebar">
        <div style="font-weight: 900; font-size: 1.5rem; color: #fff; margin-bottom: 40px;">SEFULL<span style="color: var(--primary);">BET</span></div>
        
        <nav>
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Início</a>
            <a href="feed.php" class="nav-link"><i class="fas fa-rss"></i> Feed de Sinais</a>
            <a href="historico.php" class="nav-link"><i class="fas fa-history"></i> Histórico</a>
            <a href="perfil.php" class="nav-link"><i class="fas fa-user-gear"></i> Minha Conta</a>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">
            <a href="logout.php" class="nav-link" style="color: #ff4444;"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </nav>
    </aside>

    <main class="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Olá, <span style="color: var(--primary);"><?php echo explode(' ', $user['nome'])[0]; ?></span> 👋</h1>
            <div class="plano-badge" style="background: var(--vip); color: #000; padding: 5px 15px; border-radius: 20px; font-weight: 900; font-size: 0.7rem;">
                PLANO <?php echo strtoupper($user['plano']); ?>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Créditos Disponíveis</h3>
                <div class="stat-value"><?php echo $user['creditos']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Análises Hoje</h3>
                <div class="stat-value" style="color: var(--primary);">12</div>
            </div>
            <div class="stat-card">
                <h3>Taxa de Acerto</h3>
                <div class="stat-value" style="color: var(--vip);">84%</div>
            </div>
        </div>

        <section style="margin-top: 50px;">
            <h2 style="margin-bottom: 20px;">Ações Rápidas</h2>
            <div style="display: flex; gap: 20px;">
                <a href="feed.php" class="btn-main" style="width: auto; padding: 15px 30px; text-decoration: none;">IR PARA O FEED</a>
                <a href="planos.php" class="btn-main" style="width: auto; padding: 15px 30px; background: transparent; border: 1px solid var(--primary); text-decoration: none;">UPGRADE</a>
            </div>
        </section>
    </main>
</div>

</body>
</html>
