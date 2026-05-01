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
    <title>Dashboard | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00ff88;
            --primary-glow: rgba(0, 255, 136, 0.3);
            --bg: #0d1117;
            --card: #161b22;
            --border: #30363d;
            --text-main: #f0f6fc;
            --text-dim: #8b949e;
            --vip: #ffd700;
            --danger: #ff4d4d;
            --info: #00e5ff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: var(--bg); 
            color: var(--text-main);
            background-image: radial-gradient(circle at 0% 0%, rgba(0, 255, 136, 0.05) 0%, transparent 40%);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        nav { 
            width: 280px; background: rgba(22, 27, 34, 0.8); backdrop-filter: blur(10px);
            border-right: 1px solid var(--border); padding: 40px 20px;
            display: flex; flex-direction: column; position: fixed; height: 100vh;
        }

        .nav-logo { font-weight: 800; font-size: 1.6rem; letter-spacing: -1px; margin-bottom: 50px; text-align: center; }
        .nav-logo span { color: var(--primary); }

        .nav-group { margin-bottom: 30px; }
        .nav-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-left: 15px; margin-bottom: 10px; display: block; }

        .nav-btn { 
            color: var(--text-dim); padding: 14px 18px; border-radius: 12px; text-decoration: none; 
            display: flex; align-items: center; gap: 15px; font-size: 14px; font-weight: 500;
            transition: 0.3s;
        }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .nav-btn.active { background: var(--primary-glow); color: var(--primary); border: 1px solid rgba(0, 255, 136, 0.2); }

        /* MAIN CONTENT */
        main { flex: 1; margin-left: 280px; padding: 40px 60px; max-width: 1200px; }

        /* HEADER CARDS */
        .top-bar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
        .user-status { 
            background: var(--card); padding: 20px 30px; border-radius: 20px; border: 1px solid var(--border);
            display: flex; align-items: center; gap: 25px;
        }

        /* PERFORMANCE GRID - ATUALIZADO CONFORME IMAGE_48F285.PNG */
        .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
        .perf-card { 
            background: var(--card); padding: 25px; border-radius: 20px; border: 1px solid var(--border); 
            position: relative;
        }
        .perf-vip-card { border: 1px solid var(--vip); }
        
        .perf-header { 
            font-size: 13px; font-weight: 800; text-transform: uppercase; 
            margin-bottom: 25px; display: flex; align-items: center; gap: 8px; 
        }
        .perf-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); text-align: center; }
        
        .stat-box span { display: block; font-size: 10px; color: var(--text-dim); font-weight: 700; margin-bottom: 8px; text-transform: uppercase; }
        .stat-box b { font-size: 24px; font-weight: 800; }
        .stat-green { color: var(--primary); }
        .stat-red { color: #ff4d4d; }

        /* BOTÃO ANALISADOR DESTAQUE */
        .analisador-cta {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 0, 0, 0) 100%);
            border: 2px solid var(--primary); padding: 30px; border-radius: 20px; margin-bottom: 40px; text-align: center;
        }
        .btn-destaque-ai {
            background: var(--primary); color: #0d1117; padding: 15px 35px; border-radius: 12px;
            text-decoration: none; font-weight: 800; display: inline-flex; align-items: center; gap: 10px;
            transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;
        }

        /* LISTAS */
        .list-container { display: flex; flex-direction: column; gap: 12px; margin-bottom: 50px; }
        .list-item { 
            background: var(--card); padding: 18px 25px; border-radius: 16px; border: 1px solid var(--border);
            display: grid; align-items: center; transition: 0.2s; position: relative;
        }
        .grid-palpites { grid-template-columns: 100px 1.5fr 1fr 100px 120px; }
        .grid-vitorias { grid-template-columns: 50px 1.5fr 1fr 120px; }
        .grid-notas { grid-template-columns: 50px 1fr 120px; }

        .v-icon-circle { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .tag { font-size: 10px; font-weight: 900; padding: 5px 10px; border-radius: 6px; text-transform: uppercase; text-align: center; }
        .tag-green { background: rgba(0, 255, 136, 0.1); color: var(--primary); }
        .tag-wait { background: rgba(255, 215, 0, 0.1); color: var(--vip); }
        .tag-info { background: rgba(0, 229, 255, 0.1); color: var(--info); }
        .blur-lock { filter: blur(6px); opacity: 0.3; pointer-events: none; }

        @media (max-width: 1100px) {
            nav { width: 80px; padding: 40px 10px; }
            .nav-label, .nav-btn span, .nav-logo span, .nav-logo { display: none; }
            main { margin-left: 80px; padding: 20px; }
            .list-item { grid-template-columns: 1fr !important; gap: 10px; text-align: center; }
            .perf-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    <div class="nav-group">
        <span class="nav-label">Menu Principal</span>
        <a class="nav-btn active" href="#"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
        <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Hall da Fama</span></a>
    </div>
    <div class="nav-group">
        <span class="nav-label">Conteúdo</span>
        <a class="nav-btn" href="noticias.php"><i class="fas fa-book-open"></i> <span>Estratégias</span></a>
        <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>
    </div>
    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <!-- CABEÇALHO -->
    <div class="top-bar">
        <div class="welcome">
            <h1 style="color: var(--text-dim); font-weight: 400;">Bem-vindo,</h1>
            <h1><?php echo explode(' ', $user['nome'])[0]; ?> 👋</h1>
        </div>
        <div class="user-status">
            <div style="text-align: center; border-right: 1px solid var(--border); padding-right: 20px;">
                <span style="font-size: 10px; color: var(--text-dim); text-transform: uppercase;">Créditos</span>
                <div style="font-size: 20px; font-weight: 800; color: var(--primary);"><?php echo $is_platinum ? '∞' : $user['saldo_creditos']; ?></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; color: var(--text-dim);">Status</div>
                <div style="color: <?php echo $cor_perfil; ?>; font-weight: 800; font-size: 14px;"><?php echo strtoupper($perfil); ?></div>
            </div>
        </div>
    </div>

    <!-- PERFORMANCE (CORRIGIDO CONFORME IMAGE_48F285.PNG) -->
    <div class="perf-grid">
        <!-- Card Grátis -->
        <div class="perf-card">
            <div class="perf-header">
                <i class="fas fa-chart-line"></i> PERFORMANCE GRÁTIS
            </div>
            <div class="perf-stats-row">
                <div class="stat-box"><span>TOTAL</span><b>45</b></div>
                <div class="stat-box"><span>GREENS</span><b class="stat-green">38</b></div>
                <div class="stat-box"><span>REDS</span><b class="stat-red">7</b></div>
                <div class="stat-box"><span>WIN%</span><b>84%</b></div>
            </div>
        </div>

        <!-- Card VIP -->
        <div class="perf-card perf-vip-card">
            <div class="perf-header" style="color: var(--vip);">
                <i class="fas fa-gem"></i> PERFORMANCE VIP
            </div>
            <div class="perf-stats-row">
                <div class="stat-box"><span>TOTAL</span><b>120</b></div>
                <div class="stat-box"><span>GREENS</span><b class="stat-green">108</b></div>
                <div class="stat-box"><span>REDS</span><b class="stat-red">12</b></div>
                <div class="stat-box"><span>WIN%</span><b>90%</b></div>
            </div>
        </div>
    </div>

    <!-- 1. ANALISADOR DESTAQUE -->
    <section class="analisador-cta">
        <h2 style="margin-bottom: 10px; font-weight: 900;">ANALISADOR SEFULLBET AI</h2>
        <p style="color: var(--text-dim); margin-bottom: 20px; font-size: 14px;">Inicie sua análise avançada com processamento de dados em tempo real.</p>
        <a href="analisador.php" class="btn-destaque-ai"><i class="fas fa-robot"></i> Abrir Analisador Agora</a>
    </section>

    <!-- 2. PALPITES EM LINHA -->
    <h3 style="margin-bottom: 20px; font-weight: 800;">🔥 Palpites em Tempo Real</h3>
    <div class="list-container">
        <div class="list-item grid-palpites">
            <div class="tag tag-green">Finalizado</div>
            <div><div style="font-weight:700; font-size:14px;">Bayern vs Arsenal</div><div style="font-size:11px; color:var(--text-dim);">Champions League</div></div>
            <div style="font-size:13px; font-weight:600;">Ambas Marcam</div>
            <div style="text-align:center; background:rgba(255,255,255,0.05); padding:5px; border-radius:5px;"><b>1.80</b></div>
            <div style="text-align:right;"><span class="tag" style="background:#21262d;">FREE</span></div>
        </div>

        <div class="list-item grid-palpites">
            <?php if(!$pode_ver_vip): ?>
            <div style="position:absolute; width:100%; height:100%; display:flex; align-items:center; justify-content:center; z-index:5; background:rgba(0,0,0,0.3); border-radius:16px; left:0;">
                <a href="upgrade.php" style="background:var(--vip); color:#000; padding:8px 20px; border-radius:50px; font-weight:900; font-size:11px; text-decoration:none;"><i class="fas fa-lock"></i> UPGRADE VIP</a>
            </div>
            <?php endif; ?>
            <div class="tag tag-wait <?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">Analisando</div>
            <div class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>"><div style="font-weight:700; font-size:14px;">Real Madrid vs City</div><div style="font-size:11px; color:var(--text-dim);">Champions League</div></div>
            <div style="font-size:13px; font-weight:600;" class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">Resultado Final</div>
            <div style="text-align:center; background:rgba(255,255,255,0.05); padding:5px; border-radius:5px;" class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>"><b>2.45</b></div>
            <div style="text-align:right;"><span class="tag" style="border:1px solid var(--vip); color:var(--vip);">VIP</span></div>
        </div>
    </div>

    <!-- 3. ULTIMAS VITORIAS -->
    <h3 style="margin-bottom: 20px; font-weight: 800;"><i class="fas fa-trophy" style="color: var(--vip)"></i> Últimas Vitórias</h3>
    <div class="list-container">
        <div class="list-item grid-vitorias">
            <div class="v-icon-circle" style="background:rgba(0,255,136,0.1); color:var(--primary);"><i class="fas fa-check"></i></div>
            <div><div style="font-weight:700; font-size:14px;">@usuario_gold</div><div style="font-size:11px; color:var(--text-dim);">Lucrou 2.5 unidades no sinal anterior</div></div>
            <div style="font-size:12px; font-style:italic; color:var(--text-dim);">"Analisador perfeito!"</div>
            <div style="text-align:right;"><span class="tag tag-green">Lucro +R$ 250</span></div>
        </div>
    </div>

    <!-- 4. ULTIMAS NOTAS -->
    <h3 style="margin-bottom: 20px; font-weight: 800;"><i class="fas fa-sticky-note" style="color: var(--info)"></i> Últimas Notas</h3>
    <div class="list-container">
        <div class="list-item grid-notas">
            <div class="v-icon-circle" style="background:rgba(0,229,255,0.1); color:var(--info);"><i class="fas fa-info"></i></div>
            <div><div style="font-weight:700; font-size:14px;">Estratégia de Cantos HT</div><div style="font-size:11px; color:var(--text-dim);">Filtre jogos com 0x0 aos 35 minutos para buscar o canto limite.</div></div>
            <div style="text-align:right;"><span class="tag tag-info">Educativo</span></div>
        </div>
    </div>

    <footer style="margin-top: 60px; padding: 40px 0; border-top: 1px solid var(--border); color: #444; font-size: 11px; text-align: center;">
        &copy; 2026 SeFullBet - Inteligência de Dados aplicada ao Esporte. Apostas são para maiores de 18 anos. Jogue com responsabilidade.
    </footer>
</main>

</body>
</html>
