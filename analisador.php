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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2ECC71; 
            --bg-body: #f4f7f6; 
            --sidebar-bg: #2d3436;
            --card-bg: #ffffff;
            --text-main: #2d3436;
            --text-dim: #636e72;
            --border: #edf2f7;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); min-height: 100vh; }

        /* Sidebar Pattern */
        .sidebar { height: 100%; width: 280px; position: fixed; z-index: 2000; top: 0; left: -280px; background-color: #2d3436; overflow-x: hidden; transition: 0.4s; padding-top: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.1); }
        .sidebar .nav-btn { padding: 12px 25px; text-decoration: none; font-size: 15px; color: #b2bec3; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #3d4648; transition: 0.3s; }
        .sidebar .nav-btn:hover, .sidebar .nav-btn.active { background: #3d4648; color: var(--primary); }
        .sidebar .logout-btn { color: #ff7675 !important; font-weight: bold; border-bottom: none !important; }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 30px; cursor: pointer; color: var(--primary); }
        .nav-label { color: var(--primary); font-size: 11px; text-transform: uppercase; padding: 15px 25px 5px; display: block; font-weight: 800; letter-spacing: 1px; }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }

        header { background-color: #ffffff; padding: 15px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #eee; }
        .menu-icon { font-size: 24px; cursor: pointer; color: #2d3436; }
        .logo { font-weight: 900; font-size: 1.3rem; letter-spacing: 1px; }
        .logo span { color: var(--primary); }

        main { padding: 30px 15px; max-width: 1200px; margin: 0 auto; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 15px; }
        .status-badge { background: var(--card-bg); padding: 10px 20px; border-radius: 15px; border: 1px solid var(--border); display: flex; gap: 20px; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }

        .input-card { background: var(--card-bg); padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); display: flex; gap: 15px; align-items: flex-end; margin-bottom: 30px; flex-wrap: wrap; }
        .input-group { flex: 1; min-width: 120px; }
        .input-group label { display: block; font-size: 11px; color: var(--text-dim); text-transform: uppercase; font-weight: 700; margin-bottom: 10px; }
        .input-group input { width: 100%; padding: 15px; background: #f8fafc; border: 2px solid #edf2f7; border-radius: 12px; font-weight: 800; font-size: 1.2rem; text-align: center; }
        
        .btn-analisar { height: 58px; padding: 0 35px; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; text-transform: uppercase; box-shadow: 0 6px 20px rgba(46, 204, 113, 0.3); width: 100%; max-width: 250px; }

        .best-entries-card { background: linear-gradient(135deg, #2ecc71, #27ae60); border-radius: 20px; padding: 25px; margin-bottom: 30px; color: #fff; }
        .entry-row { background: rgba(255,255,255,0.2); border-radius: 15px; padding: 15px 25px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 15px; font-weight: 600; }
        .entry-perc { background: #fff; color: var(--primary); padding: 5px 15px; border-radius: 10px; font-weight: 900; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
        .stat-card { background: #fff; border-radius: 20px; padding: 20px; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .stat-card h4 { font-size: 11px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid #f8fafc; padding-bottom: 10px; display: flex; justify-content: space-between; }
        .data-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13px; font-weight: 600; }
        .data-row b { color: var(--primary); font-weight: 800; }

        #loader { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(255,255,255,0.9); z-index:2000; flex-direction:column; justify-content:center; align-items:center; }
        .spinner { width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid var(--primary); border-radius: 50%; animation: spin 1s infinite linear; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div id="loader">
    <div class="spinner"></div>
    <p style="color: var(--primary); margin-top:20px; font-weight:800; text-transform:uppercase;">Sefullbet Analisando Suas Odds...</p>
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
        <hr style="border: 0; border-top: 1px solid #3d4648; margin: 15px 10px;">
        <span class="nav-label">Gestão Administrativa</span>
        <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
        <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
        <a class="nav-btn" href="base_dados_ai.php"><i class="fas fa-database"></i> <span>Verificar Dados AI</span></a>
        <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
        <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
    <?php endif; ?>
    
    <a href="logout.php" class="nav-btn logout-btn" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Sair da Conta</a>
</div>

<header>
    <div class="menu-icon" onClick="openNav()">☰</div>
    <div class="logo">SEFULL<span>BET</span></div>
    <div style="text-align: right; line-height: 1.2;">
        <div style="font-size: 14px; font-weight: 800; color: #2d3436">Olá, <?= explode(' ', ($user['nome'] ?? 'Usuário'))[0] ?></div>
        <div style="font-size: 11px; font-weight: 700; color: var(--text-dim);">
            Créditos: <b id="header-saldo" style="color: var(--primary);"><?php echo (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos']; ?></b>
        </div>
    </div>
</header>

<main>
    <div class="header-top">
        <h1 style="font-weight: 800; font-size: 1.8rem;">Sefullbet 🚀</h1>
        <div class="status-badge">
            <div style="text-align: center; border-right: 1px solid var(--border); padding-right: 20px;">
                <span style="font-size: 10px; color: var(--text-dim); font-weight: 700;">SALDO</span>
                <div id="saldo-display" style="font-size: 18px; font-weight: 800; color: var(--primary);"><?php echo (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos']; ?></div>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 10px; color: var(--text-dim); font-weight: 700;">PERFIL</span>
                <div style="color: <?php echo $cor_perfil; ?>; font-weight: 800;"><?php echo strtoupper($perfil); ?></div>
            </div>
        </div>
    </div>

    <div class="input-card">
        <div class="input-group"><label>Odd Casa</label><input type="text" id="o-casa" placeholder="1.80"></div>
        <div class="input-group"><label>Odd Empate</label><input type="text" id="o-empate" placeholder="3.40"></div>
        <div class="input-group"><label>Odd Fora</label><input type="text" id="o-fora" placeholder="4.20"></div>
        <button class="btn-analisar" onclick="processarIA()">Iniciar Análise</button>
    </div>

    <div id="resultado-display" style="display: none;">
        <div class="best-entries-card">
            <div style="font-weight: 800; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px;">
                <i class="fas fa-star"></i> Melhores Oportunidades Identificadas
            </div>
            <div id="top-list"></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><h4><i class="fas fa-trophy"></i> Resultado Final</h4><div id="col-principal"></div></div>
            <div class="stat-card"><h4><i class="fas fa-shield-alt"></i> Dupla Chance</h4><div id="col-dupla"></div></div>
            <div class="stat-card"><h4><i class="fas fa-arrow-up"></i> Mercados Over</h4><div id="col-over"></div></div>
            <div class="stat-card"><h4><i class="fas fa-arrow-down"></i> Mercados Under</h4><div id="col-under"></div></div>
            <div class="stat-card"><h4><i class="fas fa-chart-line"></i> Métricas IA</h4><div id="col-medias"></div></div>
        </div>
    </div>
</main>

<script>
function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }

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
            return alert("Padrão não encontrado.");
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
    const pO05 = calcProb('over_05', 'Sim');
    const pO15 = calcProb('over_15', 'Sim');
    const pO25 = calcProb('over_25', 'Sim');
    const pO35 = calcProb('over_35', 'Sim');
    const pO45 = calcProb('over_45', 'Sim');

    document.getElementById('col-principal').innerHTML = `
        <div class="data-row"><span>Casa</span><b>${probCasa.toFixed(1)}%</b></div>
        <div class="data-row"><span>Empate</span><b>${probEmpa.toFixed(1)}%</b></div>
        <div class="data-row"><span>Fora</span><b>${probFora.toFixed(1)}%</b></div>
        <div class="data-row"><span>Ambos Marcam</span><b>${pAMB_Sim.toFixed(1)}%</b></div>
    `;

    document.getElementById('col-dupla').innerHTML = `
        <div class="data-row"><span>Casa ou Empate</span><b>${(probCasa + probEmpa).toFixed(1)}%</b></div>
        <div class="data-row"><span>Casa ou Fora</span><b>${(probCasa + probFora).toFixed(1)}%</b></div>
        <div class="data-row"><span>Empate ou Fora</span><b>${(probFora + probEmpa).toFixed(1)}%</b></div>
    `;

    document.getElementById('col-over').innerHTML = `
        <div class="data-row"><span>+0.5 Gols</span><b>${pO05.toFixed(1)}%</b></div>
        <div class="data-row"><span>+1.5 Gols</span><b>${pO15.toFixed(1)}%</b></div>
        <div class="data-row"><span>+2.5 Gols</span><b>${pO25.toFixed(1)}%</b></div>
        <div class="data-row"><span>+3.5 Gols</span><b>${pO35.toFixed(1)}%</b></div>
        <div class="data-row"><span>+4.5 Gols</span><b>${pO45.toFixed(1)}%</b></div>
    `;

    document.getElementById('col-under').innerHTML = `
        <div class="data-row"><span>-0.5 Gols</span><b>${(100 - pO05).toFixed(1)}%</b></div>
        <div class="data-row"><span>-1.5 Gols</span><b>${(100 - pO15).toFixed(1)}%</b></div>
        <div class="data-row"><span>-2.5 Gols</span><b>${(100 - pO25).toFixed(1)}%</b></div>
        <div class="data-row"><span>-3.5 Gols</span><b>${(100 - pO35).toFixed(1)}%</b></div>
        <div class="data-row"><span>-4.5 Gols</span><b>${(100 - pO45).toFixed(1)}%</b></div>
    `;

    const mediaTotal = (dados.reduce((acc, j) => acc + (limparNumero(j.gols_total) * j.peso), 0) / somaPesos).toFixed(2);
    const mediaCasa = (dados.reduce((acc, j) => acc + (limparNumero(j.gols_casa) * j.peso), 0) / somaPesos).toFixed(2);
    const mediaFora = (dados.reduce((acc, j) => acc + (limparNumero(j.gols_fora) * j.peso), 0) / somaPesos).toFixed(2);

    document.getElementById('col-medias').innerHTML = `
        <div class="data-row"><span>Média Gols Total</span><b>${mediaTotal}</b></div>
        <div class="data-row"><span>Média Gols Casa</span><b>${mediaCasa}</b></div>
        <div class="data-row"><span>Média Gols Fora</span><b>${mediaFora}</b></div>
        <div class="data-row"><span>Base Analisada</span><b>${dados.length} jogos</b></div>
    `;

    // TOP 3 DINÂMICO - Agora inclui todos os Over e Under solicitados
    let todosMercados = [
        { n: "Casa ou Empate", v: probCasa + probEmpa },
        { n: "Ambos Marcam", v: pAMB_Sim },
        { n: "Over 0.5 Gols", v: pO05 },
        { n: "Over 1.5 Gols", v: pO15 },
        { n: "Over 2.5 Gols", v: pO25 },
        { n: "Over 3.5 Gols", v: pO35 },
        { n: "Over 4.5 Gols", v: pO45 },
        { n: "Under 0.5 Gols", v: 100 - pO05 },
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
