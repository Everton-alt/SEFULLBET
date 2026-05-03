<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// 1. DADOS DO USUÁRIO
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

$perfil = $user['perfil']; 
$pode_ver_vip = in_array($perfil, ['VIP', 'Platinum', 'Supervisor', 'Admin']);

// 2. FUNÇÃO DE ESTATÍSTICAS
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

// 3. BUSCAR PALPITES RECENTES (Últimos 5)
$stmt_sinais = $pdo->prepare("SELECT * FROM sinais ORDER BY id DESC LIMIT 5");
$stmt_sinais->execute();
$sinais = $stmt_sinais->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefullbet - Dashboard</title>
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
            color: var(--text-main); padding-bottom: 50px;
        }

        /* --- SIDEBAR & OVERLAY --- */
        .sidebar {
            height: 100%; width: 280px; position: fixed; z-index: 2000;
            top: 0; left: -280px; background-color: #2d3436;
            overflow-x: hidden; transition: 0.4s; padding-top: 60px;
            box-shadow: 5px 0 15px rgba(0,0,0,0.1);
        }
        .sidebar a { padding: 15px 25px; text-decoration: none; font-size: 18px; color: white; display: block; border-bottom: 1px solid #3d4648; }
        .sidebar a:hover { background: #3d4648; }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 36px; cursor: pointer; color: var(--primary); }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }

        /* --- HEADER --- */
        header { 
            background-color: #ffffff; color: var(--primary); padding: 15px; 
            display: flex; justify-content: space-between; align-items: center; 
            position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #eee;
        }
        .menu-icon { font-size: 24px; cursor: pointer; color: #2d3436; }
        .logo { font-weight: 900; font-size: 1.3rem; color: #2d3436; }
        .logo span { color: var(--primary); }

        /* --- STATS --- */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 15px 10px 5px; }
        .stat-card { 
            background: var(--card-bg); padding: 12px; border-radius: 15px; 
            text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            border-top: 4px solid var(--primary);
        }
        .stat-card.vip { border-top-color: var(--warning); }
        .stat-value { font-size: 1.4rem; font-weight: bold; }
        .stat-total { font-size: 0.75rem; color: var(--text-dim); }
        .stat-label { font-size: 0.6rem; color: #aaa; text-transform: uppercase; }
        .stat-counts { font-size: 0.65rem; margin-top: 8px; font-weight: bold; background: var(--bg-secondary); padding: 5px; border-radius: 8px; }

        /* --- ACTION BOX --- */
        .action-box { padding: 20px 15px; position: relative; }
        .btn-analisador { 
            background: linear-gradient(45deg, #2ecc71, #27ae60); 
            color: #fff; padding: 18px; border-radius: 15px; text-decoration: none; 
            display: block; text-align: center; font-weight: 800; border: none; width: 100%;
            box-shadow: 0 10px 20px rgba(46, 204, 113, 0.3); text-transform: uppercase; 
        }

        /* --- PALPITES --- */
        .section-title { padding: 20px 15px 10px; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; }
        .content-container { padding: 0 10px; }
        .history-row { 
            background: var(--card-bg); margin-bottom: 12px; border-radius: 15px; padding: 15px; 
            display: flex; flex-direction: column; border-left: 6px solid #dfe6e9;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); position: relative;
        }
        .vip-row { border-left-color: var(--warning); }
        .free-row { border-left-color: var(--accent-blue); }
        .row-top { display: flex; justify-content: space-between; font-size: 0.7rem; color: #b2bec3; margin-bottom: 10px; border-bottom: 1px solid #f1f1f1; padding-bottom: 6px; }
        .row-main { display: flex; justify-content: space-between; align-items: center; }
        .match-teams { font-weight: bold; font-size: 1rem; }
        .match-market { font-size: 0.8rem; color: var(--accent-blue); font-weight: 700; }
        .status-badge { font-size: 0.65rem; padding: 5px 10px; border-radius: 6px; font-weight: bold; text-transform: uppercase; margin-top: 5px; display: inline-block; }
        
        /* CORES STATUS */
        .bg-green { background: #eafaf1; color: #27ae60; }
        .bg-red { background: #fdf2f2; color: #e74c3c; }
        .bg-waiting { background: #fef9e7; color: #f1c40f; }

        .lock-overlay {
            position: absolute; top:0; left:0; width:100%; height:100%;
            background: rgba(255,255,255,0.8); backdrop-filter: blur(4px);
            z-index: 5; border-radius: 15px; display: flex; align-items: center; justify-content: center;
        }
        .btn-lock { background: var(--warning); color: #000; padding: 8px 15px; border-radius: 20px; text-decoration: none; font-weight: bold; font-size: 0.7rem; }

        footer { text-align: center; padding: 40px 20px; font-size: 0.75rem; color: #b2bec3; }
    </style>
</head>
<body>

    <div id="overlay" class="overlay" onclick="closeNav()"></div>

    <div id="mySidebar" class="sidebar">
        <span class="close-btn" onclick="closeNav()">&times;</span>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="analisador.php">📊 Analisador Pro</a>
        <a href="perfil.php">⚙️ Minha Conta</a>
        <?php if (in_array($perfil, ['Supervisor', 'Admin'])): ?>
            <a href="gestao_sinais.php">🛠️ Gestão de Sinais</a>
        <?php endif; ?>
        <a href="logout.php" style="color: #ff7675;">🚪 Sair</a>
    </div>

    <header>
        <div class="menu-icon" onclick="openNav()">☰</div>
        <div class="logo">SEFULL<span>BET</span></div>
        <div style="font-size: 14px; font-weight: 800; color: #2d3436;"><?= strtoupper($perfil) ?></div>
    </header>

    <!-- STATUS DO SERVIDOR (DINÂMICO) -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stats_gratis['p'] ?></div>
            <div class="stat-total"><?= $stats_gratis['t'] ?> Palpites</div>
            <div class="stat-label">Acerto Grátis</div>
            <div class="stat-counts"><span style="color:#27ae60"><?= $stats_gratis['g'] ?>G</span> / <span style="color:#d63031"><?= $stats_gratis['r'] ?>R</span></div>
        </div>
        <div class="stat-card vip">
            <div class="stat-value" style="color: #f39c12;"><?= $stats_vip['p'] ?></div>
            <div class="stat-total"><?= $stats_vip['t'] ?> Palpites</div>
            <div class="stat-label">Acerto VIP</div>
            <div class="stat-counts"><span style="color:#27ae60"><?= $stats_vip['g'] ?>G</span> / <span style="color:#d63031"><?= $stats_vip['r'] ?>R</span></div>
        </div>
    </div>

    <div class="action-box">
        <a href="analisador.php" class="btn-analisador">🔥 ANALISADOR PRO ⚡</a>
    </div>

    <div class="section-title">🎯 Palpites em Tempo Real</div>
    <div class="content-container">
        <?php foreach ($sinais as $sinal): 
            $is_vip_signal = ($sinal['p_categoria'] === 'VIP');
            $lock = ($is_vip_signal && !$pode_ver_vip);
            
            // Classes de CSS dinâmicas
            $row_class = $is_vip_signal ? 'vip-row' : 'free-row';
            $status_class = 'bg-waiting';
            $status_text = 'PENDENTE ⏳';
            
            if ($sinal['p_status'] === 'Green') { $status_class = 'bg-green'; $status_text = 'GREEN ✅'; }
            if ($sinal['p_status'] === 'Red') { $status_class = 'bg-red'; $status_text = 'RED ❌'; }
        ?>
        <div class="history-row <?= $row_class ?>">
            <?php if($lock): ?>
                <div class="lock-overlay">
                    <a href="upgrade.php" class="btn-lock">🔒 UPGRADE PARA VER VIP</a>
                </div>
            <?php endif; ?>

            <div class="row-top">
                <span><?= date('d/m/Y - H:i', strtotime($sinal['criado_em'])) ?></span>
                <span><?= htmlspecialchars($sinal['p_liga']) ?></span>
            </div>
            <div class="row-main">
                <div>
                    <span class="match-teams"><?= htmlspecialchars($sinal['p_confronto']) ?></span><br>
                    <span class="match-market"><?= htmlspecialchars($sinal['p_mercado']) ?></span>
                </div>
                <div style="text-align:right">
                    <div style="font-weight:900;"><?= $sinal['p_odd'] ?></div>
                    <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <footer>
        <strong>SEFULLBET PRO &copy; 2026</strong><br>
        Bem-vindo, <?= explode(' ', $user['nome'])[0] ?>.
    </footer>

    <script>
        function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
        function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }
    </script>
</body>
</html>
