<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Busca dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();
$perfil = $user['perfil']; 

/**
 * 2. BUSCA DA BASE COMPLETA (15 mil registros)
 * Usamos REPLACE para garantir que vírgulas virem pontos antes de chegar no JS
 */
$sql = "SELECT 
            REPLACE(odd_casa, ',', '.') as odd_casa,
            REPLACE(odd_empate, ',', '.') as odd_empate,
            REPLACE(odd_fora, ',', '.') as odd_fora,
            resultado, ambos_marcam, gols_total,
            over_05, over_15, over_25, over_35, over_45 
        FROM base_analisador"; // Nome da sua nova tabela
$stmt_data = $pdo->query($sql);
$dados_historicos = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

// 3. Lógica de Débito
if (isset($_POST['action']) && $_POST['action'] == 'debitar') {
    if (!in_array($perfil, ['Admin', 'Platinum', 'Supervisor'])) {
        $pdo->prepare("UPDATE usuarios SET saldo_creditos = saldo_creditos - 1 WHERE id = ? AND saldo_creditos > 0")->execute([$_SESSION['usuario_id']]);
    }
    exit();
}

$cores = ['Grátis' => '#8b949e', 'VIP' => '#ffd700', 'Platinum' => '#ffffff', 'Supervisor' => '#00e5ff', 'Admin' => '#00ff88'];
$cor_perfil = $cores[$perfil] ?? $cores['Grátis'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisador AI Pro | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Mantendo seu CSS original que é excelente */
        :root { --primary: #00ff88; --primary-glow: rgba(0, 255, 136, 0.3); --bg: #0d1117; --card: #161b22; --border: #30363d; --text-main: #f0f6fc; --text-dim: #8b949e; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text-main); display: flex; min-height: 100vh; }
        nav { width: 280px; background: rgba(22, 27, 34, 0.8); backdrop-filter: blur(10px); border-right: 1px solid var(--border); padding: 30px 15px; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .nav-logo { font-weight: 800; font-size: 1.6rem; letter-spacing: -1px; margin-bottom: 30px; text-align: center; color: #fff; }
        .nav-logo span { color: var(--primary); }
        .nav-btn { color: var(--text-dim); padding: 12px 18px; border-radius: 12px; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 13px; transition: 0.3s; margin-bottom: 2px; }
        .nav-btn.active { background: var(--primary-glow); color: var(--primary); }
        main { flex: 1; margin-left: 280px; padding: 40px 60px; }
        .input-card { background: var(--card); padding: 25px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 30px; display: flex; gap: 20px; align-items: flex-end; }
        .input-group { flex: 1; }
        .input-group label { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 8px; font-weight: 700; }
        .input-group input { width: 100%; padding: 15px; background: #0d1117; border: 1px solid var(--border); border-radius: 10px; color: var(--primary); font-weight: 800; text-align: center; }
        .btn-analisar { padding: 0 40px; background: var(--primary); color: #0d1117; border: none; border-radius: 10px; font-weight: 800; cursor: pointer; height: 55px; }
        .best-entries-container { border: 2px solid var(--primary); border-radius: 20px; padding: 25px; margin-bottom: 30px; background: linear-gradient(135deg, rgba(0, 255, 136, 0.05) 0%, transparent 100%); }
        .entry-row { background: rgba(255,255,255,0.03); border-radius: 50px; padding: 12px 25px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .entry-perc { background: var(--primary); color: #000; font-weight: 900; padding: 5px 15px; border-radius: 20px; }
        .stats-grid-5 { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; }
        .stat-col { background: var(--card); border-radius: 15px; border: 1px solid var(--border); padding: 20px; }
        .stat-col h4 { font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        .data-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13px; }
        .data-row b { color: var(--primary); }
        #loader { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(13,17,23,0.98); z-index:1000; flex-direction:column; justify-content:center; align-items:center; }
        .spinner { width: 60px; height: 60px; border: 5px solid #161b22; border-top-color: var(--primary); border-radius: 50%; animation: spin 1s infinite linear; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div id="loader">
    <div class="spinner"></div>
    <p style="color: var(--primary); margin-top:20px; font-weight:800;">IA ANALISANDO 15.000 REGISTROS...</p>
</div>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    <div class="nav-group">
        <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
        <a class="nav-btn active" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <?php if (in_array($perfil, ['Supervisor', 'Admin'])): ?>
            <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
        <?php endif; ?>
    </div>
</nav>

<main>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
        <div>
            <h1 style="color: var(--text-dim); font-weight: 400; font-size: 1.1rem;">Analisador Pro,</h1>
            <h1 style="font-size: 1.8rem; font-weight: 800;"><?= explode(' ', $user['nome'])[0] ?> 🚀</h1>
        </div>
        <div style="background: var(--card); padding: 15px 25px; border-radius: 18px; border: 1px solid var(--border); text-align: right;">
            <div style="font-size: 9px; color: var(--text-dim); font-weight: 700;">SALDO DE CRÉDITOS</div>
            <div style="color: var(--primary); font-weight: 800; font-size: 18px;"><?= (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos']; ?></div>
        </div>
    </div>

    <div class="input-card">
        <div class="input-group"><label>Odd Casa</label><input type="text" id="o-casa" placeholder="1.85"></div>
        <div class="input-group"><label>Odd Empate</label><input type="text" id="o-empate" placeholder="3.40"></div>
        <div class="input-group"><label>Odd Fora</label><input type="text" id="o-fora" placeholder="4.50"></div>
        <button class="btn-analisar" onclick="processarIA()"><i class="fas fa-sync-alt"></i> Analisar Agora</button>
    </div>

    <div id="resultado-display" style="display: none;">
        <div class="best-entries-container">
            <div id="top-list"></div>
        </div>

        <div class="stats-grid-5">
            <div class="stat-col"><h4>Vencedor</h4><div id="col-principal"></div></div>
            <div class="stat-col"><h4>Dupla Chance</h4><div id="col-dupla"></div></div>
            <div class="stat-col"><h4>Over Gols</h4><div id="col-over"></div></div>
            <div class="stat-col"><h4>Under Gols</h4><div id="col-under"></div></div>
            <div class="stat-col"><h4>Inteligência</h4><div id="col-medias"></div></div>
        </div>
    </div>
</main>

<script>
// A base de 15 mil registros agora está aqui como um objeto JS puro
const DB = <?= json_encode($dados_historicos) ?>;

function limparNumero(val) {
    if (!val) return 0;
    return parseFloat(val.toString().replace(',', '.'));
}

function processarIA() {
    const oc = limparNumero(document.getElementById('o-casa').value);
    const oe = limparNumero(document.getElementById('o-empate').value);
    const of = limparNumero(document.getElementById('o-fora').value);

    if(!oc || !oe || !of) return alert("Insira as 3 odds.");

    document.getElementById('loader').style.display = 'flex';
    
    setTimeout(() => {
        // Cálculo de similaridade em toda a base (15.000 iterações)
        const similares = DB.map(j => {
            const dCasa = Math.pow(limparNumero(j.odd_casa) - oc, 2);
            const dEmpa = Math.pow(limparNumero(j.odd_empate) - oe, 2);
            const dFora = Math.pow(limparNumero(j.odd_fora) - of, 2);
            return {...j, diff: Math.sqrt(dCasa + dEmpa + dFora)};
        })
        .sort((a,b) => a.diff - b.diff)
        .slice(0, 50); // Pegamos os 50 jogos mais parecidos de toda a história

        renderizar(similares);
        debitar();
        
        document.getElementById('loader').style.display = 'none';
        document.getElementById('resultado-display').style.display = 'block';
    }, 1200);
}

function renderizar(dados) {
    const total = dados.length;
    const calc = (col, val) => (dados.filter(j => j[col] === val).length / total * 100).toFixed(1);

    const pCasa = parseFloat(calc('resultado', 'Casa'));
    const pEmpa = parseFloat(calc('resultado', 'Empate'));
    const pFora = parseFloat(calc('resultado', 'Fora'));

    document.getElementById('col-principal').innerHTML = `
        <div class="data-row"><span>Casa</span><b>${pCasa}%</b></div>
        <div class="data-row"><span>Empate</span><b>${pEmpa}%</b></div>
        <div class="data-row"><span>Fora</span><b>${pFora}%</b></div>
        <div class="data-row"><span>Ambos Marcam</span><b>${calc('ambos_marcam', 'Sim')}%</b></div>
    `;

    document.getElementById('col-over').innerHTML = `
        <div class="data-row"><span>+1.5 Gols</span><b>${calc('over_15', 'Sim')}%</b></div>
        <div class="data-row"><span>+2.5 Gols</span><b>${calc('over_25', 'Sim')}%</b></div>
        <div class="data-row"><span>+3.5 Gols</span><b>${calc('over_35', 'Sim')}%</b></div>
    `;

    // Ranking Dinâmico
    const opcoes = [
        {n: "Casa ou Empate", v: pCasa + pEmpa},
        {n: "Vitoria Casa", v: pCasa},
        {n: "Over 1.5 Gols", v: parseFloat(calc('over_15', 'Sim'))},
        {n: "Ambos Marcam", v: parseFloat(calc('ambos_marcam', 'Sim'))}
    ].sort((a,b) => b.v - a.v).slice(0, 3);

    document.getElementById('top-list').innerHTML = `<h3 style='margin-bottom:15px; color:var(--primary)'>🔥 TOP ENTRADAS SUGERIDAS</h3>` + 
        opcoes.map((item, i) => `
        <div class="entry-row">
            <span>#${i+1} ${item.n}</span>
            <div class="entry-perc">${item.v.toFixed(1)}%</div>
        </div>`).join('');
}

function debitar() {
    fetch('analisador.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=debitar' });
}
</script>
</body>
</html>
