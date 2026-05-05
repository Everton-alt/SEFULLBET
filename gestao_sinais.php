<?php
session_start();
require_once 'config.php';

// 1. Verificação de Acesso (Apenas Admin/Supervisor)
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

$perfil = $user['perfil']; 
if (!in_array($perfil, ['Supervisor', 'Admin'])) {
    header("Location: dashboard.php");
    exit();
}

// 2. Lógica de Estatísticas para a Gestão
function getAdminStats($pdo, $cat) {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as t, 
        SUM(CASE WHEN p_status = 'Green' THEN 1 ELSE 0 END) as g,
        SUM(CASE WHEN p_status = 'Red' THEN 1 ELSE 0 END) as r
        FROM sinais WHERE p_categoria = ?");
    $stmt->execute([$cat]);
    $res = $stmt->fetch();
    $res['p'] = ($res['t'] > 0) ? round(($res['g'] / $res['t']) * 100) . '%' : '0%';
    return $res;
}

$stats_gratis = getAdminStats($pdo, 'Grátis');
$stats_vip = getAdminStats($pdo, 'VIP');

// 3. Paginação da Tabela de Gestão
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$total_sinais = $pdo->query("SELECT COUNT(*) FROM sinais")->fetchColumn();
$total_paginas = ceil($total_sinais / $itens_por_pagina);

