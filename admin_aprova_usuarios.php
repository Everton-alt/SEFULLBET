<?php
include 'config.php';
session_start();

// 1. Trava de Segurança: Só Admin e Supervisor entram aqui
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['Admin', 'Supervisor'])) {
    die("Acesso negado. Área restrita a administradores.");
}

// 2. Lógica de Aprovação
if (isset($_GET['aprovar_id'])) {
    $id = $_GET['aprovar_id'];
    
    // Atualiza para 'Ativo' e define os 30 créditos iniciais (conforme Escopo 1)
    $stmt = $pdo->prepare("UPDATE usuarios SET status_aprovacao = 'Ativo', saldo_creditos = 30 WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: admin_aprova_usuarios.php?sucesso=1");
    exit();
}

// 3. Busca usuários aguardando (Ordenados pelo ID mais recente para facilitar a gestão)
$stmt = $pdo->query("SELECT id, nome, login, perfil FROM usuarios WHERE status_aprovacao = 'Aguardando Aprovação' ORDER BY id DESC");
$pendentes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovação de Membros | Sefullbet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #00ff88;
            --bg: #080a0f;
            --card: #12151c;
            --border: #262c3a;
        }

        body { 
            background: var(--bg); 
            color: white; 
            font-family: 'Segoe UI', sans-serif; 
            padding: 20px; 
            margin: 0;
        }

        .container { max-width: 800px; margin: 0 auto; }

        h1 { color: var(--primary); font-weight: 900; }

        .card { 
            background: var(--card); 
            border: 1px solid var(--border); 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 12px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            transition: 0.3s;
        }

        .card:hover { border-color: var(--primary); }

        .user-info strong { display: block; font-size: 1.1rem; }

        .badge { 
            color: var(--primary); 
            border: 1px solid var(--primary); 
            padding: 2px 8px; 
            border-radius: 4px; 
            font-size: 0.75rem; 
            text-transform: uppercase;
        }

        .btn-aprovar { 
            background: var(--primary); 
            color: #000; 
            padding: 10px 20px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: bold;
            font-size: 0.9rem;
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            color: var(--primary);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid var(--primary);
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Aprovações Pendentes</h1>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert-success"><i class="fas fa-check"></i> Usuário aprovado com sucesso!</div>
    <?php endif; ?>
    
    <?php if (empty($pendentes)): ?>
        <p style="color: #a0aec0;">Nenhum usuário aguardando aprovação no momento.</p>
    <?php else: ?>
        <?php foreach ($pendentes as $u): ?>
            <div class="card">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($u['nome']); ?></strong>
                    <span style="color: #a0aec0; font-size: 0.9rem;"><?php echo htmlspecialchars($u['login']); ?></span>
                    <div style="margin-top: 10px;">
                        <span class="badge"><?php echo $u['perfil']; ?></span>
                    </div>
                </div>
                <a href="?aprovar_id=<?php echo $u['id']; ?>" class="btn-aprovar">
                    <i class="fas fa-check"></i> APROVAR
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <br>
    <a href="dashboard.php" style="color: #a0aec0; text-decoration: none;">
        <i class="fas fa-arrow-left"></i> Voltar para Dashboard
    </a>
</div>

</body>
</html>
