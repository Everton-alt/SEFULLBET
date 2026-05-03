<?php
session_start();
require_once 'config.php';

// 1. Verificação de Login e Nível de Acesso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

$perfil = $user['perfil']; 
if (!in_array($perfil, ['Supervisor', 'Admin'])) {
    header("Location: dashboard.php");
    exit();
}

// 2. Lógica de Banco de Dados (Criar tabela se não existir)
$pdo->exec("CREATE TABLE IF NOT EXISTS vitorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    foto_principal TEXT,
    foto_miniatura TEXT,
    texto_completo TEXT,
    data_publicacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 3. Processamento de Ações (Excluir)
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM vitorias WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: gestao_vitorias.php?msg=sucesso_del");
    exit();
}

// 4. Processamento de Ações (Publicar)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_publicar'])) {
    $titulo = $_POST['titulo'];
    $foto1 = $_POST['foto1'];
    $foto2 = $_POST['foto2'];
    $texto = $_POST['texto'];

    $stmt = $pdo->prepare("INSERT INTO vitorias (titulo, foto_principal, foto_miniatura, texto_completo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$titulo, $foto1, $foto2, $texto]);
    $msg_sucesso = "Vitória publicada com sucesso!";
}

// 5. Busca as últimas 10 para o painel de gestão
$vitorias_gestao = $pdo->query("SELECT * FROM vitorias ORDER BY id DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefullbet - Gestão de Vitórias</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2ECC71; --bg-body: #ffffff; --bg-secondary: #f8f9fa;
            --card-bg: #ffffff; --text-main: #2d3436; --text-dim: #636e72;
            --accent-blue: #0984e3; --danger: #d63031; --warning: #f1c40f; --border: #f1f1f1;
        }

        body { font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; background-color: var(--bg-body); color: var(--text-main); padding-bottom: 50px; }

        /* --- SIDEBAR & HEADER (ESTRUTURA SOLICITADA) --- */
        .sidebar { height: 100%; width: 280px; position: fixed; z-index: 2000; top: 0; left: -280px; background-color: #2d3436; overflow-x: hidden; transition: 0.4s; padding-top: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.1); }
        .sidebar .nav-btn { padding: 12px 25px; text-decoration: none; font-size: 15px; color: #b2bec3; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #3d4648; transition: 0.3s; }
        .sidebar .nav-btn i { width: 20px; text-align: center; }
        .sidebar .nav-btn:hover, .sidebar .nav-btn.active { background: #3d4648; color: var(--primary); }
        .sidebar .logout-btn { color: #ff7675 !important; font-weight: bold; border-bottom: none !important; }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 30px; cursor: pointer; color: var(--primary); z-index: 2001; }
        .nav-label { color: var(--primary); font-size: 11px; text-transform: uppercase; padding: 15px 25px 5px; display: block; font-weight: 800; letter-spacing: 1px; }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }

        header { background-color: #ffffff; color: var(--primary); padding: 15px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #eee; }
        .menu-icon { font-size: 24px; cursor: pointer; color: #2d3436; }
        .logo { font-weight: 900; font-size: 1.3rem; letter-spacing: 1px; color: #2d3436; }
        .logo span { color: var(--primary); }

        /* --- CONTEÚDO DA GESTÃO --- */
        .admin-content { padding: 20px 15px; }
        .section-title { font-size: 0.85rem; font-weight: 800; color: #2d3436; text-transform: uppercase; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        
        .card-admin { background: var(--card-bg); border-radius: 15px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid var(--border); margin-bottom: 25px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-dim); margin-bottom: 5px; text-transform: uppercase; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; font-family: inherit; box-sizing: border-box; }
        textarea.form-control { height: 100px; resize: none; }
        
        .btn-submit { background: var(--primary); color: #fff; border: none; padding: 15px; border-radius: 10px; font-weight: 800; width: 100%; cursor: pointer; text-transform: uppercase; box-shadow: 0 5px 15px rgba(46, 204, 113, 0.2); }
        
        /* Tabela de Gestão */
        .table-vitoria { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .table-vitoria th { text-align: left; padding: 12px 8px; color: var(--text-dim); border-bottom: 2px solid var(--border); }
        .table-vitoria td { padding: 12px 8px; border-bottom: 1px solid var(--border); }
        .mini-thumb { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; background: #eee; }
        
        .btn-tool { padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 0.75rem; font-weight: bold; }
        .btn-del { background: #fdf2f2; color: var(--danger); }
        .alert-success { background: #eafaf1; color: var(--primary); padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; text-align: center; }
    </style>
</head>
<body>

    <div id="overlay" class="overlay" onClick="closeNav()"></div>

    <!-- SIDEBAR -->
    <div id="mySidebar" class="sidebar">
        <span class="close-btn" onClick="closeNav()">&times;</span>
        <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Início</span></a>
        <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
        
        <?php if (in_array($perfil, ['Supervisor', 'Admin'])): ?>
            <hr style="border: 0; border-top: 1px solid #3d4648; margin: 15px 10px;">
            <span class="nav-label">Gestão Administrativa</span>
            <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
            <a class="nav-btn active" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
            <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
        <?php endif; ?>
        <a href="logout.php" class="nav-btn logout-btn" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </div>

    <!-- HEADER -->
    <header>
        <div class="menu-icon" onClick="openNav()">☰</div>
        <div class="logo">SEFULL<span>BET</span></div>
        <div style="font-size: 14px; font-weight: 800; color: var(--text-dim)">Admin: <?= explode(' ', $user['nome'])[0] ?></div>
    </header>

    <div class="admin-content">
        <?php if(isset($msg_sucesso)): ?>
            <div class="alert-success"><?= $msg_sucesso ?></div>
        <?php endif; ?>

        <div class="section-title"><i class="fas fa-plus-circle"></i> Nova Publicação de Vitória</div>
        <div class="card-admin">
            <form method="POST">
                <div class="form-group">
                    <label>Título da Vitória</label>
                    <input type="text" name="titulo" class="form-control" placeholder="Ex: Green Absurdo na Bundesliga!" required>
                </div>
                <div class="form-group">
                    <label>URL Foto Principal (Dashboard)</label>
                    <input type="url" name="foto1" class="form-control" placeholder="https://imagem.com/foto.jpg">
                </div>
                <div class="form-group">
                    <label>URL Foto 2 (Miniatura Gestão)</label>
                    <input type="url" name="foto2" class="form-control" placeholder="https://imagem.com/thumb.jpg">
                </div>
                <div class="form-group">
                    <label>Texto da Publicação</label>
                    <textarea name="texto" class="form-control" placeholder="Conte como foi essa vitória..."></textarea>
                </div>
                <button type="submit" name="btn_publicar" class="btn-submit">PUBLICAR AGORA</button>
            </form>
        </div>

        <div class="section-title"><i class="fas fa-tasks"></i> Gerenciar Últimas 10</div>
        <div class="card-admin" style="padding: 10px;">
            <table class="table-vitoria">
                <thead>
                    <tr>
                        <th>Capa</th>
                        <th>Título</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($vitorias_gestao as $v): ?>
                    <tr>
                        <td><img src="<?= $v['foto_miniatura'] ?>" class="mini-thumb" onerror="this.src='https://via.placeholder.com/40'"></td>
                        <td><strong><?= substr($v['titulo'], 0, 25) ?>...</strong></td>
                        <td>
                            <a href="?delete=<?= $v['id'] ?>" class="btn-tool btn-del" onclick="return confirm('Apagar permanentemente?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <strong>SEFULLBET ADMIN &copy; 2026</strong>
    </footer>

    <script>
        function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
        function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }
    </script>
</body>
</html>
