<?php
session_start();
require_once 'config.php';

// Proteção de Acesso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

if (!in_array($user['perfil'], ['Supervisor', 'Admin'])) {
    header("Location: dashboard.php");
    exit();
}

// --- Lógica de Estatísticas ---
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

// --- Lógica de Paginação (10 por página) ---
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$total_sinais = $pdo->query("SELECT COUNT(*) FROM sinais")->fetchColumn();
$total_paginas = ceil($total_sinais / $itens_por_pagina);

$sinais = $pdo->prepare("SELECT * FROM sinais ORDER BY id DESC LIMIT ? OFFSET ?");
$sinais->bindValue(1, $itens_por_pagina, PDO::PARAM_INT);
$sinais->bindValue(2, $offset, PDO::PARAM_INT);
$sinais->execute();
$lista_sinais = $sinais->fetchAll();
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
            --info: #3498db;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        /* Barra de Rolagem da Sidebar */
        nav::-webkit-scrollbar { width: 4px; }
        nav::-webkit-scrollbar-track { background: transparent; }
        nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

        body { 
            background-color: var(--bg); 
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* SIDEBAR */
        nav { 
            width: 280px; background: rgba(22, 27, 34, 0.8); backdrop-filter: blur(10px);
            border-right: 1px solid var(--border); padding: 30px 15px;
            display: flex; flex-direction: column; position: fixed; height: 100vh;
            overflow-y: auto; z-index: 1000;
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

        /* CONTEÚDO */
        main { flex: 1; margin-left: 280px; padding: 40px 60px; width: calc(100% - 280px); }

        .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
        .perf-card { background: var(--card); padding: 25px; border-radius: 20px; border: 1px solid var(--border); }
        .perf-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); text-align: center; }
        .stat-box span { display: block; font-size: 10px; color: var(--text-dim); margin-bottom: 5px; }
        .stat-box b { font-size: 20px; font-weight: 800; }

        .form-container { background: var(--card); border: 1px solid var(--border); padding: 30px; border-radius: 20px; margin-bottom: 40px; }
        .form-grid { display: grid; grid-template-columns: 1fr 2fr 0.8fr 1.2fr 0.8fr 1.2fr 0.8fr; gap: 15px; align-items: flex-end; }
        .input-group label { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 8px; font-weight: 700; }
        .input-group input, .input-group select { 
            width: 100%; background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; outline: none; font-size: 13px;
        }
        
        .btn-pub { background: var(--primary); color: #0d1117; border: none; padding: 15px; border-radius: 12px; font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-pub:hover { filter: brightness(1.1); box-shadow: 0 0 15px rgba(0, 255, 136, 0.3); }

        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(255,255,255,0.02); padding: 15px 20px; text-align: left; font-size: 11px; color: var(--text-dim); text-transform: uppercase; }
        td { padding: 18px 20px; border-bottom: 1px solid var(--border); font-size: 13px; }
        
        .st-green { color: var(--primary); font-weight: bold; }
        .st-red { color: var(--danger); font-weight: bold; }
        .st-pendente { color: var(--vip); font-weight: bold; }

        .pagination { display: flex; justify-content: center; gap: 10px; margin-top: 25px; }
        .page-link { padding: 8px 16px; background: var(--card); border: 1px solid var(--border); color: var(--text-main); text-decoration: none; border-radius: 8px; font-size: 13px; }
        .page-link.active { background: var(--primary); color: #000; font-weight: 700; border-color: var(--primary); }

        /* MODAL STYLE */
        #modalEditar {
            display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
            background:rgba(0,0,0,0.85); z-index:3000; align-items:center; justify-content:center;
            backdrop-filter: blur(5px);
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
    <h1 style="font-weight: 800; margin-bottom: 30px;">Gestão de Sinais</h1>

    <div class="perf-grid">
        <div class="perf-card">
            <div class="perf-stats-row">
                <div class="stat-box"><span>GRÁTIS</span><b><?= $stats_gratis['t'] ?></b></div>
                <div class="stat-box"><span>GREEN</span><b style="color:var(--primary)"><?= $stats_gratis['g'] ?></b></div>
                <div class="stat-box"><span>RED</span><b style="color:var(--danger)"><?= $stats_gratis['r'] ?></b></div>
                <div class="stat-box"><span>ASSERT.</span><b><?= $stats_gratis['p'] ?></b></div>
            </div>
        </div>
        <div class="perf-card" style="border-top: 3px solid var(--vip);">
            <div class="perf-stats-row">
                <div class="stat-box"><span>VIP</span><b><?= $stats_vip['t'] ?></b></div>
                <div class="stat-box"><span>GREEN</span><b style="color:var(--primary)"><?= $stats_vip['g'] ?></b></div>
                <div class="stat-box"><span>RED</span><b style="color:var(--danger)"><?= $stats_vip['r'] ?></b></div>
                <div class="stat-box"><span>ASSERT.</span><b><?= $stats_vip['p'] ?></b></div>
            </div>
        </div>
    </div>

    <section class="form-container">
        <form action="processar_sinal.php" method="POST" class="form-grid">
            <div class="input-group"><label>Categoria</label><select name="p_categoria"><option value="Grátis">Grátis</option><option value="VIP">VIP</option></select></div>
            <div class="input-group"><label>Confronto</label><input type="text" name="p_confronto" placeholder="Time A x Time B" required></div>
            <div class="input-group"><label>Placar</label><input type="text" name="p_placar" placeholder="0-0"></div>
            <div class="input-group"><label>Data</label><input type="date" name="p_data" value="<?= date('Y-m-d') ?>"></div>
            <div class="input-group"><label>Hora</label><input type="time" name="p_hora"></div>
            <div class="input-group"><label>Mercado</label><input type="text" name="p_mercado" placeholder="Over 2.5" required></div>
            <div class="input-group"><label>Odd</label><input type="text" name="p_odd" placeholder="1.80" required></div>
            <button type="submit" class="btn-pub" style="grid-column: span 7;">Publicar agora</button>
        </form>
    </section>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Cod</th>
                    <th>Cat.</th>
                    <th>Confronto</th>
                    <th>Placar</th>
                    <th>Hora</th>
                    <th>Mercado</th>
                    <th>Odd</th>
                    <th>Status</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($lista_sinais as $s): ?>
                <tr>
                    <td style="color: var(--primary); font-weight: 700;"><?= $s['p_codigo'] ?></td>
                    <td><b><?= strtoupper($s['p_categoria']) ?></b></td>
                    <td><?= $s['p_confronto'] ?></td>
                    <td style="color: var(--primary); font-weight: 800;"><?= $s['p_placar'] ?: '0-0' ?></td>
                    <td><?= $s['p_hora'] ?: '--:--' ?></td>
                    <td><?= $s['p_mercado'] ?></td>
                    <td>@<?= number_format($s['p_odd'], 2) ?></td>
                    <td class="st-<?= strtolower($s['p_status']) ?>"><?= $s['p_status'] ?></td>
                    <td style="text-align:right">
                        <a href="status.php?id=<?= $s['id'] ?>&set=Green" title="Green"><i class="fas fa-check-circle" style="color: var(--primary); margin-left: 12px;"></i></a>
                        <a href="status.php?id=<?= $s['id'] ?>&set=Red" title="Red"><i class="fas fa-times-circle" style="color: var(--danger); margin-left: 12px;"></i></a>
                        <a href="javascript:void(0)" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($s)) ?>)" title="Editar"><i class="fas fa-edit" style="color: var(--info); margin-left: 12px;"></i></a>
                        <a href="apagar.php?id=<?= $s['id'] ?>" title="Excluir"><i class="fas fa-trash" style="color: var(--text-dim); margin-left: 12px;"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if($pagina_atual > 1): ?><a href="?p=<?= $pagina_atual - 1 ?>" class="page-link">Anterior</a><?php endif; ?>
        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?p=<?= $i ?>" class="page-link <?= ($i == $pagina_atual) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if($pagina_atual < $total_paginas): ?><a href="?p=<?= $pagina_atual + 1 ?>" class="page-link">Próxima</a><?php endif; ?>
    </div>
