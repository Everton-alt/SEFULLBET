<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

$perfil = $user['perfil']; 

// Busca os dados da tabela BASE_HISTORICA (Limitado a 5000 para performance)
$stmt_data = $pdo->query("SELECT * FROM base_historica ORDER BY id DESC LIMIT 5000");
$dados_historicos = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

// Lógica de Débito de Créditos
if (isset($_POST['action']) && $_POST['action'] == 'debitar') {
    if (!in_array($perfil, ['Admin', 'Platinum', 'Supervisor'])) {
        $pdo->prepare("UPDATE usuarios SET saldo_creditos = saldo_creditos - 1 WHERE id = ? AND saldo_creditos > 0")->execute([$_SESSION['usuario_id']]);
    }
    exit();
}

$cores = [
    'Grátis' => '#8b949e', 'VIP' => '#ffd700', 'Platinum' => '#ffffff',
    'Supervisor' => '#00e5ff', 'Admin' => '#00ff88'
];
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
        :root {
            --primary: #00ff88;
            --primary-glow: rgba(0, 255, 136, 0.3);
            --bg: #0d1117;
            --card: #161b22;
            --border: #30363d;
            --text-main: #f0f6fc;
            --text-dim: #8b949e;
            --vip: #ffd700;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text-main); display: flex; min-height: 100vh; }

        nav { 
            width: 280px; background: rgba(22, 27, 34, 0.8); backdrop-filter: blur(10px);
            border-right: 1px solid var(--border); padding: 30px 15px;
            display: flex; flex-direction: column; position: fixed; height: 100vh;
        }
        .nav-logo { font-weight: 800; font-size: 1.6rem; letter-spacing: -1px; margin-bottom: 30px; text-align: center; }
        .nav-logo span { color: var(--primary); }
        .nav-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-left: 15px; margin-bottom: 8px; display: block; font-weight: 700; }
        .nav-btn { color: var(--text-dim); padding: 12px 18px; border-radius: 12px; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 500; transition: 0.3s; margin-bottom: 2px; }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .nav-btn.active { background: var(--primary-glow); color: var(--primary); border: 1px solid rgba(0, 255, 136, 0.2); }

        main { flex: 1; margin-left: 280px; padding: 40px 60px; max-width: 1400px; }

        .input-card { background: var(--card); padding: 25px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 30px; display: flex; gap: 20px; align-items: flex-end; }
        .input-group { flex: 1; }
        .input-group label { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 8px; font-weight: 700; }
        .input-group input { width: 100%; padding: 15px; background: #0d1117; border: 1px solid var(--border); border-radius: 10px; color: var(--primary); font-weight: 800; outline: none; text-align: center; font-size: 1.1rem; }
        .btn-analisar { padding: 0 40px; background: var(--primary); color: #0d1117; border: none; border-radius: 10px; font-weight: 800; cursor: pointer; text-transform: uppercase; height: 55px; transition: 0.3s; }
        .btn-analisar:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 5px 15px var(--primary-glow); }

        .best-entries-container { border: 2px solid var(--primary); border-radius: 20px; padding: 25px; margin-bottom: 30px; background: linear-gradient(135deg, rgba(0, 255, 136, 0.05) 0%, transparent 100%); }
        .best-entries-title { color: var(--primary); font-weight: 900; font-size: 1.1rem; margin-bottom: 20px; text-transform: uppercase; display: flex; align-items: center; gap: 10px; }
        .entry-row { background: rgba(255,255,255,0.03); border-radius: 50px; padding: 12px 25px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(255,255,255,0.05); }
        .entry-perc { background: var(--primary); color: #000; font-weight: 900; padding: 5px 15px; border-radius: 20px; min-width: 80px; text-align: center; }

        .stats-grid-5 { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-top: 30px; }
        .stat-col { background: var(--card); border-radius: 15px; border: 1px solid var(--border); padding: 20px; }
        .stat-col h4 { font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 10px; letter-spacing: 1px; }
        .data-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13px; }
        .data-row span { color: var(--text-dim); }
        .data-row b { color: var(--primary); }

        #loader { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(13,17,23,0.98); z-index:1000; flex-direction:column; justify-content:center; align-items:center; }
        .spinner { width: 60px; height: 60px; border: 5px solid #161b22; border-top-color: var(--primary); border-radius: 50%; animation: spin 1s infinite linear; }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 1200px) {
            nav { width: 80px; }
            .nav-label, .nav-btn span, .nav-logo { display: none; }
            main { margin-left: 80px; padding: 20px; }
            .stats-grid-5 { grid-template-columns: repeat(2, 1fr); }
            .input-card { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>

<div id="loader">
    <div class="spinner"></div>
    <p style="color: var(--primary); margin-top:20px; font-weight:800; letter-spacing:2px; text-transform:uppercase;">IA Analisando base_historica...</p>
</div>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    <div class="nav-group">
        <span class="nav-label">Menu Principal</span>
        <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
        <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
        <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
        <a class="nav-btn active" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>

        <?php if (in_array($perfil, ['Supervisor', 'Admin'])): ?>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 15px 10px;">
            <span class="nav-label">Gestão Administrativa</span>
            <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
            <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
            <a class="nav-btn" href="base_dados_ai.php"><i class="fas fa-database"></i> <span>Dados Importados</span></a>
            <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
        <?php endif; ?>
    </div>
    <a class="nav-btn" style="margin-top:auto; color: #ff4d4d" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
        <div>
            <h1 style="color: var(--text-dim); font-weight: 400; font-size: 1.1rem;">Bem-vindo ao Analisador,</h1>
            <h1 style="font-size: 1.8rem; font-weight: 800;"><?php echo explode(' ', $user['nome'])[0]; ?> 🚀</h1>
        </div>
        <div style="background: var(--card); padding: 15px 25px; border-radius: 18px; border: 1px solid var(--border); display: flex; gap: 20px; align-items: center;">
            <div style="text-align: center; border-right: 1px solid var(--border); padding-right: 20px;">
                <span style="font-size: 9px; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Créditos</span>
                <div style="font-size: 18px; font-weight: 800; color: var(--primary);"><?php echo (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos']; ?></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 9px; color: var(--text-dim); font-weight: 700;">STATUS</div>
                <div style="color: <?php echo $cor_perfil; ?>; font-weight: 800; font-size: 13px;"><?php echo strtoupper($perfil); ?></div>
            </div>
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
            <div class="best-entries-title"><i class="fas fa-fire"></i> Melhores Oportunidades Encontradas</div>
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
const DB = <?= json_encode($dados_historicos) ?>;

// Função universal para tratar números (aceita ponto ou vírgula)
function limparNumero(val) {
    if (val === null || val === undefined || val === '') return 0;
    return parseFloat(val.toString().replace(',', '.'));
}

function processarIA() {
    const oc = limparNumero(document.getElementById('o-casa').value);
    const oe = limparNumero(document.getElementById('o-empate').value);
    const of = limparNumero(document.getElementById('o-fora').value);

    if(!oc || !oe || !of) return alert("Por favor, insira as odds para iniciar a análise.");

    document.getElementById('loader').style.display = 'flex';
    
    setTimeout(() => {
        // Encontra os jogos mais próximos usando distância euclidiana
        const similares = DB.map(j => {
            const ocDB = limparNumero(j.odd_casa);
            const oeDB = limparNumero(j.odd_empate);
            const ofDB = limparNumero(j.odd_fora);

            const diff = Math.sqrt(
                Math.pow(ocDB - oc, 2) + 
                Math.pow(oeDB - oe, 2) + 
                Math.pow(ofDB - of, 2)
            );
            return {...j, diff};
        }).sort((a,b) => a.diff - b.diff).slice(0, 40);

        renderizar(similares);
        debitar();
        
        document.getElementById('loader').style.display = 'none';
        document.getElementById('resultado-display').style.display = 'block';
    }, 1800);
}

function renderizar(dados) {
    const total = dados.length;
    
    // Funções de porcentagem com verificação de string
    const pRES = (v) => (dados.filter(j => j.resultado === v).length / total * 100).toFixed(1);
    const pAMB = (v) => (dados.filter(j => j.ambos_marcam === v).length / total * 100).toFixed(1);
    const pOVR = (c, v) => (dados.filter(j => j[c] === v).length / total * 100).toFixed(1);

    const probCasa = parseFloat(pRES('Casa'));
    const probEmpa = parseFloat(pRES('Empate')); 
    const probFora = parseFloat(pRES('Fora'));
    
    const prob1X = (probCasa + probEmpa).toFixed(1);
    const prob12 = (probCasa + probFora).toFixed(1);
    const probX2 = (probFora + probEmpa).toFixed(1);
    
    const probO45 = parseFloat(pOVR('over_45', 'Sim'));
    const probU45 = (100 - probO45).toFixed(1);

    // Renderização das Colunas
    document.getElementById('col-principal').innerHTML = `
        <div class="data-row"><span>Vitória Casa</span><b>${probCasa}%</b></div>
        <div class="data-row"><span>Empate</span><b>${probEmpa}%</b></div>
        <div class="data-row"><span>Vitória Fora</span><b>${probFora}%</b></div>
        <div class="data-row"><span>Ambos Sim</span><b>${pAMB('Sim')}%</b></div>
    `;

    document.getElementById('col-dupla').innerHTML = `
        <div class="data-row"><span>Casa ou Empate (1X)</span><b>${prob1X}%</b></div>
        <div class="data-row"><span>Casa ou Fora (12)</span><b>${prob12}%</b></div>
        <div class="data-row"><span>Fora ou Empate (X2)</span><b>${probX2}%</b></div>
    `;

    document.getElementById('col-over').innerHTML = `
        <div class="data-row"><span>+0.5 Gols</span><b>${pOVR('over_05','Sim')}%</b></div>
        <div class="data-row"><span>+1.5 Gols</span><b>${pOVR('over_15','Sim')}%</b></div>
        <div class="data-row"><span>+2.5 Gols</span><b>${pOVR('over_25','Sim')}%</b></div>
        <div class="data-row"><span>+3.5 Gols</span><b>${pOVR('over_35','Sim')}%</b></div>
        <div class="data-row"><span>+4.5 Gols</span><b>${probO45}%</b></div>
    `;

    document.getElementById('col-under').innerHTML = `
        <div class="data-row"><span>-0.5 Gols</span><b>${(100 - parseFloat(pOVR('over_05','Sim'))).toFixed(1)}%</b></div>
        <div class="data-row"><span>-1.5 Gols</span><b>${(100 - parseFloat(pOVR('over_15','Sim'))).toFixed(1)}%</b></div>
        <div class="data-row"><span>-2.5 Gols</span><b>${(100 - parseFloat(pOVR('over_25','Sim'))).toFixed(1)}%</b></div>
        <div class="data-row"><span>-3.5 Gols</span><b>${(100 - parseFloat(pOVR('over_35','Sim'))).toFixed(1)}%</b></div>
        <div class="data-row"><span>-4.5 Gols</span><b>${probU45}%</b></div>
    `;

    const somaGols = dados.reduce((acc, j) => acc + limparNumero(j.gols_total || 0), 0);
    const mediaGols = (somaGols / total).toFixed(2);

    document.getElementById('col-medias').innerHTML = `
        <div class="data-row"><span>Gols p/ Jogo</span><b>${mediaGols}</b></div>
        <div class="data-row"><span>Amostra (N)</span><b>${total}</b></div>
        <div class="data-row"><span>Confiança</span><b>${total >= 40 ? 'Alta' : 'Média'}</b></div>
    `;

    // --- Ranking das 3 melhores chances ---
    let ranking = [
        { n: "Vitória Casa", v: probCasa },
        { n: "Vitória Fora", v: probFora },
        { n: "Empate", v: probEmpa },
        { n: "Casa ou Empate (1X)", v: parseFloat(prob1X) },
        { n: "Casa ou Fora (12)", v: parseFloat(prob12) },
        { n: "Empate ou Fora (X2)", v: parseFloat(probX2) },
        { n: "Over 0.5 Gols", v: parseFloat(pOVR('over_05','Sim')) },
        { n: "Over 1.5 Gols", v: parseFloat(pOVR('over_15','Sim')) },
        { n: "Over 2.5 Gols", v: parseFloat(pOVR('over_25','Sim')) },
        { n: "Over 3.5 Gols", v: parseFloat(pOVR('over_35','Sim')) },
        { n: "Over 4.5 Gols", v: probO45 },
        { n: "Under 1.5 Gols", v: (100 - parseFloat(pOVR('over_15','Sim'))) },
        { n: "Under 2.5 Gols", v: (100 - parseFloat(pOVR('over_25','Sim'))) },
        { n: "Ambos Marcam Sim", v: parseFloat(pAMB('Sim')) },
        { n: "Ambos Marcam Não", v: (100 - parseFloat(pAMB('Sim'))) }
    ].sort((a,b) => b.v - a.v).slice(0, 3);

    document.getElementById('top-list').innerHTML = ranking.map((item, i) => `
        <div class="entry-row">
            <span style="font-weight:700;">#${i+1} ${item.n}</span>
            <div class="entry-perc">${item.v.toFixed(1)}%</div>
        </div>
    `).join('');
}

function debitar() {
    fetch('analisador.php', { 
        method: 'POST', 
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
        body: 'action=debitar' 
    });
}
</script>
</body>
</html>
