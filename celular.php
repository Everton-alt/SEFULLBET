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

// 2. Lógica de Débito de Créditos via AJAX
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

// 3. Busca da base histórica para a IA
try {
    $sql = "SELECT odd_casa, odd_empate, odd_fora, resultado, ambos_marcam, gols_total, over_05, over_15, over_25, over_35, over_45 FROM base_analisador"; 
    $dados_historicos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dados_historicos = [];
}

$cores = ['Grátis' => '#8b949e', 'VIP' => '#ffd700', 'Platinum' => '#ffffff', 'Supervisor' => '#00e5ff', 'Admin' => '#00ff88'];
$cor_perfil = $cores[$perfil] ?? $cores['Grátis'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SefullBet AI | Mobile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00ff88;
            --bg: #0d1117;
            --card: #161b22;
            --border: #30363d;
            --text: #f0f6fc;
            --text-dim: #8b949e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        body { background: var(--bg); color: var(--text); padding-bottom: 90px; overflow-x: hidden; }

        /* Header Estilo App */
        .header { padding: 25px 20px; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(to bottom, #1c2128, var(--bg)); }
        .user-badge { background: var(--card); border: 1px solid var(--border); padding: 6px 14px; border-radius: 30px; display: flex; align-items: center; gap: 8px; font-weight: 600; }

        /* Grade de Odds Mobile */
        .odds-container { padding: 0 20px; margin-top: -10px; }
        .odds-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .odd-input-group { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 12px; text-align: center; transition: 0.3s; }
        .odd-input-group:focus-within { border-color: var(--primary); box-shadow: 0 0 10px rgba(0, 255, 136, 0.2); }
        .odd-input-group label { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 1px; }
        .odd-input-group input { width: 100%; background: transparent; border: none; color: var(--primary); font-weight: 800; font-size: 20px; text-align: center; outline: none; }

        /* Botão de Ação */
        .btn-analyze { width: 100%; height: 60px; background: var(--primary); color: #000; border: none; border-radius: 16px; font-weight: 800; font-size: 16px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0, 255, 136, 0.3); }

        /* Layout de Resultados Clean */
        .section-title { font-size: 11px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px; padding-left: 5px; }
        .card-result { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 20px; margin-bottom: 20px; }
        .data-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .data-item:last-child { border-bottom: none; }
        .data-label { color: var(--text-dim); font-size: 14px; }
        .data-value { color: var(--primary); font-weight: 800; font-size: 16px; }

        /* Barra de Navegação Inferior (Tab Bar) */
        .tab-bar { position: fixed; bottom: 0; width: 100%; height: 75px; background: rgba(22, 27, 34, 0.98); backdrop-filter: blur(10px); border-top: 1px solid var(--border); display: flex; justify-content: space-around; align-items: center; z-index: 1000; padding-bottom: 10px; }
        .tab-link { color: var(--text-dim); text-decoration: none; text-align: center; font-size: 10px; font-weight: 600; display: flex; flex-direction: column; gap: 4px; }
        .tab-link i { font-size: 22px; }
        .tab-link.active { color: var(--primary); }

        /* Loader */
        #loader { display: none; position: fixed; inset: 0; background: rgba(13, 17, 23, 0.95); z-index: 2000; flex-direction: column; justify-content: center; align-items: center; }
        .spinner { width: 50px; height: 50px; border: 5px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div id="loader">
    <div class="spinner"></div>
    <p style="color: var(--primary); margin-top: 15px; font-weight: 800; font-size: 12px; letter-spacing: 2px;">PROCESSANDO IA...</p>
</div>

<div class="header">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 800;">Olá, <?= explode(' ', $user['nome'])[0] ?></h1>
        <p style="color: var(--text-dim); font-size: 12px;">Analise as tendências agora</p>
    </div>
    <div class="user-badge" style="color: <?= $cor_perfil ?>;">
        <i class="fas fa-coins"></i>
        <span id="saldo-display"><?= (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos'] ?></span>
    </div>
</div>

<div class="odds-container">
    <div class="odds-grid">
        <div class="odd-input-group"><label>Casa</label><input type="number" step="0.01" id="o-casa" placeholder="1.80" inputmode="decimal"></div>
        <div class="odd-input-group"><label>Empate</label><input type="number" step="0.01" id="o-empate" placeholder="3.40" inputmode="decimal"></div>
        <div class="odd-input-group"><label>Fora</label><input type="number" step="0.01" id="o-fora" placeholder="4.20" inputmode="decimal"></div>
    </div>
    
    <button class="btn-analyze" onclick="processarIA()">Gerar Sinal <i class="fas fa-bolt"></i></button>

    <div id="resultado-display" style="display: none;">
        <h3 class="section-title">Oportunidades IA</h3>
        <div class="card-result" id="top-signals">
            </div>

        <h3 class="section-title">Probabilidades Detalhadas</h3>
        <div class="card-result" id="full-stats">
            </div>
    </div>
</div>

<nav class="tab-bar">
    <a href="dashboard.php" class="tab-link"><i class="fas fa-th-large"></i>Feed</a>
    <a href="analisador.php" class="tab-link active"><i class="fas fa-robot"></i>IA</a>
    <a href="palpites.php" class="tab-link"><i class="fas fa-bullseye"></i>Palpites</a>
    <a href="perfil.php" class="tab-link"><i class="fas fa-user-circle"></i>Conta</a>
</nav>

<script>
const DB = <?= json_encode($dados_historicos) ?>;

async function processarIA() {
    const oc = parseFloat(document.getElementById('o-casa').value);
    const oe = parseFloat(document.getElementById('o-empate').value);
    const of = parseFloat(document.getElementById('o-fora').value);

    if(!oc || !oe || !of) return alert("⚠️ Por favor, preencha todas as Odds!");

    document.getElementById('loader').style.display = 'flex';
    
    // Processamento de débito via PHP
    try {
        const response = await fetch('analisador.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=debitar'
        });
        const res = await response.json();

        if(res.status === 'erro') {
            document.getElementById('loader').style.display = 'none';
            return alert("❌ " + res.mensagem);
        }
        
        if(res.novo_saldo) document.getElementById('saldo-display').innerText = res.novo_saldo;

        // Simulação de processamento da IA
        setTimeout(() => {
            const similares = DB.filter(j => {
                const diff = Math.abs(j.odd_casa - oc) + Math.abs(j.odd_empate - oe) + Math.abs(j.odd_fora - of);
                return diff < 0.25; // Raio de similaridade
            }).slice(0, 40);

            if(similares.length < 5) {
                document.getElementById('loader').style.display = 'none';
                return alert("🔍 Dados insuficientes para este padrão de Odds.");
            }

            renderizarResultados(similares);
            document.getElementById('loader').style.display = 'none';
            document.getElementById('resultado-display').style.display = 'block';
            window.scrollTo({ top: document.getElementById('resultado-display').offsetTop - 20, behavior: 'smooth' });
        }, 1200);

    } catch (e) {
        document.getElementById('loader').style.display = 'none';
        alert("Erro de conexão com o servidor.");
    }
}

function renderizarResultados(dados) {
    const total = dados.length;
    
    // Cálculo de probabilidades básicas
    const pCasa = (dados.filter(j => j.resultado === 'Casa').length / total * 100).toFixed(1);
    const pEmpate = (dados.filter(j => j.resultado === 'Empate').length / total * 100).toFixed(1);
    const pFora = (dados.filter(j => j.resultado === 'Fora').length / total * 100).toFixed(1);
    const pAmbos = (dados.filter(j => j.ambos_marcam === 'Sim').length / total * 100).toFixed(1);
    const pOver25 = (dados.filter(j => j.over_25 === 'Sim').length / total * 100).toFixed(1);

    // Renderiza os sinais principais (Top 3)
    let sinais = [
        { label: 'Vitória Casa', val: pCasa },
        { label: 'Ambos Marcam', val: pAmbos },
        { label: 'Over 2.5 Gols', val: pOver25 },
        { label: 'Vitória Fora', val: pFora }
    ].sort((a,b) => b.val - a.val).slice(0, 2);

    document.getElementById('top-signals').innerHTML = sinais.map(s => `
        <div class="data-item">
            <span class="data-label" style="font-weight: 700; color: var(--text);">${s.label}</span>
            <span class="data-value">${s.val}%</span>
        </div>
    `).join('');

    // Renderiza estatísticas completas
    document.getElementById('full-stats').innerHTML = `
        <div class="data-item"><span class="data-label">Tendência Empate</span><span class="data-value">${pEmpate}%</span></div>
        <div class="data-item"><span class="data-label">Amostra Analisada</span><span class="data-value">${total} jogos</span></div>
        <div class="data-item"><span class="data-label">Grau de Confiança</span><span class="data-value">${total > 20 ? 'ALTO' : 'MÉDIO'}</span></div>
    `;
}
</script>
</body>
</html>
