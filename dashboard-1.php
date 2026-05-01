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
$pode_ver_vip = in_array($perfil, ['VIP', 'Platinum', 'Supervisor', 'Admin']);
$is_platinum = ($perfil === 'Platinum');

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
    <title>Dashboard | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
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
            --danger: #ff4d4d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: var(--bg); 
            color: var(--text-main);
            background-image: radial-gradient(circle at 0% 0%, rgba(0, 255, 136, 0.05) 0%, transparent 40%);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        nav { 
            width: 280px; 
            background: rgba(22, 27, 34, 0.8);
            backdrop-filter: blur(10px);
            border-right: 1px solid var(--border);
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .nav-logo { font-weight: 800; font-size: 1.6rem; letter-spacing: -1px; margin-bottom: 50px; text-align: center; }
        .nav-logo span { color: var(--primary); }

        .nav-btn { 
            color: var(--text-dim); padding: 14px 18px; border-radius: 12px; text-decoration: none; 
            display: flex; align-items: center; gap: 15px; font-size: 14px; font-weight: 500;
            transition: 0.3s; margin-bottom: 5px;
        }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .nav-btn.active { background: var(--primary-glow); color: var(--primary); border: 1px solid rgba(0, 255, 136, 0.2); }

        /* MAIN CONTENT */
        main { flex: 1; margin-left: 280px; padding: 40px 60px; max-width: 1200px; }

        /* WELCOME & STATUS */
        .top-bar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
        .user-status { 
            background: var(--card); padding: 15px 25px; border-radius: 20px; border: 1px solid var(--border);
            display: flex; align-items: center; gap: 25px;
        }

        /* PERFORMANCE */
        .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
        .perf-card { background: var(--card); padding: 25px; border-radius: 24px; border: 1px solid var(--border); }

        /* PALPITES */
        .palpite-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 40px; }
        .palpite-item { 
            background: var(--card); padding: 18px 25px; border-radius: 16px; border: 1px solid var(--border);
            display: grid; grid-template-columns: 100px 1.5fr 1fr 100px 100px; align-items: center; position: relative;
        }

        /* DESTAQUE ANALISADOR */
        .analisador-callout {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 229, 255, 0.1) 100%);
            border: 2px solid var(--primary); padding: 40px; border-radius: 30px; text-align: center;
            margin-bottom: 60px; box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .btn-analisador {
            background: var(--primary); color: #000; padding: 18px 40px; border-radius: 15px;
            text-decoration: none; font-weight: 800; font-size: 1.1rem; display: inline-block;
            transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-analisador:hover { transform: scale(1.05); box-shadow: 0 0 30px var(--primary-glow); }

        /* VITÓRIAS */
        .vitorias-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 60px; }
        .v-card { background: var(--card); border-radius: 20px; border: 1px solid var(--border); overflow: hidden; }
        .v-card img { width: 100%; height: 180px; object-fit: cover; }
        .v-content { padding: 20px; }

        /* NOTAS */
        .notas-box { background: #1c2128; border-left: 4px solid var(--primary); padding: 25px; border-radius: 15px; }

        .tag { font-size: 10px; font-weight: 900; padding: 5px 10px; border-radius: 6px; text-transform: uppercase; text-align: center; }
        .tag-green { background: rgba(0, 255, 136, 0.1); color: var(--primary); }
        .tag-wait { background: rgba(255, 215, 0, 0.1); color: var(--vip); }
        .blur-lock { filter: blur(6px); opacity: 0.3; pointer-events: none; }

        @media (max-width: 1024px) {
            nav { width: 80px; }
            .nav-btn span, .nav-logo span, .nav-logo { display: none; }
            main { margin-left: 80px; padding: 20px; }
            .perf-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    <a class="nav-btn active" href="#"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
    <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
    <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
    <a class="nav-btn" href="noticias.php"><i class="fas fa-book-open"></i> <span>Notas</span></a>
    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <div class="top-bar">
        <div class="welcome">
            <h1 style="color: var(--text-dim); font-weight: 400;">Olá,</h1>
            <h1><?php echo $user['nome']; ?> 👋</h1>
        </div>
        <div class="user-status">
            <div style="text-align: center; border-right: 1px solid var(--border); padding-right: 20px;">
                <span style="font-size: 10px; color: var(--text-dim); text-transform: uppercase;">Créditos</span>
                <div style="font-size: 20px; font-weight: 800; color: var(--primary);"><?php echo $is_platinum ? '∞' : $user['saldo_creditos']; ?></div>
            </div>
            <div>
                <span style="font-size: 10px; color: var(--text-dim); text-transform: uppercase;">Nível</span>
                <div style="color: <?php echo $cor_perfil; ?>; font-weight: 800;"><?php echo strtoupper($perfil); ?></div>
            </div>
        </div>
    </div>

    <!-- PERFORMANCE -->
    <div class="perf-grid">
        <div class="perf-card" style="border-left: 4px solid var(--text-dim);">
            <div style="display:flex; justify-content:space-between; margin-bottom:15px; font-size:12px; font-weight:700;">
                <span>PERFORMANCE GRÁTIS</span> <i class="fas fa-chart-line"></i>
            </div>
            <div style="display:flex; justify-content:space-between; text-align:center;">
                <div><small style="display:block; font-size:9px; color:var(--text-dim);">SINAIS</small><b>45</b></div>
                <div><small style="display:block; font-size:9px; color:var(--text-dim);">GREENS</small><b style="color:var(--primary)">38</b></div>
                <div><small style="display:block; font-size:9px; color:var(--text-dim);">WINRATE</small><b>84%</b></div>
            </div>
        </div>
        <div class="perf-card" style="border-left: 4px solid var(--vip);">
            <div style="display:flex; justify-content:space-between; margin-bottom:15px; font-size:12px; font-weight:700; color:var(--vip);">
                <span>PERFORMANCE VIP</span> <i class="fas fa-crown"></i>
            </div>
            <div style="display:flex; justify-content:space-between; text-align:center;">
                <div><small style="display:block; font-size:9px; color:var(--text-dim);">SINAIS</small><b>120</b></div>
                <div><small style="display:block; font-size:9px; color:var(--text-dim);">GREENS</small><b style="color:var(--primary)">108</b></div>
                <div><small style="display:block; font-size:9px; color:var(--text-dim);">WINRATE</small><b style="color:var(--vip)">90%</b></div>
            </div>
        </div>
    </div>

    <!-- PALPITES -->
    <h3 style="margin-bottom: 20px;">🔥 Palpites do Dia</h3>
    <div class="palpite-list">
        <div class="palpite-item">
            <div class="tag tag-green">Green</div>
            <div>
                <div style="font-weight: 700; font-size: 14px;">Bayern vs Arsenal</div>
                <div style="font-size: 11px; color: var(--text-dim);">Over 2.5 Gols</div>
            </div>
            <div style="font-size: 13px; font-weight: 600; text-align:center;">ODD 1.85</div>
            <div style="text-align:center;">3 x 2</div>
            <div style="text-align: right;"><span class="tag" style="background:#21262d;">FREE</span></div>
        </div>

        <div class="palpite-item">
            <?php if(!$pode_ver_vip): ?>
                <div style="position:absolute; width:100%; height:100%; display:flex; align-items:center; justify-content:center; z-index:5; background:rgba(0,0,0,0.2); border-radius:16px;">
                    <a href="upgrade.php" style="background:var(--vip); color:#000; padding:6px 15px; border-radius:50px; font-weight:900; font-size:10px; text-decoration:none;"><i class="fas fa-lock"></i> UPGRADE VIP</a>
                </div>
            <?php endif; ?>
            <div class="tag tag-wait <?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">Pendente</div>
            <div class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">
                <div style="font-weight: 700; font-size: 14px;">Real Madrid vs City</div>
                <div style="font-size: 11px; color: var(--text-dim);">ML Real Madrid</div>
            </div>
            <div style="font-size: 13px; font-weight: 600; text-align:center;" class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">ODD 2.40</div>
            <div style="text-align:center;" class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">- x -</div>
            <div style="text-align: right;"><span class="tag" style="border:1px solid var(--vip); color:var(--vip);">VIP</span></div>
        </div>
    </div>

    <!-- BOTÃO ANALISADOR (DESTAQUE) -->
    <section class="analisador-callout">
        <h2 style="font-size: 2rem; font-weight: 900; margin-bottom: 10px;">PRONTO PARA OPERAR?</h2>
        <p style="color: var(--text-dim); margin-bottom: 30px;">Acesse agora a inteligência matemática mais avançada do mercado.</p>
        <a href="analisador.php" class="btn-analisador">
            <i class="fas fa-robot"></i> Abrir Analisador SefullBet
        </a>
    </section>

    <!-- VITÓRIAS -->
    <h3 style="margin-bottom: 25px;"><i class="fas fa-trophy" style="color: var(--vip);"></i> Vitórias da Comunidade</h3>
    <div class="vitorias-grid">
        <div class="v-card">
            <img src="https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?auto=format&fit=crop&w=600&q=80">
            <div class="v-content">
                <h4 style="font-size: 15px;">Alavancagem Odd 5.0</h4>
                <p style="font-size: 12px; color: var(--text-dim); margin-top: 5px;">Usuário VIP transformou R$ 50 em R$ 250 seguindo a gestão...</p>
            </div>
        </div>
        <div class="v-card">
            <img src="https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=600&q=80">
            <div class="v-content">
                <h4 style="font-size: 15px;">Sequência de 12 Greens</h4>
                <p style="font-size: 12px; color: var(--text-dim); margin-top: 5px;">O robô de inteligência artificial bateu recorde de assertividade no...</p>
            </div>
        </div>
    </div>

    <!-- NOTAS -->
    <h3 style="margin-bottom: 20px;"><i class="fas fa-lightbulb" style="color: var(--primary);"></i> Notas e Estratégias</h3>
    <div class="notas-box">
        <h4 style="font-size: 14px; margin-bottom: 10px;">Estratégia de Gestão: Ciclo de 3 Pastas</h4>
        <p style="font-size: 13px; color: var(--text-dim); line-height: 1.6;">
            Ao utilizar o analisador, recomendamos dividir sua stake em 3 partes. A primeira entrada deve ser conservadora (1%), buscando garantir o green inicial. Se a IA indicar valor acima de 85%...
        </p>
    </div>

    <footer style="margin-top: 80px; padding: 40px 0; border-top: 1px solid var(--border); text-align: center; color: #444; font-size: 11px;">
        &copy; 2026 SeFull Bet AI - Inteligência Aplicada ao Esporte.
    </footer>
</main>

</body>
</html>
