<?php
session_start();
require_once 'config.php';

// 1. Verificação de Login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Processar Troca de Senha (POST)
$msg_feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'])) {
    if (strlen($_POST['nova_senha']) >= 6) {
        $nova_senha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        if ($upd->execute([$nova_senha, $_SESSION['usuario_id']])) {
            $msg_feedback = 'success';
        } else {
            $msg_feedback = 'error';
        }
    } else {
        $msg_feedback = 'curta';
    }
}

// 3. Busca dados atualizados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

$perfil = $user['perfil']; 

// 4. Lógica de Dias Restantes (Apenas para VIP, Platinum, Supervisor, Admin)
$exibir_dias = in_array($perfil, ['VIP', 'Platinum', 'Supervisor', 'Admin']);
$dias_restantes = "---";

if ($exibir_dias && !empty($user['data_validade'])) {
    $data_venc = new DateTime($user['data_validade']);
    $hoje = new DateTime(date('Y-m-d'));
    
    if ($hoje > $data_venc) {
        $dias_restantes = "Expirado";
    } else {
        $diff = $hoje->diff($data_venc);
        $dias_restantes = $diff->days . " dias";
    }
}

// 5. Estatísticas Dinâmicas (Mantidas para espelhar o layout)
function getStats($pdo, $cat) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, 
        SUM(CASE WHEN p_status = 'Green' THEN 1 ELSE 0 END) as greens,
        SUM(CASE WHEN p_status = 'Red' THEN 1 ELSE 0 END) as reds
        FROM sinais WHERE p_categoria = ?");
    $stmt->execute([$cat]);
    return $stmt->fetch();
}
$stat_g = getStats($pdo, 'Grátis');
$stat_v = getStats($pdo, 'VIP');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefullbet - Meu Perfil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2ECC71; 
            --bg-body: #ffffff; 
            --bg-secondary: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #2d3436;
            --text-dim: #636e72;
            --accent-blue: #0984e3;
            --danger: #d63031;
            --warning: #f1c40f;
            --border: #f1f1f1;
        }

        body { font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; background-color: var(--bg-body); color: var(--text-main); padding-bottom: 50px; }

        /* SIDEBAR (ESPELHADO) */
        .sidebar { height: 100%; width: 280px; position: fixed; z-index: 2000; top: 0; left: -280px; background-color: #2d3436; transition: 0.4s; padding-top: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.1); }
        .sidebar .nav-btn { padding: 12px 25px; text-decoration: none; font-size: 15px; color: #b2bec3; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #3d4648; transition: 0.3s; }
        .sidebar .nav-btn:hover, .sidebar .nav-btn.active { background: #3d4648; color: var(--primary); }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 30px; cursor: pointer; color: var(--primary); }
        .nav-label { color: var(--primary); font-size: 11px; text-transform: uppercase; padding: 15px 25px 5px; display: block; font-weight: 800; }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }

        /* HEADER (ESPELHADO) */
        header { background: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #eee; }
        .menu-icon { font-size: 24px; cursor: pointer; color: #2d3436; }
        .logo { font-weight: 900; font-size: 1.3rem; color: #2d3436; }
        .logo span { color: var(--primary); }

        /* STATS (ESPELHADO) */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 15px 10px 5px; }
        .stat-card { background: var(--card-bg); padding: 12px; border-radius: 15px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #f1f1f1; border-top: 4px solid var(--primary); }
        .stat-card.vip { border-top-color: var(--warning); }
        .stat-value { font-size: 1.4rem; font-weight: bold; }

        /* PERFIL CARD */
        .section-title { padding: 20px 15px 10px; font-size: 0.85rem; font-weight: 800; color: #2d3436; text-transform: uppercase; display: flex; align-items: center; gap: 8px; }
        .content-container { padding: 0 10px; }
        .profile-card { background: #fff; border-radius: 20px; padding: 25px; border: 1px solid var(--border); box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        
        .info-row { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #f9f9f9; }
        .info-row label { display: block; font-size: 0.65rem; text-transform: uppercase; font-weight: 800; color: var(--text-dim); margin-bottom: 4px; }
        .info-row span { font-size: 0.95rem; font-weight: 600; color: var(--text-main); }

        /* FORMULÁRIO */
        .pw-section { margin-top: 30px; background: #f8f9fa; padding: 20px; border-radius: 15px; }
        .input-group { margin-bottom: 15px; }
        .input-group label { font-size: 0.8rem; font-weight: 700; display: block; margin-bottom: 8px; }
        .input-group input { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #ddd; box-sizing: border-box; font-family: inherit; }
        
        .btn-update { background: var(--primary); color: #fff; border: none; padding: 15px; border-radius: 12px; width: 100%; font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-update:hover { filter: brightness(1.1); }

        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; text-align: center; }
        .alert-success { background: #eafaf1; color: #27ae60; border: 1px solid #2ecc71; }
        .alert-error { background: #fdf2f2; color: #e74c3c; border: 1px solid #d63031; }

        footer { text-align: center; padding: 40px 20px; font-size: 0.75rem; color: #b2bec3; background: #f8f9fa; margin-top: 30px; border-top: 1px solid #eee; }
    </style>
</head>
<body>

<div id="overlay" class="overlay" onClick="closeNav()"></div>

<div id="mySidebar" class="sidebar">
    <span class="close-btn" onClick="closeNav()">&times;</span>
    <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Início</span></a>
    <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
    <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
    <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
    <a class="nav-btn active" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
    <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
    <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>
    
    <?php if (in_array($perfil, ['Supervisor', 'Admin'])): ?>
        <hr style="border: 0; border-top: 1px solid #3d4648; margin: 15px 10px;">
        <span class="nav-label">Gestão Administrativa</span>
        <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
    <?php endif; ?>
    
    <a href="logout.php" class="nav-btn logout-btn" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Sair</a>
</div>

<header>
    <div class="menu-icon" onClick="openNav()">☰</div>
    <div class="logo">SEFULL<span>BET</span></div>
    
    <div style="text-align: right; line-height: 1.2;">
        <div style="font-size: 14px; font-weight: 800;">Olá, <?= explode(' ', $user['nome'])[0] ?></div>
        <div style="font-size: 11px; font-weight: 700; color: var(--primary);">
            <?= htmlspecialchars($user['plano_interesse']) ?>
        </div>
    </div>
</header>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $stat_g['total'] > 0 ? round(($stat_g['greens']/$stat_g['total'])*100) : 0 ?>%</div>
        <div style="font-size: 10px; color: var(--text-dim);">ACERTO GRÁTIS</div>
    </div>
    <div class="stat-card vip">
        <div class="stat-value" style="color: var(--warning);"><?= $stat_v['total'] > 0 ? round(($stat_v['greens']/$stat_v['total'])*100) : 0 ?>%</div>
        <div style="font-size: 10px; color: var(--text-dim);">ACERTO VIP</div>
    </div>
</div>

<div class="section-title"><i class="fas fa-user-shield"></i> Dados do Perfil</div>

<div class="content-container">
    <div class="profile-card">
        
        <?php if($msg_feedback == 'success'): ?>
            <div class="alert alert-success">Senha atualizada com sucesso!</div>
        <?php elseif($msg_feedback == 'error'): ?>
            <div class="alert alert-error">Erro ao atualizar. Tente novamente.</div>
        <?php elseif($msg_feedback == 'curta'): ?>
            <div class="alert alert-error">A senha deve ter pelo menos 6 caracteres.</div>
        <?php endif; ?>

        <div class="info-row">
            <label>Nome Completo</label>
            <span><?= htmlspecialchars($user['nome']) ?></span>
        </div>

        <div class="info-row">
            <label>Login de Usuário</label>
            <span><?= htmlspecialchars($user['login']) ?></span>
        </div>

        <div class="info-row">
            <label>E-mail</label>
            <span><?= htmlspecialchars($user['email']) ?></span>
        </div>

        <div class="info-row">
            <label>Nível da Conta</label>
            <span style="color: var(--accent-blue)"><?= strtoupper($user['perfil']) ?></span>
        </div>

        <?php if($exibir_dias): ?>
        <div class="info-row" style="background: #fff8e1; padding: 10px; border-radius: 10px; border-bottom: none;">
            <label style="color: #f57f17;">Tempo Restante de Plano</label>
            <span style="font-size: 1.1rem; color: #e65100;"><?= $dias_restantes ?></span>
        </div>
        <?php endif; ?>

        <div class="pw-section">
            <h4 style="margin: 0 0 15px 0; font-size: 0.9rem;">Segurança</h4>
            <form method="POST">
                <div class="input-group">
                    <label>Alterar Senha</label>
                    <input type="password" name="nova_senha" placeholder="Digite a nova senha" required>
                </div>
                <button type="submit" class="btn-update">Salvar Nova Senha</button>
            </form>
        </div>
    </div>
</div>

<footer>
    <span style="font-weight: 900; color: #2d3436; letter-spacing: 1px;">SEFULLBET</span><br>
    © 2026 SeFullBet - Área Privada do Usuário.
</footer>

<script>
    function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
    function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }
</script>

</body>
</html>
