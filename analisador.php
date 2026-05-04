<?php
session_start();
require_once 'config.php';

// 1. Verificação de Login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Busca dados do usuário atualizados
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();
$perfil = $user['perfil']; 

// 3. Lógica de Débito de Créditos (AJAX)
if (isset($_POST['action']) && $_POST['action'] == 'debitar') {
    header('Content-Type: application/json');
    $is_premium = in_array($perfil, ['Admin', 'Platinum', 'Supervisor']);
    
    if (!$is_premium) {
        if ($user['saldo_creditos'] <= 0) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Saldo insuficiente']);
            exit();
        }
        $pdo->prepare("UPDATE usuarios SET saldo_creditos = saldo_creditos - 1 WHERE id = ? AND saldo_creditos > 0")->execute([$_SESSION['usuario_id']]);
        
        $stmt_s = $pdo->prepare("SELECT saldo_creditos FROM usuarios WHERE id = ?");
        $stmt_s->execute([$_SESSION['usuario_id']]);
        $novo_saldo = $stmt_s->fetchColumn();
        echo json_encode(['status' => 'sucesso', 'novo_saldo' => $novo_saldo]);
    } else {
        echo json_encode(['status' => 'isento', 'novo_saldo' => '∞']);
    }
    exit();
}

// 4. Busca da base histórica
try {
    $stmt_data = $pdo->query("SELECT odd_casa, odd_empate, odd_fora, resultado, ambos_marcam, gols_total, gols_casa, gols_fora, over_05, over_15, over_25, over_35, over_45 FROM base_analisador");
    $dados_historicos = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dados_historicos = [];
}

