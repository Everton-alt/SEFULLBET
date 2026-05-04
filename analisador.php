<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$perfil = $user['perfil']; 

if (isset($_POST['action']) && $_POST['action'] == 'debitar') {
    header('Content-Type: application/json');
    $is_premium = in_array($perfil, ['Admin', 'Platinum', 'Supervisor']);
    if (!$is_premium) {
        if (($user['saldo_creditos'] ?? 0) <= 0) {
            echo json_encode(['status' => 'erro']);
            exit();
        }
        $pdo->prepare("UPDATE usuarios SET saldo_creditos = saldo_creditos - 1 WHERE id = ? AND saldo_creditos > 0")->execute([$user_id]);
        $stmt_s = $pdo->prepare("SELECT saldo_creditos FROM usuarios WHERE id = ?");
        $stmt_s->execute([$user_id]);
        echo json_encode(['status' => 'sucesso', 'novo_saldo' => $stmt_s->fetchColumn()]);
    } else {
        echo json_encode(['status' => 'isento', 'novo_saldo' => '∞']);
    }
    exit();
}

try {
    $stmt_data = $pdo->query("SELECT * FROM base_analisador LIMIT 5000");
    $dados_historicos = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $dados_historicos = []; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefullbet - Analisador Pro AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #2ECC71; --bg-body: #ffffff; --dark-bg: #0b0e11; --text-dim: #636e72; --border: #f1f1f1; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-body); padding-bottom: 50px; }
        .sidebar { height: 100%; width: 280px; position: fixed; z-index: 2000; top: 0; left: -280px; background-color: #2d3436; transition: 0.4s; padding-top: 20px; }
        .sidebar .nav-btn { padding: 12px 25px; text-decoration: none; font-size: 15px; color: #b2bec3; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #3d4648; }
        .sidebar .nav-btn.active { color: var(--primary); background: #3d4648; }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 30px; cursor: pointer; color: var(--primary); }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }
        header { background: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; position: sticky; top:0; z-index: 100; }
        .logo { font-weight: 900; font-size: 1.3rem; } .logo span { color: var(--primary); }
        .analyzer-main { padding: 20px 15px; max-width: 1200px; margin: 0 auto; }
        .input-card { background: var(--dark-bg); padding: 25px; border-radius: 18px; display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 25px; }
        .input-group { flex: 1; min-width: 120px; }
        .input-group label { display: block; color: #848d95; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; }
        .input-group input { width: 100%; background: #1c2127; border: 1px solid #2b2f36; padding: 14px; border-radius: 10px; color: var(--primary); font-weight: 800; text-align: center; }
        .btn-analisar { background: var(--primary); color: #fff; border: none; padding: 0 30px; border-radius: 10px; font-weight: 800; cursor: pointer; text-transform: uppercase; }
        .best-entries-container { background: #eafaf1; border-radius: 15px; padding: 20px; margin-bottom: 25px; border-left: 6px solid var(--primary); }
        .entry-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dotted #badbcc; align-items: center; }
        .entry-perc { background: var(--primary); color: #fff; padding: 4px 12px; border-radius: 20px; font-weight: 900; font-size: 12px; }
        .stats-grid-5 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .stat-col { background: #fff; border: 1px solid var(--border); border-radius: 18px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .stat-col h4 { margin: 0 0 15px 0; font-size: 11px; color: var(--text-dim); text-transform: uppercase; border-bottom: 2px solid #f8f9fa; padding-bottom: 8px; font-weight: 800; }
        .data-row { display: flex; justify-content: space-between; font-size: 13px; padding: 8px 0; border-bottom: 1px solid #f8f9fa; }
        .data-row b { color: var(--primary); font-weight: 900; }
        #loader { display: none; flex-direction: column; align-items: center; padding: 40px; }
        .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid var(--primary); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div id="overlay" class="overlay" onclick="closeNav()"></div>
<div id="mySidebar" class="sidebar">
    <span class="close-btn" onclick="closeNav()">&times;</span>
    <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Início</span></a>
    <a class="nav-btn active" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
    <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
    <a href="logout.php" class="nav-btn"><i class="fas fa-sign-out-alt"></i> Sair</a>
</div>

<header>
    <div class="menu-icon" onclick="openNav()">☰</div>
    <div class="logo">SEFULL<span>BET</span> AI</div>
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
