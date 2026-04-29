<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare("SELECT nome, login, saldo_creditos, perfil, status_aprovacao FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $user = $stmt->fetch();

    // Dados Fictícios para o Layout (Depois você conectará com sua tabela de palpites)
    $estatisticas = [
        'greens' => 128,
        'vitorias_hoje' => 12,
        'nota_algoritmo' => 9.8,
        'palpites_ativos' => 5
    ];

} catch (PDOException $e) {
    die("Erro ao carregar Dashboard: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sefullbet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #00ff88;
            --bg: #080a0f;
            --card: #12151c;
            --border: #262c3a;
            --text-main: #ffffff;
            --text-dim: #a0aec0;
            --danger: #ff4d4d;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .wrapper { display: flex; flex: 1; }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--card);
            border-right: 1px solid var(--border);
            padding: 30px 20px;
        }

        .logo { font-size: 1.6rem; font-weight: 900; color: var(--primary); text-align: center; display: block; margin-bottom: 40px; letter-spacing: 2px; }
        
        .nav-link {
            display: flex; align-items: center; padding: 12px 15px; color: var(--text-dim);
            text-decoration: none; border-radius: 8px; margin-bottom: 8px; transition: 0.3s;
        }

        .nav-link i { margin-right: 12px; width: 20px; text-align: center; }
        .nav-link:hover, .nav-link.active { background: rgba(0, 255, 136, 0.1); color: var(--primary); }

        /* Main Content */
        .main-content { flex: 1; padding: 40px; }

        /* Grid de Contadores */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-box {
            background: var(--card);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            text-align: center;
            transition: 0.3s;
        }

        .metric-box:hover { border-color: var(--primary); transform: translateY(-5px); }
        .metric-box i { font-size: 1.5rem; color: var(--primary); margin-bottom: 10px; display: block; }
        .metric-val { font-size: 1.8rem; font-weight: bold; display: block; }
        .metric-label { font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; }

        /* Badge de Nota */
        .nota-badge {
            background: rgba(0, 255, 136, 0.2);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        /* Footer */
        footer {
            background: var(--card);
            border-top: 1px solid var(--border);
            padding: 30px;
            text-align: center;
            margin-top: auto;
        }

        .footer-content { color: var(--text-dim); font-size: 0.85rem; }
        .footer-links a { color: var(--primary); text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <span class="logo">SEFULLBET</span>
        <a href="#" class="nav-link active"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="analisador.php" class="nav-link"><i class="fas fa-robot"></i> Analisador Pro</a>
        <a href="palpites.php" class="nav-link"><i class="fas fa-history"></i> Histórico</a>
        <a href="perfil.php" class="nav-link"><i class="fas fa-user-circle"></i> Minha Conta</a>
        
        <?php if ($_SESSION['perfil'] == 'Admin'): ?>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">
            <a href="admin_aprova_usuarios.php" class="nav-link"><i class="fas fa-user-shield"></i> Admin Panel</a>
        <?php endif; ?>

        <a href="logout.php" class="nav-link" style="color: var(--danger); margin-top: 20px;"><i class="fas fa-power-off"></i> Sair</a>
    </div>

    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="margin: 0;">Painel de Operações</h1>
                <p style="color: var(--text-dim);">Bem-vindo, <?php echo explode(' ', $user['nome'])[0]; ?>. Suas métricas estão atualizadas.</p>
            </div>
            <div class="nota-badge">
                <i class="fas fa-star"></i> NOTA ALGORITMO: <?php echo $estatisticas['nota_algoritmo']; ?>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-box">
                <i class="fas fa-check-double"></i>
                <span class="metric-val" style="color: var(--primary);"><?php echo $estatisticas['greens']; ?></span>
                <span class="metric-label">Greens Acumulados</span>
            </div>
            <div class="metric-box">
                <i class="fas fa-fire"></i>
                <span class="metric-val"><?php echo $estatisticas['vitorias_hoje']; ?></span>
                <span class="metric-label">Vitórias Hoje</span>
            </div>
            <div class="metric-box">
                <i class="fas fa-bullseye"></i>
                <span class="metric-val"><?php echo $estatisticas['palpites_ativos']; ?></span>
                <span class="metric-label">Palpites Ativos</span>
            </div>
            <div class="metric-box">
                <i class="fas fa-wallet"></i>
                <span class="metric-val"><?php echo $user['saldo_creditos']; ?></span>
                <span class="metric-label">Créditos Restantes</span>
            </div>
        </div>

        <div style="background: linear-gradient(90deg, #12151c 0%, #1c222d 100%); padding: 30px; border-radius: 15px; border: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; color: var(--primary);">Pronto para a próxima análise?</h3>
                <p style="color: var(--text-dim); margin: 5px 0 0 0;">O algoritmo processou novos dados de 15 ligas diferentes agora mesmo.</p>
            </div>
            <a href="analisador.php" style="background: var(--primary); color: #000; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold; text-transform: uppercase;">Acessar Analisador</a>
        </div>
    </div>
</div>

<footer>
    <div class="footer-content">
        <p>&copy; 2026 <strong>Sefullbet Intelligence</strong> - Todos os direitos reservados.</p>
        <div class="footer-links">
            <a href="termos.php">Termos de Uso</a> | 
            <a href="suporte.php">Suporte</a> | 
            <a href="privacidade.php">Privacidade</a>
        </div>
        <p style="font-size: 0.7rem; margin-top: 15px; opacity: 0.5;">Lembre-se: Apostas envolvem risco. Gerencie sua banca com responsabilidade.</p>
    </div>
</footer>

</body>
</html>
