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

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', sans-serif; }
        
        /* Layout de Dashboard: Trava a altura da tela e esconde rolagem global */
        body { 
            background: var(--bg); 
            color: var(--text); 
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
        }

        /* Sidebar Fixa */
        nav { 
            width: 260px; 
            background: var(--card); 
            border-right: 1px solid var(--border); 
            padding: 30px 20px; 
            display: flex; 
            flex-direction: column; 
            flex-shrink: 0; 
            height: 100vh; /* Ocupa 100% da altura da tela */
        }
        
        .nav-logo { font-size: 1.6rem; font-weight: 900; color: var(--primary); margin-bottom: 40px; text-align: center; letter-spacing: -1px; }
        .nav-logo span { color: #fff; }
        
        .nav-menu { display: flex; flex-direction: column; gap: 10px; }
        .nav-btn { color: #8b949e; padding: 14px 18px; border-radius: 12px; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; transition: 0.3s; }
        .nav-btn:hover, .nav-btn.active { background: #1c222d; color: var(--primary); box-shadow: inset 4px 0 0 var(--primary); }
        
        .logout-section { margin-top: auto; border-top: 1px solid var(--border); padding-top: 20px; }
        .btn-logout { color: var(--danger); text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: bold; padding: 10px; transition: 0.3s; }
        .btn-logout:hover { opacity: 0.7; }

        /* Conteúdo Principal: A rolagem acontece APENAS aqui dentro */
        main { 
            flex: 1; 
            padding: 30px; 
            height: 100vh; /* Ocupa 100% da altura da tela */
            overflow-y: auto; /* Libera a rolagem vertical apenas no main */
            scroll-behavior: smooth; 
        }

        /* Custom Scrollbar aplicada diretamente ao Main (Garante que vai aparecer) */
        main::-webkit-scrollbar {
            width: 12px;
        }
        main::-webkit-scrollbar-track {
            background: var(--bg);
        }
        main::-webkit-scrollbar-thumb {
            background: #1c222d;
            border-radius: 10px;
            border: 3px solid var(--bg);
            transition: 0.3s;
        }
        main::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Banner Neon do Analisador */
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

        /* Stats Cards */
        .stats-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .stats-box { background: var(--card); padding: 25px; border-radius: 18px; border: 1px solid var(--border); position: relative; overflow: hidden; }
        .stats-box::after { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 70%); pointer-events: none; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 20px; text-align: center; }
        .stat-val { display: block; font-size: 1.4rem; font-weight: 900; color: #fff; }
        .stat-label { font-size: 10px; color: #8b949e; text-transform: uppercase; font-weight: bold; }

        /* Feed de Palpites */
        .palpite-row { 
            background: var(--card); border: 1px solid var(--border); padding: 18px; border-radius: 16px; 
            margin-bottom: 12px; display: grid; grid-template-columns: 120px 1fr 90px 90px 120px; 
            align-items: center; gap: 20px; transition: 0.3s;
        }
        .palpite-row:hover { border-color: #444; background: #161b26; }
        
        .status-badge { padding: 6px; border-radius: 8px; font-size: 10px; font-weight: 900; text-align: center; letter-spacing: 0.5px; }
        .st-green { background: rgba(0, 255, 136, 0.1); color: var(--primary); border: 1px solid rgba(0, 255, 136, 0.2); }
        .st-red { background: rgba(255, 77, 77, 0.1); color: var(--danger); border: 1px solid rgba(255, 77, 77, 0.2); }
        .st-wait { background: rgba(255, 215, 0, 0.1); color: var(--vip); border: 1px solid rgba(255, 215, 0, 0.2); }

        /* Lock & Misc */
        .locked { filter: blur(6px); opacity: 0.3; pointer-events: none; }
        .lock-badge { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; background: var(--vip); color: #000; padding: 5px 15px; border-radius: 20px; font-weight: 900; font-size: 12px; }

        .perfil-tag { padding: 4px 12px; border-radius: 6px; font-size: 10px; font-weight: 900; border: 1px solid; }

        /* Vitórias Grids */
        .v-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .v-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; cursor: pointer; transition: 0.3s; }
        .v-card:hover { border-color: var(--primary); transform: translateY(-5px); }
        .v-img { width: 100%; height: 180px; object-fit: cover; }
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
            <p style="color: #8b949e; font-size: 13px; margin-top: 5px;">Você tem <b><?php echo $is_platinum ? 'Acesso Ilimitado' : $user['saldo_creditos'] . ' créditos'; ?></b> no analisador.</p>
        </div>
        <a href="analisador.php" class="btn-analisador">
            <i class="fas fa-bolt"></i> Abrir Analisador
        </a>
    </div>

    <div class="stats-wrapper">
        <div class="stats-box">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h4 style="font-size: 12px; color: #8b949e;">PERFORMANCE FREE</h4>
                <span class="perfil-tag" style="border-color: #30363d; color: #8b949e;">GRÁTIS</span>
            </div>
            <div class="stats-grid">
                <div><span class="stat-label">Total</span><span class="stat-val">45</span></div>
                <div><span class="stat-label">Greens</span><span class="stat-val" style="color:var(--primary)">38</span></div>
                <div><span class="stat-label">Reds</span><span class="stat-val" style="color:var(--danger)">7</span></div>
                <div><span class="stat-label">Win%</span><span class="stat-val">84%</span></div>
            </div>
        </div>

        <div class="stats-box" style="border-color: rgba(255, 215, 0, 0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h4 style="font-size: 12px; color: var(--vip);">PERFORMANCE VIP</h4>
                <span class="perfil-tag" style="border-color: var(--vip); color: var(--vip);">VIP / PLATINUM</span>
            </div>
            <div class="stats-grid">
                <div><span class="stat-label">Total</span><span class="stat-val">120</span></div>
                <div><span class="stat-label">Greens</span><span class="stat-val" style="color:var(--primary)">108</span></div>
                <div><span class="stat-label">Reds</span><span class="stat-val" style="color:var(--danger)">12</span></div>
                <div><span class="stat-label">Win%</span><span class="stat-val">90%</span></div>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3><i class="fas fa-bolt" style="color: var(--primary)"></i> Sinais em Tempo Real</h3>
        <span style="font-size: 12px; color: #8b949e;">Mostrando 10 palpites por página</span>
    </div>

    <div class="palpite-row">
        <div class="status-badge st-green">VITORIOSO</div>
        <div><b>Flamengo vs Palmeiras</b><br><small style="color: #8b949e">Hoje - 21:30 | Over 1.5 Gols</small></div>
        <div style="text-align: center;"><small>ODD</small><br><b style="color: var(--primary)">1.85</b></div>
        <div style="text-align: center;"><small>PLACAR</small><br><b>2 - 0</b></div>
        <div style="text-align: right;"><span class="perfil-tag" style="border-color: #30363d; color: #8b949e;">GRÁTIS</span></div>
    </div>

    <div class="palpite-row" style="position: relative;">
        <?php if(!$pode_ver_vip): ?>
            <div class="lock-badge"><i class="fas fa-lock"></i> UPGRADE PARA VER</div>
        <?php endif; ?>
        <div class="status-badge st-wait <?php echo !$pode_ver_vip ? 'locked' : ''; ?>">AGUARDANDO</div>
        <div class="<?php echo !$pode_ver_vip ? 'locked' : ''; ?>"><b>Man. City vs Arsenal</b><br><small style="color: #8b949e">Amanhã - 16:00 | ML City</small></div>
        <div style="text-align: center;" class="<?php echo !$pode_ver_vip ? 'locked' : ''; ?>"><small>ODD</small><br><b style="color: var(--primary)">2.10</b></div>
        <div style="text-align: center;" class="<?php echo !$pode_ver_vip ? 'locked' : ''; ?>"><small>PLACAR</small><br><b>-</b></div>
        <div style="text-align: right;"><span class="perfil-tag" style="border-color: var(--vip); color: var(--vip);">SINAL VIP</span></div>
    </div>

    <div style="display: flex; justify-content: center; margin-top: 20px;">
        <a href="#" class="btn-analisador" style="background: #1c222d; color: #fff; box-shadow: none; font-size: 12px;">Carregar Palpites Anteriores</a>
    </div>

    <h3 style="margin-top: 50px;"><i class="fas fa-fire" style="color: #ff8800"></i> Vitórias Recentes</h3>
    <div class="v-grid">
        <div class="v-card">
            <img src="https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=400&q=80" class="v-img">
            <div style="padding: 20px;">
                <h4 style="font-size: 15px;">Alavancagem Completa: Odd 5.0</h4>
                <p style="font-size: 13px; color: #8b949e; margin-top: 8px;">Análise tática resultou em lucro máximo para o grupo VIP...</p>
            </div>
        </div>
    </div>

    <footer style="margin-top: 60px; text-align: center; border-top: 1px solid var(--border); padding: 30px; color: #444; font-size: 12px;">
        &copy; 2026 SeFull Bet Intelligence.
    </footer>
</main>

</body>
</html>
