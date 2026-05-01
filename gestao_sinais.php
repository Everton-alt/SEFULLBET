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

// --- Lógica de Estatísticas Reais ---
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
            --bg: #0d1117;
            --card: #161b22;
            --border: #30363d;
            --text-main: #f0f6fc;
            --text-dim: #8b949e;
            --vip: #ffd700;
            --danger: #ff4d4d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: var(--bg); 
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR COMPLETA DASHBOARD */
        nav { 
            width: 280px; background: rgba(22, 27, 34, 0.95); backdrop-filter: blur(10px);
            border-right: 1px solid var(--border); padding: 30px 15px;
            display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100;
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

        /* PERFORMANCE CARDS (ESTILO IMAGEM REFERÊNCIA) */
        .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
        .perf-card { 
            background: var(--card); padding: 25px; border-radius: 20px; border: 1px solid var(--border); 
            border-left: 4px solid var(--primary);
        }
        .perf-vip { border-left-color: var(--vip); }
        .perf-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); text-align: center; }
        .stat-box span { display: block; font-size: 9px; color: var(--text-dim); margin-bottom: 8px; font-weight: 800; text-transform: uppercase; }
        .stat-box b { font-size: 20px; font-weight: 800; }

        /* FORMULÁRIO COM CAMPOS SEPARADOS */
        .form-container { background: var(--card); border: 1px solid var(--border); padding: 25px; border-radius: 20px; margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1.5fr 0.8fr 1.2fr 0.8fr 1.2fr 0.8fr; gap: 12px; align-items: flex-end; }
        .input-group label { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 8px; font-weight: 700; }
        .input-group input, .input-group select { 
            width: 100%; background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; outline: none; font-size: 13px;
        }
        .btn-pub { 
            grid-column: 1 / -1; background: var(--primary); color: #0d1117; border: none; padding: 15px; 
            border-radius: 10px; font-weight: 800; cursor: pointer; margin-top: 15px; text-transform: uppercase;
        }

        /* TABELA COM CAMPOS SEPARADOS */
        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(255,255,255,0.02); padding: 15px 20px; text-align: left; font-size: 10px; color: var(--text-dim); text-transform: uppercase; }
        td { padding: 15px 20px; border-bottom: 1px solid var(--border); font-size: 12px; }
        
        .tag-tipo { padding: 4px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; background: rgba(0, 255, 136, 0.1); color: var(--primary); }
        .tag-status { padding: 4px 8px; border-radius: 6px; font-size: 9px; font-weight: 800; border: 1px solid; }
        .st-green { color: var(--primary); border-color: var(--primary); }
        .st-red { color: var(--danger); border-color: var(--danger); }
        .st-pendente { color: var(--vip); border-color: var(--vip); }

        .action-icons i { margin-left: 12px; cursor: pointer; color: var(--text-dim); transition: 0.2s; }
        .action-icons i:hover { color: #fff; }

        @media (max-width: 1100px) {
            nav { width: 80px; }
            .nav-label, .nav-btn span, .nav-logo { display: none; }
            main { margin-left: 80px; width: calc(100% - 80px); padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    
    <div class="nav-group">
        <span class="nav-label">Menu Principal</span>
        <a class="nav-btn active" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
        
        <!-- Novos itens adicionados -->
        <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
        <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
        
        <!-- Itens mantidos dos grupos anteriores -->
        <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 15px 10px;">
        
        <!-- Gestão Administrativa -->
        <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
        <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
        <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
        <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
        <a class="nav-btn" href="gestao_noticias.php"><i class="fas fa-newspaper"></i> <span>Gestão de Notícias</span></a>
        <a class="nav-btn" href="gestao_notas.php"><i class="fas fa-edit"></i> <span>Gestão de Notas</span></a>
    </div>

    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <!-- PERFORMANCE REAL (Cards Superiores) -->
    <div class="perf-grid">
        <div class="perf-card">
            <div class="perf-stats-row">
                <div class="stat-box"><span>TOTAL GRÁTIS</span><b><?= $stats_gratis['t'] ?></b></div>
                <div class="stat-box"><span>GREENS</span><b style="color:var(--primary)"><?= $stats_gratis['g'] ?></b></div>
                <div class="stat-box"><span>REDS</span><b style="color:var(--danger)"><?= $stats_gratis['r'] ?></b></div>
                <div class="stat-box"><span>ASSERTIVIDADE</span><b style="color:var(--primary)"><?= $stats_gratis['p'] ?></b></div>
            </div>
        </div>
        <div class="perf-card perf-vip">
            <div class="perf-stats-row">
                <div class="stat-box"><span>TOTAL VIP</span><b><?= $stats_vip['t'] ?></b></div>
                <div class="stat-box"><span>GREENS</span><b style="color:var(--primary)"><?= $stats_vip['g'] ?></b></div>
                <div class="stat-box"><span>REDS</span><b style="color:var(--danger)"><?= $stats_vip['r'] ?></b></div>
                <div class="stat-box"><span>ASSERTIVIDADE</span><b style="color:var(--primary)"><?= $stats_vip['p'] ?></b></div>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIO (TODOS OS CAMPOS SEPARADOS) -->
    <section class="form-container">
        <form action="processar_sinal.php" method="POST" class="form-grid">
            <div class="input-group">
                <label>Tipo</label>
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
                <label>Placar</label>
                <input type="text" name="p_placar" placeholder="0x0">
            </div>
            <div class="input-group">
                <label>Data Evento</label>
                <input type="date" name="p_data" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="input-group">
                <label>Hora</label>
                <input type="time" name="p_hora">
            </div>
            <div class="input-group">
                <label>Mercado</label>
                <input type="text" name="p_mercado" placeholder="Ex: Over 2.5" required>
            </div>
            <div class="input-group">
                <label>Odd</label>
                <input type="text" name="p_odd" placeholder="1.80" required>
            </div>
            <button type="submit" class="btn-pub">Publicar Palpite Agora</button>
        </form>
    </section>

    <!-- TABELA (TODOS OS CAMPOS SEPARADOS) -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Confronto</th>
                    <th>Placar</th>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Mercado</th>
                    <th>Odd</th>
                    <th>Status</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sinais as $s): ?>
                <tr>
                    <td style="color: var(--primary); font-family: monospace; font-size: 11px;"><?= $s['p_codigo'] ?></td>
                    <td><span class="tag-tipo <?= ($s['p_categoria'] == 'VIP') ? 'tag-vip' : '' ?>"><?= strtoupper($s['p_categoria']) ?></span></td>
                    <td style="font-weight: 600;"><?= $s['p_confronto'] ?></td>
                    <td style="font-weight: 800; color: var(--primary);"><?= $s['p_placar'] ?: '0x0' ?></td>
                    <td><?= date('d/m/Y', strtotime($s['p_data'])) ?></td>
                    <td style="color: var(--text-dim);"><?= $s['p_hora'] ?: '--:--' ?></td>
                    <td><?= $s['p_mercado'] ?></td>
                    <td><b><?= number_format($s['p_odd'], 2) ?></b></td>
                    <td>
                        <span class="tag-status st-<?= strtolower($s['p_status']) ?>">
                            <?= strtoupper($s['p_status']) ?>
                        </span>
                    </td>
                    <td class="action-icons" style="text-align:right">
                        <a href="status.php?id=<?= $s['id'] ?>&set=Green" title="Green"><i class="fas fa-check-circle" style="color: var(--primary)"></i></a>
                        <a href="status.php?id=<?= $s['id'] ?>&set=Red" title="Red"><i class="fas fa-times-circle" style="color: var(--danger)"></i></a>
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
