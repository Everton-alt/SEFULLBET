<?php
// 1. Configurações de Sessão e Erros
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// 2. Proteção: Se não houver sessão, expulsa para o login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

try {
    // 3. Busca dados REAIS do banco de dados
    // Ajustado para as colunas: nome, login, saldo_creditos, perfil
    $stmt = $pdo->prepare("SELECT nome, login, saldo_creditos, perfil, status_aprovacao FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // 4. Dados de Performance (Estatísticas do Algoritmo)
    // Nota: Futuramente estes dados podem vir de outra tabela (ex: palpites)
    $stats = [
        'greens' => 128,
        'vitorias_hoje' => 14,
        'nota_algoritmo' => '9.8',
        'palpites_ativos' => 6
    ];

} catch (PDOException $e) {
    die("Erro crítico na Dashboard: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sefullbet Pro</title>
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
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .layout-wrapper { display: flex; flex: 1; }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: var(--card);
            border-right: 1px solid var(--border);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar-logo {
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--primary);
            text-align: center;
            margin-bottom: 40px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .nav-menu { flex: 1; }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-dim);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: 0.3s;
        }

        .nav-link i { margin-right: 12px; width: 20px; text-align: center; }
        .nav-link:hover, .nav-link.active {
            background: rgba(0, 255, 136, 0.1);
            color: var(--primary);
        }

        .nav-logout { color: var(--danger); margin-top: auto; }

        /* CONTEÚDO PRINCIPAL */
        .main-content { flex: 1; padding: 40px; }

        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .badge-algoritmo {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        /* GRID DE MÉTRICAS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--card);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid var(--border);
            text-align: center;
            transition: 0.3s;
        }

        .stat-card:hover { border-color: var(--primary); transform: translateY(-5px); }
        .stat-card i { font-size: 1.8rem; color: var(--primary); margin-bottom: 12px; display: block; }
        .stat-value { font-size: 2rem; font-weight: bold; display: block; }
        .stat-label { font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; display: block; }

        /* BANNER DE AÇÃO */
        .action-banner {
            background: linear-gradient(135deg, #12151c 0%, #1c222d 100%);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-pro {
            background: var(--primary);
            color: #000;
            padding: 15px 35px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            text-transform: uppercase;
            box-shadow: 0 5px 15px rgba(0, 255, 136, 0.3);
            transition: 0.3s;
        }

        .btn-pro:hover { transform: scale(1.05); background: #00cc6e; }

        /* FOOTER */
        .footer {
            background: var(--card);
            padding: 30px;
            text-align: center;
            border-top: 1px solid var(--border);
            color: var(--text-dim);
            font-size: 0.85rem;
        }

        .footer a { color: var(--primary); text-decoration: none; margin: 0 10px; }

        @media (max-width: 768px) {
            .layout-wrapper { flex-direction: column; }
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid var(--border); }
            .main-content { padding: 20px; }
            .action-banner { flex-direction: column; text-align: center; gap: 20px; }
        }
    </style>
</head>
<body>

<div class="layout-wrapper">
    <aside class="sidebar">
        <div class="sidebar-logo">Sefullbet</div>
        
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="analisador.php" class="nav-link"><i class="fas fa-robot"></i> Analisador Pro</a>
            <a href="historico.php" class="nav-link"><i class="fas fa-history"></i> Histórico</a>
            <a href="perfil.php" class="nav-link"><i class="fas fa-user-circle"></i> Meu Perfil</a>
            
            <?php if ($user['perfil'] === 'Admin'): ?>
                <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">
                <a href="admin_aprova_usuarios.php" class="nav-link"><i class="fas fa-user-shield"></i> Área Admin</a>
            <?php endif; ?>
        </nav>

        <a href="logout.php" class="nav-link nav-logout"><i class="fas fa-power-off"></i> Sair da Conta</a>
    </aside>

    <main class="main-content">
        <header class="welcome-header">
            <div>
                <h1 style="margin: 0;">Bem-vindo, <?php echo explode(' ', htmlspecialchars($user['nome']))[0]; ?>!</h1>
                <p style="color: var(--text-dim); margin-top: 5px;">Seu plano: <strong style="color: var(--primary);"><?php echo $user['perfil']; ?></strong></p>
            </div>
            <div class="badge-algoritmo">
                <i class="fas fa-star"></i> NOTA ALGORITMO: <?php echo $stats['nota_algoritmo']; ?>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-check-double"></i>
                <span class="stat-value" style="color: var(--primary);"><?php echo $stats['greens']; ?></span>
                <span class="stat-label">Greens Acumulados</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-fire"></i>
                <span class="stat-value"><?php echo $stats['vitorias_hoje']; ?></span>
                <span class="stat-label">Vitórias Hoje</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-bullseye"></i>
                <span class="stat-value"><?php echo $stats['palpites_ativos']; ?></span>
                <span class="stat-label">Palpites Ativos</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-wallet"></i>
                <span class="stat-value"><?php echo $user['saldo_creditos']; ?></span>
                <span class="stat-label">Créditos Atuais</span>
            </div>
        </div>

        <div class="action-banner">
            <div>
                <h2 style="margin: 0; color: var(--primary);">Pronto para operar?</h2>
                <p style="color: var(--text-dim); margin-top: 5px;">O algoritmo identificou oportunidades em 12 ligas agora mesmo.</p>
            </div>
            <a href="analisador.php" class="btn-pro">Aceder ao Analisador</a>
        </div>
    </main>
</div>

<footer class="footer">
    <p>&copy; 2026 <strong>Sefullbet Intelligence</strong> - Todos os direitos reservados.</p>
    <div>
        <a href="termos.php">Termos de Uso</a> | 
        <a href="suporte.php">Suporte</a> | 
        <a href="privacidade.php">Privacidade</a>
    </div>
</footer>

</body>
</html>