$stmt_sinais = $pdo->prepare("SELECT * FROM sinais ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt_sinais->bindValue(1, $itens_por_pagina, PDO::PARAM_INT);
$stmt_sinais->bindValue(2, $offset, PDO::PARAM_INT);
$stmt_sinais->execute();
$lista_sinais = $stmt_sinais->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefullbet - Gestão de Sinais</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2ECC71; 
            --bg-body: #ffffff; 
            --bg-secondary: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #2d3436;
            --text-dim: #636e72;
            --danger: #d63031;
            --warning: #f1c40f;
            --vip: #f1c40f;
            --border: #eee;
            --info: #0984e3;
        }

        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-body); color: var(--text-main); }

        /* SIDEBAR (ESPELHADA DO DASHBOARD) */
        .sidebar { height: 100%; width: 280px; position: fixed; z-index: 2000; top: 0; left: -280px; background-color: #2d3436; transition: 0.4s; padding-top: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.1); overflow-y: auto; }
        .sidebar .nav-btn { padding: 12px 25px; text-decoration: none; font-size: 15px; color: #b2bec3; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #3d4648; }
        .sidebar .nav-btn:hover, .sidebar .nav-btn.active { background: #3d4648; color: var(--primary); }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 30px; cursor: pointer; color: var(--primary); }
        .nav-label { color: var(--primary); font-size: 11px; text-transform: uppercase; padding: 15px 25px 5px; display: block; font-weight: 800; letter-spacing: 1px; }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }

        /* HEADER */
        header { background: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #eee; }
        .menu-icon { font-size: 24px; cursor: pointer; color: #2d3436; }
        .logo { font-weight: 900; font-size: 1.3rem; color: #2d3436; }
        .logo span { color: var(--primary); }

        /* CONTEÚDO ADM */
        main { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
        .perf-card { background: #fff; padding: 20px; border-radius: 15px; border: 1px solid var(--border); border-top: 3px solid var(--primary); }
        .perf-stats-row { display: flex; justify-content: space-between; text-align: center; }
        .stat-box span { display: block; font-size: 10px; color: var(--text-dim); font-weight: 700; }
        .stat-box b { font-size: 1.2rem; }

        .form-container { background: var(--bg-secondary); padding: 25px; border-radius: 20px; margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; align-items: flex-end; }
        .input-group label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 5px; color: var(--text-dim); }
        .input-group input, .input-group select { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; box-sizing: border-box; }
        
        .btn-pub { background: var(--primary); color: #fff; border: none; padding: 12px; border-radius: 10px; font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-pub:hover { filter: brightness(0.9); }

        .table-wrapper { background: #fff; border-radius: 15px; border: 1px solid var(--border); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { background: #fcfcfc; padding: 15px; text-align: left; font-size: 12px; color: var(--text-dim); border-bottom: 1px solid var(--border); }
        td { padding: 15px; font-size: 13px; border-bottom: 1px solid var(--border); }
        
        .st-green { color: var(--primary); font-weight: 800; }
        .st-red { color: var(--danger); font-weight: 800; }
        .st-pendente { color: var(--warning); font-weight: 800; }

        .pagination { display: flex; gap: 5px; margin-top: 20px; justify-content: center; }
        .page-link { padding: 8px 15px; background: #fff; border: 1px solid #ddd; text-decoration: none; color: var(--text-main); border-radius: 5px; font-size: 13px; }
        .page-link.active { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* MODAL */
        #modalEditar { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:3000; align-items:center; justify-content:center; }

        footer { text-align: center; padding: 40px 20px; font-size: 0.75rem; color: #b2bec3; background: #f8f9fa; margin-top: 30px; line-height: 1.6; border-top: 1px solid #eee; }
        
        @media (max-width: 768px) { .perf-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div id="overlay" class="overlay" onClick="closeNav()"></div>

    <div id="mySidebar" class="sidebar">
        <span class="close-btn" onClick="closeNav()">&times;</span>
        <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Início</span></a>
        <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
        <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
        <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>

        <?php if (in_array($perfil, ['Supervisor', 'Admin'])): ?>
            <hr style="border: 0; border-top: 1px solid #3d4648; margin: 15px 10px;">
            <span class="nav-label">Gestão Administrativa</span>
            <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
            <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
            <a class="nav-btn" href="base_dados_ai.php"><i class="fas fa-database"></i> <span>Verificar Dados AI</span></a>
            <a class="nav-btn active" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
            <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
            <a class="nav-btn" href="gestao_noticias.php"><i class="fas fa-newspaper"></i> <span>Gestão de Notícias</span></a>
            <a class="nav-btn" href="gestao_notas.php"><i class="fas fa-edit"></i> <span>Gestão de Notas</span></a>
        <?php endif; ?>

        <a href="logout.php" class="nav-btn logout-btn" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Sair da Conta</a>
    </div>

    <header>
        <div class="menu-icon" onClick="openNav()">☰</div>
        <div class="logo">SEFULL<span>BET</span></div>
        <div style="font-size: 14px; font-weight: 800; color: var(--text-dim)">Admin: <?= explode(' ', $user['nome'])[0] ?></div>
    </header>

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
            <button type="submit" class="btn-pub" style="margin-top:10px;">Publicar agora</button>
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
                    <td style="color: var(--primary); font-weight: 700;">#<?= $s['id'] ?></td>
                    <td><b><?= strtoupper($s['p_categoria']) ?></b></td>
                    <td><?= $s['p_confronto'] ?></td>
                    <td style="color: var(--primary); font-weight: 800;"><?= $s['p_placar'] ?: '0-0' ?></td>
                    <td><?= $s['p_hora'] ?: '--:--' ?></td>
                    <td><?= $s['p_mercado'] ?></td>
                    <td>@<?= number_format((float)$s['p_odd'], 2) ?></td>
                    <td class="st-<?= strtolower($s['p_status']) ?>"><?= $s['p_status'] ?: 'Pendente' ?></td>
                    <td style="text-align:right">
                        <a href="status.php?id=<?= $s['id'] ?>&set=Green" title="Green"><i class="fas fa-check-circle" style="color: var(--primary); margin-left: 12px;"></i></a>
                        <a href="status.php?id=<?= $s['id'] ?>&set=Red" title="Red"><i class="fas fa-times-circle" style="color: var(--danger); margin-left: 12px;"></i></a>
                        <a href="javascript:void(0)" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($s)) ?>)" title="Editar"><i class="fas fa-edit" style="color: var(--info); margin-left: 12px;"></i></a>
                        <a href="apagar.php?id=<?= $s['id'] ?>" onclick="return confirm('Excluir sinal?')" title="Excluir"><i class="fas fa-trash" style="color: var(--text-dim); margin-left: 12px;"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?p=<?= $i ?>" class="page-link <?= ($i == $pagina_atual) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</main>

<footer>
    <b style="color: var(--text-main); letter-spacing: 1px;">SEFULLBET</b><br>
    © 2026 SeFullBet - Inteligência de Dados aplicada ao Esporte.<br>
    Apostas são para maiores de 18 anos. Jogue com responsabilidade.
</footer>

<!-- MODAL DE EDIÇÃO -->
<div id="modalEditar">
    <div style="background:#fff; width:90%; max-width:500px; padding:30px; border-radius:20px;">
        <h2 style="margin-bottom:20px; font-weight: 800;">Editar Sinal</h2>
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
                <button type="submit" class="btn-pub" style="flex:1;">Salvar Alterações</button>
                <button type="button" onclick="fecharModal()" style="background:#eee; color:#666; border:none; padding:10px 20px; border-radius:10px; cursor:pointer; font-weight:bold;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
    function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }
    
    function abrirModalEditar(dados) {
        document.getElementById('edit_id').value = dados.id;
        document.getElementById('edit_confronto').value = dados.p_confronto;
        document.getElementById('edit_placar').value = dados.p_placar;
        document.getElementById('edit_odd').value = dados.p_odd;
        document.getElementById('edit_mercado').value = dados.p_mercado;
        document.getElementById('modalEditar').style.display = 'flex';
    }
    function fecharModal() { document.getElementById('modalEditar').style.display = 'none'; }
</script>

</body>
</html>
