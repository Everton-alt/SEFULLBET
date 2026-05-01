<?php
session_start();
require_once 'config.php';

// Proteção de acesso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

// Somente Admin ou Supervisor acessam esta página
if (!in_array($user['perfil'], ['Supervisor', 'Admin'])) {
    header("Location: dashboard.php");
    exit();
}

// Estatísticas Reais para os Cards
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
$stats_vip = getStats($pdo, 'VIP');

// Paginação
$limit = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limit;
$sinais = $pdo->query("SELECT * FROM sinais ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Sinais | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-sidebar: #12191d;
            --bg-main: #0d1117;
            --card: #161b22;
            --border: #30363d;
            --primary: #00ff88;
            --primary-hover: rgba(0, 255, 136, 0.15);
            --text-main: #f0f6fc;
            --text-dim: #8b949e;
            --danger: #ff4d4d;
            --vip: #ffd700;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-main); color: var(--text-main); display: flex; min-height: 100vh; }

        /* SIDEBAR CONFORME A IMAGEM */
        nav { 
            width: 280px; 
            background: var(--bg-sidebar); 
            padding: 40px 20px;
            display: flex; 
            flex-direction: column; 
            position: fixed; 
            height: 100vh;
            border-right: 1px solid var(--border);
        }

        .nav-logo { 
            font-weight: 900; 
            font-size: 1.8rem; 
            text-align: center; 
            margin-bottom: 40px; 
            letter-spacing: -1px;
            color: #fff;
        }
        .nav-logo span { color: var(--primary); }

        .nav-label { 
            font-size: 11px; 
            color: var(--text-dim); 
            text-transform: uppercase; 
            font-weight: 700; 
            margin-left: 10px; 
            margin-bottom: 15px; 
            display: block; 
        }

        .nav-group { margin-bottom: 30px; }

        .nav-btn { 
            color: var(--text-dim); 
            padding: 14px 18px; 
            border-radius: 14px; 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            font-size: 14px; 
            font-weight: 600; 
            transition: 0.2s;
            margin-bottom: 5px;
        }

        .nav-btn i { font-size: 18px; width: 20px; text-align: center; }

        .nav-btn:hover { background: var(--primary-hover); color: #fff; }

        /* ITEM ATIVO CONFORME IMAGEM (VERDE) */
        .nav-btn.active { 
            background: #065f46; 
            color: var(--primary); 
        }

        .nav-separator {
            border: 0;
            border-top: 1px solid var(--border);
            margin: 20px 0;
            opacity: 0.5;
        }

        .btn-sair {
            margin-top: auto;
            color: var(--danger);
            font-weight: 700;
        }

        /* CONTEÚDO PRINCIPAL */
        main { flex: 1; margin-left: 280px; padding: 40px 50px; width: calc(100% - 280px); }

        .header-title { margin-bottom: 30px; }
        .header-title h1 { font-size: 26px; font-weight: 900; }
        .header-title p { color: var(--text-dim); margin-top: 5px; }

        /* CARDS DE PERFORMANCE */
        .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
        .perf-card { background: var(--card); padding: 25px; border-radius: 20px; border: 1px solid var(--border); }
        .perf-header { font-size: 12px; font-weight: 800; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: var(--text-dim); }
        .perf-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); text-align: center; }
        .stat-box span { display: block; font-size: 10px; color: var(--text-dim); margin-bottom: 8px; }
        .stat-box b { font-size: 22px; font-weight: 900; }

        /* FORMULÁRIO */
        .form-container { background: var(--card); border: 1px solid var(--border); padding: 30px; border-radius: 20px; margin-bottom: 40px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 20px; }
        .input-group label { display: block; font-size: 11px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 10px; font-weight: 800; }
        .input-group input, .input-group select { 
            width: 100%; background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; outline: none; font-size: 14px;
        }
        .btn-pub { 
            grid-column: 1 / -1; background: var(--primary); color: #0d1117; border: none; padding: 16px; 
            border-radius: 12px; font-weight: 900; cursor: pointer; margin-top: 10px; text-transform: uppercase; transition: 0.3s;
        }
        .btn-pub:hover { filter: brightness(1.1); transform: translateY(-2px); }

        /* TABELA */
        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(255,255,255,0.03); padding: 18px 25px; text-align: left; font-size: 11px; color: var(--text-dim); text-transform: uppercase; }
        td { padding: 20px 25px; border-bottom: 1px solid var(--border); font-size: 14px; }
        
        .tag-status { padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 900; border: 1px solid transparent; text-transform: uppercase; }
        .st-green { color: var(--primary); border-color: var(--primary); background: rgba(0, 255, 136, 0.1); }
        .st-red { color: var(--danger); border-color: var(--danger); background: rgba(255, 77, 77, 0.1); }
        .st-pendente { color: var(--vip); border-color: var(--vip); background: rgba(255, 215, 0, 0.1); }

        .action-btns i { margin-left: 15px; cursor: pointer; transition: 0.2s; }
        .action-btns i:hover { color: #fff; }

        @media (max-width: 1100px) {
            nav { width: 80px; padding: 20px 10px; }
            .nav-label, .nav-btn span, .nav-logo, .nav-separator { display: none; }
            main { margin-left: 80px; width: calc(100% - 80px); }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    
    <span class="nav-label">Menu Principal</span>
    <div class="nav-group">
        <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
        <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
        <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
        <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="gestao_banca.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>
    </div>

    <hr class="nav-separator">

    <div class="nav-group">
        <a class="nav-btn active" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
        <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
        <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
        <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
        <a class="nav-btn" href="gestao_noticias.php"><i class="fas fa-newspaper"></i> <span>Gestão de Notícias</span></a>
        <a class="nav-btn" href="gestao_notas.php"><i class="fas fa-edit"></i> <span>Gestão de Notas</span></a>
    </div>

    <a class="nav-btn btn-sair" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <div class="header-title">
        <h1>Gestão de Sinais</h1>
        <p>Painel de controle para publicação e validação de palpites.</p>
    </div>

    <!-- STATUS CARDS -->
    <div class="perf-grid">
        <div class="perf-card">
            <div class="perf-header"><i class="fas fa-chart-line"></i> PERFORMANCE GRÁTIS</div>
            <div class="perf-stats-row">
                <div class="stat-box"><span>TOTAL</span><b><?= $stats_gratis['t'] ?></b></div>
                <div class="stat-box"><span>GREENS</span><b style="color:var(--primary)"><?= $stats_gratis['g'] ?></b></div>
                <div class="stat-box"><span>REDS</span><b style="color:var(--danger)"><?= $stats_gratis['r'] ?></b></div>
                <div class="stat-box"><span>WIN%</span><b><?= $stats_gratis['p'] ?></b></div>
            </div>
        </div>
        <div class="perf-card" style="border-color: var(--vip);">
            <div class="perf-header" style="color: var(--vip);"><i class="fas fa-gem"></i> PERFORMANCE VIP</div>
            <div class="perf-stats-row">
                <div class="stat-box"><span>TOTAL</span><b><?= $stats_vip['t'] ?></b></div>
                <div class="stat-box"><span>GREENS</span><b style="color:var(--primary)"><?= $stats_vip['g'] ?></b></div>
                <div class="stat-box"><span>REDS</span><b style="color:var(--danger)"><?= $stats_vip['r'] ?></b></div>
                <div class="stat-box"><span>WIN%</span><b><?= $stats_vip['p'] ?></b></div>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIO DE PUBLICAÇÃO -->
    <section class="form-container">
        <form action="processar_sinal.php" method="POST" class="form-grid">
            <div class="input-group">
                <label>Categoria</label>
                <select name="p_categoria">
                    <option value="Grátis">GRÁTIS</option>
                    <option value="VIP">VIP</option>
                </select>
            </div>
            <div class="input-group">
                <label>Confronto</label>
                <input type="text" name="p_confronto" placeholder="Santos x Inter" required>
            </div>
            <div class="input-group">
                <label>Data</label>
                <input type="date" name="p_data" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="input-group">
                <label>Hora</label>
                <input type="time" name="p_hora" value="<?= date('H:i') ?>">
            </div>
            <div class="input-group">
                <label>Mercado</label>
                <input type="text" name="p_mercado" placeholder="Over 2.5" required>
            </div>
            <div class="input-group">
                <label>Odd</label>
                <input type="text" name="p_odd" placeholder="1.80" required>
            </div>
            <div class="input-group">
                <label>Placar Final</label>
                <input type="text" name="p_placar" placeholder="0x0">
            </div>
            <button type="submit" class="btn-pub">Publicar Palpite Agora</button>
        </form>
    </section>

    <!-- LISTAGEM DE SINAIS -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Confronto</th>
                    <th>Placar</th>
                    <th>Mercado</th>
                    <th>Odd</th>
                    <th>Status</th>
                    <th style="text-align:right">Validação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sinais as $s): ?>
                <tr>
                    <td style="color: var(--primary); font-family: monospace; font-weight: 700;"><?= $s['p_codigo'] ?></td>
                    <td>
                        <b style="color: <?= ($s['p_categoria'] == 'VIP') ? 'var(--vip)' : 'var(--text-dim)' ?>">
                            <?= strtoupper($s['p_categoria']) ?>
                        </b>
                    </td>
                    <td><?= $s['p_confronto'] ?></td>
                    <td style="font-weight: 800;"><?= $s['p_placar'] ?: '- x -' ?></td>
                    <td><?= $s['p_mercado'] ?></td>
                    <td><b><?= number_format($s['p_odd'], 2) ?></b></td>
                    <td>
                        <span class="tag-status st-<?= strtolower($s['p_status'] == 'Pendente' ? 'pendente' : ($s['p_status'] == 'Green' ? 'green' : 'red')) ?>">
                            <?= $s['p_status'] ?>
                        </span>
                    </td>
                    <td class="action-btns" style="text-align:right">
                        <a href="status.php?id=<?= $s['id'] ?>&set=Green"><i class="fas fa-check-circle" style="color: var(--primary);"></i></a>
                        <a href="status.php?id=<?= $s['id'] ?>&set=Red"><i class="fas fa-times-circle" style="color: var(--danger);"></i></a>
                        <a href="apagar.php?id=<?= $s['id'] ?>"><i class="fas fa-trash-alt" style="color: var(--text-dim);"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
