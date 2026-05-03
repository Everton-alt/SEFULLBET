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
$aprovado = ($user['status_aprovacao'] == 'Ativo');

// 3. Regras de Permissão
$pode_ver_vip = in_array($perfil, ['VIP', 'Platinum', 'Supervisor', 'Admin']);
$is_platinum = ($perfil === 'Platinum');

// 4. Configuração Visual de Cores
$cores = [
    'Grátis' => '#8b949e', 'VIP' => '#ffd700', 'Platinum' => '#e5e7eb',
    'Supervisor' => '#00e5ff', 'Admin' => '#00ff88'
];
$cor_perfil = $cores[$perfil] ?? $cores['Grátis'];

// 5. Busca de Sinais Reais (Palpites) - DINÂMICO
$stmt_sinais = $pdo->query("SELECT * FROM sinais ORDER BY id DESC LIMIT 10");
$lista_sinais = $stmt_sinais->fetchAll();

// Estatísticas Dinâmicas para os Cards de Performance
function getGlobalStats($pdo, $cat) {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN p_status = 'Green' THEN 1 ELSE 0 END) as greens,
        SUM(CASE WHEN p_status = 'Red' THEN 1 ELSE 0 END) as reds
        FROM sinais WHERE p_categoria = ?");
    $stmt->execute([$cat]);
    return $stmt->fetch();
}
$stat_g = getGlobalStats($pdo, 'Grátis');
$stat_v = getGlobalStats($pdo, 'VIP');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00ff88; --bg: #0b0e14; --card: #161b22; --border: #30363d;
            --text: #c9d1d9; --vip: #ffd700; --danger: #ff4d4d; --plat: #e5e7eb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; overflow: hidden; }
        nav { width: 260px; background: var(--card); border-right: 1px solid var(--border); padding: 25px 20px; display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
        .nav-logo { font-size: 1.5rem; font-weight: 900; color: var(--primary); margin-bottom: 30px; text-align: center; }
        .nav-logo span { color: #fff; }
        .nav-btn { color: #8b949e; padding: 14px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 14px; transition: 0.3s; }
        .nav-btn:hover, .nav-btn.active { background: #21262d; color: var(--primary); }
        main { flex: 1; padding: 30px; overflow-y: auto; background-image: radial-gradient(at 0% 0%, rgba(0,255,136,0.03) 0%, transparent 50%); }
        .header-dash { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: var(--card); padding: 20px; border-radius: 15px; border: 1px solid var(--border); }
        .badge-perfil { padding: 6px 14px; border-radius: 50px; font-weight: 900; font-size: 11px; border: 1px solid; text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px; }
        .stats-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .stats-box { background: var(--card); padding: 20px; border-radius: 15px; border: 1px solid var(--border); }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 15px; text-align: center; }
        .stat-val { display: block; font-size: 1.2rem; font-weight: 800; color: #fff; }
        .stat-label { font-size: 9px; color: #8b949e; text-transform: uppercase; }
        .palpite-row { background: var(--card); border: 1px solid var(--border); padding: 15px; border-radius: 12px; margin-bottom: 10px; display: grid; grid-template-columns: 110px 1fr 80px 80px 100px; align-items: center; gap: 15px; position: relative; overflow: hidden; }
        .status-tag { padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 900; text-align: center; text-transform: uppercase; }
        .st-green { background: rgba(0,255,136,0.1); color: var(--primary); }
        .st-red { background: rgba(255,77,77,0.1); color: var(--danger); }
        .st-pendente { background: rgba(255,215,0,0.1); color: var(--vip); }
        .locked-content { filter: blur(6px); opacity: 0.3; pointer-events: none; user-select: none; }
        .lock-notice { position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; z-index:10; color: var(--vip); font-weight:bold; font-size: 12px; background: rgba(0,0,0,0.2); }
        .vitorias-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
        .v-card { background: var(--card); border: 1px solid var(--border); border-radius: 15px; overflow: hidden; cursor: pointer; transition: 0.3s; }
        .v-card:hover { border-color: var(--primary); transform: translateY(-3px); }
        .v-img { width: 100%; height: 160px; object-fit: cover; background: #21262d; }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    <a class="nav-btn active" href="#"><i class="fas fa-home"></i> Feed Usuário</a>
    <a class="nav-btn" href="analisador.php"><i class="fas fa-robot"></i> Analisador Sefull</a>
    <a class="nav-btn" href="vitorias.php"><i class="fas fa-trophy"></i> Vitórias</a>
    <a class="nav-btn" href="noticias.php"><i class="fas fa-newspaper"></i> Notas</a>
    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
</nav>

<main>
    <header class="header-dash">
        <div>
            <h2 style="font-size: 1.2rem;">Olá, <?php echo $user['nome']; ?></h2>
            <span class="badge-perfil" style="color:<?php echo $cor_perfil; ?>; border-color:<?php echo $cor_perfil; ?>;">
                <i class="fas fa-crown"></i> <?php echo $perfil; ?>
            </span>
        </div>
        <div style="text-align: right">
            <small style="color: #8b949e">Créditos Analisador</small>
            <div style="font-size: 1.5rem; color: var(--primary); font-weight: 900;">
                <?php echo $is_platinum ? '∞ <span style="font-size:12px">ILIMITADO</span>' : $user['saldo_creditos']; ?>
            </div>
        </div>
    </header>

    <div class="stats-wrapper">
        <div class="stats-box">
            <h4 style="font-size: 12px;"><i class="fas fa-chart-line"></i> PERFORMANCE GRÁTIS</h4>
            <div class="stats-grid">
                <div><span class="stat-label">Total</span><span class="stat-val"><?= $stat_g['total'] ?></span></div>
                <div><span class="stat-label">Greens</span><span class="stat-val" style="color:var(--primary)"><?= $stat_g['greens'] ?: 0 ?></span></div>
                <div><span class="stat-label">Reds</span><span class="stat-val" style="color:var(--danger)"><?= $stat_g['reds'] ?: 0 ?></span></div>
                <div><span class="stat-label">Win%</span><span class="stat-val"><?= $stat_g['total'] > 0 ? round(($stat_g['greens']/$stat_g['total'])*100) : 0 ?>%</span></div>
            </div>
        </div>
        <div class="stats-box" style="border-color: var(--vip)">
            <h4 style="font-size: 12px; color: var(--vip)"><i class="fas fa-gem"></i> PERFORMANCE VIP</h4>
            <div class="stats-grid">
                <div><span class="stat-label">Total</span><span class="stat-val"><?= $stat_v['total'] ?></span></div>
                <div><span class="stat-label">Greens</span><span class="stat-val" style="color:var(--primary)"><?= $stat_v['greens'] ?: 0 ?></span></div>
                <div><span class="stat-label">Reds</span><span class="stat-val" style="color:var(--danger)"><?= $stat_v['reds'] ?: 0 ?></span></div>
                <div><span class="stat-label">Win%</span><span class="stat-val"><?= $stat_v['total'] > 0 ? round(($stat_v['greens']/$stat_v['total'])*100) : 0 ?>%</span></div>
            </div>
        </div>
    </div>

    <h3>Palpites Recentes</h3>
    <div style="margin-top: 15px;">
        <?php foreach($lista_sinais as $s): 
            $bloqueado = ($s['p_categoria'] == 'VIP' && !$pode_ver_vip);
        ?>
        <div class="palpite-row">
            <?php if($bloqueado): ?>
                <div class="lock-notice"><i class="fas fa-lock"></i> CONTEÚDO EXCLUSIVO VIP</div>
            <?php endif; ?>

            <div class="<?= $bloqueado ? 'locked-content' : '' ?>" style="display: contents;">
                <div class="status-tag st-<?= strtolower($s['p_status']) ?>"><?= $s['p_status'] ?></div>
                <div>
                    <b><?= $s['p_confronto'] ?></b><br>
                    <small style="color:#8b949e"><?= $s['p_hora'] ?> - <?= $s['p_mercado'] ?></small>
                </div>
                <div style="text-align:center">@<?= number_format($s['p_odd'], 2) ?></div>
                <div style="text-align:center"><?= $s['p_placar'] ?: '- x -' ?></div>
                <div style="text-align:right">
                    <span class="badge-perfil" style="border-color:<?= ($s['p_categoria']=='VIP'?'#ffd700':'#30363d') ?>; color:<?= ($s['p_categoria']=='VIP'?'#ffd700':'#8b949e') ?>">
                        <?= strtoupper($s['p_categoria']) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <h3 style="margin-top: 40px;">Vitórias & Resultados</h3>
    <div class="vitorias-container">
        <!-- Aqui entrará o loop das vitórias que você enviar depois -->
        <div class="v-card">
            <img src="https://via.placeholder.com/400x200/12151c/00ff88" class="v-img">
            <div style="padding: 15px;">
                <h4 style="font-size: 14px;">Green épico na Odd 4.50</h4>
                <p style="font-size: 12px; color:#8b949e; margin-top:5px;">Veja como o Analisador previu esta vitória...</p>
            </div>
        </div>
    </div>

    <footer style="margin-top: 50px; text-align: center; padding: 20px; color: #444; font-size: 11px; border-top: 1px solid var(--border);">
        &copy; 2026 Sefull Bet - Inteligência em Apostas.
    </footer>
</main>

</body>
</html>
