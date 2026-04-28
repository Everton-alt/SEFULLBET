<?php
require_once '../config/db.php';
require_once '../modules/auth_logic.php';
require_once '../modules/palpites.php';
verificarLogin();

$stats_gratis = calcularEstatisticas($pdo, 'Grátis');
$stats_vip = calcularEstatisticas($pdo, 'VIP');
?>
<header class="main-header">
    <div class="logo">SEFULLBET</div>
    <div class="user-info">
        <span class="badge"><?php echo $_SESSION['usuario_perfil']; ?></span>
        <span class="credits">Créditos: <?php echo $_SESSION['usuario_creditos']; ?></span>
        <?php if($_SESSION['usuario_perfil'] == 'Admin' || $_SESSION['usuario_perfil'] == 'Supervisor'): ?>
            <a href="../admin/index.php" class="btn-adm">Painel Geral</a>
        <?php endif; ?>
    </div>
</header>

<main class="container">
    <div class="hero-section">
        <button onclick="abrirAnalisador()" class="btn-neon-pulse">ACESSAR ANALISADOR SEFULLBET</button>
    </div>

    <section class="stats-bar">
        <div>Grátis: <?php echo $stats_gratis['win_rate']; ?>% Win</div>
        <div>VIP: <?php echo $stats_vip['win_rate']; ?>% Win</div>
    </section>

    <section class="palpites-horizontal">
        </section>
</main>