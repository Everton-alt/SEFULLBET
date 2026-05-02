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

// 3. Lógica de Débito de Créditos (Movida para cima para processar o fetch antes do HTML)
if (isset($_POST['action']) && $_POST['action'] == 'debitar') {
    header('Content-Type: application/json');
    $is_premium = in_array($perfil, ['Admin', 'Platinum', 'Supervisor']);
    
    if (!$is_premium) {
        if ($user['saldo_creditos'] <= 0) {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Saldo insuficiente']);
            exit();
        }
        $pdo->prepare("UPDATE usuarios SET saldo_creditos = saldo_creditos - 1 WHERE id = ? AND saldo_creditos > 0")->execute([$_SESSION['usuario_id']]);
        
        // Busca o saldo atualizado
        $stmt_s = $pdo->prepare("SELECT saldo_creditos FROM usuarios WHERE id = ?");
        $stmt_s->execute([$_SESSION['usuario_id']]);
        $novo_saldo = $stmt_s->fetchColumn();
        echo json_encode(['status' => 'sucesso', 'novo_saldo' => $novo_saldo]);
    } else {
        echo json_encode(['status' => 'isento', 'novo_saldo' => '∞']);
    }
    exit();
}

/**
 * 2. BUSCA DA BASE COMPLETA
 */
