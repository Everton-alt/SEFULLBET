<?php
session_start();
require_once 'config.php';

// 1. TRAVA DE SEGURANÇA: Só Admin e Supervisor passam daqui
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['perfil'], ['Admin', 'Supervisor'])) {
    // Se não for autorizado, destrói a tentativa e manda para o feed de usuário
    header("Location: dashboard.php");
    exit();
}

$perfil_adm = $_SESSION['perfil'];
$nome_adm = $_SESSION['usuario_nome'] ?? 'Administrador';

// Contadores rápidos para o Admin
$membros_pendentes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE status_aprovacao = 'Aguardando Aprovação'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00ff88; --bg: #0b0e14; --card: #161b22; --border: #30363d;
            --text: #c9d1d9; --vip: #ffd700; --danger: #ff4d4d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; overflow: hidden; }

        /* MENU LATERAL (Idêntico ao seu escopo) */
        nav { width: 260px; background: var(--card); border-right: 1px solid var(--border); padding: 25px 20px; display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
        .nav-logo { font-size: 1.5rem; font-weight: 900; color: var(--primary); margin-bottom: 30px; text-align: center; }
        .nav-logo span { color: #fff; }
        .nav-btn { background: transparent; border: 1px solid transparent; color: #8b949e; padding: 14px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 14px; transition: 0.25s; }
        .nav-btn:hover, .nav-btn.active { background: #21262d; border-color: var(--border); color: var(--primary); }
        .badge-count { background: var(--danger); color: white; padding: 2px 7px; border-radius: 50%; font-size: 10px; margin-left: auto; }
        .logout-btn { margin-top: auto; color: var(--danger); }

        main { flex: 1; padding: 30px; overflow-y: auto; }
        .admin-card { background: var(--card); padding: 25px; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 25px; }
        
        /* Badges de Perfil no Painel */
        .perfil-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; background: rgba(0,255,136,0.1); color: var(--primary); }
    </style>
</head>
<body>

<nav>
  <div class="nav-logo">ADMIN<span>BET</span></div>
  <a class="nav-btn active" href="admin_dashboard.php"><i class="fas fa-chart-line"></i> Início</a>
  <a class="nav-btn" href="gestao-sinais.php"><i class="fas fa-signal"></i> Gestão de Sinais</a>
  <a class="nav-btn" href="analisador-odds.php"><i class="fas fa-calculator"></i> Analisador de ODDS</a>
  <a class="nav-btn" href="membros.php">
      <i class="fas fa-users"></i> Membros 
      <?php if($membros_pendentes > 0): ?> <span class="badge-count"><?php echo $membros_pendentes; ?></span> <?php endif; ?>
  </a>
  <a class="nav-btn" href="vitorias.php"><i class="fas fa-trophy"></i> Vitórias</a>
  <a class="nav-btn" href="noticias.php"><i class="fas fa-newspaper"></i> Notícias</a>
  <a class="nav-btn" href="importacao.php"><i class="fas fa-file-import"></i> Importação</a>
  <a class="nav-btn logout-btn" href="logout.php"><i class="fas fa-power-off"></i> Sair do Painel</a>
</nav>

<main>
    <section>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <h2>Painel de Controle</h2>
            <span class="perfil-badge"><?php echo strtoupper($perfil_adm); ?> LOGADO</span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="admin-card" style="text-align:center; margin-bottom:0;">
                <small style="color:#8b949e">Aprovações Pendentes</small>
                <h1 style="color:<?php echo $membros_pendentes > 0 ? 'var(--vip)' : 'var(--primary)'; ?>"><?php echo $membros_pendentes; ?></h1>
            </div>
            <div class="admin-card" style="text-align:center; margin-bottom:0;">
                <small style="color:#8b949e">Tips Enviadas Hoje</small>
                <h1 style="color:#fff">12</h1>
            </div>
            <div class="admin-card" style="text-align:center; margin-bottom:0;">
                <small style="color:#8b949e">Taxa de Acerto Global</small>
                <h1 style="color:var(--primary)">92%</h1>
            </div>
        </div>

        <div class="admin-card">
            <h3>Ações Rápidas</h3>
            <p style="color:#8b949e; margin-bottom:20px;">O que você deseja publicar agora?</p>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="gestao-sinais.php" class="nav-btn active" style="padding:10px 20px;">+ Novo Palpite</a>
                <a href="vitorias.php" class="nav-btn active" style="padding:10px 20px;">+ Cadastrar Vitória</a>
                <a href="noticias.php" class="nav-btn active" style="padding:10px 20px;">+ Nova Nota</a>
            </div>
        </div>

        <?php if($perfil_adm === 'Admin'): ?>
        <div class="admin-card" style="border-color: var(--danger);">
            <h3 style="color: var(--danger);"><i class="fas fa-shield-alt"></i> Verificação de Exclusões</h3>
            <p style="color:#8b949e; font-size:14px;">Itens excluídos por Supervisores aguardam sua decisão final aqui.</p>
            <p style="margin-top:15px; font-size:13px;">✅ <i>Nenhuma exclusão pendente no momento.</i></p>
        </div>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
