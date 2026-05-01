<?php
session_start();
require_once 'config.php';

// --- LOGICA DE CONTAGEM REAL (SQL) ---
function getStats($pdo, $cat) {
    $total = $pdo->prepare("SELECT COUNT(*) FROM sinais WHERE p_categoria = ?");
    $total->execute([$cat]);
    $t = $total->fetchColumn();

    $greens = $pdo->prepare("SELECT COUNT(*) FROM sinais WHERE p_categoria = ? AND p_status = 'Green'");
    $greens->execute([$cat]);
    $g = $greens->fetchColumn();

    $reds = $pdo->prepare("SELECT COUNT(*) FROM sinais WHERE p_categoria = ? AND p_status = 'Red'");
    $reds->execute([$cat]);
    $r = $reds->fetchColumn();

    $assert = ($t > 0) ? round(($g / $t) * 100, 1) : 0;
    return ['t' => $t, 'g' => $g, 'r' => $r, 'p' => $assert . '%'];
}

$stats_gratis = getStats($pdo, 'Grátis');
$stats_vip = getStats($pdo, 'VIP');

// --- PAGINAÇÃO E BUSCA ---
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limit = 10;
$offset = ($pagina - 1) * $limit;

$stmt = $pdo->prepare("SELECT * FROM sinais ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->execute([$limit, $offset]);
$sinais = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Sinais | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00ff88; --bg: #080a0c; --card: #111418;
            --border: #1e2329; --text: #ffffff; --text-dim: #848e9c; --vip: #ffd700;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); display: flex; color: var(--text); }

        /* --- MENU LATERAL (SIDEBAR) --- */
        .sidebar {
            width: 260px; height: 100vh; background: #0b0e11;
            border-right: 1px solid var(--border); position: fixed;
            padding: 30px 20px; display: flex; flex-direction: column;
        }
        .logo { font-size: 22px; font-weight: 900; margin-bottom: 40px; color: #fff; text-decoration:none; }
        .logo span { color: var(--primary); }
        
        .nav-link {
            color: var(--text-dim); text-decoration: none; padding: 12px 15px;
            border-radius: 8px; display: flex; align-items: center; gap: 12px;
            margin-bottom: 5px; transition: 0.3s; font-size: 14px;
        }
        .nav-link:hover, .nav-link.active { background: #161a1e; color: var(--primary); }

        /* --- CONTEÚDO DIREITO --- */
        .main-wrapper { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }

        /* --- STATS CARDS --- */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .stat-card { 
            background: var(--card); border: 1px solid var(--border); border-radius: 15px; 
            padding: 20px; display: flex; justify-content: space-around; position: relative;
        }
        .stat-card::after { content:''; position:absolute; left:0; top:25%; height:50%; width:4px; border-radius:0 4px 4px 0; }
        .sc-gratis::after { background: var(--primary); box-shadow: 0 0 10px var(--primary); }
        .sc-vip::after { background: var(--vip); box-shadow: 0 0 10px var(--vip); }

        .stat-box span { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 5px; }
        .stat-box b { font-size: 18px; }

        /* --- FORMULÁRIO HORIZONTAL ATUALIZADO --- */
        .form-section { background: var(--card); border: 1px solid var(--border); border-radius: 15px; padding: 25px; margin-bottom: 30px; }
        .form-row { display: grid; grid-template-columns: 100px 1.2fr 130px 100px 1fr 80px 80px; gap: 12px; }
        .field-group label { display: block; font-size: 10px; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; }
        .field-group input, .field-group select {
            width: 100%; background: #080a0c; border: 1px solid var(--border); color: #fff;
            padding: 10px; border-radius: 6px; outline: none; font-size: 13px;
        }
        .btn-pub { 
            grid-column: 1 / -1; background: var(--primary); color: #000; border: none; 
            padding: 15px; border-radius: 8px; font-weight: 800; cursor: pointer; 
            margin-top: 15px; text-transform: uppercase; transition: 0.3s;
        }

        /* --- TABELA GESTÃO --- */
        .table-box { background: var(--card); border: 1px solid var(--border); border-radius: 15px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #161a1e; padding: 15px; text-align: left; font-size: 11px; color: var(--text-dim); text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 13px; }

        .badge-cat { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; }
        .cat-gratis { background: rgba(0, 255, 136, 0.1); color: var(--primary); }
        .cat-vip { background: rgba(255, 215, 0, 0.1); color: var(--vip); }

        .st-badge { 
            padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800;
            border: 1px solid; display: inline-block; text-align: center; min-width: 70px;
        }
        .st-green { color: var(--primary); border-color: var(--primary); background: rgba(0,255,136,0.05); }
        .st-red { color: #ff4d4d; border-color: #ff4d4d; background: rgba(255,77,77,0.05); }
        .st-pendente { color: var(--text-dim); border-color: var(--border); }

        .actions i { cursor: pointer; margin-left: 10px; color: var(--text-dim); transition: 0.2s; }
        .actions i:hover { color: #fff; }
        
        .placar-text { font-weight: bold; color: var(--primary); }
    </style>
</head>
<body>

    <!-- MENU LATERAL -->
    <aside class="sidebar">
        <a href="#" class="logo">SEFULL <span>BET</span></a>
        <nav>
            <a href="admin.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="gestao_sinais.php" class="nav-link active"><i class="fas fa-signal"></i> Gestão de Sinais</a>
            <a href="usuarios.php" class="nav-link"><i class="fas fa-users"></i> Usuários</a>
            <a href="logout.php" class="nav-link" style="margin-top: 50px; color: #ff4d4d;"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </nav>
    </aside>

    <main class="main-wrapper">

        <!-- CARDS DE ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card sc-gratis">
                <div class="stat-box"><span>Total Grátis</span><b><?= $stats_gratis['t'] ?></b></div>
                <div class="stat-box"><span>Greens</span><b style="color:var(--primary)"><?= $stats_gratis['g'] ?></b></div>
                <div class="stat-box"><span>Reds</span><b style="color:#ff4d4d"><?= $stats_gratis['r'] ?></b></div>
                <div class="stat-box"><span>Assertividade</span><b style="color:var(--primary)"><?= $stats_gratis['p'] ?></b></div>
            </div>
            <div class="stat-card sc-vip">
                <div class="stat-box"><span>Total VIP</span><b><?= $stats_vip['t'] ?></b></div>
                <div class="stat-box"><span>Greens</span><b style="color:var(--primary)"><?= $stats_vip['g'] ?></b></div>
                <div class="stat-box"><span>Reds</span><b style="color:#ff4d4d"><?= $stats_vip['r'] ?></b></div>
                <div class="stat-box"><span>Assertividade</span><b style="color:var(--primary)"><?= $stats_vip['p'] ?></b></div>
            </div>
        </div>

        <!-- FORMULÁRIO COM CAMPO PLACAR -->
        <section class="form-section">
            <form action="processar_sinal.php" method="POST">
                <div class="form-row">
                    <div class="field-group">
                        <label>Tipo</label>
                        <select name="p_categoria">
                            <option value="Grátis">GRÁTIS</option>
                            <option value="VIP">VIP</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Confronto</label>
                        <input type="text" name="p_confronto" placeholder="Santos x Inter" required>
                    </div>
                    <div class="field-group">
                        <label>Data</label>
                        <input type="date" name="p_data" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="field-group">
                        <label>Hora</label>
                        <input type="time" name="p_hora">
                    </div>
                    <div class="field-group">
                        <label>Mercado</label>
                        <input type="text" name="p_mercado" placeholder="Over 2.5" required>
                    </div>
                    <div class="field-group">
                        <label>Odd</label>
                        <input type="text" name="p_odd" placeholder="1.80" required>
                    </div>
                    <div class="field-group">
                        <label>Placar</label>
                        <input type="text" name="p_placar" placeholder="0x0">
                    </div>
                    <button type="submit" class="btn-pub">Publicar Palpite</button>
                </div>
            </form>
        </section>

        <!-- TABELA COM CAMPO PLACAR -->
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Confronto</th>
                        <th>Placar</th>
                        <th>Data/Hora</th>
                        <th>Mercado</th>
                        <th>Odd</th>
                        <th>Status</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sinais as $s): ?>
                    <tr>
                        <td style="color:var(--primary);"><?= $s['p_codigo'] ?></td>
                        <td><span class="badge-cat <?= ($s['p_categoria'] == 'VIP') ? 'cat-vip' : 'cat-gratis' ?>"><?= strtoupper($s['p_categoria']) ?></span></td>
                        <td><?= $s['p_confronto'] ?></td>
                        <td class="placar-text"><?= $s['p_placar'] ?: '- x -' ?></td>
                        <td style="color:var(--text-dim)"><?= $s['p_data'] ?> | <?= substr($s['p_hora'], 0, 5) ?></td>
                        <td><?= $s['p_mercado'] ?></td>
                        <td><b><?= number_format($s['p_odd'], 2) ?></b></td>
                        <td><span class="st-badge st-<?= strtolower($s['p_status']) ?>"><?= strtoupper($s['p_status']) ?></span></td>
                        <td class="actions" style="text-align: right;">
                            <a href="status.php?id=<?= $s['id'] ?>&set=Green"><i class="fas fa-check-circle" style="color:var(--primary)"></i></a>
                            <a href="status.php?id=<?= $s['id'] ?>&set=Red"><i class="fas fa-times-circle" style="color:#ff4d4d"></i></a>
                            <a href="apagar.php?id=<?= $s['id'] ?>"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