</main>

<!-- MODAL DE EDIÇÃO -->
<div id="modalEditar">
    <div style="background:var(--card); width:90%; max-width:500px; padding:30px; border-radius:20px; border:1px solid var(--border);">
        <h2 style="margin-bottom:20px;">Editar Sinal</h2>
        <form action="atualizar_sinal.php" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div style="display:grid; gap:15px;">
                <div class="input-group"><label>Confronto</label><input type="text" name="p_confronto" id="edit_confronto" required></div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="input-group"><label>Placar</label><input type="text" name="p_placar" id="edit_placar"></div>
                    <div class="input-group"><label>Odd</label><input type="text" name="p_odd" id="edit_odd" required></div>
                </div>
                <div class="input-group"><label>Mercado</label><input type="text" name="p_mercado" id="edit_mercado" required></div>
            </div>
            <div style="margin-top:25px; display:flex; gap:10px;">
                <button type="submit" class="btn-pub" style="flex:1;">Salvar</button>
                <button type="button" onclick="fecharModal()" style="background:transparent; color:var(--text-dim); border:1px solid var(--border); padding:10px 20px; border-radius:12px; cursor:pointer;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalEditar(dados) {
    document.getElementById('edit_id').value = dados.id;
    document.getElementById('edit_confronto').value = dados.p_confronto;
    document.getElementById('edit_placar').value = dados.p_placar;
    document.getElementById('edit_odd').value = dados.p_odd;
    document.getElementById('edit_mercado').value = dados.p_mercado;
    document.getElementById('modalEditar').style.display = 'flex';
}
function fecharModal() { document.getElementById('modalEditar').style.display = 'none'; }
window.onclick = function(e) { if (e.target == document.getElementById('modalEditar')) fecharModal(); }
</script>

</body>
</html>
