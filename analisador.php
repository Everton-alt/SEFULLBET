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

// 4. Busca da base de dados histórica para a IA
try {
    $stmt_data = $pdo->query("SELECT odd_casa, odd_empate, odd_fora, resultado, ambos_marcam, gols_total, over_05, over_15, over_25, over_35, over_45 FROM base_analisador");
    $dados_historicos = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dados_historicos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisador AI Pro - Sefullbet</title>
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

        /* --- SIDEBAR (IDÊNTICA AO DASHBOARD) --- */
        .sidebar { height: 100%; width: 280px; position: fixed; z-index: 2000; top: 0; left: -280px; background-color: #2d3436; overflow-x: hidden; transition: 0.4s; padding-top: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.1); }
        .sidebar .nav-btn { padding: 12px 25px; text-decoration: none; font-size: 15px; color: #b2bec3; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #3d4648; transition: 0.3s; }
        .sidebar .nav-btn i { width: 20px; text-align: center; }
        .sidebar .nav-btn:hover, .sidebar .nav-btn.active { background: #3d4648; color: var(--primary); }
        .sidebar .logout-btn { color: #ff7675 !important; font-weight: bold; border-bottom: none !important; }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 30px; cursor: pointer; color: var(--primary); z-index: 2001; }
        .nav-label { color: var(--primary); font-size: 11px; text-transform: uppercase; padding: 15px 25px 5px; display: block; font-weight: 800; letter-spacing: 1px; }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }

        /* --- HEADER --- */
        header { background-color: #ffffff; color: var(--primary); padding: 15px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #eee; }
        .menu-icon { font-size: 24px; cursor: pointer; color: #2d3436; }
        .logo { font-weight: 900; font-size: 1.3rem; letter-spacing: 1px; color: #2d3436; }
        .logo span { color: var(--primary); }

        /* --- ANALISADOR LAYOUT --- */
        .container { padding: 20px 15px; max-width: 800px; margin: 0 auto; }
        
        .user-welcome { margin-bottom: 25px; }
        .user-welcome h1 { font-size: 1.4rem; font-weight: 800; margin: 0; }
        .user-welcome p { color: var(--text-dim); font-size: 0.9rem; margin: 5px 0 0; }

        .credit-badge { background: var(--bg-secondary); padding: 10px 20px; border-radius: 12px; border: 1px solid var(--border); display: inline-flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .credit-badge i { color: var(--primary); }
        .credit-badge span { font-weight: 800; font-size: 0.9rem; }

        .input-card { background: #fff; padding: 20px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border); border-top: 5px solid var(--primary); margin-bottom: 25px; }
        .input-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .input-group label { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; font-weight: 700; margin-bottom: 8px; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; background: var(--bg-secondary); font-weight: 800; font-size: 1.1rem; text-align: center; color: var(--text-main); outline: none; }
        .input-group input:focus { border-color: var(--primary); background: #fff; }

        .btn-analisar { width: 100%; padding: 16px; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-weight: 800; font-size: 1rem; text-transform: uppercase; cursor: pointer; transition: 0.3s; box-shadow: 0 5px 15px rgba(46, 204, 113, 0.2); }
        .btn-analisar:hover { transform: translateY(-2px); filter: brightness(1.1); }

        /* --- RESULTADOS --- */
        .result-section { display: none; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .best-entry { background: linear-gradient(135deg, #2ecc71, #27ae60); color: #fff; border-radius: 20px; padding: 20px; margin-bottom: 20px; position: relative; overflow: hidden; }
        .best-entry h3 { font-size: 0.8rem; text-transform: uppercase; margin-bottom: 10px; opacity: 0.9; letter-spacing: 1px; }
        .entry-item { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.15); padding: 12px 18px; border-radius: 50px; margin-bottom: 8px; font-weight: 800; }
        
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .stat-box { background: #fff; padding: 15px; border-radius: 15px; border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .stat-box h4 { font-size: 11px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 12px; border-bottom: 1px solid var(--bg-secondary); padding-bottom: 8px; }
        .row-data { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; }
        .row-data b { color: var(--primary); font-weight: 800; }

        /* LOADER */
        #loader { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(255,255,255,0.9); z-index:5000; flex-direction:column; justify-content:center; align-items:center; }
        .spinner { width: 50px; height: 50px; border: 5px solid var(--bg-secondary); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s infinite linear; }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 600px) {
            .input-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div id="loader">
    <div class="spinner"></div>
    <p style="margin-top:15px; font-weight:800; color: var(--primary);">IA ANALISANDO ODDS...</p>
</div>

<div id="overlay" class="overlay" onClick="closeNav()"></div>

<div id="mySidebar" class="sidebar">
    <span class="close-btn" onClick="closeNav()">&times;</span>
    <div class="nav-logo" style="color: #fff; margin-bottom: 30px;">SEFULL<span>BET</span></div>
    
    <span class="nav-label">Menu Principal</span>
    <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
    <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
    <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
    <a class="nav-btn active" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
    <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>
    <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>

    <?php if (in_array($perfil, ['Supervisor', 'Admin'])): ?>
        <hr style="border: 0; border-top: 1px solid #3d4648; margin: 15px 10px;">
        <span class="nav-label">Gestão Administrativa</span>
        <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
        <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
        <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
    <?php endif; ?>
    
    <a href="logout.php" class="nav-btn logout-btn" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Sair da Conta</a>
</div>

<header>
    <div class="menu-icon" onClick="openNav()">☰</div>
    <div class="logo">SEFULL<span>BET</span></div>
    <div style="font-size: 14px; font-weight: 800; color: var(--text-dim)"><?= explode(' ', $user['nome'])[0] ?></div>
</header>

<main class="container">
    <div class="user-welcome">
        <h1>Analisador AI <span style="color: var(--primary)">Pro</span></h1>
        <p>A tecnologia da SeFullBet prevendo o próximo Green.</p>
    </div>

    <div class="credit-badge">
        <i class="fas fa-bolt"></i>
        <span>Créditos: <b id="saldo-display" style="color: var(--primary);"><?php echo (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos']; ?></b></span>
        <span style="margin-left: 10px; border-left: 1px solid #ddd; padding-left: 10px; font-size: 0.7rem; color: var(--text-dim);"><?php echo strtoupper($perfil); ?></span>
    </div>

    <div class="input-card">
        <div class="input-grid">
            <div class="input-group"><label>Odd Casa</label><input type="text" id="o-casa" placeholder="1.85"></div>
            <div class="input-group"><label>Odd Empate</label><input type="text" id="o-empate" placeholder="3.40"></div>
            <div class="input-group"><label>Odd Fora</label><input type="text" id="o-fora" placeholder="4.50"></div>
        </div>
        <button class="btn-analisar" onclick="processarIA()">Iniciar Análise Inteligente</button>
    </div>

    <div id="resultado-display" class="result-section">
        <div class="best-entry">
            <h3><i class="fas fa-star"></i> Top Oportunidades</h3>
            <div id="top-list"></div>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <h4>Vencedor Final</h4>
                <div id="col-principal"></div>
            </div>
            <div class="stat-box">
                <h4>Mercado de Gols</h4>
                <div id="col-over"></div>
            </div>
            <div class="stat-box">
                <h4>Dupla Chance</h4>
                <div id="col-dupla"></div>
            </div>
            <div class="stat-box">
                <h4>Dados Técnicos</h4>
                <div id="col-medias"></div>
            </div>
        </div>
    </div>
</main>

<script>
const DB = <?php echo json_encode($dados_historicos); ?>;

function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }
function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }

function limparNumero(val) {
    if (!val) return 0;
    return parseFloat(val.toString().replace(',', '.'));
}

async function processarIA() {
    const oc = limparNumero(document.getElementById('o-casa').value);
    const oe = limparNumero(document.getElementById('o-empate').value);
    const of = limparNumero(document.getElementById('o-fora').value);

    if(!oc || !oe || !of) return alert("Insira as 3 Odds do confronto.");

    document.getElementById('loader').style.display = 'flex';

    // Débito via AJAX
    const resDebito = await debitar();
    if(resDebito.status === 'erro') {
        document.getElementById('loader').style.display = 'none';
        alert("Créditos insuficientes! Renove seu plano.");
        return;
    }

    if(resDebito.novo_saldo !== undefined) {
        document.getElementById('saldo-display').innerText = resDebito.novo_saldo;
    }

    // Algoritmo KNN (K-Nearest Neighbors) Ponderado
    setTimeout(() => {
        const similares = DB.map(j => {
            const dist = Math.sqrt(Math.pow(limparNumero(j.odd_casa) - oc, 2) + Math.pow(limparNumero(j.odd_empate) - oe, 2) + Math.pow(limparNumero(j.odd_fora) - of, 2));
            const peso = 1 / (dist + 0.001);
            return {...j, dist, peso};
        })
        .filter(j => j.dist <= 0.15) // Margem de similaridade
        .sort((a,b) => a.dist - b.dist)
        .slice(0, 40);

        if (similares.length === 0) {
            document.getElementById('loader').style.display = 'none';
            alert("Nenhum padrão similar encontrado na base.");
            return;
        }

        renderizar(similares);
        document.getElementById('loader').style.display = 'none';
        document.getElementById('resultado-display').style.display = 'block';
    }, 1200);
}

function renderizar(dados) {
    const somaPesos = dados.reduce((acc, j) => acc + j.peso, 0);
    const calcProb = (campo, valor) => (dados.filter(j => j[campo] === valor).reduce((acc, j) => acc + j.peso, 0) / somaPesos * 100);

    const pCasa = calcProb('resultado', 'Casa');
    const pEmpa = calcProb('resultado', 'Empate');
    const pFora = calcProb('resultado', 'Fora');
    const pAMB = calcProb('ambos_marcam', 'Sim');
    const pO15 = calcProb('over_15', 'Sim');
    const pO25 = calcProb('over_25', 'Sim');

    document.getElementById('col-principal').innerHTML = `
        <div class="row-data"><span>V. Casa</span><b>${pCasa.toFixed(1)}%</b></div>
        <div class="row-data"><span>Empate</span><b>${pEmpa.toFixed(1)}%</b></div>
        <div class="row-data"><span>V. Fora</span><b>${pFora.toFixed(1)}%</b></div>
    `;

    document.getElementById('col-over').innerHTML = `
        <div class="row-data"><span>Over 1.5</span><b>${pO15.toFixed(1)}%</b></div>
        <div class="row-data"><span>Over 2.5</span><b>${pO25.toFixed(1)}%</b></div>
        <div class="row-data"><span>Ambos Marcam</span><b>${pAMB.toFixed(1)}%</b></div>
    `;

    document.getElementById('col-dupla').innerHTML = `
        <div class="row-data"><span>1X (Casa ou Emp)</span><b>${(pCasa+pEmpa).toFixed(1)}%</b></div>
        <div class="row-data"><span>X2 (Fora ou Emp)</span><b>${(pFora+pEmpa).toFixed(1)}%</b></div>
        <div class="row-data"><span>12 (Casa ou Fora)</span><b>${(pCasa+pFora).toFixed(1)}%</b></div>
    `;

    const mediaGols = (dados.reduce((acc, j) => acc + (limparNumero(j.gols_total) * j.peso), 0) / somaPesos).toFixed(2);
    document.getElementById('col-medias').innerHTML = `
        <div class="row-data"><span>Média Gols</span><b>${mediaGols}</b></div>
        <div class="row-data"><span>Amostra</span><b>${dados.length} jogos</b></div>
    `;

    // Ranking
    let rank = [
        {n: "V. Casa", v: pCasa}, {n: "Over 1.5", v: pO15}, {n: "1X (Dupla)", v: pCasa+pEmpa}, {n: "Ambos Marcam", v: pAMB}
    ].sort((a,b) => b.v - a.v).slice(0, 3);

    document.getElementById('top-list').innerHTML = rank.map((it, idx) => `
        <div class="entry-item">
            <span>#${idx+1} ${it.n}</span>
            <span style="color: #fff;">${it.v.toFixed(1)}%</span>
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
    } catch (e) { return { status: 'erro' }; }
}
</script>
</body>
</html>
