<?php
session_start();
require_once 'config.php';

// 1. Verificação de Login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Busca dados do usuário atualizados
$user_id = (int)$_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$perfil = $user['perfil'] ?? 'Grátis'; 

// 3. Lógica de Débito de Créditos (AJAX)
if (isset($_POST['action']) && $_POST['action'] == 'debitar') {
    header('Content-Type: application/json');
    $is_premium = in_array($perfil, ['Admin', 'Platinum', 'Supervisor']);
    
    if (!$is_premium) {
        if ($user['saldo_creditos'] <= 0) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Saldo insuficiente']);
            exit();
        }
        $pdo->prepare("UPDATE usuarios SET saldo_creditos = saldo_creditos - 1 WHERE id = ? AND saldo_creditos > 0")->execute([$user_id]);
        
        $stmt_s = $pdo->prepare("SELECT saldo_creditos FROM usuarios WHERE id = ?");
        $stmt_s->execute([$user_id]);
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
        body { background-color: var(--bg-body); color: var(--text-main); min-height: 100vh; overflow-x: hidden; }

        /* Sidebar & Layout */
        .sidebar { height: 100%; width: 260px; position: fixed; z-index: 2000; top: 0; left: -260px; background-color: var(--sidebar-bg); transition: 0.3s; padding-top: 15px; }
        .sidebar .nav-btn { padding: 12px 20px; text-decoration: none; font-size: 14px; color: #dcdde1; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #2f3542; }
        .sidebar .nav-btn:hover, .sidebar .nav-btn.active { background: #2f3542; color: var(--primary); }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.4); z-index: 1500; }

        header { background: #fff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid var(--border); }
        .menu-icon { font-size: 22px; cursor: pointer; }
        .logo { font-weight: 800; font-size: 1.1rem; }
        .logo span { color: var(--primary); }

        main { padding: 15px 10px; max-width: 1100px; margin: 0 auto; }
        .header-top { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px; }
        
        .status-badge { background: #fff; padding: 8px 15px; border-radius: 12px; border: 1px solid var(--border); display: flex; gap: 15px; }

        /* Input Card */
        .input-card { background: var(--card-bg); padding: 18px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; gap: 12px; align-items: flex-end; margin-bottom: 20px; flex-wrap: wrap; }
        .input-group { flex: 1; min-width: 100px; }
        .input-group label { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; font-weight: 800; margin-bottom: 5px; }
        .input-group input { width: 100%; padding: 12px; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; font-weight: 800; font-size: 1.1rem; text-align: center; color: var(--text-main); }
        .btn-analisar { height: 48px; padding: 0 30px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.2s; }
        .btn-analisar:hover { filter: brightness(1.1); transform: translateY(-2px); }

        /* Resultado Card */
        .best-entries-card { background: linear-gradient(135deg, #2ecc71, #27ae60); border-radius: 15px; padding: 18px; margin-bottom: 20px; color: #fff; box-shadow: 0 8px 20px rgba(46, 204, 113, 0.2); }
        .entry-row { background: rgba(255,255,255,0.15); border-radius: 10px; padding: 10px 15px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; font-weight: 700; }
        .entry-perc { background: #fff; color: var(--primary); padding: 4px 12px; border-radius: 6px; font-weight: 900; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .stat-card { background: #fff; border-radius: 15px; padding: 15px; border: 1px solid var(--border); }
        .stat-card h4 { font-size: 11px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 12px; border-bottom: 1px solid #f8fafc; padding-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .data-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.85rem; font-weight: 600; }
        .data-row b { color: var(--primary); font-weight: 800; }

        /* Loader */
        #loader { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(255,255,255,0.95); z-index:2500; flex-direction:column; justify-content:center; align-items:center; }
        .spinner { width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid var(--primary); border-radius: 50%; animation: spin 1s infinite linear; }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 600px) {
            .input-card { flex-direction: column; }
            .input-group, .btn-analisar { width: 100%; }
        }
    </style>
</head>
<body>

<div id="loader">
    <div class="spinner"></div>
    <p style="color: var(--primary); margin-top:20px; font-weight:800;">SEFULLBET ANALISANDO SUAS ODDS AGUARDE...</p>
</div>

<div id="overlay" class="overlay" onClick="closeNav()"></div>

<div id="mySidebar" class="sidebar">
    <span style="position: absolute; top: 5px; right: 15px; font-size: 30px; cursor: pointer; color: var(--primary);" onClick="closeNav()">&times;</span>
    <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> Início</a>
    <a class="nav-btn active" href="analisador.php"><i class="fas fa-microchip"></i> Analisador Sefullbet</a>
    <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> Conta</a>
    <a href="logout.php" class="nav-btn" style="color: #ff7675;"><i class="fas fa-sign-out-alt"></i> Sair</a>
</div>

<header>
    <div class="menu-icon" onClick="openNav()">☰</div>
    <div class="logo">SEFULL<span>BET</span></div>
    <div style="text-align: right;">
        <div style="font-size: 12px; font-weight: 800;"><?= explode(' ', ($user['nome'] ?? 'Usuário'))[0] ?></div>
        <div style="font-size: 10px; font-weight: 700; color: var(--text-dim);">Créditos: <b id="header-saldo" style="color: var(--primary);"><?php echo (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos']; ?></b></div>
    </div>
</header>

<main>
    <div class="header-top">
        <h1 style="font-weight: 800; font-size: 1.4rem;">Sefullbet Pro AI 🚀</h1>
        <div class="status-badge">
            <div style="text-align: center; border-right: 1px solid #eee; padding-right: 15px;">
                <span style="font-size: 9px; color: var(--text-dim); font-weight: 800; display: block;">SALDO</span>
                <span id="saldo-display" style="font-size: 15px; font-weight: 900; color: var(--primary);"><?php echo (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos']; ?></span>
            </div>
            <div>
                <span style="font-size: 9px; color: var(--text-dim); font-weight: 800; display: block;">PLANO</span>
                <span style="color: <?= $cor_perfil ?>; font-weight: 900; font-size: 13px;"><?= strtoupper($perfil) ?></span>
            </div>
        </div>
    </div>

    <div class="input-card">
        <div class="input-group"><label>Odd Casa</label><input type="text" id="o-casa" placeholder="1.80" inputmode="decimal"></div>
        <div class="input-group"><label>Odd Empate</label><input type="text" id="o-empate" placeholder="3.40" inputmode="decimal"></div>
        <div class="input-group"><label>Odd Fora</label><input type="text" id="o-fora" placeholder="4.20" inputmode="decimal"></div>
        <button class="btn-analisar" onclick="processarIA()">Analisar Agora</button>
    </div>

    <div id="resultado-display" style="display: none;">
        <div class="best-entries-card">
            <h3 style="font-size: 0.9rem; margin-bottom: 15px;"><i class="fas fa-crown"></i> TOP 3 ENTRADAS RECOMENDADAS</h3>
            <div id="top-list"></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><h4><i class="fas fa-list"></i> Resultado Final</h4><div id="col-principal"></div></div>
            <div class="stat-card"><h4><i class="fas fa-copy"></i> Dupla Chance</h4><div id="col-dupla"></div></div>
            <div class="stat-card"><h4><i class="fas fa-plus-circle"></i> Gols Over</h4><div id="col-over"></div></div>
            <div class="stat-card"><h4><i class="fas fa-minus-circle"></i> Gols Under</h4><div id="col-under"></div></div>
            <div class="stat-card"><h4><i class="fas fa-calculator"></i> Médias e Amostra</h4><div id="col-medias"></div></div>
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

    if(!oc || !oe || !of) return alert("Por favor, preencha todas as Odds.");

    document.getElementById('loader').style.display = 'flex';
    
    setTimeout(async () => {
        // Lógica de similaridade Euclidiana
        const similares = DB.map(j => {
            const ocDB = limparNumero(j.odd_casa);
            const oeDB = limparNumero(j.odd_empate);
            const ofDB = limparNumero(j.odd_fora);
            const dist = Math.sqrt(Math.pow(ocDB - oc, 2) + Math.pow(oeDB - oe, 2) + Math.pow(ofDB - of, 2));
            const peso = 1 / (dist + 0.001);
            return {...j, dist, peso};
        })
        .filter(j => j.dist <= 0.1) // Filtro de similaridade
        .sort((a,b) => a.dist - b.dist)
        .slice(0, 50);

        if (similares.length === 0) {
            document.getElementById('loader').style.display = 'none';
            return alert("Recomendamos a seleção de um confronto alternativo. No cenário atual, identificamos uma volatilidade acentuada nas odds, o que compromete a previsibilidade estatística e eleva a exposição ao risco. Sugerimos priorizar eventos com maior estabilidade técnica.");
        }

        // Executa débito no banco via PHP
        const resDebito = await debitar();
        if(resDebito.status === 'erro') {
            document.getElementById('loader').style.display = 'none';
            return alert("Créditos insuficientes para realizar a análise.");
        }

        // Atualiza interface de saldo
        if(resDebito.novo_saldo !== undefined) {
            document.getElementById('saldo-display').innerText = resDebito.novo_saldo;
            document.getElementById('header-saldo').innerText = resDebito.novo_saldo;
        }

        renderizar(similares);
        document.getElementById('loader').style.display = 'none';
        document.getElementById('resultado-display').style.display = 'block';
        window.scrollTo({ top: 400, behavior: 'smooth' });
    }, 800);
}

function renderizar(dados) {
    const somaPesos = dados.reduce((acc, j) => acc + j.peso, 0);
    
    const calcProb = (campo, valor) => {
        const pesoOcorrido = dados.filter(j => j[campo] === valor).reduce((acc, j) => acc + j.peso, 0);
        return ((pesoOcorrido / somaPesos) * 100);
    };

    const probCasa = calcProb('resultado', 'Casa');
    const probEmpa = calcProb('resultado', 'Empate'); 
    const probFora = calcProb('resultado', 'Fora');
    const pAMB_Sim = calcProb('ambos_marcam', 'Sim');
    const pAMB_Nao = 100 - pAMB_Sim;

    const pO05 = calcProb('over_05', 'Sim');
    const pO15 = calcProb('over_15', 'Sim');
    const pO25 = calcProb('over_25', 'Sim');
    const pO35 = calcProb('over_35', 'Sim');
    const pO45 = calcProb('over_45', 'Sim');

    const pU05 = 100 - pO05;
    const pU15 = 100 - pO15;
    const pU25 = 100 - pO25;
    const pU35 = 100 - pO35;
    const pU45 = 100 - pO45;

    const prob1X = probCasa + probEmpa;
    const prob12 = probCasa + probFora;
    const probX2 = probFora + probEmpa;

    document.getElementById('col-principal').innerHTML = `
        <div class="data-row"><span>V. Casa</span><b>${probCasa.toFixed(1)}%</b></div>
        <div class="data-row"><span>Empate</span><b>${probEmpa.toFixed(1)}%</b></div>
        <div class="data-row"><span>V. Fora</span><b>${probFora.toFixed(1)}%</b></div>
        <div class="data-row"><span>Ambos Sim</span><b>${pAMB_Sim.toFixed(1)}%</b></div>
        <div class="data-row"><span>Ambos Não</span><b>${pAMB_Nao.toFixed(1)}%</b></div>
    `;

    document.getElementById('col-dupla').innerHTML = `
        <div class="data-row"><span>Casa ou Empate 1X</span><b>${prob1X.toFixed(1)}%</b></div>
        <div class="data-row"><span>Casa ou Fora 12</span><b>${prob12.toFixed(1)}%</b></div>
        <div class="data-row"><span>Empate ou Fora X2</span><b>${probX2.toFixed(1)}%</b></div>
    `;

    document.getElementById('col-over').innerHTML = `
        <div class="data-row"><span>+0.5 Gols</span><b>${pO05.toFixed(1)}%</b></div>
        <div class="data-row"><span>+1.5 Gols</span><b>${pO15.toFixed(1)}%</b></div>
        <div class="data-row"><span>+2.5 Gols</span><b>${pO25.toFixed(1)}%</b></div>
        <div class="data-row"><span>+3.5 Gols</span><b>${pO35.toFixed(1)}%</b></div>
        <div class="data-row"><span>+4.5 Gols</span><b>${pO45.toFixed(1)}%</b></div>
    `;

    document.getElementById('col-under').innerHTML = `
        <div class="data-row"><span>-0.5 Gols</span><b>${pU05.toFixed(1)}%</b></div>
        <div class="data-row"><span>-1.5 Gols</span><b>${pU15.toFixed(1)}%</b></div>
        <div class="data-row"><span>-2.5 Gols</span><b>${pU25.toFixed(1)}%</b></div>
        <div class="data-row"><span>-3.5 Gols</span><b>${pU35.toFixed(1)}%</b></div>
        <div class="data-row"><span>-4.5 Gols</span><b>${pU45.toFixed(1)}%</b></div>
    `;

    const somaGolsPonderada = dados.reduce((acc, j) => acc + (limparNumero(j.gols_total) * j.peso), 0);
    const mediaGols = (somaGolsPonderada / somaPesos).toFixed(2);

    document.getElementById('col-medias').innerHTML = `
        <div class="data-row"><span>Média Gols (AI)</span><b>${mediaGols}</b></div>
        <div class="data-row"><span>Amostra (N)</span><b>${dados.length}</b></div>
        <div class="data-row"><span>Confiança</span><b>${dados.length >= 25 ? 'Alta' : 'Média'}</b></div>
    `;

    let todosMercados = [
        { n: "Vitória Direta Casa", v: probCasa },
        { n: "Vitória Direta Fora", v: probFora },
        { n: "1X (Casa ou Empate)", v: prob1X },
        { n: "X2 (Fora ou Empate)", v: probX2 },
        { n: "12 (Casa ou Fora)", v: prob12 },
        { n: "Over 0.5 Gols", v: pO05 },
        { n: "Over 1.5 Gols", v: pO15 },
        { n: "Over 2.5 Gols", v: pO25 },
        { n: "Under 2.5 Gols", v: pU25 },
        { n: "Under 3.5 Gols", v: pU35 },
        { n: "Ambos Marcam Sim", v: pAMB_Sim },
        { n: "Ambos Marcam Não", v: pAMB_Nao }
    ];

    let ranking = todosMercados.sort((a,b) => b.v - a.v).slice(0, 3);

    document.getElementById('top-list').innerHTML = ranking.map((item, i) => `
        <div class="entry-row">
            <span style="font-weight:700;">#${i+1} ${item.n}</span>
            <div class="entry-perc">${item.v.toFixed(1)}%</div>
        </div>
    `).join('');
}

async function debitar() {
    try {
        const response = await fetch('analisador.php', { 
            method: 'POST', 
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
            body: 'action=debitar' 
        });
        return await response.json();
    } catch (e) {
        return { status: 'erro' };
    }
}
</script>
</body>
</html>
