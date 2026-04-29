<?php
session_start();
require_once 'config.php';

// SEGURANÇA: Só deixa entrar se for Admin ou Supervisor
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['Admin', 'Supervisor'])) {
    die("Acesso restrito aos administradores.");
}

// Lógica para Aprovar ou Excluir
if (isset($_GET['acao']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    if ($_GET['acao'] == 'aprovar') {
        $stmt = $pdo->prepare("UPDATE usuarios SET status_aprovacao = 'Ativo' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($_GET['acao'] == 'excluir') {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: admin_aprova_usuarios.php");
    exit();
}

// Busca usuários pendentes
$stmt = $pdo->query("SELECT id, nome, login, perfil, criado_em FROM usuarios WHERE status_aprovacao = 'Aguardando Aprovação' ORDER BY criado_em DESC");
$pendentes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel de Aprovação - Sefullbet</title>
    <style>
        body { background: #080a0f; color: white; font-family: sans-serif; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        h2 { color: #00ff88; border-bottom: 2px solid #262c3a; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; background: #12151c; border-radius: 10px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #262c3a; }
        th { background: #1c222d; color: #00ff88; }
        .btn { padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.8rem; font-weight: bold; }
        .btn-aprovar { background: #00ff88; color: #000; }
        .btn-excluir { background: #ff4d4d; color: white; margin-left: 10px; }
        .vazio { text-align: center; padding: 40px; color: #a0aec0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Solicitações de Acesso</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Usuário</th>
                    <th>Plano</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pendentes) > 0): ?>
                    <?php foreach ($pendentes as $user): ?>
                        <tr>
                            <td><?php echo $user['nome']; ?></td>
                            <td><?php echo $user['login']; ?></td>
                            <td><?php echo $user['perfil']; ?></td>
                            <td>
                                <a href="?acao=aprovar&id=<?php echo $user['id']; ?>" class="btn btn-aprovar">APROVAR</a>
                                <a href="?acao=excluir&id=<?php echo $user['id']; ?>" class="btn btn-excluir" onclick="return confirm('Deseja recusar este cadastro?')">RECUSAR</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="vazio">Nenhuma solicitação pendente no momento.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <br>
        <a href="dashboard.php" style="color: #a0aec0; text-decoration: none;">← Voltar para Dashboard</a>
    </div>
</body>
</html>
