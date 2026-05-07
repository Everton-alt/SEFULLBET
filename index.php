<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeFull Bet | Inteligência em Odds</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --primary: #00ff88; 
            --bg: #0b0e14; 
            --card-bg: #161b22; 
            --text: #ffffff; 
            --accent: #6e40ff; 
            --vip: #ffd700; 
            --premium: #00ddeb; 
            --glass: rgba(22, 27, 34, 0.7);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', sans-serif; }
        
        body { 
            background: var(--bg); 
            color: var(--text); 
            line-height: 1.6; 
            overflow-x: hidden; 
            background-image: radial-gradient(circle at 20% 30%, #162c23 0%, #0b0e14 100%);
        }

        /* HEADER REFINADO */
        header { 
            padding: 20px 8%; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo { font-weight: 900; font-size: 1.6rem; color: #fff; letter-spacing: -1px; }
        .logo span { color: var(--primary); text-shadow: 0 0 10px rgba(0, 255, 136, 0.3); }

        /* HERO SECTION */
        .hero { 
            padding: 100px 5% 40px; 
            text-align: center; 
            max-width: 1100px; 
            margin: 0 auto; 
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .hero-badge { 
            background: rgba(0, 255, 136, 0.1); 
            color: var(--primary); 
            padding: 10px 25px; 
            border-radius: 50px; 
            font-size: 0.75rem; 
            font-weight: 800; 
            border: 1px solid rgba(0, 255, 136, 0.3); 
            display: inline-block; 
            margin-bottom: 25px; 
            letter-spacing: 1px;
        }

        .hero h1 { font-size: clamp(2.5rem, 6vw, 4.2rem); line-height: 1; margin-bottom: 25px; font-weight: 900; letter-spacing: -2px; }
        .hero h1 span { background: linear-gradient(90deg, var(--primary), var(--premium)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .hero p { color: #94a3b8; font-size: 1.15rem; margin-bottom: 45px; max-width: 750px; margin-inline: auto; }

        /* SEÇÃO DE VITÓRIAS (NOVO) */
        .live-results {
            max-width: 1200px;
            margin: 0 auto 60px;
            padding: 0 5%;
        }

        .wins-scroll {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 10px 0 20px;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) transparent;
        }

        .wins-scroll::-webkit-scrollbar { height: 4px; }
        .wins-scroll::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        .win-card {
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 12px;
            min-width: 250px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: 0.3s;
        }

        .win-card:hover { border-color: var(--primary); background: rgba(0,255,136,0.02); }

        /* BOTÕES */
        .btn-group { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        
        .btn-primary { 
            background: var(--primary); 
            color: #000; padding: 20px 40px; 
            border-radius: 16px; 
            text-decoration: none; 
            font-weight: 800; 
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            box-shadow: 0 10px 25px rgba(0,255,136,0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary:hover { 
            transform: scale(1.05); 
            box-shadow: 0 15px 35px rgba(0,255,136,0.4); 
        }

        .btn-secondary { 
            background: rgba(255,255,255,0.03); 
            color: #fff; padding: 20px 40px; 
            border-radius: 16px; 
            text-decoration: none; 
            font-weight: 800; 
            border: 1px solid rgba(255,255,255,0.1); 
            transition: 0.3s;
            text-transform: uppercase;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.08); border-color: var(--primary); }

        /* FEATURE BOX GLASS */
        .feature-box { 
            margin: 60px auto; 
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.05); 
            border-radius: 30px; 
            padding: 40px; 
            max-width: 900px; 
            display: flex; 
            align-items: center; 
            gap: 30px; 
            text-align: left; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .feature-icon { 
            font-size: 2.8rem; 
            color: var(--primary); 
            background: rgba(0,255,136,0.1); 
            min-width: 90px; height: 90px; 
            display: flex; align-items: center; justify-content: center; 
            border-radius: 22px; 
            border: 1px solid rgba(0,255,136,0.2); 
        }

        /* PLANS GRID */
        .plans-container { padding: 80px 5%; max-width: 1200px; margin: 0 auto; }
        .grid-plans { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        
        .card-plan { 
            background: var(--card-bg); 
            border-radius: 28px; 
            padding: 50px 35px; 
            border: 1px solid rgba(255,255,255,0.05); 
            position: relative; 
            transition: 0.4s; 
            display: flex; 
            flex-direction: column; 
        }

        .card-plan:hover { transform: translateY(-10px); border-color: var(--primary); }
        
        .card-plan.highlight { 
            border: 2px solid var(--vip); 
            background: linear-gradient(180deg, #1c2128 0%, #161b22 100%);
            transform: scale(1.05); 
        }

        .price { font-size: 3rem; font-weight: 900; margin: 25px 0; letter-spacing: -2px; }
        .price span { font-size: 1.1rem; color: #64748b; font-weight: 500; letter-spacing: 0; }

        .features-list { list-style: none; margin-bottom: 40px; flex-grow: 1; }
        .features-list li { margin-bottom: 18px; font-size: 1rem; color: #cbd5e1; display: flex; align-items: center; }
        .features-list li i { color: var(--primary); margin-right: 15px; font-size: 0.9rem; }

        .tag-promo { 
            position: absolute; top: -15px; right: 30px; 
            background: var(--vip); color: #000; 
            padding: 8px 20px; border-radius: 50px; 
            font-size: 0.8rem; font-weight: 900; 
        }

        footer { padding: 80px 5%; text-align: center; border-top: 1px solid rgba(255,255,255,0.05); color: #64748b; }

        @media (max-width: 768px) { 
            .feature-box { flex-direction: column; text-align: center; padding: 30px; } 
            .hero h1 { font-size: 2.8rem; }
            .card-plan.highlight { transform: scale(1); }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">SEFULL<span>BET</span></div>
        <a href="login.html" class="btn-secondary" style="padding:12px 30px; font-size:0.85rem; border-radius:12px;">ÁREA DO MEMBRO</a>
    </header>

    <section class="hero">
        <div class="hero-badge"><i class="fas fa-microchip"></i> ANALISADOR SEFULLBET ATIVO</div>
        <h1>Esqueça a Sorte. <br><span>Opere a Matemática.</span></h1>
        <p>A primeira plataforma que não te dá apenas palpites, mas sim <b>análise de valor real</b>. Nosso sistema processa milhões de dados para encontrar as melhores oportinudades com base a ODD escolhida atual do jogo escolhi, pode ser o jogo da serie A ou serie D, conseguimos gerar as melhores entradas.</p>
        
        <div class="btn-group">
            <a href="cadastro.html" class="btn-primary">COMEÇAR AGORA</a>
            <a href="feed.html" class="btn-secondary">VER FEED AO VIVO</a>
        </div>
    </section>

    <!-- SEÇÃO DE VITÓRIAS RECENTES (Integrada com o Banco) -->
    <section class="live-results">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="font-size: 0.8rem; letter-spacing: 2px; color: var(--primary); text-transform: uppercase;">
                <i class="fas fa-trophy"></i> Últimas Vitórias
            </h4>
            <span style="font-size: 0.7rem; color: #64748b;">RESULTADOS REAIS</span>
        </div>
        
        <div class="wins-scroll">
            <?php foreach($lista_vitorias as $v): ?>
            <div class="win-card">
                <?php if(!empty($v['v_foto_miniatura'])): ?>
                    <img src="<?= htmlspecialchars($v['v_foto_miniatura']) ?>" 
                         style="width: 45px; height: 45px; border-radius: 8px; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 45px; height: 45px; border-radius: 8px; background: rgba(0,255,136,0.1); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check-circle" style="color: var(--primary);"></i>
                    </div>
                <?php endif; ?>
                
                <div style="overflow: hidden;">
                    <span style="display: block; font-weight: 700; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($v['v_titulo']) ?>
                    </span>
                    <small style="color: var(--primary); font-size: 0.7rem; font-weight: 600;">
                        <?= $v['v_fixado'] ? '⭐ DESTAQUE' : '✅ GREEN CONFIRMADO' ?>
                    </small>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($lista_vitorias)): ?>
                <div style="color: #64748b; font-size: 0.8rem;">Aguardando novos resultados...</div>
            <?php endif; ?>
        </div>

        <div class="feature-box">
            <div class="feature-icon"><i class="fas fa-brain"></i></div>
            <div>
                <h3 style="color:var(--primary); margin-bottom:10px; font-size:1.3rem;">Analisador Sob Demanda</h3>
                <p style="color:#94a3b8;">Diferente de grupos de sinais comuns promessas, aqui <b>você escolhe o jogo</b>. Insira qualquer partida e receba em segundos os 3 melhores mercados baseados nas ODDS informadas que o jogo está pagando.</p>
            </div>
        </div>
    </section>

    <section class="plans-container">
        <div class="grid-plans">
            <div class="card-plan">
                <h3 style="letter-spacing: 2px; font-weight: 800; opacity: 0.7;">GRÁTIS</h3>
                <div class="price">R$ 0<span>/mês</span></div>
                <ul class="features-list">
                    <li><i class="fas fa-check-circle"></i> 1 Análise do analisador SEFULLBET</li>
                    <li><i class="fas fa-check-circle"></i> Acesso aos Palpites diários</li>
                    <li><i class="fas fa-check-circle"></i> Histórico de Greens</li>
                    <li><i class="fas fa-check-circle"></i> Grupo Telegram</li>
                </ul>
                <a href="cadastro.html" class="btn-secondary" style="text-align:center;">CRIAR CONTA</a>
            </div>

            <div class="card-plan highlight">
                <div class="tag-promo">RECOMENDADO</div>
                <h3 style="color:var(--vip); letter-spacing: 2px; font-weight: 800;">VIP GOLD</h3>
                <div class="price">R$ 49<span>/mês</span></div>
                <ul class="features-list">
                    <li><i class="fas fa-star" style="color:var(--vip);"></i> <b>30 Créditos de Análise/Mês do Analisador SEFULLBET</b></li>
                    <li><i class="fas fa-star" style="color:var(--vip);"></i> Sinais VIP no Feed</li>
                    <li><i class="fas fa-star" style="color:var(--vip);"></i> Gestão de Banca de forma simples com nosso sistema</li>
                     <li><i class="fas fa-star" style="color:var(--vip);"></i> Histórico de Greens</li>
                    <li><i class="fas fa-star" style="color:var(--vip);"></i> Grupo Telegram</li>
                </ul>
                <a href="cadastro.html" class="btn-primary" style="text-align:center; background:var(--vip);">ASSINAR VIP</a>
            </div>

            <div class="card-plan" style="border-color:var(--premium);">
                <h3 style="color:var(--premium); letter-spacing: 2px; font-weight: 800;">PLATINUM</h3>
                <div class="price">R$ 97<span>/mês</span></div>
                <ul class="features-list">
                    <li><i class="fas fa-infinity" style="color:var(--premium);"></i> <b>Análises Ilimitadas do Analisador SEFULLBET </b></li>
                    <li><i class="fas fa-infinity" style="color:var(--premium);"></i> Todas as funções VIP</li>
                     <li><i class="fas fa-infinity" style="color:var(--premium);"></i> Gestão de Banca de forma simples com nosso sistema h</li>
                    <li><i class="fas fa-infinity" style="color:var(--premium);"></i> Histórico de Greens h</li>
                    <li><i class="fas fa-infinity" style="color:var(--premium);"></i> Grupo Telegram h</li>
                    <li><i class="fas fa-infinity" style="color:var(--premium);"></i> Suporte Prioritário h</li>
                </ul>
                <a href="cadastro.html" class="btn-primary" style="text-align:center; background:var(--premium);">GO PLATINUM</a>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-brand" style="color:#fff; font-weight:900; font-size:1.4rem; margin-bottom:20px;">SEFULL<span>BET</span></div>
        <p>&copy; 2026 SeFullBet - Inteligência de Dados aplicada ao Esporte.<br>Lembre-se: Apostas são para maiores de 18 anos. Jogue com responsabilidade.</p>
    </footer>
</body>
</html>
