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

// Definição do perfil e permissões
$perfil = $user['perfil']; 
$pode_ver_vip = in_array($perfil, ['VIP', 'Platinum', 'Supervisor', 'Admin']);

// --- LÓGICA DE PAGINAÇÃO PALPITES ---
$itens_por_pagina = 5;
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

// --- LÓGICA DE PAGINAÇÃO VITÓRIAS (CORRIGIDA PARA v_fixado) ---
$vitorias_por_pagina = 5;
$v_pagina_atual = isset($_GET['vp']) ? (int)$_GET['vp'] : 1;
if ($v_pagina_atual < 1) $v_pagina_atual = 1;
$v_offset = ($v_pagina_atual - 1) * $vitorias_por_pagina;

$total_vitorias = $pdo->query("SELECT COUNT(*) FROM v_vitorias")->fetchColumn();
$v_total_paginas = ceil($total_vitorias / $vitorias_por_pagina);

// Ordenação prioritária por v_fixado DESC (1 sobe, 0 desce)
$stmt_vitorias = $pdo->prepare("SELECT * FROM v_vitorias ORDER BY v_fixado DESC, v_id DESC LIMIT ? OFFSET ?");
$stmt_vitorias->bindValue(1, $vitorias_por_pagina, PDO::PARAM_INT);
$stmt_vitorias->bindValue(2, $v_offset, PDO::PARAM_INT);
$stmt_vitorias->execute();
$lista_vitorias = $stmt_vitorias->fetchAll();

