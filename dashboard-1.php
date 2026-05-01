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
    <style>
        :root {
            --primary: #00ff88; --premium: #00e5ff; --bg: #07090d; --card-bg: #12161f; 
            --border: #262c36; --text: #fff; --text-dim: #8b949e; --vip: #ffd700;
            --neon-glow: 0 0 15px rgba(0, 255, 136, 0.4);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', sans-serif; }
        
        body { background: var(--bg); color: var(--text); line-height: 1.6; }

        /* Barra de Rolagem Personalizada */
        ::-webkit-scrollbar { width: 10px; background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 5px; border: 2px solid var(--bg); }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        /* Header Estilo Index */
        header {
            padding: 15px 8%; display: flex; justify-content: space-between; align-items: center;
            backdrop-filter: blur(10px); background: rgba(7, 9, 13, 0.8);
            border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100;
        }

        .btn-logout { 
            color: #ff4d4d; text-decoration: none; font-weight: 800; font-size: 0.75rem; 
            border: 1px solid rgba(255, 77, 77, 0.3); padding: 8px 15px; border-radius: 8px; transition: 0.3s;
        }
        .btn-logout:hover { background: rgba(255, 77, 77, 0.1); }

        /* Container Principal */
        .container { max-width: 1100px; margin: 0 auto; padding: 40px 5%; }

        /* Banner Analisador */
        .hero-dashboard { text-align: center; margin-bottom: 50px; }
        .hero-badge { 
            background: rgba(0, 255, 136, 0.1); color: var(--primary); padding: 8px 20px; 
            border-radius: 50px; font-size: 0.7rem; font-weight: 800; border: 1px solid rgba(0, 255, 136, 0.3);
            display: inline-block; margin-bottom: 20px;
        }

        .analisador-card {
            background: linear-gradient(135deg, #12161f 0%, #1c222d 100%);
            border: 1px solid var(--primary); border-radius: 24px; padding: 40px;
            box-shadow: var(--neon-glow); display: flex; justify-content: space-between; align-items: center;
            gap: 20px; flex-wrap: wrap;
        }

        .btn-main {
            background: var(--primary); color: #000; padding: 18px 35px; border-radius: 12px;
            text-decoration: none; font-weight: 900; text-transform: uppercase;
            box-shadow: 0 10px 20px rgba(0, 255, 136, 0.2); transition: 0.3s; display: inline-block;
        }
        .btn-main:hover { transform: translateY(-3px); filter: brightness(1.1); }

        /* Performance Grids */
        .grid-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 50px; }
        .card-stat { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 25px; }
        .stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .stat-numbers { display: grid; grid-template-columns: repeat(4, 1fr); text-align: center; }
        .stat-val { display: block; font-size: 1.2rem; font-weight: 900; }
        .stat-label { font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700; }

        /* Feed de Palpites */
        .section-title { font-size: 1.5rem; font-weight: 900; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .palpite-row { 
            background: var(--card-bg); border: 1px solid var(--border); padding: 20px; 
            border-radius: 18px; margin-bottom: 15px; display: grid; 
            grid-template-columns: 130px 1fr 100px 120px; align-items: center; gap: 20px;
        }

        .badge-status { padding: 6px; border-radius: 8px; font-size: 0.65rem; font-weight: 900; text-align: center; border: 1px solid; }
        .st-green { background: rgba(0, 255, 136, 0.1); color: var(--primary); border-color: rgba(0, 255, 136, 0.2); }
        .st-wait { background: rgba(255, 215, 0, 0.1); color: var(--vip); border-color: rgba(255, 215, 0, 0.2); }

        /* Travas VIP */
        .locked-content { filter: blur(5px); pointer-events: none; opacity: 0.4; }
        .lock-overlay { position: absolute; background: var(--vip); color: #000; padding: 5px 15px; border-radius: 20px; font-weight: 900; font-size: 0.7rem; left: 50%; top: 50%; transform: translate(-50%, -50%); z-index: 5; }

        /* Vitórias Recentes */
        .grid-vitorias { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
        .card-v { background: var(--card-bg); border-radius: 20px; overflow: hidden; border: 1px solid var(--border); }
        .card-v img { width: 100%; height: 180px; object-fit: cover; }
        .card-v-body { padding: 20px; }

        /* Rodapé */
        footer { border-top: 1px solid var(--border); padding: 60px 5%; text-align: center; color: var(--text-dim); font-size: 0.85rem; }

        @media (max-width: 768px) {
            .grid-stats { grid-template-columns: 1fr; }
            .palpite-row { grid-template-columns: 1fr 1fr; }
            .analisador-card { text-align: center; justify-content: center; }
        }
    </style>
</head>
<body>

    <header>
        <a href="#" style="text-decoration: none; font-weight: 900; font-size: 1.4rem; color: #fff;">
            SEFULL<span style="color: var(--primary);">BET</span>
        </a>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="text-align: right; line-height: 1;">
                <span style="font-size: 0.8rem; font-weight: 800;"><?php echo $user['nome']; ?></span><br>
                <span style="font-size: 0.65rem; color: <?php echo $cor_perfil; ?>; font-weight: 900;"><?php echo strtoupper($perfil); ?></span>
            </div>
            <a href="logout.php" class="btn-logout">SAIR</a>
        </div>
    </header>

    <div class="container">
        
        <!-- Seção Analisador -->
        <section class="hero-dashboard">
            <div class="hero-badge"><i class="fas fa-microchip"></i> INTELIGÊNCIA ARTIFICIAL ATIVA</div>
            <div class="analisador-card">
                <div style="text-align: left;">
                    <h2 style="font-size: 1.8rem; font-weight: 900; margin-bottom: 10px;">Analisador Pro</h2>
                    <p style="color: var(--text-dim); font-size: 0.9rem;">
                        Você possui <b><?php echo $is_platinum ? 'Acesso Ilimitado' : $user['saldo_creditos'] . ' créditos'; ?></b> disponíveis.
                    </p>
                </div>
                <a href="analisador.php" class="btn-main"><i class="fas fa-bolt"></i> Abrir Analisador</a>
            </div>
        </section>

        <!-- Seção Performance -->
        <div class="grid-stats">
            <div class="card-stat">
                <div class="stat-header">
                    <span class="stat-label">Performance Free</span>
                    <i class="fas fa-chart-line" style="color: var(--text-dim);"></i>
                </div>
                <div class="stat-numbers">
                    <div><span class="stat-label">Total</span><span class="stat-val">45</span></div>
                    <div><span class="stat-label">Greens</span><span class="stat-val" style="color: var(--primary);">38</span></div>
                    <div><span class="stat-label">Reds</span><span class="stat-val" style="color: #ff4d4d;">7</span></div>
                    <div><span class="stat-label">Winrate</span><span class="stat-val">84%</span></div>
                </div>
            </div>
            <div class="card-stat" style="border-color: var(--vip);">
                <div class="stat-header">
                    <span class="stat-label" style="color: var(--vip);">Performance VIP</span>
                    <i class="fas fa-crown" style="color: var(--vip);"></i>
                </div>
                <div class="stat-numbers">
                    <div><span class="stat-label">Total</span><span class="stat-val">120</span></div>
                    <div><span class="stat-label">Greens</span><span class="stat-val" style="color: var(--primary);">108</span></div>
                    <div><span class="stat-label">Reds</span><span class="stat-val" style="color: #ff4d4d;">12</span></div>
                    <div><span class="stat-label">Winrate</span><span class="stat-val" style="color: var(--vip);">90%</span></div>
                </div>
            </div>
        </div>

        <!-- Seção Palpites -->
        <h3 class="section-title"><i class="fas fa-satellite-dish" style="color: var(--primary);"></i> Feed de Sinais</h3>
        
        <!-- Sinal Free -->
        <div class="palpite-row">
            <div class="badge-status st-green">VITORIOSO</div>
            <div>
                <b style="font-size: 0.95rem;">Flamengo vs Palmeiras</b><br>
                <small style="color: var(--text-dim);">Hoje 21:30 | Over 1.5 Gols</small>
            </div>
            <div style="text-align: center;"><span class="stat-label">ODD</span><br><b style="color: var(--primary);">1.85</b></div>
            <div style="text-align: right;"><span class="badge-status" style="border-color: var(--border);">FREE</span></div>
        </div>

        <!-- Sinal VIP -->
        <div class="palpite-row" style="position: relative; overflow: hidden;">
            <?php if(!$pode_ver_vip): ?>
                <div class="lock-overlay"><i class="fas fa-lock"></i> UPGRADE VIP</div>
            <?php endif; ?>
            <div class="badge-status st-wait <?php echo !$pode_ver_vip ? 'locked-content' : ''; ?>">AGUARDANDO</div>
            <div class="<?php echo !$pode_ver_vip ? 'locked-content' : ''; ?>">
                <b style="font-size: 0.95rem;">Man. City vs Arsenal</b><br>
                <small style="color: var(--text-dim);">Amanhã 16:00 | ML City</small>
            </div>
            <div style="text-align: center;" class="<?php echo !$pode_ver_vip ? 'locked-content' : ''; ?>">
                <span class="stat-label">ODD</span><br><b>2.10</b>
            </div>
            <div style="text-align: right;"><span class="badge-status" style="border-color: var(--vip); color: var(--vip);">VIP GOLD</span></div>
        </div>

        <!-- Vitórias Recentes -->
        <h3 class="section-title" style="margin-top: 60px;"><i class="fas fa-fire" style="color: #ff8800;"></i> Vitórias da Comunidade</h3>
        <div class="grid-vitorias">
            <div class="card-v">
                <img src="https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=600&q=80" alt="Vitória">
                <div class="card-v-body">
                    <h4 style="font-size: 1rem; margin-bottom: 5px;">Alavancagem Odd 5.0</h4>
                    <p style="font-size: 0.8rem; color: var(--text-dim);">Análise de valor em mercados alternativos garantiram lucro máximo...</p>
                </div>
            </div>
        </div>

    </div>

    <footer>
        <div style="font-weight: 900; font-size: 1.4rem; color: #fff; margin-bottom: 15px;">SEFULL<span>BET</span></div>
        <p>© 2026 SeFullBet - Inteligência de Dados aplicada ao Esporte.<br>Apostas são para maiores de 18 anos. Jogue com responsabilidade.</p>
    </footer>

</body>
</html>
