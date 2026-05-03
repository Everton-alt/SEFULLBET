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

// --- ATUALIZAÇÃO DOS CONTADORES (ESPELHADO DA GESTÃO) ---
function getStats($pdo, $cat) {
    $t = $pdo->prepare("SELECT COUNT(*) FROM sinais WHERE p_categoria = ?");
    $t->execute([$cat]);
    $total = $t->fetchColumn();

    $g = $pdo->prepare("SELECT COUNT(*) FROM sinais WHERE p_categoria = ? AND p_status = 'Green'");
    $g->execute([$cat]);
    $greens = $g->fetchColumn();

    $r = $pdo->prepare("SELECT COUNT(*) FROM sinais WHERE p_categoria = ? AND p_status = 'Red'");
    $r->execute([$cat]);
    $reds = $r->fetchColumn();

    $percent = ($total > 0) ? round(($greens / ($greens + $reds ?: 1)) * 100, 1) : 0;
    return ['t' => $total, 'g' => $greens, 'r' => $reds, 'p' => $percent . '%'];
}

$stats_gratis = getStats($pdo, 'Grátis');
$stats_vip    = getStats($pdo, 'VIP');
// --- FIM DA ATUALIZAÇÃO ---

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
    <title>Sefullbet - Clean Neon Edition</title>
   <style>
    :root {
        --primary: #2ECC71; 
        --bg-body: #ffffff; 
        --bg-secondary: #f8f9fa;
        --card-bg: #ffffff;
        --text-main: #2d3436;
        --text-dim: #636e72;
        --accent-blue: #0984e3;
        --danger: #d63031;
        --warning: #f1c40f;
    }

    body {
        font-family: 'Segoe UI', Roboto, sans-serif;
        margin: 0; background-color: var(--bg-body);
        color: var(--text-main); padding-bottom: 80px; /* Aumentado para não colar no fim */
    }

    /* --- SIDEBAR & OVERLAY --- */
    .sidebar {
        height: 100%; width: 300px; position: fixed; z-index: 2000;
        top: 0; left: -300px; background-color: #2d3436;
        overflow-x: hidden; transition: 0.4s; padding-top: 60px;
        box-shadow: 5px 0 15px rgba(0,0,0,0.2);
    }
    .sidebar a { padding: 20px 25px; text-decoration: none; font-size: 20px; color: white; display: block; border-bottom: 1px solid #3d4648; }
    .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 45px; cursor: pointer; color: var(--primary); }
    .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.6); z-index: 1500; }

    /* --- HEADER --- */
    header { 
        background-color: #ffffff; padding: 20px 15px; 
        display: flex; justify-content: space-between; align-items: center; 
        position: sticky; top: 0; z-index: 100; border-bottom: 2px solid #eee;
    }
    .menu-icon { font-size: 32px; cursor: pointer; color: #2d3436; }
    .logo { font-weight: 900; font-size: 1.6rem; color: #2d3436; letter-spacing: -1px; }
    .logo span { color: var(--primary); }

    /* --- STATS GRID --- */
    .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 15px; }
    .stat-card { 
        background: var(--card-bg); padding: 18px 10px; border-radius: 20px; 
        text-align: center; box-shadow: 0 6px 15px rgba(0,0,0,0.06); 
        border-top: 5px solid var(--primary);
    }
    .stat-card.vip { border-top-color: var(--warning); }
    .stat-value { font-size: 1.8rem; font-weight: 800; display: block; }
    .stat-total { font-size: 0.9rem; color: var(--text-dim); margin-bottom: 5px; }
    .stat-label { font-size: 0.7rem; color: #aaa; text-transform: uppercase; font-weight: 700; }
    .stat-counts { font-size: 0.85rem; margin-top: 10px; font-weight: bold; background: var(--bg-secondary); padding: 8px; border-radius: 10px; }

    /* --- ACTION BOX --- */
    .action-box { padding: 20px 15px; }
    .btn-analisador { 
        background: linear-gradient(45deg, #2ecc71, #27ae60); 
        color: #fff; padding: 22px; border-radius: 18px; text-decoration: none; 
        display: block; text-align: center; font-weight: 900; font-size: 1.3rem;
        box-shadow: 0 12px 24px rgba(46, 204, 113, 0.4); text-transform: uppercase; border: none;
    }

    /* --- PALPITES --- */
    .section-title { padding: 25px 15px 15px; font-size: 1.1rem; font-weight: 900; text-transform: uppercase; color: #2d3436; }
    .content-container { padding: 0 15px; }
    
    .history-row { 
        background: var(--card-bg); margin-bottom: 18px; border-radius: 20px; padding: 20px; 
        display: flex; flex-direction: column; border-left: 8px solid #dfe6e9;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: relative;
    }
    .vip-row { border-left-color: var(--warning); }
    .free-row { border-left-color: var(--accent-blue); }

    .row-top { display: flex; justify-content: space-between; font-size: 0.85rem; color: #a1a1a1; margin-bottom: 12px; border-bottom: 1px solid #f1f1f1; padding-bottom: 8px; }
    .row-main { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    
    .match-teams { font-weight: 800; font-size: 1.2rem; line-height: 1.2; color: #2d3436; }
    .match-market { font-size: 1rem; color: var(--accent-blue); font-weight: 700; margin-top: 4px; display: block; }
    
    .status-badge { font-size: 0.8rem; padding: 8px 12px; border-radius: 8px; font-weight: 900; text-transform: uppercase; margin-top: 8px; display: inline-block; }
    
    /* CORES STATUS */
    .bg-green { background: #eafaf1; color: #27ae60; }
    .bg-red { background: #fdf2f2; color: #e74c3c; }
    .bg-waiting { background: #fef9e7; color: #f1c40f; }

    /* TRAVA VIP MAIOR */
    .lock-overlay {
        position: absolute; top:0; left:0; width:100%; height:100%;
        background: rgba(255,255,255,0.92); backdrop-filter: blur(6px);
        z-index: 5; border-radius: 20px; display: flex; align-items: center; justify-content: center;
    }
    .btn-lock { background: #2d3436; color: #fff; padding: 12px 25px; border-radius: 30px; text-decoration: none; font-weight: 900; font-size: 0.9rem; border: 2px solid var(--warning); }

    footer { text-align: center; padding: 60px 20px; font-size: 0.9rem; color: #b2bec3; line-height: 1.5; }

    /* AJUSTES ESPECÍFICOS PARA CELULARES MUITO PEQUENOS */
    @media (max-width: 380px) {
        .stat-value { font-size: 1.4rem; }
        .match-teams { font-size: 1.1rem; }
        .logo { font-size: 1.3rem; }
    }
</style>
</head>
<body>

    <div id="overlay" class="overlay" onClick="closeNav()"></div>

    <div id="mySidebar" class="sidebar">
        <span class="close-btn" onClick="closeNav()">&times;</span>
        <a href="#">🏠 Início</a>
        <a href="#">💎 Planos</a>
        <a href="#">📊 Analisador Sefullbet</a>
        <a href="#">📈 Histórico de Greens</a>
        <a href="#">⚙️ Minha Conta</a>
        <a href="#" class="logout-btn">🚪 Sair</a>
    </div>

    <header>
        <div class="menu-icon" onClick="openNav()">☰</div>
        <div class="logo">SEFULL<span>BET</span></div>
        <div style="font-size: 20px;">👤</div>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><span class="stat-value1">
              <?= $stats_gratis['p'] ?>
            </span></div>
            <div class="stat-total"><span class="stat-total1">
              <?= $stats_gratis['t'] ?>
            </span> Palpites</div>
            <div class="stat-label">Acerto GrÁtis</div>
            <div class="stat-counts"><span class="txt-green"><span style="color:#27ae60">
              <?= $stats_gratis['g'] ?>
            </span> GREENS</span> / <span class="txt-red"><span style="color:#d63031">
            <?= $stats_gratis['r'] ?>
            </span> REDS</span></div>
        </div>
        <div class="stat-card vip">
            <div class="stat-value" style="color: #f39c12;"><span class="stat-value1" style="color: #f39c12;">
              <?= $stats_vip['p'] ?>
            </span></div>
            <div class="stat-total"><span class="stat-total1">
              <?= $stats_vip['t'] ?>
            </span> Palpites</div>
            <div class="stat-label">Acerto VIP</div>
            <div class="stat-counts"><span class="txt-green"><span style="color:#27ae60">
              <?= $stats_vip['g'] ?>
            </span>GREENS</span> / <span class="txt-red"><span style="color:#d63031">
            <?= $stats_vip['r'] ?>
            </span>REDS</span></div>
        </div>
    </div>

    <div class="action-box">
        <div class="highlight-ring"></div>
        <button class="btn-analisador">🔥 ANALISADOR PRO ⚡</button>
    </div>

    <div class="section-title">🎯 Palpites Recentes</div>
    <div class="content-container">
        <div class="history-row vip-row">
            <div class="row-top"><span>02/05/2026 - 16:00</span></div>
            <div class="row-main">
                <div><span class="match-teams">Arsenal vs Man. City</span><br><span class="match-market">Ambas Marcam</span></div>
                <div style="text-align:right"><div style="font-weight:900;">2 - 1</div><span class="status-badge bg-green">GREEN ✅</span></div>
            </div>
        </div>
        <div class="history-row free-row">
            <div class="row-top"><span>02/05/2026 - 21:00</span></div>
            <div class="row-main">
                <div><span class="match-teams">Bahia vs Vitória</span><br><span class="match-market">Over 2.5 Gols</span></div>
                <div style="text-align:right"><div style="font-weight:900;">- x -</div><span class="status-badge bg-waiting">PENDENTE ⏳</span></div>
            </div>
        </div>
    </div>

    <div class="section-title">🏆 Ultimas Vitorias</div>
    <div class="content-container">
        <div class="victory-card">
            <img src="https://via.placeholder.com/60/2ecc71/ffffff?text=$$" class="micro-foto">
            <div class="victory-info">
                <p class="victory-title">Alavancagem 5x Concluída</p>
                <p class="victory-summary">Nossa consultoria VIP ajudou mais de 200 membros a quintuplicarem a stake inicial.</p>
                <span class="post-date">Postado em 01/05/2026</span>
            </div>
        </div>
    </div>

    <div class="section-title">📰 Notícias Sefullbet</div>
    <div class="content-container">
        <div class="news-card">
            <span class="news-subject">Gestão de Banca para Iniciantes</span>
            <p class="news-summary">Aprenda a regra dos 3% e por que ela é o segredo dos apostadores profissionais.</p>
            <span class="post-date">Atualizado hoje às 10:30</span>
        </div>
    </div>

    <footer>
        <strong>SEFULLBET PRO &copy; 2026</strong><br>
        2026 SeFullBet - Inteligência de Dados aplicada ao Esporte. Apostas são para maiores de 18 anos. Jogue com responsabilidade.
    </footer>

    <script>
        function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
        function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }
    </script>
</body>
</html>
