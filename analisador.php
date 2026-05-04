<?php
session_start();
require_once 'config.php';

// 1. Verificação de Login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Busca dados do usuário
$user_id = (int)$_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$perfil = $user['perfil'] ?? 'Grátis'; 

// 3. Lógica de Débito (AJAX)
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

// 4. Busca da base histórica - CERTIFIQUE-SE QUE ESTES NOMES EXISTEM NA TABELA
try {
    $stmt_data = $pdo->query("SELECT * FROM base_analisador LIMIT 5000");
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
    <title>Sefullbet Pro AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2ECC71; --bg-body: #f0f2f5; --card-bg: #ffffff; --text-main: #2d3436; --text-dim: #636e72; --border: #e1e8ed; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); }
        header { background: #fff; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top:0; z-index: 1000; }
        .logo { font-weight: 800; color: #1e272e; } .logo span { color: var(--primary); }
        main { padding: 20px; max-width: 1100px; margin: 0 auto; }
        .input-card { background: #fff; padding: 20px; border-radius: 15px; display: flex; gap: 15px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); align-items: flex-end; }
        .input-group { flex: 1; }
        .input-group label { display: block; font-size: 11px; font-weight: 800; color: var(--text-dim); margin-bottom: 5px; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; text-align: center; font-weight: 700; }
        .btn-analisar { background: var(--primary); color: #fff; border: none; padding: 13px 25px; border-radius: 8px; font-weight: 800; cursor: pointer; height: 48px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; display: none; }
        .stat-card { background: #fff; padding: 15px; border-radius: 12px; border: 1px solid var(--border); }
        .stat-card h4 { font-size: 12px; margin-bottom: 10px; color: var(--text-dim); border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .data-row { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; font-weight: 600; }
        .data-row b { color: var(--primary); }
        .best-entries { background: linear-gradient(135deg, #2ecc71, #27ae60); padding: 20px; border-radius: 15px; color: #fff; margin-bottom: 20px; display: none; }
        .entry-row { display: flex; justify-content: space-between; background: rgba(255,255,255,0.2); padding: 10px; border-radius: 8px; margin-top: 10px; font-weight: 700; }
        #loader { display: none; flex-direction: column; align-items: center; padding: 40px; }
        .spinner { width: 40px; height: 40px; border: 4px solid #ddd; border-top-color: var(--primary); border-radius: 50%; animation: spin 1s infinite linear; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 768px) { .input-card { flex-direction: column; } .input-group, .btn-analisar { width: 100%; } }
    </style>
</head>
<body>

<header>
    <div class="logo">SEFULL<span>BET</span> PRO</div>
    <div style="text-align: right; font-size: 12px;">
        <b><?= $perfil ?></b><br>
        <span id="header-saldo">Créditos: <?= (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos'] ?></span>
    </div>
</header>

<main>
    <div class="input-card">
        <div class="input-group"><label>ODD CASA</label><input type="text" id="o-casa" placeholder="1.80"></div>
        <div class="input-group"><label>ODD EMPATE</label><input type="text" id="o-empate" placeholder="3.40"></div>
        <div class="input-group"><label>ODD FORA</label><input type="text" id="o-fora" placeholder="4.20"></div>
        <button class="btn-analisar" onclick="processarIA()">ANALISAR</button>
    </div>

    <div id="loader">
        <div class="spinner"></div>
        <p style="margin-top: 10px; font-weight: 700;">IA CRUZANDO DADOS...</p>
    </div>

    <div id="best-entries" class="best-entries">
        <h3 style="font-size: 14px;"><i class="fas fa-star"></i> MELHORES ENTRADAS</h3>
        <div id="top-list"></div>
    </div>

    <div id="stats-grid" class="stats-grid">
        <div class="stat-card"><h4>RESULTADOS</h4><div id="col-res"></div></div>
        <div class="stat-card"><h4>GOLS OVER</h4><div id="col-over"></div></div>
        <div class="stat-card"><h4>GOLS UNDER</h4><div id="col-under"></div></div>
        <div class="stat-card"><h4>OUTROS</h4><div id="col-outros"></div></div>
    </div>
</main>

<script>
// Log para conferir se os dados chegaram
const DB = <?php echo json_encode($dados_historicos); ?>;
console.log("Base carregada:", DB.length, "registros");

function limparNumero(v) {
    if(!v) return 0;
    return parseFloat(v.toString().replace(',', '.'));
}

async function processarIA() {
    const oc = limparNumero(document.getElementById('o-casa').value);
    const oe = limparNumero(document.getElementById('o-empate').value);
    const of = limparNumero(document.getElementById('o-fora').value);

    if(!oc || !oe || !of) return alert("Preencha as odds!");

    document.getElementById('loader').style.display = 'flex';
    document.getElementById('stats-grid').style.display = 'none';
    document.getElementById('best-entries').style.display = 'none';

    // Simulação de delay para IA
    setTimeout(async () => {
        const similares = DB.map(j => {
            const dCasa = Math.pow(limparNumero(j.odd_casa) - oc, 2);
            const dEmpa = Math.pow(limparNumero(j.odd_empate) - oe, 2);
            const dFora = Math.pow(limparNumero(j.odd_fora) - of, 2);
            const dist = Math.sqrt(dCasa + dEmpa + dFora);
            const peso = 1 / (dist + 0.005);
            return {...j, dist, peso};
        })
        .filter(j => j.dist <= 0.15) // Filtro um pouco mais aberto para garantir resultados
        .sort((a,b) => a.dist - b.dist)
        .slice(0, 50);

        if(similares.length === 0) {
            document.getElementById('loader').style.display = 'none';
            return alert("Nenhum jogo similar encontrado na base para estas odds.");
        }

        const debito = await debitar();
        if(debito.status === 'erro') {
            document.getElementById('loader').style.display = 'none';
            return alert("Saldo insuficiente.");
        }

        renderizar(similares);
        document.getElementById('loader').style.display = 'none';
        document.getElementById('stats-grid').style.display = 'grid';
        document.getElementById('best-entries').style.display = 'block';
    }, 1000);
}

function renderizar(dados) {
    const totalPeso = dados.reduce((a, b) => a + b.peso, 0);
    
    const getProb = (campo, valor) => {
        const pesoOcorrido = dados.filter(j => j[campo] === valor).reduce((a, b) => a + b.peso, 0);
        return ((pesoOcorrido / totalPeso) * 100);
    };

    const pcasa = getProb('resultado', 'Casa');
    const pempa = getProb('resultado', 'Empate');
    const pfora = getProb('resultado', 'Fora');
    const pambos = getProb('ambos_marcam', 'Sim');

    const row = (l, v) => `<div class="data-row"><span>${l}</span><b>${v.toFixed(1)}%</b></div>`;

    document.getElementById('col-res').innerHTML = row('Casa', pcasa) + row('Empate', pempa) + row('Fora', pfora);
    document.getElementById('col-over').innerHTML = row('Over 0.5', getProb('over_05', 'Sim')) + row('Over 1.5', getProb('over_15', 'Sim')) + row('Over 2.5', getProb('over_25', 'Sim'));
    document.getElementById('col-under').innerHTML = row('Under 2.5', 100 - getProb('over_25', 'Sim')) + row('Under 3.5', 100 - getProb('over_35', 'Sim'));
    document.getElementById('col-outros').innerHTML = row('Ambos Marcam', pambos) + `<div class="data-row"><span>Amostra</span><b>${dados.length} jogos</b></div>`;

    // TOP 3
    const mercados = [
        {n: 'Vitória Casa', v: pcasa}, {n: 'Ambos Marcam', v: pambos}, 
        {n: 'Over 1.5 Gols', v: getProb('over_15', 'Sim')}, {n: 'Casa ou Empate', v: pcasa + pempa}
    ].sort((a,b) => b.v - a.v).slice(0,3);

    document.getElementById('top-list').innerHTML = mercados.map(m => `
        <div class="entry-row"><span>${m.n}</span><span>${m.v.toFixed(1)}%</span></div>
    `).join('');
}

async function debitar() {
    try {
        const res = await fetch('analisador.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=debitar'
        });
        return await res.json();
    } catch (e) { return {status: 'erro'}; }
}
</script>
</body>
</html>
