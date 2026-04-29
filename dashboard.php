<?php
session_start();
require_once 'config.php';

// SEGURANÇA: Só Admin ou Supervisor entram aqui
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['perfil'], ['Admin', 'Supervisor'])) {
    header("Location: dashboard.php"); // Usuário comum é expulso para o feed dele
    exit();
}

$perfil = $_SESSION['perfil'];

// Contadores para o Resumo do Admin
$total_membros = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$pendentes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE status_aprovacao = 'Aguardando Aprovação'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Painel | SeFull Bet</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ... Mantendo o teu CSS original (mesmas cores e layout) ... */
:root { --primary: #00ff88; --bg: #0b0e14; --card: #161b22; --border: #30363d; --text: #c9d1d9; --danger: #ff4d4d; }
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; overflow: hidden; }
nav { width: 260px; background: var(--card); border-right: 1px solid var(--border); padding: 25px 20px; display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
.nav-logo { font-size: 1.5rem; font-weight: 900; color: var(--primary); margin-bottom: 30px; text-align: center; }
.nav-logo span { color: #fff; }
.nav-btn { background: transparent; border: 1px solid transparent; color: #8b949e; padding: 14px; border-radius: 10px; cursor: pointer; text-align: left; font-weight: 600; transition: 0.25s; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 14px; }
.nav-btn:hover, .nav-btn.active { background: #21262d; border-color: var(--border); color: var(--primary); }
.logout-btn { margin-top: auto; color: var(--danger); }
main { flex: 1; padding: 30px; overflow-y: auto; }
.admin-card { background: var(--card); padding: 25px; border-radius: 15px; border: 1px solid var(--border); margin-bottom: 25px; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
.stat-box { background: #21262d; padding: 20px; border-radius: 12px; border: 1px solid var(--border); text-align: center; }
.stat-box h4 { font-size: 0.8rem; color: #8b949e; margin-bottom: 10px; }
.stat-box span { font-size: 1.8rem; font-weight: bold; color: var(--primary); }
</style>
</head>
<body>

<nav>
  <div class="nav-logo">ADMIN<span>BET</span></div>
  <a class="nav-btn active" href="admin_dashboard.php"><i class="fas fa-home"></i> Início</a>
  <a class="nav-btn" href="gestao-sinais.php"><i class="fas fa-signal"></i> Gestão de Sinais</a>
  <a class="nav-btn" href="analisador-odds.php"><i class="fas fa-chart-line"></i> Analisador de ODDS</a>
  <a class="nav-btn" href="membros.php"><i class="fas fa-users"></i> Membros <?php if($pendentes > 0) echo "<small style='background:red; color:white; padding:2px 6px; border-radius:50%; margin-left:5px;'>$pendentes</small>"; ?></a>
  <a class="nav-btn" href="vitorias.php"><i class="fas fa-trophy"></i> Vitórias</a>
  <a class="nav-btn" href="noticias.php"><i class="fas fa-newspaper"></i> Notícias</a>
  <a class="nav-btn" href="importacao.php"><i class="fas fa-file-import"></i> Importação</a>
  <a class="nav-btn" href="dashboard.php" target="_blank"><i class="fas fa-eye"></i> Visualizar como Usuário</a>
  <a class="nav-btn logout-btn" href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair do Painel</a>
</nav>

<main>
  <section>
    <h2>Painel Administrativo</h2>
    <p style="color:#8b949e; margin-bottom: 25px;">Bem-vindo, <strong><?php echo $perfil; ?></strong>. Aqui tens o controlo total da Sefullbet.</p>

    <div class="stats-grid">
        <div class="stat-box">
            <h4>Total Membros</h4>
            <span><?php echo $total_membros; ?></span>
        </div>
        <div class="stat-box">
            <h4>Aprovações Pendentes</h4>
            <span style="color: <?php echo ($pendentes > 0) ? '#ffcc00' : 'var(--primary)'; ?>"><?php echo $pendentes; ?></span>
        </div>
        <div class="stat-box">
            <h4>Tips VIP Hoje</h4>
            <span>12</span>
        </div>
    </div>

    <div class="admin-card">
      <h3>Atalhos Rápidos</h3>
      <div style="display: flex; gap: 10px; margin-top: 15px;">
          <a href="membros.php" class="nav-btn active">Aprovar Novos Membros</a>
          <a href="gestao-sinais.php" class="nav-btn active">Postar Novo Palpite</a>
      </div>
    </div>

    <?php if($perfil === 'Admin'): ?>
    <div class="admin-card" style="border-color: var(--danger);">
      <h3 style="color: var(--danger);">Área de Segurança (Travas)</h3>
      <p style="color:#8b949e;">Existem <strong>0</strong> itens marcados para exclusão por supervisores.</p>
    </div>
    <?php endif; ?>

  </section>
</main>

</body>
</html>
