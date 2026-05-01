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
        main { flex: 1; margin-left: 280px; padding: 40px 60px; }

        /* HEADER CARDS */
        .top-bar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
        .welcome h1 { font-size: 24px; font-weight: 800; }
        
        .user-status { 
            background: var(--card); 
            padding: 20px 30px; 
            border-radius: 20px; 
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .credit-badge { text-align: center; border-right: 1px solid var(--border); padding-right: 25px; }
        .credit-badge span { font-size: 10px; color: var(--text-dim); text-transform: uppercase; font-weight: 700; }
        .credit-badge div { font-size: 22px; font-weight: 800; color: var(--primary); }

        /* PERFORMANCE GRID */
        .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
        .perf-card { 
            background: var(--card); 
            padding: 25px; 
            border-radius: 24px; 
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        .perf-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
        }
        .perf-free::before { background: var(--text-dim); }
        .perf-vip::before { background: var(--vip); }

        .perf-header { display: flex; justify-content: space-between; margin-bottom: 20px; font-weight: 700; font-size: 13px; }
        
        .stat-row { display: flex; justify-content: space-between; }
        .stat-item { text-align: center; flex: 1; }
        .stat-item small { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 5px; }
        .stat-item b { font-size: 18px; font-weight: 800; }

        /* BOTÃO ANALISADOR DESTAQUE */
        .analisador-cta {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.15) 0%, rgba(0, 0, 0, 0) 100%);
            border: 2px solid var(--primary);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.1);
        }
        .btn-destaque-ai {
            background: var(--primary);
            color: #0d1117;
            padding: 15px 35px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-destaque-ai:hover { transform: translateY(-3px); box-shadow: 0 5px 20px var(--primary-glow); }

        /* PALPITES */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .palpite-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 40px;}
        
        .palpite-item { 
            background: var(--card); 
            padding: 18px 25px; 
            border-radius: 16px; 
            border: 1px solid var(--border);
            display: grid;
            grid-template-columns: 100px 1.5fr 1fr 100px 120px;
            align-items: center;
            transition: 0.2s;
            position: relative;
        }
        .palpite-item:hover { border-color: #444; background: #1c2128; }

        /* VITÓRIAS & NOTAS */
        .grid-info { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-bottom: 40px; }
        .vitorias-box { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 25px; }
        .notas-box { background: rgba(0, 255, 136, 0.03); border: 1px dashed var(--primary); border-radius: 20px; padding: 25px; }

        .tag { font-size: 10px; font-weight: 900; padding: 5px 10px; border-radius: 6px; text-align: center; text-transform: uppercase; }
        .tag-green { background: rgba(0, 255, 136, 0.1); color: var(--primary); }
        .tag-wait { background: rgba(255, 215, 0, 0.1); color: var(--vip); }

        .odd-box { background: rgba(255,255,255,0.03); padding: 8px; border-radius: 8px; text-align: center; }
        .odd-box small { font-size: 9px; color: var(--text-dim); display: block; }

        /* LOCK SYSTEM */
        .blur-lock { filter: blur(6px); opacity: 0.3; pointer-events: none; }
        .lock-overlay { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            display: flex; align-items: center; justify-content: center; 
            z-index: 5; background: rgba(13, 17, 23, 0.4); border-radius: 16px;
        }
        .lock-btn { 
            background: var(--vip); color: #000; padding: 8px 16px; 
            border-radius: 50px; font-weight: 800; font-size: 11px; text-decoration: none;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        /* RESPONSIVO */
        @media (max-width: 1100px) {
            nav { width: 80px; padding: 40px 10px; }
            .nav-label, .nav-btn span, .nav-logo span, .nav-logo { display: none; }
            main { margin-left: 80px; padding: 20px; }
            .palpite-item { grid-template-columns: 1fr 1fr; gap: 15px; }
            .perf-grid, .grid-info { grid-template-columns: 1fr; }
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

    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php">
        <i class="fas fa-power-off"></i> <span>Encerrar Sessão</span>
    </a>
</nav>

<main>
    <div class="top-bar">
        <div class="welcome">
            <h1 style="color: var(--text-dim); font-weight: 400;">Bem-vindo,</h1>
            <h1><?php echo explode(' ', $user['nome'])[0]; ?> 👋</h1>
        </div>

        <div class="user-status">
            <div class="credit-badge">
                <span>Saldo de Créditos</span>
                <div><?php echo $is_platinum ? '∞' : $user['saldo_creditos']; ?></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; color: var(--text-dim); margin-bottom: 5px;">Status da Conta</div>
                <div style="color: <?php echo $cor_perfil; ?>; font-weight: 800; font-size: 14px;">
                    <i class="fas fa-shield-alt"></i> <?php echo strtoupper($perfil); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="perf-grid">
        <div class="perf-card perf-free">
            <div class="perf-header"><span>PERFORMANCE FREE</span> <i class="fas fa-chart-bar"></i></div>
            <div class="stat-row">
                <div class="stat-item"><small>Sinais</small><b>45</b></div>
                <div class="stat-item"><small>Greens</small><b style="color: var(--primary)">38</b></div>
                <div class="stat-item"><small>Assertividade</small><b>84%</b></div>
            </div>
        </div>
        <div class="perf-card perf-vip">
            <div class="perf-header" style="color: var(--vip)"><span>PERFORMANCE VIP</span> <i class="fas fa-crown"></i></div>
            <div class="stat-row">
                <div class="stat-item"><small>Sinais</small><b>120</b></div>
                <div class="stat-item"><small>Greens</small><b style="color: var(--primary)">108</b></div>
                <div class="stat-item"><small>Assertividade</small><b style="color: var(--vip)">90%</b></div>
            </div>
        </div>
    </div>

    <!-- BOTÃO ANALISADOR ACIMA DOS PALPITES -->
    <section class="analisador-cta">
        <h2 style="margin-bottom: 10px; font-weight: 800; font-size: 1.5rem;">ANALISADOR SEFULLBET AI</h2>
        <p style="color: var(--text-dim); margin-bottom: 20px; font-size: 14px;">Acesse agora a ferramenta de análise probabilística em tempo real.</p>
        <a href="analisador.php" class="btn-destaque-ai">
            <i class="fas fa-robot"></i> Abrir Analisador Agora
        </a>
    </section>

    <div class="section-header">
        <h3 style="font-weight: 800;">🔥 Palpites em Tempo Real</h3>
        <a href="#" style="color: var(--primary); font-size: 12px; font-weight: 600; text-decoration: none;">Ver Histórico</a>
    </div>

    <div class="palpite-list">
        <div class="palpite-item">
            <div class="tag tag-green">Finalizado</div>
            <div>
                <div style="font-weight: 700; font-size: 14px;">Bayern Munich vs Arsenal</div>
                <div style="font-size: 12px; color: var(--text-dim);">Champions League • 20:00</div>
            </div>
            <div style="font-size: 13px; font-weight: 600;">Ambas Marcam</div>
            <div class="odd-box"><small>ODD</small><b>1.80</b></div>
            <div style="text-align: right;"><span style="font-size: 10px; color: var(--text-dim); background: #21262d; padding: 4px 8px; border-radius: 4px;">FREE</span></div>
        </div>

        <div class="palpite-item">
            <?php if(!$pode_ver_vip): ?>
            <div class="lock-overlay">
                <a href="upgrade.php" class="lock-btn"><i class="fas fa-lock"></i> LIBERAR ACESSO VIP</a>
            </div>
            <?php endif; ?>
            
            <div class="tag tag-wait <?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">Analizando</div>
            <div class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">
                <div style="font-weight: 700; font-size: 14px;">Real Madrid vs Man. City</div>
                <div style="font-size: 12px; color: var(--text-dim);">Champions League • 21:00</div>
            </div>
            <div style="font-size: 13px; font-weight: 600;" class="<?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>">Resultado Final</div>
            <div class="odd-box <?php echo !$pode_ver_vip ? 'blur-lock' : ''; ?>"><small>ODD</small><b>2.45</b></div>
            <div style="text-align: right;"><span style="font-size: 10px; color: var(--vip); border: 1px solid var(--vip); padding: 4px 8px; border-radius: 4px;">VIP</span></div>
        </div>
    </div>

    <!-- SEÇÃO VITÓRIAS E NOTAS ABAIXO -->
    <div class="grid-info">
        <div class="vitorias-box">
            <h3 style="margin-bottom: 15px; font-size: 16px;"><i class="fas fa-trophy" style="color: var(--vip)"></i> Últimas Vitórias</h3>
            <div style="font-size: 13px; color: var(--text-dim); line-height: 1.6;">
                <p>🏆 <b>@usuario123</b> acaba de lucrar 5.5 unidades no mercado de cantos!</p>
                <hr style="border: 0; border-top: 1px solid var(--border); margin: 10px 0;">
                <p>🏆 <b>@bet_master</b> bateu a meta do dia com a ODD 2.10 do Analisador.</p>
            </div>
        </div>
        <div class="notas-box">
            <h3 style="margin-bottom: 10px; font-size: 16px; color: var(--primary);">📝 Notas</h3>
            <p style="font-size: 12px; line-height: 1.5;">Lembre-se: O Analisador AI funciona melhor em jogos com mais de 20 minutos de live. Siga a gestão de banca rigorosamente.</p>
        </div>
    </div>

    <footer style="margin-top: 60px; padding-top: 20px; border-top: 1px solid var(--border); color: var(--text-dim); font-size: 11px; display: flex; justify-content: space-between;">
        <span>&copy; © 2026 SeFullBet - Inteligência de Dados aplicada ao Esporte.</span>
        <span>Apostas envolvem risco. Jogue com responsabilidade.</span>
    </footer>
</main>

</body>
</html>