$cores = ['Grátis' => '#8b949e', 'VIP' => '#ffd700', 'Platinum' => '#2ecc71', 'Supervisor' => '#00e5ff', 'Admin' => '#2ecc71'];
$cor_perfil = $cores[$perfil] ?? $cores['Grátis'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analista Pro AI - Sefullbet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2ECC71; 
            --bg-body: #f0f2f5; 
            --sidebar-bg: #1e272e;
            --card-bg: #ffffff;
            --text-main: #2d3436;
            --text-dim: #636e72;
            --border: #e1e8ed;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); min-height: 100vh; }

        /* Sidebar */
        .sidebar { height: 100%; width: 260px; position: fixed; z-index: 2000; top: 0; left: -260px; background-color: var(--sidebar-bg); overflow-x: hidden; transition: 0.3s; padding-top: 15px; }
        .sidebar .nav-btn { padding: 10px 20px; text-decoration: none; font-size: 14px; color: #dcdde1; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #2f3542; transition: 0.2s; }
        .sidebar .nav-btn:hover, .sidebar .nav-btn.active { background: #2f3542; color: var(--primary); }
        .sidebar .logout-btn { color: #ff7675 !important; border-bottom: none !important; margin-top: 10px; }
        .sidebar .close-btn { position: absolute; top: 5px; right: 15px; font-size: 25px; cursor: pointer; color: var(--primary); }
        .nav-label { color: var(--primary); font-size: 10px; text-transform: uppercase; padding: 12px 20px 4px; display: block; font-weight: 800; opacity: 0.7; }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.4); z-index: 1500; }

        header { background: #fff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid var(--border); }
        .menu-icon { font-size: 20px; cursor: pointer; color: var(--text-main); }
        .logo { font-weight: 800; font-size: 1.1rem; }
        .logo span { color: var(--primary); }

        main { padding: 15px 10px; max-width: 1100px; margin: 0 auto; }
        .header-top { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px; }
        .header-top h1 { font-size: 1.3rem; font-weight: 800; }
        
        .status-badge { background: #fff; padding: 8px 15px; border-radius: 12px; border: 1px solid var(--border); display: flex; gap: 15px; align-items: center; }

        /* Input Card Compacto */
        .input-card { background: var(--card-bg); padding: 15px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; gap: 10px; align-items: flex-end; margin-bottom: 15px; flex-wrap: wrap; }
        .input-group { flex: 1; min-width: 100px; }
        .input-group label { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; font-weight: 700; margin-bottom: 5px; }
        .input-group input { width: 100%; padding: 10px; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; font-weight: 700; font-size: 1rem; text-align: center; }
        
        .btn-analisar { height: 42px; padding: 0 25px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; text-transform: uppercase; font-size: 0.85rem; width: auto; min-width: 150px; }

        /* Resultado Top 3 Compacto */
        .best-entries-card { background: linear-gradient(135deg, #2ecc71, #27ae60); border-radius: 15px; padding: 15px; margin-bottom: 20px; color: #fff; }
        .best-entries-card h3 { font-size: 0.85rem; text-transform: uppercase; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; opacity: 0.9; }
        .entry-row { background: rgba(255,255,255,0.15); border-radius: 10px; padding: 8px 15px; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; font-weight: 600; }
        .entry-perc { background: #fff; color: var(--primary); padding: 3px 10px; border-radius: 6px; font-weight: 800; font-size: 0.85rem; }

        /* Grid de Estatísticas */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .stat-card { background: #fff; border-radius: 15px; padding: 12px; border: 1px solid var(--border); }
        .stat-card h4 { font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 10px; border-bottom: 1px solid #f8fafc; padding-bottom: 6px; display: flex; justify-content: space-between; font-weight: 700; }
        
        .data-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 0.8rem; font-weight: 600; color: #444; }
        .data-row b { color: var(--primary); font-weight: 800; }

        #loader { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(255,255,255,0.9); z-index:2500; flex-direction:column; justify-content:center; align-items:center; }
        .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid var(--primary); border-radius: 50%; animation: spin 1s infinite linear; }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 600px) {
            .input-card { flex-direction: column; align-items: stretch; }
            .btn-analisar { width: 100%; }
            .header-top { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>

<div id="loader">
    <div class="spinner"></div>
    <p style="color: var(--primary); margin-top:15px; font-weight:700; font-size: 0.9rem;">SEFULLBET ANALISANDO SUAS ODDS AGUARDE...</p>
</div>

<div id="overlay" class="overlay" onClick="closeNav()"></div>

<div id="mySidebar" class="sidebar">
    <span class="close-btn" onClick="closeNav()">&times;</span>
    <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Início</span></a>
    <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
    <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
    <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
    <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
    <a class="nav-btn active" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
    <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>
    
    <?php if (in_array($perfil, ['Supervisor', 'Admin'])): ?>
        <hr style="border: 0; border-top: 1px solid #2f3542; margin: 10px 0;">
        <span class="nav-label">Administrativo</span>
        <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Sinais</span></a>
        <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar</span></a>
        <a class="nav-btn" href="base_dados_ai.php"><i class="fas fa-database"></i> <span>Base AI</span></a>
        <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Membros</span></a>
    <?php endif; ?>
    
    <a href="logout.php" class="nav-btn logout-btn"><i class="fas fa-sign-out-alt"></i> Sair</a>
</div>

<header>
    <div class="menu-icon" onClick="openNav()">☰</div>
    <div class="logo">SEFULL<span>BET</span></div>
    <div style="text-align: right;">
        <div style="font-size: 12px; font-weight: 700;"><?= explode(' ', ($user['nome'] ?? 'Usuário'))[0] ?></div>
        <div style="font-size: 10px; font-weight: 600; color: var(--text-dim);">Créditos: <b id="header-saldo" style="color: var(--primary);"><?php echo (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos']; ?></b></div>
    </div>
</header>

<main>
    <div class="header-top">
        <h1>Sefullbet Pro 🚀</h1>
        <div class="status-badge">
            <div style="text-align: center; border-right: 1px solid #eee; padding-right: 12px;">
                <span style="font-size: 9px; color: var(--text-dim); font-weight: 700; display: block;">SALDO</span>
                <span id="saldo-display" style="font-size: 14px; font-weight: 800; color: var(--primary);"><?php echo (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos']; ?></span>
            </div>
            <div>
                <span style="font-size: 9px; color: var(--text-dim); font-weight: 700; display: block;">PERFIL</span>
                <span style="color: <?php echo $cor_perfil; ?>; font-weight: 800; font-size: 12px;"><?php echo strtoupper($perfil); ?></span>
            </div>
        </div>
    </div>

    <div class="input-card">
        <div class="input-group"><label>Odd Casa</label><input type="text" id="o-casa" placeholder="1.80"></div>
        <div class="input-group"><label>Odd Empate</label><input type="text" id="o-empate" placeholder="3.40"></div>
        <div class="input-group"><label>Odd Fora</label><input type="text" id="o-fora" placeholder="4.20"></div>
        <button class="btn-analisar" onclick="processarIA()">Analisar</button>
    </div>

    <div id="resultado-display" style="display: none;">
        <div class="best-entries-card">
            <h3><i class="fas fa-star"></i> Top 3 Oportunidades</h3>
            <div id="top-list"></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><h4><i class="fas fa-trophy"></i> Resultado</h4><div id="col-principal"></div></div>
            <div class="stat-card"><h4><i class="fas fa-shield-alt"></i> Dupla Chance</h4><div id="col-dupla"></div></div>
            <div class="stat-card"><h4><i class="fas fa-arrow-up"></i> Over</h4><div id="col-over"></div></div>
            <div class="stat-card"><h4><i class="fas fa-arrow-down"></i> Under</h4><div id="col-under"></div></div>
            <div class="stat-card"><h4><i class="fas fa-chart-line"></i> Métricas</h4><div id="col-medias"></div></div>
        </div>
    </div>
</main>

<script>
function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
function closeNav() { document.getElementById("mySidebar").style.left = "-260px"; document.getElementById("overlay").style.display = "none"; }

const DB = <?php echo json_encode($dados_historicos); ?>;

function limparNumero(val) {
    if (val === null || val === undefined || val === '') return 0;
    return parseFloat(val.toString().replace(',', '.'));
}

async function processarIA() {
    const oc = limparNumero(document.getElementById('o-casa').value);
    const oe = limparNumero(document.getElementById('o-empate').value);
    const of = limparNumero(document.getElementById('o-fora').value);

    if(!oc || !oe || !of) return alert("Insira as odds.");

    document.getElementById('loader').style.display = 'flex';
    
    setTimeout(async () => {
        const similares = DB.map(j => {
            const ocDB = limparNumero(j.odd_casa);
            const oeDB = limparNumero(j.odd_empate);
            const ofDB = limparNumero(j.odd_fora);
            const dist = Math.sqrt(Math.pow(ocDB - oc, 2) + Math.pow(oeDB - oe, 2) + Math.pow(ofDB - of, 2));
            const peso = 1 / (dist + 0.001);
            return {...j, dist, peso};
        })
        .filter(j => j.dist <= 0.1) 
        .sort((a,b) => a.dist - b.dist)
        .slice(0, 50);

        if (similares.length === 0) {
            document.getElementById('loader').style.display = 'none';
            return alert("Recomendamos a seleção de um confronto alternativo. No cenário atual, identificamos uma volatilidade acentuada nas odds, o que compromete a previsibilidade estatística e eleva a exposição ao risco. Sugerimos priorizar eventos com maior estabilidade técnica.");
        }

        const resDebito = await debitar();
        if(resDebito.status === 'erro') {
            document.getElementById('loader').style.display = 'none';
            return alert("Saldo insuficiente.");
        }

        if(resDebito.novo_saldo !== undefined) {
            document.getElementById('saldo-display').innerText = resDebito.novo_saldo;
            document.getElementById('header-saldo').innerText = resDebito.novo_saldo;
        }

        renderizar(similares);
        document.getElementById('loader').style.display = 'none';
        document.getElementById('resultado-display').style.display = 'block';
    }, 600);
}

function renderizar(dados) {
    const somaPesos = dados.reduce((acc, j) => acc + j.weight, 0) || dados.reduce((acc, j) => acc + j.peso, 0);
    const calcProb = (campo, valor) => {
        const pesoOcorrido = dados.filter(j => j[campo] === valor).reduce((acc, j) => acc + j.peso, 0);
        return ((pesoOcorrido / somaPesos) * 100);
    };

    const probCasa = calcProb('resultado', 'Casa');
    const probEmpa = calcProb('resultado', 'Empate'); 
    const probFora = calcProb('resultado', 'Fora');
    const pAMB_Sim = calcProb('ambos_marcam', 'Sim');
    const pO05 = calcProb('over_05', 'Sim');
    const pO15 = calcProb('over_15', 'Sim');
    const pO25 = calcProb('over_25', 'Sim');
    const pO35 = calcProb('over_35', 'Sim');
    const pO45 = calcProb('over_45', 'Sim');

    const htmlRow = (l, v) => `<div class="data-row"><span>${l}</span><b>${v}%</b></div>`;

    document.getElementById('col-principal').innerHTML = htmlRow('Casa', probCasa.toFixed(1)) + htmlRow('Empate', probEmpa.toFixed(1)) + htmlRow('Fora', probFora.toFixed(1)) + htmlRow('Ambos', pAMB_Sim.toFixed(1));

    document.getElementById('col-dupla').innerHTML = htmlRow('C ou E', (probCasa + probEmpa).toFixed(1)) + htmlRow('C ou F', (probCasa + probFora).toFixed(1)) + htmlRow('E ou F', (probFora + probEmpa).toFixed(1));

    document.getElementById('col-over').innerHTML = htmlRow('+0.5', pO05.toFixed(1)) + htmlRow('+1.5', pO15.toFixed(1)) + htmlRow('+2.5', pO25.toFixed(1)) + htmlRow('+3.5', pO35.toFixed(1)) + htmlRow('+4.5', pO45.toFixed(1));

    document.getElementById('col-under').innerHTML = htmlRow('-0.5', (100 - pO05).toFixed(1)) + htmlRow('-1.5', (100 - pO15).toFixed(1)) + htmlRow('-2.5', (100 - pO25).toFixed(1)) + htmlRow('-3.5', (100 - pO35).toFixed(1)) + htmlRow('-4.5', (100 - pO45).toFixed(1));

    const mediaTotal = (dados.reduce((acc, j) => acc + (limparNumero(j.gols_total) * j.peso), 0) / somaPesos).toFixed(2);
    const mediaCasa = (dados.reduce((acc, j) => acc + (limparNumero(j.gols_casa) * j.peso), 0) / somaPesos).toFixed(2);
    const mediaFora = (dados.reduce((acc, j) => acc + (limparNumero(j.gols_fora) * j.peso), 0) / somaPesos).toFixed(2);

    document.getElementById('col-medias').innerHTML = `
        <div class="data-row"><span>Média Total</span><b>${mediaTotal}</b></div>
        <div class="data-row"><span>Média Casa</span><b>${mediaCasa}</b></div>
        <div class="data-row"><span>Média Fora</span><b>${mediaFora}</b></div>
        <div class="data-row"><span>Amostra</span><b>${dados.length}j</b></div>
    `;

    let todosMercados = [
        { n: "Casa ou Empate", v: probCasa + probEmpa },
        { n: "Ambos Marcam", v: pAMB_Sim },
        { n: "Over 0.5 Gols", v: pO05 },
        { n: "Over 1.5 Gols", v: pO15 },
        { n: "Over 2.5 Gols", v: pO25 },
        { n: "Over 3.5 Gols", v: pO35 },
        { n: "Over 4.5 Gols", v: pO45 },
        { n: "Under 1.5 Gols", v: 100 - pO15 },
        { n: "Under 2.5 Gols", v: 100 - pO25 },
        { n: "Under 3.5 Gols", v: 100 - pO35 },
        { n: "Under 4.5 Gols", v: 100 - pO45 },
        { n: "Vitória Casa", v: probCasa },
        { n: "Vitória Fora", v: probFora }
    ];

    document.getElementById('top-list').innerHTML = todosMercados
        .sort((a,b) => b.v - a.v)
        .slice(0, 3)
        .map((item, i) => `
            <div class="entry-row"><span>#${i+1} ${item.n}</span><div class="entry-perc">${item.v.toFixed(1)}%</div></div>
        `).join('');
}

async function debitar() {
    try {
        const response = await fetch('analisador.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=debitar' });
        return await response.json();
    } catch (e) { return { status: 'erro' }; }
}
</script>
</body>
</html>
