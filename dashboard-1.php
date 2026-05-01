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

        /* SIDEBAR MODERNA */
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
        }

        .nav-logo { font-weight: 800; font-size: 1.6rem; letter-spacing: -1px; margin-bottom: 50px; text-align: center; }
        .nav-logo span { color: var(--primary); }

        .nav-group { margin-bottom: 30px; }
        .nav-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-left: 15px; margin-bottom: 10px; display: block; }

        .nav-btn { 
            color: var(--text-dim); 
            padding: 14px 18px; 
            border-radius: 12px; 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            font-size: 14px; 
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .nav-btn.active { background: var(--primary-glow); color: var(--primary); border: 1px solid rgba(0, 255, 136, 0.2); }

        /* MAIN CONTENT */
        main { flex: 1; margin-left: 280px; padding: 40px 60px; max-width: 1200px; }

        /* HEADER CARDS */
        .top-bar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
        .welcome h1 { font-size: 24px; font-weight: 800; }
        
        .user-status { 
            background: var(--card); padding: 20px 30px; border-radius: 20px; border: 1px solid var(--border);
            display: flex; align-items: center; gap: 25px;
        }

        .credit-badge { text-align: center; border-right: 1px solid var(--border); padding-right: 25px; }
        .credit-badge span { font-size: 10px; color: var(--text-dim); text-transform: uppercase; font-weight: 700; }
        .credit-badge div { font-size: 22px; font-weight: 800; color: var(--primary); }

        /* PERFORMANCE GRID */
        .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
        .perf-card { 
            background: var(--card); padding: 25px; border-radius: 24px; border: 1px solid var(--border);
            position: relative; overflow: hidden;
        }
        .perf-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; }
        .perf-free::before { background: var(--text-dim); }
        .perf-vip::before { background: var(--vip); }

        .perf-header { display: flex; justify-content: space-between; margin-bottom: 20px; font-weight: 700; font-size: 13px; }
        .stat-row { display: flex; justify-content: space-between; }
        .stat-item { text-align: center; flex: 1; }
        .stat-item small { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 5px; }
        .stat-item b { font-size: 18px; font-weight: 800; }

        /* BOTÃO ANALISADOR DESTAQUE */
        .analisador-cta {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 0, 0, 0) 100%);
            border: 2px solid var(--primary); padding: 30px; border-radius: 20px; margin-bottom: 40px; text-align: center;
        }
        .btn-destaque-ai {
            background: var(--primary); color: #0d1117; padding: 15px 35px; border-radius: 12px;
            text-decoration: none; font-weight: 800; display: inline-flex; align-items: center; gap: 10px;
            transition: 0.3s; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-destaque-ai:hover { transform: scale(1.02); box-shadow: 0 5px 20px var(--primary-glow); }

        /* PALPITES */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .palpite-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 50px; }
        .palpite-item { 
            background: var(--card); padding: 18px 25px; border-radius: 16px; border: 1px solid var(--border);
            display: grid; grid-template-columns: 100px 1.5fr 1fr 100px 120px; align-items: center; position: relative;
        }

        /* VITÓRIAS & NOTAS (ESTILIZADAS) */
        .full-width-section { margin-bottom: 30px; width: 100%; }
        .info-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 30px; }
        
        .victory-item { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .victory-item:last-child { border: none; }
        .v-icon { width: 40px; height: 40px; background: rgba(0, 255, 136, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary); }

        .note-alert { background: rgba(0, 229, 255, 0.05); border-left: 4px solid #00e5ff; padding: 20px; border-radius: 0 15px 15px 0; }

        .tag { font-size: 10px; font-weight: 900; padding: 5px 10px; border-radius: 6px; text-transform: uppercase; }
        .tag-green { background: rgba(0, 255, 136, 0.1); color: var(--primary); }
        .tag-wait { background: rgba(255, 215, 0, 0.1); color: var(--vip); }
        .blur-lock { filter: blur(6px); opacity: 0.3; pointer-events: none; }

        @media (max-width: 1100px) {
            nav { width: 80px; padding: 40px 10px; }
            .nav-label, .nav-btn span, .nav-logo span, .nav-logo { display: none; }
            main { margin-left: 80px; padding: 20px; }
            .palpite-item { grid-template-columns: 1fr 1fr; gap: 15px; }
            .perf-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    <div class="nav-group">
        <span class="nav-label">Menu Principal</span>
        <a class="nav-btn active" href="#"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
        <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Hall da Fama</span></a>
    </div>
    <div class="nav-group">
        <span class="nav-label">Conteúdo</span>
        <a class="nav-btn" href="noticias.php"><i class="fas fa-book-open"></i> <span>Estratégias</span></a>
        <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>
    </div>
    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="welcome">
            <h1 style="color: var(--text-dim); font-weight: 400;">Bem-vindo,</h1>
            <h1><?php echo explode(' ', $user['nome'])[0]; ?> 👋</h1>
        </div>
        <div class="user-status">
            <div class="credit-badge">
                <span>Saldo</span>
                <div><?php echo $is_platinum ? '∞' : $user['saldo_creditos']; ?></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; color: var(--text-dim);">Status</div>
                <div style="color: <?php echo $cor_perfil; ?>; font-weight: 800; font-size: 14px;"><?php echo strtoupper($perfil); ?></div>
            </div>
        </div>
    </div>

    <!-- PERFORMANCE -->
    <div class="perf-grid">
        <div class="perf-card perf-free">
            <div class="perf-header"><span>FREE PERFORMANCE</span> <i class="fas fa-chart-bar"></i></div>
            <div class="stat-row">
                <div class="stat-item"><small>Greens</small><b style="color: var(--primary)">38</b></div>
                <div class="stat-item"><small>Assertividade</small><b>84%</b></div>
            </div>
        </div>
        <div class="perf-card perf-vip">
            <div class="perf-header" style="color: var(--vip)"><span>VIP PERFORMANCE</span> <i class="fas fa-crown"></i></div>
            <div class="stat-row">
                <div class="stat-item"><small>Greens</small><b style="color: var(--primary)">108</b></div>
                <div class="stat-item"><small>Assertividade</small><b style="color: var(--vip)">90%</b></div>
            </div>
        </div>
    </div>

    <!-- 1. ANALISADOR SEFULLBET -->
    <section class="analisador-cta">
        <h2 style="margin-bottom: 10px; font-weight: 900; letter-spacing: -0.5px;">ANALISADOR SEFULLBET AI</h2>
        <p style="color: var(--text-dim); margin-bottom: 20px; font-size: 14px;">Inicie sua análise avançada com processamento de dados em tempo real.</p>
        <a href="analisador.php" class="btn-destaque-ai">
            <i class="fas fa-robot"></i> Abrir Analisador Agora
        </a>
    </section>

    <!-- 2. PALPITES -->
    <div class="section-header">
        <h3 style="font-weight: 800;">🔥 Palpites em Tempo Real</h3>
    </div>
    <div class="palpite-list">
        <div class="palpite-item">
            <div class="tag tag-green">Finalizado</div>
            <div>
                <div style="font-weight: 700; font-size: 14px;">Bayern vs Arsenal</div>
                <div style="font-size: 11px; color: var(--text-dim);">Champions League</div>
            </div>
            <div style="font-size: 13px; font-weight: 600;">Ambas Marcam</div>
            <div style="text-align:center"><b>1.80</b></div>
            <div style="text-align: right;"><span class="tag" style="background: #21262d;">FREE</span></div>
        </div>

        <div class="palpite-item">
            <?php if(!$pode_ver_vip): ?>
            <div style="position:absolute; width:100%; height:100%; display:flex; align-items:center; justify-content:center; z-index:5; background:rgba(0,0,0,0.3); border-radius:16px;">
                <a href="upgrade.php" style="background:var(--vip); color:#000; padding:8px 20px; border-radius:50px; font-weight:900; font-size:11px; text-decoration:none;"><i class="fas fa-lock"></i> LIBERAR ACESSO VIP</a>
            </div>
            <?php endif; ?>
            <div class="tag tag-wait <?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">Analizando</div>
            <div class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">
                <div style="font-weight: 700; font-size: 14px;">Real Madrid vs City</div>
                <div style="font-size: 11px; color: var(--text-dim);">Champions League</div>
            </div>
            <div style="font-size: 13px; font-weight: 600;" class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">Resultado Final</div>
            <div style="text-align:center" class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>"><b>2.45</b></div>
            <div style="text-align: right;"><span class="tag" style="border:1px solid var(--vip); color:var(--vip);">VIP</span></div>
        </div>
    </div>

    <!-- 3. ÚLTIMAS VITÓRIAS -->
    <div class="full-width-section">
        <h3 style="margin-bottom: 20px; font-weight: 800;"><i class="fas fa-trophy" style="color: var(--vip)"></i> Últimas Vitórias</h3>
        <div class="info-card">
            <div class="victory-item">
                <div class="v-icon"><i class="fas fa-check-double"></i></div>
                <div>
                    <div style="font-size: 14px; font-weight: 700;">Green Confirmado: ODD 2.15</div>
                    <div style="font-size: 12px; color: var(--text-dim);">Usuário @felipe_bet obteve lucro no mercado de Gols FT.</div>
                </div>
            </div>
            <div class="victory-item">
                <div class="v-icon"><i class="fas fa-bolt"></i></div>
                <div>
                    <div style="font-size: 14px; font-weight: 700;">Sequência do Analisador: 5/0</div>
                    <div style="font-size: 12px; color: var(--text-dim);">O analisador bateu a meta de assertividade nas últimas 2 horas.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. ÚLTIMAS NOTAS -->
    <div class="full-width-section">
        <h3 style="margin-bottom: 20px; font-weight: 800;"><i class="fas fa-sticky-note" style="color: var(--primary)"></i> Últimas Notas</h3>
        <div class="info-card">
            <div class="note-alert">
                <h4 style="font-size: 14px; margin-bottom: 5px; color: #fff;">Atenção: Mercado de Cantos</h4>
                <p style="font-size: 13px; color: var(--text-dim); line-height: 1.6;">
                    Identificamos uma alta taxa de valor em jogos da Premier League após os 75 minutos. Use o Analisador para filtrar entradas de cantos asiáticos.
                </p>
            </div>
            <div style="margin-top: 20px; font-size: 12px; color: var(--text-dim);">
                <i class="fas fa-info-circle"></i> Siga sempre a gestão de banca recomendada no menu "Minha Banca".
            </div>
        </div>
    </div>

    <footer style="margin-top: 60px; padding: 40px 0; border-top: 1px solid var(--border); color: #444; font-size: 11px; text-align: center;">
        &copy; © 2026 SeFullBet - Inteligência de Dados aplicada ao Esporte. Apostas são para maiores de 18 anos. Jogue com responsabilidade.
    </footer>
</main>

</body>
</html>
