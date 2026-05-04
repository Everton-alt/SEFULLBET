<?php
session_start();
require_once 'config.php';

// 1. Verificação de Login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Busca dados do usuário e saldo
$user_id = (int)$_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$perfil = $user['perfil']; 

// 3. Lógica de Débito de Créditos (via AJAX)
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

// 4. Busca base de dados para o Analisador
try {
    $stmt_data = $pdo->query("SELECT * FROM base_analisador LIMIT 8000");
    $dados_historicos = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $dados_historicos = []; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefullbet - Analisador AI Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2ECC71; --bg-body: #ffffff; --bg-secondary: #f8f9fa;
            --card-bg: #ffffff; --text-main: #2d3436; --text-dim: #636e72;
            --accent-blue: #0984e3; --border: #f1f1f1; --dark-card: #0b0e11;
        }
        body { font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; background-color: var(--bg-body); color: var(--text-main); padding-bottom: 50px; }
        
        /* Sidebar & Nav */
        .sidebar { height: 100%; width: 280px; position: fixed; z-index: 2000; top: 0; left: -280px; background-color: #2d3436; transition: 0.4s; padding-top: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.1); }
        .sidebar .nav-btn { padding: 12px 25px; text-decoration: none; font-size: 15px; color: #b2bec3; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #3d4648; transition: 0.3s; }
        .sidebar .nav-btn:hover, .sidebar .nav-btn.active { background: #3d4648; color: var(--primary); }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 30px; cursor: pointer; color: var(--primary); }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }
        
        header { background-color: #ffffff; color: var(--primary); padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 100; }
        .menu-icon { font-size: 24px; cursor: pointer; color: #2d3436; }
        .logo { font-weight: 900; font-size: 1.3rem; color: #2d3436; }
        .logo span { color: var(--primary); }

        .admin-content { padding: 15px; max-width: 1200px; margin: auto; }

        /* Analisador Specific CSS */
        .input-card-dark { background: var(--dark-card); border-radius: 15px; padding: 25px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .input-group label { display: block; color: #848d95; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; }
        .input-group input { width: 100%; background: #1c2127; border: 1px solid #2b2f36; padding: 12px; border-radius: 8px; color: var(--primary); font-weight: 800; text-align: center; box-sizing: border-box; }
        .btn-analisar { background: var(--primary); color: #fff; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-analisar:hover { filter: brightness(1.1); }

        /* Results Grid */
        .results-container { display: none; }
        .best-entry-box { background: #eafaf1; border-left: 5px solid var(--primary); padding: 20px; border-radius: 12px; margin-bottom: 25px; }
        .entry-line { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed #badbcc; }
        .badge-prob { background: var(--primary); color: white; padding: 4px 10px; border-radius: 20px; font-weight: 900; font-size: 12px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .stat-card { background: #fff; border: 1px solid var(--border); border-radius: 15px; padding: 18px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .stat-card h4 { margin: 0 0 15px 0; font-size: 11px; color: var(--text-dim); text-transform: uppercase; border-bottom: 2px solid #f8f9fa; padding-bottom: 8px; font-weight: 800; }
        .data-row { display: flex; justify-content: space-between; font-size: 13px; padding: 7px 0; border-bottom: 1px solid #f8f9fa; }
        .data-row b { color: var(--primary); font-weight: 800; }

        #loader { display: none; text-align: center; padding: 50px; }
        .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div id="overlay" class="overlay" onClick="closeNav()"></div>

    <div id="mySidebar" class="sidebar">
        <span class="close-btn" onClick="closeNav()">&times;</span>
        <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Início</span></a>
        <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
        <a class="nav-btn active" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>

        <?php if (in_array($perfil, ['Supervisor', 'Admin'])): ?>
            <hr style="border: 0; border-top: 1px solid #3d4648; margin: 15px 10px;">
            <span class="nav-label" style="color: var(--primary); font-size: 11px; padding: 15px 25px 5px; display: block; font-weight: 800;">Gestão Administrativa</span>
            <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
            <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
            <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
        <?php endif; ?>

        <a href="logout.php" class="nav-btn" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </div>

    <header>
        <div class="menu-icon" onClick="openNav()">☰</div>
        <div class="logo">SEFULL<span>BET</span> AI</div>
        <div style="font-size: 13px; font-weight: 800;">CRÉDITOS: <span id="saldo-display" style="color: var(--primary);"><?= in_array($perfil, ['Admin','Supervisor','Platinum']) ? '∞' : $user['saldo_creditos'] ?></span></div>
    </header>

    <div class="admin-content">
        <div style="padding: 10px 0 20px; font-weight: 800; text-transform: uppercase; font-size: 14px;">Analisador de Probabilidades</div>
        
        <!-- Input de Odds -->
        <div class="input-card-dark">
            <div class="input-group"><label>Odd Casa</label><input type="number" step="0.01" id="o-casa" placeholder="1.80"></div>
            <div class="input-group"><label>Odd Empate</label><input type="number" step="0.01" id="o-empate" placeholder="3.40"></div>
            <div class="input-group"><label>Odd Fora</label><input type="number" step="0.01" id="o-fora" placeholder="4.20"></div>
            <button class="btn-analisar" onclick="iniciarAnalise()"><i class="fas fa-robot"></i> Analisar Jogo</button>
        </div>

        <!-- Loader -->
        <div id="loader">
            <div class="spinner"></div>
            <div style="font-weight: 800; color: var(--primary); font-size: 12px; letter-spacing: 1px;">IA CALCULANDO PROBABILIDADES...</div>
        </div>

        <!-- Resultados -->
        <div id="resultado-display" class="results-container">
            <div class="best-entry-box">
                <div style="font-weight: 900; font-size: 12px; color: #27ae60; margin-bottom: 10px;"><i class="fas fa-star"></i> MELHORES ENTRADAS IDENTIFICADAS</div>
                <div id="top-rank"></div>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><h4>Resultado Final</h4><div id="res-final"></div></div>
                <div class="stat-card"><h4>Gols Over (+)</h4><div id="res-over"></div></div>
                <div class="stat-card"><h4>Gols Under (-)</h4><div id="res-under"></div></div>
                <div class="stat-card"><h4>Métricas da IA</h4><div id="res-metrics"></div></div>
            </div>
        </div>
    </div>

    <script>
        const BASE_DADOS = <?php echo json_encode($dados_historicos); ?>;

        function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
        function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }

        async function iniciarAnalise() {
            const oc = parseFloat(document.getElementById('o-casa').value);
            const oe = parseFloat(document.getElementById('o-empate').value);
            const of = parseFloat(document.getElementById('o-fora').value);

            if(!oc || !oe || !of) { alert("Por favor, insira as 3 odds."); return; }

            document.getElementById('loader').style.display = 'block';
            document.getElementById('resultado-display').style.display = 'none';

            // Processa débito
            const response = await fetch('analisador.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=debitar'
            }).then(r => r.json());

            if(response.status === 'erro') {
                alert("Créditos insuficientes!");
                document.getElementById('loader').style.display = 'none';
                return;
            }
            if(response.novo_saldo) document.getElementById('saldo-display').innerText = response.novo_saldo;

            setTimeout(() => {
                // Cálculo de Similaridade Euclidiana
                const similares = BASE_DADOS.map(jogo => {
                    const d = Math.sqrt(
                        Math.pow(parseFloat(jogo.odd_casa) - oc, 2) +
                        Math.pow(parseFloat(jogo.odd_empate) - oe, 2) +
                        Math.pow(parseFloat(jogo.odd_fora) - of, 2)
                    );
                    return { ...jogo, dist: d, peso: 1 / (d + 0.001) };
                }).filter(j => j.dist <= 0.18).sort((a,b) => a.dist - b.dist).slice(0, 60);

                if(similares.length < 2) {
                    alert("Dados insuficientes para este padrão de odds.");
                    document.getElementById('loader').style.display = 'none';
                    return;
                }

                gerarRelatorio(similares);
                document.getElementById('loader').style.display = 'none';
                document.getElementById('resultado-display').style.display = 'block';
            }, 1200);
        }

        function gerarRelatorio(dados) {
            const somaPesos = dados.reduce((a, b) => a + b.peso, 0);
            const prob = (campo, valor) => (dados.filter(j => j[campo] === valor).reduce((a, b) => a + b.peso, 0) / somaPesos * 100);

            const pCasa = prob('resultado', 'Casa'), pEmp = prob('resultado', 'Empate'), pFora = prob('resultado', 'Fora');
            const pO05 = prob('over_05', 'Sim'), pO15 = prob('over_15', 'Sim'), pO25 = prob('over_25', 'Sim'), pO35 = prob('over_35', 'Sim'), pO45 = prob('over_45', 'Sim');
            const pAMB = prob('ambos_marcam', 'Sim');

            // Renderizar Colunas
            document.getElementById('res-final').innerHTML = `
                <div class="data-row"><span>Vitória Casa</span><b>${pCasa.toFixed(1)}%</b></div>
                <div class="data-row"><span>Empate</span><b>${pEmp.toFixed(1)}%</b></div>
                <div class="data-row"><span>Vitória Fora</span><b>${pFora.toFixed(1)}%</b></div>
                <div class="data-row"><span>Ambos Marcam</span><b>${pAMB.toFixed(1)}%</b></div>
            `;

            document.getElementById('res-over').innerHTML = `
                <div class="data-row"><span>Over 0.5</span><b>${pO05.toFixed(1)}%</b></div>
                <div class="data-row"><span>Over 1.5</span><b>${pO15.toFixed(1)}%</b></div>
                <div class="data-row"><span>Over 2.5</span><b>${pO25.toFixed(1)}%</b></div>
                <div class="data-row"><span>Over 4.5</span><b>${pO45.toFixed(1)}%</b></div>
            `;

            document.getElementById('res-under').innerHTML = `
                <div class="data-row"><span>Under 0.5</span><b>${(100-pO05).toFixed(1)}%</b></div>
                <div class="data-row"><span>Under 2.5</span><b>${(100-pO25).toFixed(1)}%</b></div>
                <div class="data-row"><span>Under 3.5</span><b>${(100-pO35).toFixed(1)}%</b></div>
                <div class="data-row"><span>Under 4.5</span><b>${(100-pO45).toFixed(1)}%</b></div>
            `;

            // Médias baseadas nas colunas gol_casa e gol_fora
            const mCasa = (dados.reduce((a, b) => a + (parseFloat(b.gol_casa)*b.peso), 0) / somaPesos).toFixed(2);
            const mFora = (dados.reduce((a, b) => a + (parseFloat(b.gol_fora)*b.peso), 0) / somaPesos).toFixed(2);

            document.getElementById('res-metrics').innerHTML = `
                <div class="data-row"><span>Média Gols Casa</span><b>${mCasa}</b></div>
                <div class="data-row"><span>Média Gols Fora</span><b>${mFora}</b></div>
                <div class="data-row"><span>Similaridade (N)</span><b>${dados.length}</b></div>
            `;

            // Top Rank
            let entries = [
                {n: "Casa ou Empate", v: pCasa + pEmp},
                {n: "Over 1.5 Gols", v: pO15},
                {n: "Fora ou Empate", v: pFora + pEmp},
                {n: "Ambos Marcam", v: pAMB}
            ].sort((a,b) => b.v - a.v).slice(0, 3);

            document.getElementById('top-rank').innerHTML = entries.map((e, i) => `
                <div class="entry-line">
                    <span><b>#${i+1}</b> ${e.n}</span>
                    <span class="badge-prob">${e.v.toFixed(1)}%</span>
                </div>
            `).join('');
        }
    </script>
</body>
</html>