try {
    $sql = "SELECT 
                odd_casa, odd_empate, odd_fora, 
                resultado, ambos_marcam, gols_total,
                over_05, over_15, over_25, over_35, over_45 
            FROM base_analisador"; 
    $stmt_data = $pdo->query($sql);
    $dados_historicos = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dados_historicos = [];
    $erro_db = $e->getMessage();
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
        :root {
            --primary: #00ff88;
            --primary-glow: rgba(0, 255, 136, 0.3);
            --bg: #0d1117;
            --card: #161b22;
            --border: #30363d;
            --text-main: #f0f6fc;
            --text-dim: #8b949e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text-main); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* Barra de navegação Mobile (oculta no PC) */
        .mobile-header {
            display: none;
            background: rgba(22, 27, 34, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 2000;
        }
        .mobile-header .logo { font-weight: 800; font-size: 1.4rem; letter-spacing: -1px; }
        .mobile-header .logo span { color: var(--primary); }
        .menu-toggle { background: none; border: none; color: var(--text-main); font-size: 1.5rem; cursor: pointer; }

        nav { 
            width: 280px; background: rgba(22, 27, 34, 0.95); backdrop-filter: blur(10px);
            border-right: 1px solid var(--border); padding: 30px 15px;
            display: flex; flex-direction: column; position: fixed; height: 100vh;
            z-index: 3000; transition: transform 0.3s ease;
            overflow-y: auto;
        }
        .nav-logo { font-weight: 800; font-size: 1.6rem; letter-spacing: -1px; margin-bottom: 30px; text-align: center; }
        .nav-logo span { color: var(--primary); }
        .nav-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-left: 15px; margin-bottom: 8px; display: block; font-weight: 700; }
        .nav-btn { color: var(--text-dim); padding: 12px 18px; border-radius: 12px; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 500; transition: 0.3s; margin-bottom: 2px; }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .nav-btn.active { background: var(--primary-glow); color: var(--primary); border: 1px solid rgba(0, 255, 136, 0.2); }

        /* Botão fechar menu no mobile */
        .close-menu { display: none; background: none; border: none; color: var(--text-dim); font-size: 1.5rem; cursor: pointer; position: absolute; top: 20px; right: 20px; }

        main { flex: 1; margin-left: 280px; padding: 40px 60px; max-width: 1400px; width: 100%; }

        .header-info { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
        .user-status-card { background: var(--card); padding: 15px 25px; border-radius: 18px; border: 1px solid var(--border); display: flex; gap: 20px; align-items: center; }

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
        .data-row b { color: var(--primary); }

        #loader { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(13,17,23,0.98); z-index:4000; flex-direction:column; justify-content:center; align-items:center; text-align: center; padding: 20px; }
        .spinner { width: 60px; height: 60px; border: 5px solid #161b22; border-top-color: var(--primary); border-radius: 50%; animation: spin 1s infinite linear; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Overlay Escuro para o menu Mobile */
        .menu-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6); z-index: 2500; }

        /* ================= RESPONSIVO MOBILE ================= */
        @media (max-width: 1024px) {
            .stats-grid-5 { grid-template-columns: repeat(2, 1fr); }
            nav { width: 240px; }
            main { margin-left: 240px; padding: 30px; }
        }

        @media (max-width: 768px) {
            .mobile-header { display: flex; }
            
            nav { transform: translateX(-100%); }
            nav.open { transform: translateX(0); }
            .nav-logo { margin-top: 10px; }
            .close-menu { display: block; }
            .menu-overlay.open { display: block; }

            main { margin-left: 0; padding: 90px 20px 30px 20px; }
            
            .header-info { flex-direction: column; align-items: flex-start; gap: 20px; margin-bottom: 30px; }
            .user-status-card { width: 100%; justify-content: space-between; padding: 15px; }
            
            .input-card { flex-direction: column; align-items: stretch; gap: 15px; padding: 20px; }
            .btn-analisar { width: 100%; }

            .stats-grid-5 { grid-template-columns: 1fr; }
            
            .best-entries-container { padding: 15px; }
            .entry-row { padding: 12px 15px; font-size: 14px; }
            .entry-perc { padding: 5px 10px; min-width: 60px; }
        }
    </style>
</head>
<body>

<div id="loader">
    <div class="spinner"></div>
    <p style="color: var(--primary); margin-top:20px; font-weight:800; letter-spacing:2px; text-transform:uppercase;">SEFULLBET<br>Analisando suas ODDS aguarde</p>
</div>

<!-- Header Mobile -->
<div class="mobile-header">
    <div class="logo">SEFULL<span>BET</span></div>
    <button class="menu-toggle" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
</div>

<!-- Overlay do Menu Mobile -->
<div class="menu-overlay" onclick="toggleMenu()"></div>

<nav id="menu-lateral">
    <button class="close-menu" onclick="toggleMenu()"><i class="fas fa-times"></i></button>
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
    <div class="header-info">
        <div>
            <h1 style="color: var(--text-dim); font-weight: 400; font-size: 1.1rem;">Bem-vindo ao Analisador,</h1>
            <h1 style="font-size: 1.8rem; font-weight: 800;"><?php echo explode(' ', $user['nome'])[0]; ?> 🚀</h1>
        </div>
        <div class="user-status-card">
            <div style="text-align: center; border-right: 1px solid var(--border); padding-right: 20px;">
                <span style="font-size: 9px; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Créditos</span>
                <div id="saldo-display" style="font-size: 18px; font-weight: 800; color: var(--primary);"><?php echo (in_array($perfil, ['Admin', 'Supervisor', 'Platinum'])) ? '∞' : $user['saldo_creditos']; ?></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 9px; color: var(--text-dim); font-weight: 700;">STATUS</div>
                <div style="color: <?php echo $cor_perfil; ?>; font-weight: 800; font-size: 13px;"><?php echo strtoupper($perfil); ?></div>
            </div>
        </div>
    </div>

    <div class="input-card">
        <div class="input-group"><label>Odd Casa</label><input type="number" step="0.01" id="o-casa" placeholder="1.85"></div>
        <div class="input-group"><label>Odd Empate</label><input type="number" step="0.01" id="o-empate" placeholder="3.40"></div>
        <div class="input-group"><label>Odd Fora</label><input type="number" step="0.01" id="o-fora" placeholder="4.50"></div>
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
const DB = <?php echo json_encode($dados_historicos); ?>;

// Função para abrir/fechar o menu mobile
function toggleMenu() {
    document.getElementById('menu-lateral').classList.toggle('open');
    document.querySelector('.menu-overlay').classList.toggle('open');
}

function limparNumero(val) {
    if (val === null || val === undefined || val === '') return 0;
    return parseFloat(val.toString().replace(',', '.'));
}

async function processarIA() {
    const oc = limparNumero(document.getElementById('o-casa').value);
    const oe = limparNumero(document.getElementById('o-empate').value);
    const of = limparNumero(document.getElementById('o-fora').value);

    if(!oc || !oe || !of) return alert("Por favor, insira as odds para iniciar a análise.");

    document.getElementById('loader').style.display = 'flex';

    // 1. Tenta debitar e aguarda resposta
    const resDebito = await debitar();

    if(resDebito.status === 'erro') {
        document.getElementById('loader').style.display = 'none';
        alert("Ops! Seus créditos acabaram. Por favor, RENOVE SEU VIP OU PLATINUM para continuar usando o analisador.");
        return;
    }

    // Atualiza saldo na tela se houver retorno
    if(resDebito.novo_saldo !== undefined) {
        document.getElementById('saldo-display').innerText = resDebito.novo_saldo;
    }
    
    setTimeout(() => {
        const similares = DB.map(j => {
            const ocDB = limparNumero(j.odd_casa);
            const oeDB = limparNumero(j.odd_empate);
            const ofDB = limparNumero(j.odd_fora);

            const dist = Math.sqrt(
                Math.pow(ocDB - oc, 2) + 
                Math.pow(oeDB - oe, 2) + 
                Math.pow(ofDB - of, 2)
            );
            
            const peso = 1 / (dist + 0.001);

            return {...j, dist, peso};
        })
        .filter(j => j.dist <= 0.1) 
        .sort((a,b) => a.dist - b.dist)
        .slice(0, 50);

        if (similares.length === 0) {
            document.getElementById('loader').style.display = 'none';
            return alert("SEFULLBET: Recomendamos a seleção de um confronto alternativo (Nenhum padrão similar encontrado).");
        }

        renderizar(similares);
        
        document.getElementById('loader').style.display = 'none';
        document.getElementById('resultado-display').style.display = 'block';
    }, 1500);
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
