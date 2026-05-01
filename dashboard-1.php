<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

$perfil = $user['perfil']; 
$pode_ver_vip = in_array($perfil, ['VIP', 'Platinum', 'Supervisor', 'Admin']);
$is_platinum = ($perfil === 'Platinum');

$cores = [
    'Grátis' => '#8b949e', 'VIP' => '#ffd700', 'Platinum' => '#ffffff',
    'Supervisor' => '#00e5ff', 'Admin' => '#00ff88'
];
$cor_perfil = $cores[$perfil] ?? $cores['Grátis'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefull Bet | Intelligence</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00ff88; --bg: #07090d; --card: #12161f; --border: #262c36;
            --text: #c9d1d9; --vip: #ffd700; --danger: #ff4d4d; --neon-glow: 0 0 15px rgba(0, 255, 136, 0.4);
        }

        /* Reset Fundamental */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', sans-serif; }
        
        /* Garantir que a página possa crescer verticalmente */
        html, body {
            background: var(--bg);
            color: var(--text);
            min-height: 100%;
            overflow-x: hidden; /* Evita scroll horizontal */
            overflow-y: auto;   /* Força o scroll vertical se necessário */
        }

        body { display: flex; align-items: stretch; }

        /* Estilização da Barra de Rolagem Global (Mais robusta) */
        ::-webkit-scrollbar {
            width: 10px;
            background-color: var(--bg);
        }
        ::-webkit-scrollbar-track {
            background: var(--bg);
        }
        ::-webkit-scrollbar-thumb {
            background: #262c36;
            border-radius: 5px;
            border: 2px solid var(--bg);
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Sidebar - Agora com position sticky para não sumir */
        nav { 
            width: 260px; 
            background: var(--card); 
            border-right: 1px solid var(--border); 
            padding: 30px 20px; 
            display: flex; 
            flex-direction: column; 
            position: sticky;
            top: 0;
            height: 100vh;
            flex-shrink: 0;
        }
        
        .nav-logo { font-size: 1.6rem; font-weight: 900; color: var(--primary); margin-bottom: 40px; text-align: center; letter-spacing: -1px; }
        .nav-logo span { color: #fff; }
        
        .nav-menu { display: flex; flex-direction: column; gap: 10px; }
        .nav-btn { color: #8b949e; padding: 14px 18px; border-radius: 12px; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; transition: 0.3s; }
        .nav-btn:hover, .nav-btn.active { background: #1c222d; color: var(--primary); box-shadow: inset 4px 0 0 var(--primary); }
        
        .logout-section { margin-top: auto; border-top: 1px solid var(--border); padding-top: 20px; }
        .btn-logout { color: var(--danger); text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: bold; padding: 10px; transition: 0.3s; }
        .btn-logout:hover { opacity: 0.7; }

        /* Conteúdo Principal - Sem travas de overflow */
        main { 
            flex: 1; 
            padding: 30px; 
            min-width: 0; /* Evita quebra de layout em flexbox */
        }

        /* Elementos Visuais */
        .neon-banner { 
            background: linear-gradient(90deg, #12161f 0%, #1c222d 100%);
            padding: 25px; border-radius: 20px; border: 1px solid var(--primary);
            box-shadow: var(--neon-glow); margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .btn-analisador { 
            background: var(--primary); color: #000; padding: 12px 25px; border-radius: 10px;
            text-decoration: none; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.6); transition: 0.3s;
        }
        .btn-analisador:hover { transform: scale(1.05); filter: brightness(1.1); }

        .stats-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .stats-box { background: var(--card); padding: 25px; border-radius: 18px; border: 1px solid var(--border); position: relative; overflow: hidden; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 20px; text-align: center; }
        .stat-val { display: block; font-size: 1.4rem; font-weight: 900; color: #fff; }
        .stat-label { font-size: 10px; color: #8b949e; text-transform: uppercase; font-weight: bold; }

        .palpite-row { 
            background: var(--card); border: 1px solid var(--border); padding: 18px; border-radius: 16px; 
            margin-bottom: 12px; display: grid; grid-template-columns: 120px 1fr 90px 90px 120px; 
            align-items: center; gap: 20px; transition: 0.3s;
        }
        
        .status-badge { padding: 6px; border-radius: 8px; font-size: 10px; font-weight: 900; text-align: center; }
        .st-green { background: rgba(0, 255, 136, 0.1); color: var(--primary); border: 1px solid rgba(0, 255, 136, 0.2); }
        .st-wait { background: rgba(255, 215, 0, 0.1); color: var(--vip); border: 1px solid rgba(255, 215, 0, 0.2); }

        .locked { filter: blur(6px); opacity: 0.3; pointer-events: none; }
        .lock-badge { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; background: var(--vip); color: #000; padding: 5px 15px; border-radius: 20px; font-weight: 900; }

        .perfil-tag { padding: 4px 12px; border-radius: 6px; font-size: 10px; font-weight: 900; border: 1px solid; }

        .v-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .v-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; }
        .v-img { width: 100%; height: 180px; object-fit: cover; }

        /* Responsividade Básica */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            nav { width: 100%; height: auto; position: relative; }
            .palpite-row { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stats-wrapper { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    <div class="nav-menu">
        <a class="nav-btn active" href="#"><i class="fas fa-layer-group"></i> Feed de Sinais</a>
        <a class="nav-btn" href="analisador.php"><i class="fas fa-brain"></i> Analisador Pro</a>
        <a class="nav-btn" href="#"><i class="fas fa-history"></i> Meu Histórico</a>
        <a class="nav-btn" href="#"><i class="fas fa-trophy"></i> Hall da Fama</a>
        <a class="nav-btn" href="#"><i class="fas fa-wallet"></i> Planos & Créditos</a>
    </div>
    <div class="logout-section">
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> SAIR DA CONTA
        </a>
    </div>
</nav>

<main>
    <div class="neon-banner">
        <div>
            <h2 style="font-size: 1.4rem; color: #fff;">Olá, <?php echo $user['nome']; ?>!</h2>
            <p style="color: #8b949e; font-size: 13px; margin-top: 5px;">Créditos: <b><?php echo $is_platinum ? 'Ilimitado' : $user['saldo_creditos']; ?></b></p>
        </div>
        <a href="analisador.php" class="btn-analisador"><i class="fas fa-bolt"></i> Analisador</a>
    </div>

    <div class="stats-wrapper">
        <div class="stats-box">
            <h4 style="font-size: 11px; color: #8b949e; margin-bottom: 10px;">FREE PERFORMANCE</h4>
            <div class="stats-grid">
                <div><span class="stat-label">Total</span><span class="stat-val">45</span></div>
                <div><span class="stat-label">Win%</span><span class="stat-val" style="color:var(--primary)">84%</span></div>
            </div>
        </div>
        <div class="stats-box" style="border-color: var(--vip);">
            <h4 style="font-size: 11px; color: var(--vip); margin-bottom: 10px;">VIP PERFORMANCE</h4>
            <div class="stats-grid">
                <div><span class="stat-label">Total</span><span class="stat-val">120</span></div>
                <div><span class="stat-label">Win%</span><span class="stat-val" style="color:var(--vip)">90%</span></div>
            </div>
        </div>
    </div>

    <h3 style="margin-bottom: 20px;">Sinais Recentes</h3>

    <!-- Exemplo Grátis -->
    <div class="palpite-row">
        <div class="status-badge st-green">VITORIOSO</div>
        <div><b>Flamengo vs Palmeiras</b><br><small style="color: #8b949e">Over 1.5 Gols</small></div>
        <div style="text-align: center;"><small>ODD</small><br><b style="color: var(--primary)">1.85</b></div>
        <div style="text-align: center;"><small>PLACAR</small><br><b>2 - 0</b></div>
        <div style="text-align: right;"><span class="perfil-tag" style="border-color: #444;">GRÁTIS</span></div>
    </div>

    <!-- Exemplo VIP com Trava -->
    <div class="palpite-row" style="position: relative;">
        <?php if(!$pode_ver_vip): ?>
            <div class="lock-badge"><i class="fas fa-lock"></i> UPGRADE</div>
        <?php endif; ?>
        <div class="status-badge st-wait <?php echo !$pode_ver_vip ? 'locked' : ''; ?>">AGUARDANDO</div>
        <div class="<?php echo !$pode_ver_vip ? 'locked' : ''; ?>"><b>Man. City vs Arsenal</b><br><small style="color: #8b949e">ML City</small></div>
        <div style="text-align: center;" class="<?php echo !$pode_ver_vip ? 'locked' : ''; ?>"><small>ODD</small><br><b>2.10</b></div>
        <div style="text-align: center;" class="<?php echo !$pode_ver_vip ? 'locked' : ''; ?>"><small>PLACAR</small><br><b>-</b></div>
        <div style="text-align: right;"><span class="perfil-tag" style="border-color: var(--vip); color: var(--vip);">VIP</span></div>
    </div>

    <h3 style="margin-top: 40px;">Vitórias Recentes</h3>
    <div class="v-grid">
        <div class="v-card">
            <img src="https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=400&q=80" class="v-img">
            <div style="padding: 20px;">
                <h4>Alavancagem Completa</h4>
                <p style="font-size: 12px; color: #8b949e;">Lucro máximo para o grupo VIP...</p>
            </div>
        </div>
    </div>

    <footer style="padding: 50px; text-align: center; color: #444; font-size: 11px;">
        &copy; 2026 SeFull Bet Intelligence.
    </footer>
</main>

</body>
</html>
