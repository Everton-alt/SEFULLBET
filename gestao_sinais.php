<?php
session_start();
require_once 'config.php';

// Proteção de Acesso: Redireciona se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

// Bloqueio de segurança: Apenas Admin ou Supervisor acessam esta página
if (!in_array($user['perfil'], ['Supervisor', 'Admin'])) {
    header("Location: dashboard.php");
    exit();
}

$perfil = $user['perfil']; 
$cores = [
    'Grátis' => '#8b949e', 'VIP' => '#ffd700', 'Platinum' => '#ffffff',
    'Supervisor' => '#00e5ff', 'Admin' => '#00ff88'
];
$cor_perfil = $cores[$perfil] ?? $cores['Grátis'];

// --- Lógica de Estatísticas Reais para a Gestão ---
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

// Paginação da Tabela
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limit = 10;
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

        /* SIDEBAR UNIFICADA */
        nav { 
            width: 280px; background: rgba(22, 27, 34, 0.8); backdrop-filter: blur(10px);
            border-right: 1px solid var(--border); padding: 30px 15px;
            display: flex; flex-direction: column; position: fixed; height: 100vh;
            overflow-y: auto;
        }

        .nav-logo { font-weight: 800; font-size: 1.6rem; letter-spacing: -1px; margin-bottom: 30px; text-align: center; }
        .nav-logo span { color: var(--primary); }
        .nav-group { margin-bottom: 25px; }
        .nav-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-left: 15px; margin-bottom: 8px; display: block; font-weight: 700; }

        .nav-btn { 
            color: var(--text-dim); padding: 12px 18px; border-radius: 12px; text-decoration: none; 
            display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 500;
            transition: 0.3s; margin-bottom: 2px;
        }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .nav-btn.active { background: #065f46; color: var(--primary); border: 1px solid rgba(0, 255, 136, 0.2); }

        /* MAIN CONTENT */
        main { flex: 1; margin-left: 280px; padding: 40px 60px; width: calc(100% - 280px); }

        /* FORMULÁRIO DE GESTÃO */
        .form-container { 
            background: var(--card); border: 1px solid var(--border); 
            padding: 30px; border-radius: 20px; margin-bottom: 40px; 
        }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
        .input-group label { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 8px; font-weight: 700; }
        .input-group input, .input-group select { 
            width: 100%; background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; outline: none; font-size: 13px;
        }
        .btn-pub { 
            grid-column: 1 / -1; background: var(--primary); color: #0d1117; border: none; padding: 15px; 
            border-radius: 12px; font-weight: 800; cursor: pointer; margin-top: 10px; text-transform: uppercase;
        }

        /* TABELA DE SINAIS */
        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(255,255,255,0.02); padding: 15px 20px; text-align: left; font-size: 11px; color: var(--text-dim); text-transform: uppercase; }
        td { padding: 18px 20px; border-bottom: 1px solid var(--border); font-size: 13px; }
        
        .tag-status { padding: 5px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .st-green { background: rgba(0, 255, 136, 0.1); color: var(--primary); }
        .st-red { background: rgba(255, 77, 77, 0.1); color: var(--danger); }
        .st-pendente { background: rgba(255, 215, 0, 0.1); color: var(--vip); }

        /* PERFORMANCE CARDS */
        .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
        .perf-card { background: var(--card); padding: 25px; border-radius: 20px; border: 1px solid var(--border); }
        .perf-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); text-align: center; }
        .stat-box span { display: block; font-size: 10px; color: var(--text-dim); margin-bottom: 5px; }
        .stat-box b { font-size: 20px; font-weight: 800; }

        @media (max-width: 1100px) {
            nav { width: 80px; }
            .nav-label, .nav-btn span, .nav-logo { display: none; }
            main { margin-left: 80px; width: calc(100% - 80px); padding: 20px; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    
    <div class="nav-group">
        <span class="nav-label">Menu Principal</span>
        <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
        <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
        <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
        <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 15px 10px;">
        
        <span class="nav-label">Gestão Administrativa</span>
        <a class="nav-btn active" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
        <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
        <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
        <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
        <a class="nav-btn" href="gestao_noticias.php"><i class="fas fa-newspaper"></i> <span>Gestão de Notícias</span></a>
        <a class="nav-btn" href="gestao_notas.php"><i class="fas fa-edit"></i> <span>Gestão de Notas</span></a>
    </div>

    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <div style="margin-bottom: 30px;">
        <h1 style="font-weight: 800;">Gestão de Sinais</h1>
        <p style="color: var(--text-dim); font-size: 14px;">Publique novos palpites e gerencie os resultados em tempo real.</p>
    </div>

    <!-- PERFORMANCE REAL -->
    <div class="perf-grid">
        <div class="perf-card">
            <div class="perf-header" style="font-size: 11px; font-weight: 800; color: var(--text-dim); margin-bottom: 15px;">
                PERFORMANCE GRÁTIS
            </div>
            <div class="perf-stats-row">
                <div class="stat-box"><span>TOTAL</span><b><?= $stats_gratis['t'] ?></b></div>
                <div class="stat-box"><span>GREENS</span><b style="color:var(--primary)"><?= $stats_gratis['g'] ?></b></div>
                <div class="stat-box"><span>REDS</span><b style="color:var(--danger)"><?= $stats_gratis['r'] ?></b></div>
                <div class="stat-box"><span>WIN%</span><b><?= $stats_gratis['p'] ?></b></div>
            </div>
        </div>
        <div class="perf-card" style="border-color: var(--vip);">
            <div class="perf-header" style="font-size: 11px; font-weight: 800; color: var(--vip); margin-bottom: 15px;">
                PERFORMANCE VIP
            </div>
            <div class="perf-stats-row">
                <div class="stat-box"><span>TOTAL</span><b><?= $stats_vip['t'] ?></b></div>
                <div class="stat-box"><span>GREENS</span><b style="color:var(--primary)"><?= $stats_vip['g'] ?></b></div>
                <div class="stat-box"><span>REDS</span><b style="color:var(--danger)"><?= $stats_vip['r'] ?></b></div>
                <div class="stat-box"><span>WIN%</span><b><?= $stats_vip['p'] ?></b></div>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIO -->
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
                <input type="text" name="p_confronto" placeholder="Ex: Santos x Inter" required>
            </div>
            <div class="input-group">
                <label>Mercado</label>
                <input type="text" name="p_mercado" placeholder="Ex: Over 2.5" required>
            </div>
            <div class="input-group">
                <label>Odd</label>
                <input type="text" name="p_odd" placeholder="1.80" required>
            </div>
            <div class="input-group">
                <label>Data</label>
                <input type="date" name="p_data" value="<?= date('Y-m-d') ?>">
            </div>
            <button type="submit" class="btn-pub">Publicar Sinal Agora</button>
        </form>
    </section>

    <!-- TABELA -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Categoria</th>
                    <th>Confronto</th>
                    <th>Mercado</th>
                    <th>Odd</th>
                    <th>Status</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sinais as $s): ?>
                <tr>
                    <td style="color: var(--primary); font-family: monospace; font-weight: 700;"><?= $s['p_codigo'] ?></td>
                    <td><b style="color: <?= ($s['p_categoria'] == 'VIP') ? 'var(--vip)' : 'var(--text-dim)' ?>"><?= strtoupper($s['p_categoria']) ?></b></td>
                    <td><?= $s['p_confronto'] ?></td>
                    <td><?= $s['p_mercado'] ?></td>
                    <td><b><?= number_format($s['p_odd'], 2) ?></b></td>
                    <td>
                        <span class="tag-status st-<?= strtolower($s['p_status']) ?>">
                            <?= $s['p_status'] ?>
                        </span>
                    </td>
                    <td style="text-align:right">
                        <a href="status.php?id=<?= $s['id'] ?>&set=Green" title="Green"><i class="fas fa-check-circle" style="color: var(--primary); margin-left: 10px; cursor:pointer;"></i></a>
                        <a href="status.php?id=<?= $s['id'] ?>&set=Red" title="Red"><i class="fas fa-times-circle" style="color: var(--danger); margin-left: 10px; cursor:pointer;"></i></a>
                        <a href="apagar.php?id=<?= $s['id'] ?>" title="Excluir"><i class="fas fa-trash" style="color: var(--text-dim); margin-left: 10px; cursor:pointer;"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