// 4. Estatísticas Dinâmicas
function getStats($pdo, $cat) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, 
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
    <title>Sefullbet - Dashboard</title>
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
            --border: #f1f1f1;
        }

        body { font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; background-color: var(--bg-body); color: var(--text-main); padding-bottom: 50px; }

        /* SIDEBAR */
        .sidebar { height: 100%; width: 280px; position: fixed; z-index: 2000; top: 0; left: -280px; background-color: #2d3436; overflow-x: hidden; transition: 0.4s; padding-top: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.1); }
        .sidebar .nav-btn { padding: 12px 25px; text-decoration: none; font-size: 15px; color: #b2bec3; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #3d4648; transition: 0.3s; }
        .sidebar .nav-btn i { width: 20px; text-align: center; }
        .sidebar .nav-btn:hover, .sidebar .nav-btn.active { background: #3d4648; color: var(--primary); }
        .sidebar .logout-btn { color: #ff7675 !important; font-weight: bold; border-bottom: none !important; }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 30px; cursor: pointer; color: var(--primary); z-index: 2001; }
        .nav-label { color: var(--primary); font-size: 11px; text-transform: uppercase; padding: 15px 25px 5px; display: block; font-weight: 800; letter-spacing: 1px; }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }

        /* HEADER */
        header { background-color: #ffffff; color: var(--primary); padding: 15px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #eee; }
        .menu-icon { font-size: 24px; cursor: pointer; color: #2d3436; }
        .logo { font-weight: 900; font-size: 1.3rem; letter-spacing: 1px; color: #2d3436; }
        .logo span { color: var(--primary); }

        /* ESTATÍSTICAS */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 15px 10px 5px; }
        .stat-card { background: var(--card-bg); padding: 12px; border-radius: 15px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #f1f1f1; border-top: 4px solid var(--primary); }
        .stat-card.vip { border-top-color: var(--warning); }
        .stat-value { font-size: 1.4rem; font-weight: bold; color: #2d3436; }
        .stat-total { font-size: 0.75rem; font-weight: 600; color: var(--text-dim); }
        .stat-label { font-size: 0.6rem; color: #aaa; text-transform: uppercase; }
        .stat-counts { font-size: 0.65rem; margin-top: 8px; font-weight: bold; background: var(--bg-secondary); padding: 5px; border-radius: 8px; }

        /* BOTÃO ANALISADOR */
        .action-box { padding: 20px 15px; position: relative; display: flex; justify-content: center; }
        .highlight-ring { position: absolute; width: 95%; height: 65px; border: 2px solid var(--primary); border-radius: 18px; animation: pulse-ring 1.5s infinite; z-index: 1; }
        @keyframes pulse-ring { 0% { transform: scale(0.98); opacity: 0.8; } 100% { transform: scale(1.05); opacity: 0; } }
        .btn-analisador { position: relative; z-index: 2; background: linear-gradient(45deg, #2ecc71, #27ae60); color: #fff; padding: 18px; border-radius: 15px; text-decoration: none; font-weight: 800; width: 100%; text-align: center; border: none; box-shadow: 0 10px 20px rgba(46, 204, 113, 0.3); text-transform: uppercase; }

        /* CARDS E LISTAS */
        .section-title { padding: 20px 15px 10px; font-size: 0.85rem; font-weight: 800; color: #2d3436; text-transform: uppercase; display: flex; align-items: center; gap: 8px; }
        .content-container { padding: 0 10px; }
        .history-row { background: var(--card-bg); margin-bottom: 12px; border-radius: 15px; padding: 15px; border-left: 6px solid #dfe6e9; box-shadow: 0 2px 10px rgba(0,0,0,0.03); position: relative; overflow: hidden; }
        .history-row.vip-row { border-left-color: var(--warning); }
        .history-row.free-row { border-left-color: var(--accent-blue); }
        .row-top { display: flex; justify-content: space-between; font-size: 0.7rem; color: #b2bec3; margin-bottom: 10px; border-bottom: 1px solid #f1f1f1; padding-bottom: 6px; }
        .row-main { display: flex; justify-content: space-between; align-items: center; }
        .status-badge { font-size: 0.65rem; padding: 5px 10px; border-radius: 6px; font-weight: bold; text-transform: uppercase; }
        .bg-green { background: #eafaf1; color: #27ae60; }
        .bg-red { background: #fdf2f2; color: #e74c3c; }
        .bg-waiting { background: #fef9e7; color: #f1c40f; }

        .pagination-box { display: flex; justify-content: space-between; gap: 10px; padding: 15px 10px; }
        .pg-btn { flex: 1; padding: 12px; border-radius: 12px; border: 1px solid #ddd; background: #fff; color: var(--text-main); text-decoration: none; text-align: center; font-size: 0.8rem; font-weight: bold; transition: 0.3s; }
        .pg-btn:hover:not(.disabled) { background: var(--bg-secondary); border-color: var(--primary); color: var(--primary); }
        .pg-btn.disabled { opacity: 0.4; pointer-events: none; }

        .locked-content { filter: blur(5px); opacity: 0.3; pointer-events: none; }
        .lock-notice { position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; z-index:10; color: var(--warning); font-weight:bold; font-size: 11px; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); }

        /* VITÓRIAS */
        .victory-card { display: flex; align-items: center; gap: 15px; cursor: pointer; transition: 0.2s; }
        .victory-thumb { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0; }
        .victory-info { flex: 1; overflow: hidden; }
        .victory-title { font-weight: 700; font-size: 0.9rem; color: var(--text-main); display: block; margin-bottom: 2px; }
        .victory-excerpt { font-size: 0.75rem; color: var(--text-dim); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pin-badge { position: absolute; top: -5px; right: -5px; background: var(--warning); color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 5; }

        /* MODAL */
        .v-modal-bg { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index: 3000; padding: 20px; box-sizing: border-box; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .v-modal-content { background: #fff; width: 100%; max-width: 500px; border-radius: 20px; overflow-y: auto; max-height: 90vh; position: relative; animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .v-modal-body { padding: 20px; }
        .v-modal-img { width: 100%; border-radius: 12px; margin-bottom: 15px; }
        .v-modal-title { font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 15px; line-height: 1.3; }
        .v-modal-text { font-size: 0.95rem; line-height: 1.6; color: var(--text-dim); white-space: pre-line; }
        .btn-close-v { background: var(--danger); color: #fff; border: none; padding: 12px; width: 100%; border-radius: 12px; font-weight: bold; margin-top: 20px; cursor: pointer; text-transform: uppercase; }

        footer { text-align: center; padding: 40px 20px; font-size: 0.75rem; color: #b2bec3; background: #f8f9fa; margin-top: 30px; }
    </style>
</head>
<body>

    <div id="overlay" class="overlay" onClick="closeNav()"></div>

    <div id="mySidebar" class="sidebar">
        <span class="close-btn" onClick="closeNav()">&times;</span>
        <a class="nav-btn active" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Início</span></a>
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
            <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
            <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
            <a class="nav-btn" href="gestao_noticias.php"><i class="fas fa-newspaper"></i> <span>Gestão de Notícias</span></a>
            <a class="nav-btn" href="gestao_notas.php"><i class="fas fa-edit"></i> <span>Gestão de Notas</span></a>
        <?php endif; ?>
        
        <a href="logout.php" class="nav-btn logout-btn" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Sair da Conta</a>
    </div>
<!-- HEADER -->
<header>
    <div class="menu-icon" onClick="openNav()">☰</div>
    <div class="logo">SEFULL<span>BET</span></div>
    <div style="font-size: 11px; font-weight: 700;">CRÉDITOS: <b id="saldo-display" style="color: var(--primary);"><?= in_array($perfil, ['Admin','Supervisor','Platinum']) ? '∞' : $user['saldo_creditos'] ?></b></div>
</header>

<main class="analyzer-main">
    <div class="input-card">
        <div class="input-group"><label>Odd Casa</label><input type="text" id="o-casa" placeholder="1.85"></div>
        <div class="input-group"><label>Odd Empate</label><input type="text" id="o-empate" placeholder="3.40"></div>
        <div class="input-group"><label>Odd Fora</label><input type="text" id="o-fora" placeholder="4.50"></div>
        <button class="btn-analisar" onclick="processarIA()"><i class="fas fa-robot"></i> Analisar</button>
    </div>

    <div id="loader"><div class="spinner"></div><p style="font-weight: 800; margin-top: 15px; color: var(--primary);">IA PROCESSANDO...</p></div>

    <div id="resultado-display" style="display: none;">
        <div class="best-entries-container">
            <div id="top-list"></div>
        </div>

        <div class="stats-grid-5">
            <div class="stat-col"><h4>Vencedor (RES)</h4><div id="col-principal"></div></div>
            <div class="stat-col"><h4>Dupla Chance</h4><div id="col-dupla"></div></div>
            <div class="stat-col"><h4>Mercado Over (+)</h4><div id="col-over"></div></div>
            <div class="stat-col"><h4>Mercado Under (-)</h4><div id="col-under"></div></div>
            <div class="stat-col"><h4>Dados da IA</h4><div id="col-medias"></div></div>
        </div>
    </div>
</main>

<script>
const DB = <?php echo json_encode($dados_historicos); ?>;
function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }

function limparNumero(val) { return val ? parseFloat(val.toString().replace(',', '.')) : 0; }

async function processarIA() {
    const oc = limparNumero(document.getElementById('o-casa').value);
    const oe = limparNumero(document.getElementById('o-empate').value);
    const of = limparNumero(document.getElementById('o-fora').value);
    if(!oc || !oe || !of) return alert("Preencha as odds.");

    document.getElementById('loader').style.display = 'flex';
    document.getElementById('resultado-display').style.display = 'none';

    const resDebito = await fetch('analisador.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=debitar' }).then(r => r.json());
    if(resDebito.status === 'erro') { document.getElementById('loader').style.display = 'none'; return alert("Créditos insuficientes."); }
    if(resDebito.novo_saldo) document.getElementById('saldo-display').innerText = resDebito.novo_saldo;

    setTimeout(() => {
        const similares = DB.map(j => {
            const dist = Math.sqrt(Math.pow(limparNumero(j.odd_casa)-oc,2)+Math.pow(limparNumero(j.odd_empate)-oe,2)+Math.pow(limparNumero(j.odd_fora)-of,2));
            return {...j, dist, peso: 1 / (dist + 0.001)};
        }).filter(j => j.dist <= 0.15).sort((a,b) => a.dist - b.dist).slice(0, 50);

        if (similares.length === 0) { document.getElementById('loader').style.display = 'none'; return alert("Sem similaridade."); }
        renderizar(similares);
        document.getElementById('loader').style.display = 'none';
        document.getElementById('resultado-display').style.display = 'block';
    }, 1000);
}

function renderizar(dados) {
    const somaPesos = dados.reduce((acc, j) => acc + j.peso, 0);
    const calcProb = (campo, valor) => ((dados.filter(j => j[campo] === valor).reduce((acc, j) => acc + j.peso, 0) / somaPesos) * 100);

    const pCasa = calcProb('resultado', 'Casa'), pEmpa = calcProb('resultado', 'Empate'), pFora = calcProb('resultado', 'Fora'), pAMB = calcProb('ambos_marcam', 'Sim');
    const pO05 = calcProb('over_05', 'Sim'), pO15 = calcProb('over_15', 'Sim'), pO25 = calcProb('over_25', 'Sim'), pO35 = calcProb('over_35', 'Sim'), pO45 = calcProb('over_45', 'Sim');

    document.getElementById('col-principal').innerHTML = `
        <div class="data-row"><span>V. Casa</span><b>${pCasa.toFixed(1)}%</b></div>
        <div class="data-row"><span>Empate</span><b>${pEmpa.toFixed(1)}%</b></div>
        <div class="data-row"><span>V. Fora</span><b>${pFora.toFixed(1)}%</b></div>
        <div class="data-row"><span>Ambos Sim</span><b>${pAMB.toFixed(1)}%</b></div>
        <div class="data-row"><span>Ambos Não</span><b>${(100-pAMB).toFixed(1)}%</b></div>
    `;

    document.getElementById('col-dupla').innerHTML = `
        <div class="data-row"><span>1X (Casa ou Emp)</span><b>${(pCasa+pEmpa).toFixed(1)}%</b></div>
        <div class="data-row"><span>12 (Casa ou Fora)</span><b>${(pCasa+pFora).toFixed(1)}%</b></div>
        <div class="data-row"><span>X2 (Fora ou Emp)</span><b>${(pFora+pEmpa).toFixed(1)}%</b></div>
    `;

    document.getElementById('col-over').innerHTML = `
        <div class="data-row"><span>+0.5 Gols</span><b>${pO05.toFixed(1)}%</b></div>
        <div class="data-row"><span>+1.5 Gols</span><b>${pO15.toFixed(1)}%</b></div>
        <div class="data-row"><span>+2.5 Gols</span><b>${pO25.toFixed(1)}%</b></div>
        <div class="data-row"><span>+3.5 Gols</span><b>${pO35.toFixed(1)}%</b></div>
        <div class="data-row"><span>+4.5 Gols</span><b>${pO45.toFixed(1)}%</b></div>
    `;

    document.getElementById('col-under').innerHTML = `
        <div class="data-row"><span>-0.5 Gols</span><b>${(100-pO05).toFixed(1)}%</b></div>
        <div class="data-row"><span>-1.5 Gols</span><b>${(100-pO15).toFixed(1)}%</b></div>
        <div class="data-row"><span>-2.5 Gols</span><b>${(100-pO25).toFixed(1)}%</b></div>
        <div class="data-row"><span>-3.5 Gols</span><b>${(100-pO35).toFixed(1)}%</b></div>
        <div class="data-row"><span>-4.5 Gols</span><b>${(100-pO45).toFixed(1)}%</b></div>
    `;

    const mGols = (dados.reduce((acc, j) => acc + (limparNumero(j.gols_total)*j.peso), 0) / somaPesos).toFixed(2);
    const mCasa = (dados.reduce((acc, j) => acc + (limparNumero(j.gols_casa)*j.peso), 0) / somaPesos).toFixed(2);
    const mFora = (dados.reduce((acc, j) => acc + (limparNumero(j.gols_fora)*j.peso), 0) / somaPesos).toFixed(2);

    document.getElementById('col-medias').innerHTML = `
        <div class="data-row"><span>Média Gols Jogo</span><b>${mGols}</b></div>
        <div class="data-row"><span>Média Gols Casa</span><b>${mCasa}</b></div>
        <div class="data-row"><span>Média Gols Fora</span><b>${mFora}</b></div>
        <div class="data-row"><span>Amostra (N)</span><b>${dados.length}</b></div>
    `;

    let rank = [{n:"1X", v:pCasa+pEmpa}, {n:"+0.5", v:pO05}, {n:"+1.5", v:pO15}, {n:"Ambos", v:pAMB}].sort((a,b)=>b.v-a.v).slice(0,3);
    document.getElementById('top-list').innerHTML = rank.map((item, i) => `<div class="entry-row"><b>#${i+1} ${item.n}</b><span class="entry-perc">${item.v.toFixed(1)}%</span></div>`).join('');
}
</script>
</body>
</html>
