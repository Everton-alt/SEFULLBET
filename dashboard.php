<?php
session_start();
require_once 'config.php';

// 1. Verificação de Login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Busca dados atualizados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

$perfil = $user['perfil']; 
$pode_ver_vip = in_array($perfil, ['VIP', 'Platinum', 'Supervisor', 'Admin']);

// 3. Busca de Sinais Reais (Palpites)
$stmt_sinais = $pdo->query("SELECT * FROM sinais ORDER BY id DESC LIMIT 10");
$lista_sinais = $stmt_sinais->fetchAll();

// 4. Estatísticas Dinâmicas
function getStats($pdo, $cat) {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN p_status = 'Green' THEN 1 ELSE 0 END) as greens,
        SUM(CASE WHEN p_status = 'Red' THEN 1 ELSE 0 END) as reds
        FROM sinais WHERE p_categoria = ?");
    $stmt->execute([$cat]);
    return $stmt->fetch();
}
$stat_g = getStats($pdo, 'Grátis');
$stat_v = getStats($pdo, 'VIP');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefullbet - Clean Neon Edition</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            margin: 0;
            background-color: var(--bg-body);
            color: var(--text-main);
            padding-bottom: 50px;
        }

        /* --- SIDEBAR & OVERLAY --- */
        .sidebar {
            height: 100%; width: 280px; position: fixed; z-index: 2000;
            top: 0; left: -280px; background-color: #2d3436;
            overflow-x: hidden; transition: 0.4s; padding-top: 60px;
            box-shadow: 5px 0 15px rgba(0,0,0,0.1);
        }
        .sidebar a { padding: 15px 25px; text-decoration: none; font-size: 18px; color: white; display: block; border-bottom: 1px solid #3d4648; }
        .sidebar a.logout-btn { color: #ff7675; font-weight: bold; margin-top: 20px; border-bottom: none; }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 36px; cursor: pointer; color: var(--primary); }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }

        /* --- HEADER --- */
        header { 
            background-color: #ffffff; 
            color: var(--primary); 
            padding: 15px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky; 
            top: 0; 
            z-index: 100;
            border-bottom: 1px solid #eee;
        }
        .menu-icon { font-size: 24px; cursor: pointer; color: #2d3436; }
        .logo { font-weight: 900; font-size: 1.3rem; letter-spacing: 1px; color: #2d3436; }
        .logo span { color: var(--primary); }

        /* --- QUADRANTES --- */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 15px 10px 5px; }
        .stat-card { 
            background: var(--card-bg); 
            padding: 12px; 
            border-radius: 15px; 
            text-align: center; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            border: 1px solid #f1f1f1;
            border-top: 4px solid var(--primary);
        }
        .stat-card.vip { border-top-color: var(--warning); }
        .stat-value { font-size: 1.4rem; font-weight: bold; color: #2d3436; margin-bottom: 2px; }
        .stat-total { font-size: 0.75rem; font-weight: 600; color: var(--text-dim); margin-bottom: 5px; }
        .stat-label { font-size: 0.6rem; color: #aaa; text-transform: uppercase; letter-spacing: 1px; }
        .stat-counts { font-size: 0.65rem; margin-top: 8px; font-weight: bold; background: var(--bg-secondary); padding: 5px; border-radius: 8px; }
        .txt-green { color: #27ae60; }
        .txt-red { color: var(--danger); }

        /* --- BOTÃO ANALISADOR --- */
        .action-box { padding: 20px 15px; position: relative; display: flex; justify-content: center; align-items: center; }
        .highlight-ring { 
            position: absolute; width: 95%; height: 65px; 
            border: 2px solid var(--primary); border-radius: 18px; 
            animation: pulse-ring 1.5s infinite; z-index: 1; 
        }
        @keyframes pulse-ring { 0% { transform: scale(0.98); opacity: 0.8; } 100% { transform: scale(1.05); opacity: 0; } }
        
        .btn-analisador { 
            position: relative; z-index: 2; display: flex; align-items: center; justify-content: center; 
            background: linear-gradient(45deg, #2ecc71, #27ae60); 
            color: #fff; padding: 18px; border-radius: 15px; text-decoration: none; 
            font-weight: 800; font-size: 1.1rem; border: none; width: 100%; 
            box-shadow: 0 10px 20px rgba(46, 204, 113, 0.3); text-transform: uppercase; 
        }

        /* --- SEÇÕES --- */
        .section-title { padding: 20px 15px 10px; font-size: 0.85rem; font-weight: 800; color: #2d3436; text-transform: uppercase; letter-spacing: 1px; }
        .content-container { padding: 0 10px; }

        /* --- PALPITES --- */
        .history-row { 
            background: var(--card-bg); margin-bottom: 12px; border-radius: 15px; padding: 15px; 
            display: flex; flex-direction: column; border-left: 6px solid #dfe6e9;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            position: relative;
        }
        .history-row.vip-row { border-left-color: var(--warning); }
        .history-row.free-row { border-left-color: var(--accent-blue); }

        .row-top { display: flex; justify-content: space-between; font-size: 0.7rem; color: #b2bec3; margin-bottom: 10px; border-bottom: 1px solid #f1f1f1; padding-bottom: 6px; }
        .row-main { display: flex; justify-content: space-between; align-items: center; }
        .match-teams { font-weight: bold; font-size: 1rem; color: #2d3436; }
        .match-market { font-size: 0.8rem; color: var(--accent-blue); font-weight: 700; }
        
        .status-badge { font-size: 0.65rem; padding: 5px 10px; border-radius: 6px; font-weight: bold; text-transform: uppercase; display: inline-block; margin-top: 5px; }
        .bg-green { background: #eafaf1; color: #27ae60; }
        .bg-red { background: #fdf2f2; color: #e74c3c; }
        .bg-waiting { background: #fef9e7; color: #f1c40f; }

        .locked-content { filter: blur(5px); opacity: 0.3; pointer-events: none; }
        .lock-notice { position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; z-index:10; color: var(--warning); font-weight:bold; font-size: 11px; }

        footer { text-align: center; padding: 40px 20px; font-size: 0.75rem; color: #b2bec3; background: #f8f9fa; margin-top: 30px; }
    </style>
</head>
<body>

    <div id="overlay" class="overlay" onClick="closeNav()"></div>

    <div id="mySidebar" class="sidebar">
        <span class="close-btn" onClick="closeNav()">&times;</span>
        <a href="dashboard.php">🏠 Início</a>
        <a href="#">💎 Planos</a>
        <a href="analisador.php">📊 Analisador Sefullbet</a>
        <a href="vitorias.php">📈 Histórico de Greens</a>
        <a href="#">⚙️ Minha Conta</a>
        <a href="logout.php" class="logout-btn">🚪 Sair</a>
    </div>

    <header>
        <div class="menu-icon" onClick="openNav()">☰</div>
        <div class="logo">SEFULL<span>BET</span></div>
        <div style="font-size: 14px; font-weight: bold; color: var(--text-dim)">Olá, <?= explode(' ', $user['nome'])[0] ?></div>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stat_g['total'] > 0 ? round(($stat_g['greens']/$stat_g['total'])*100) : 0 ?>%</div>
            <div class="stat-total"><?= $stat_g['total'] ?> Palpites</div>
            <div class="stat-label">Acerto Grátis</div>
            <div class="stat-counts"><span class="txt-green"><?= $stat_g['greens'] ?: 0 ?>G</span> / <span class="txt-red"><?= $stat_g['reds'] ?: 0 ?>R</span></div>
        </div>
        <div class="stat-card vip">
            <div class="stat-value" style="color: #f39c12;"><?= $stat_v['total'] > 0 ? round(($stat_v['greens']/$stat_v['total'])*100) : 0 ?>%</div>
            <div class="stat-total"><?= $stat_v['total'] ?> Palpites</div>
            <div class="stat-label">Acerto VIP</div>
            <div class="stat-counts"><span class="txt-green"><?= $stat_v['greens'] ?: 0 ?>G</span> / <span class="txt-red"><?= $stat_v['reds'] ?: 0 ?>R</span></div>
        </div>
    </div>

    <div class="action-box">
        <div class="highlight-ring"></div>
        <a href="analisador.php" class="btn-analisador" style="text-decoration: none;">🔥 ANALISADOR PRO ⚡</a>
    </div>

    <div class="section-title">🎯 Palpites Recentes</div>
    <div class="content-container">
        <?php foreach($lista_sinais as $s): 
            $is_vip_signal = ($s['p_categoria'] == 'VIP');
            $bloqueado = ($is_vip_signal && !$pode_ver_vip);
        ?>
        <div class="history-row <?= $is_vip_signal ? 'vip-row' : 'free-row' ?>">
            <?php if($bloqueado): ?>
                <div class="lock-notice"><i class="fas fa-lock"></i> CONTEÚDO EXCLUSIVO VIP</div>
            <?php endif; ?>

            <div class="<?= $bloqueado ? 'locked-content' : '' ?>">
                <div class="row-top">
                    <span><?= $s['p_hora'] ?></span>
                    <span><?= strtoupper($s['p_categoria']) ?></span>
                </div>
                <div class="row-main">
                    <div>
                        <span class="match-teams"><?= $s['p_confronto'] ?></span><br>
                        <span class="match-market"><?= $s['p_mercado'] ?></span>
                    </div>
                    <div style="text-align:right">
                        <div style="font-weight:900;"><?= $s['p_placar'] ?: '- x -' ?></div>
                        <?php if($s['p_status'] == 'Green'): ?>
                            <span class="status-badge bg-green">GREEN ✅</span>
                        <?php elseif($s['p_status'] == 'Red'): ?>
                            <span class="status-badge bg-red">RED ❌</span>
                        <?php else: ?>
                            <span class="status-badge bg-waiting">PENDENTE ⏳</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
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

    <footer>
        <strong>SEFULLBET PRO &copy; 2026</strong><br>
        Plataforma de Inteligência e Análise Esportiva.
    </footer>

    <script>
        function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
        function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }
    </script>
</body>
</html>
