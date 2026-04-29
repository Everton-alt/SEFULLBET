<?php
include 'config.php';
session_start();

// Trava de Segurança: Só Admin e Supervisor entram aqui
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['Admin', 'Supervisor'])) {
    die("Acesso negado. Área restrita a administradores.");
}

// Lógica de Aprovação
if (isset($_GET['aprovar_id'])) {
    $id = $_GET['aprovar_id'];
    $stmt = $pdo->prepare("UPDATE usuarios SET status_aprovacao = 'Ativo', saldo_creditos = 30 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin_aprova_usuarios.php?sucesso=1");
}

// Busca usuários aguardando
$stmt = $pdo->query("SELECT id, nome, login, perfil FROM usuarios WHERE status_aprovacao = 'Aguardando Aprovação'");
$pendentes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Aprovação de Membros | Sefullbet</title>
    <style>
        body { background: #080a0f; color: white; font-family: sans-serif; padding: 20px; }
        .card { background: #12151c; border: 1px solid #262c3a; padding: 15px; border-radius: 10px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .btn-aprovar { background: #00ff88; color: #000; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: bold; }
        .badge { color: #00ff88; border: 1px solid #00ff88; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <h1>Aprovações Pendentes</h1>
    
    <?php if (empty($pendentes)): ?>
        <p>Nenhum usuário aguardando aprovação no momento.</p>
    <?php endif; ?>

    <?php foreach ($pendentes as $u): ?>
        <div class="card">
            <div>
                <strong><?php echo $u['nome']; ?></strong> (<?php echo $u['login']; ?>) <br>
                <span class="badge"><?php echo $u['perfil']; ?></span>
            </div>
            <a href="?aprovar_id=<?php echo $u['id']; ?>" class="btn-aprovar">APROVAR ACESSO</a>
        </div>
    <?php endforeach; ?>
    
    <br>
    <a href="dashboard.php" style="color: #a0aec0;">← Voltar para Dashboard</a>
</body>
</html>
