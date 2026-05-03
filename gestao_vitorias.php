<?php
session_start();
require_once 'config.php';

// 1. Verificação de Login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Busca dados atualizados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

// 3. Verificação de Permissão (Apenas Supervisor e Admin)
$perfil = $user['perfil']; 
if (!in_array($perfil, ['Supervisor', 'Admin'])) {
    header("Location: dashboard.php");
    exit();
}

// 4. Lógica de Banco de Dados (PostgreSQL)
$pdo->exec("CREATE TABLE IF NOT EXISTS v_vitorias (
    v_id SERIAL PRIMARY KEY,
    v_titulo VARCHAR(255) NOT NULL,
    v_foto_principal TEXT,
    v_foto_miniatura TEXT,
    v_texto_completo TEXT,
    v_data_publicacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Processamento de Ações (Excluir)
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM v_vitorias WHERE v_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: gestao_vitorias.php?msg=sucesso_del");
    exit();
}

// Processamento de Ações (Publicar Nova)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_publicar'])) {
    $stmt = $pdo->prepare("INSERT INTO v_vitorias (v_titulo, v_foto_principal, v_foto_miniatura, v_texto_completo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['titulo'], $_POST['foto1'], $_POST['foto2'], $_POST['texto']]);
    $msg_sucesso = "Vitória publicada com sucesso!";
}

// Processamento de Ações (EDITAR / ATUALIZAR - via Popup)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_editar'])) {
    $stmt = $pdo->prepare("UPDATE v_vitorias SET v_titulo = ?, v_foto_principal = ?, v_foto_miniatura = ?, v_texto_completo = ? WHERE v_id = ?");
    $stmt->execute([$_POST['edit_titulo'], $_POST['edit_foto1'], $_POST['edit_foto2'], $_POST['edit_texto'], $_POST['edit_id']]);
    $msg_sucesso = "Alterações salvas com sucesso!";
}

// Busca as postagens
$vitorias_gestao = $pdo->query("SELECT * FROM v_vitorias ORDER BY v_id DESC LIMIT 50")->fetchAll();
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

        /* --- SIDEBAR & HEADER --- */
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

        /* --- CONTEÚDO --- */
        .admin-content { padding: 15px; max-width: 1200px; margin: auto; }
        .section-title { padding: 20px 0 10px; font-size: 0.95rem; font-weight: 800; color: #2d3436; text-transform: uppercase; }
        
        .card-admin { background: var(--card-bg); border-radius: 15px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #f1f1f1; margin-bottom: 25px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
        
        .table-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.85rem; box-sizing: border-box; background: var(--bg-secondary); color: var(--text-main); font-family: inherit; }
        .table-input:focus { outline: none; border-color: var(--primary); background: #fff; }
        .table-textarea { width: 100%; height: 80px; resize: vertical; }
        
        .btn-publicar { background: linear-gradient(45deg, #2ecc71, #27ae60); color: #fff; padding: 15px; border-radius: 10px; border: none; font-weight: 800; width: 100%; text-transform: uppercase; cursor: pointer; box-shadow: 0 4px 10px rgba(46, 204, 113, 0.2); grid-column: 1 / -1; }
        
        /* --- TABELA DE VISUALIZAÇÃO --- */
        .table-container { overflow-x: auto; border-radius: 8px; border: 1px solid var(--border); }
        .table-view { width: 100%; border-collapse: collapse; min-width: 700px; background: #fff; }
        .table-view th { background: var(--bg-secondary); padding: 15px; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-dim); border-bottom: 2px solid var(--border); }
        .table-view td { padding: 12px 15px; border-bottom: 1px solid var(--border); vertical-align: middle; font-size: 0.85rem; }
        .mini-img { width: 45px; height: 45px; border-radius: 8px; object-fit: cover; background: #eee; border: 1px solid #ddd; }
        
        .btn-action { color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.3s; text-decoration: none; display: inline-block; font-size: 0.8rem; }
        .btn-edit { background: var(--accent-blue); }
        .btn-del { background: var(--danger); }
        .btn-action:hover { opacity: 0.8; }
        
        .alert-success { background: #eafaf1; color: #27ae60; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: bold; border-left: 5px solid #27ae60; }
        
        /* --- ESTILOS DO MODAL / POPUP --- */
        .modal-bg { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 3000; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-box { background: #fff; width: 90%; max-width: 600px; border-radius: 15px; padding: 25px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: popIn 0.3s ease; }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        .modal-title { font-weight: 800; color: #2d3436; font-size: 1.1rem; }
        .close-modal { font-size: 24px; color: var(--danger); cursor: pointer; background: none; border: none; }
        .btn-salvar-modal { background: var(--accent-blue); color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 10px; font-size: 0.9rem; text-transform: uppercase; }

        footer { text-align: center; padding: 40px 20px; font-size: 0.75rem; color: #b2bec3; background: #f8f9fa; margin-top: 30px; }
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
        <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
        
        <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>

        <?php if (in_array($perfil, ['Supervisor', 'Admin'])): ?>
            <hr style="border: 0; border-top: 1px solid #3d4648; margin: 15px 10px;">
            <span class="nav-label">Gestão Administrativa</span>
            <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
            <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
            <a class="nav-btn" href="base_dados_ai.php"><i class="fas fa-database"></i> <span>Verificar Dados AI</span></a>
            <a class="nav-btn active" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
            <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
            <a class="nav-btn" href="gestao_noticias.php"><i class="fas fa-newspaper"></i> <span>Gestão de Notícias</span></a>
            <a class="nav-btn" href="gestao_notas.php"><i class="fas fa-edit"></i> <span>Gestão de Notas</span></a>
        <?php endif; ?>

        <a href="logout.php" class="nav-btn logout-btn" style="margin-top: 20px;"><i class="fas fa-sign-out-alt"></i> Sair da Conta</a>
    </div>

    <header>
        <div class="menu-icon" onClick="openNav()">☰</div>
        <div class="logo">SEFULL<span>BET</span></div>
        <div style="font-size: 14px; font-weight: 800; color: var(--text-dim)">Admin: <?= explode(' ', $user['nome'])[0] ?></div>
    </header>

    <div class="admin-content">
        
        <?php if(isset($msg_sucesso) || isset($_GET['msg'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> 
                <?= isset($msg_sucesso) ? $msg_sucesso : "Ação realizada com sucesso!" ?>
            </div>
        <?php endif; ?>

        <!-- FORMULÁRIO DE NOVA PUBLICAÇÃO -->
        <div class="section-title">Publicar Nova Vitória</div>
        <div class="card-admin">
            <form method="POST" class="form-grid">
                <input type="text" name="titulo" class="table-input" placeholder="Título da Postagem" required style="grid-column: 1 / -1;">
                <input type="url" name="foto1" class="table-input" placeholder="Link da Foto Principal (Ex: Imgur)">
                <input type="url" name="foto2" class="table-input" placeholder="Link da Foto Miniatura">
                <textarea name="texto" class="table-input table-textarea" placeholder="Digite a história da vitória..." style="grid-column: 1 / -1;"></textarea>
                <button type="submit" name="btn_publicar" class="btn-publicar"><i class="fas fa-paper-plane"></i> Publicar Conteúdo</button>
            </form>
        </div>

        <!-- TABELA DE VISUALIZAÇÃO -->
        <div class="section-title">Gerenciar Postagens</div>
        <div class="card-admin" style="padding: 0; overflow: hidden;">
            <div class="table-container">
                <table class="table-view">
                    <thead>
                        <tr>
                            <th width="40">ID</th>
                            <th width="60">Capa</th>
                            <th>Título</th>
                            <th>Resumo do Texto</th>
                            <th width="120" style="text-align:center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vitorias_gestao as $v): ?>
                        <tr>
                            <td><strong>#<?= $v['v_id'] ?></strong></td>
                            <td>
                                <img src="<?= $v['v_foto_miniatura'] ?: 'https://via.placeholder.com/45?text=Sem+Foto' ?>" class="mini-img" alt="Capa">
                            </td>
                            <td><?= htmlspecialchars(mb_strimwidth($v['v_titulo'], 0, 40, "...")) ?></td>
                            <td style="color: var(--text-dim)"><?= htmlspecialchars(mb_strimwidth($v['v_texto_completo'], 0, 50, "...")) ?></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <!-- Botão que aciona o JS passando os dados via Data Attributes -->
                                <button type="button" class="btn-action btn-edit" title="Editar em Popup"
                                    data-id="<?= $v['v_id'] ?>"
                                    data-titulo="<?= htmlspecialchars($v['v_titulo'], ENT_QUOTES) ?>"
                                    data-foto1="<?= htmlspecialchars($v['v_foto_principal'], ENT_QUOTES) ?>"
                                    data-foto2="<?= htmlspecialchars($v['v_foto_miniatura'], ENT_QUOTES) ?>"
                                    data-texto="<?= htmlspecialchars($v['v_texto_completo'], ENT_QUOTES) ?>"
                                    onclick="abrirModalEdit(this)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <a href="?delete=<?= $v['v_id'] ?>" class="btn-action btn-del" onclick="return confirm('Tem certeza que deseja excluir?')" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($vitorias_gestao) == 0): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-dim);">Nenhuma vitória publicada ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ESTRUTURA DO MODAL (POPUP) OCULTO -->
    <div id="modalEdicao" class="modal-bg">
        <div class="modal-box">
            <div class="modal-header">
                <div class="modal-title"><i class="fas fa-edit" style="color: var(--accent-blue)"></i> Editar Vitória <span id="modal-id-label"></span></div>
                <button class="close-modal" onclick="fecharModalEdit()">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="edit_id" id="modal_edit_id">
                
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 12px; font-weight: bold; color: var(--text-dim)">Título</label>
                    <input type="text" name="edit_titulo" id="modal_edit_titulo" class="table-input" required>
                </div>
                
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 12px; font-weight: bold; color: var(--text-dim)">Link Foto Principal</label>
                    <input type="url" name="edit_foto1" id="modal_edit_foto1" class="table-input">
                </div>
                
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 12px; font-weight: bold; color: var(--text-dim)">Link Foto Miniatura</label>
                    <input type="url" name="edit_foto2" id="modal_edit_foto2" class="table-input">
                </div>
                
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 12px; font-weight: bold; color: var(--text-dim)">Texto Completo</label>
                    <textarea name="edit_texto" id="modal_edit_texto" class="table-input table-textarea"></textarea>
                </div>
                
                <button type="submit" name="btn_editar" class="btn-salvar-modal"><i class="fas fa-save"></i> Salvar Alterações</button>
            </form>
        </div>
    </div>

    <footer>
        <strong>SEFULLBET PRO &copy; 2026</strong><br>
        Plataforma de Inteligência e Análise Esportiva.
    </footer>

    <script>
        // Funções da Sidebar
        function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
        function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }
        
        // Funções do Modal de Edição
        function abrirModalEdit(botao) {
            // Pega os dados do botão clicado
            let id = botao.getAttribute('data-id');
            let titulo = botao.getAttribute('data-titulo');
            let foto1 = botao.getAttribute('data-foto1');
            let foto2 = botao.getAttribute('data-foto2');
            let texto = botao.getAttribute('data-texto');

            // Preenche o formulário do Modal
            document.getElementById('modal-id-label').innerText = "#" + id;
            document.getElementById('modal_edit_id').value = id;
            document.getElementById('modal_edit_titulo').value = titulo;
            document.getElementById('modal_edit_foto1').value = foto1;
            document.getElementById('modal_edit_foto2').value = foto2;
            document.getElementById('modal_edit_texto').value = texto;

            // Mostra o Popup
            document.getElementById('modalEdicao').style.display = 'flex';
        }

        function fecharModalEdit() {
            // Esconde o Popup
            document.getElementById('modalEdicao').style.display = 'none';
        }

        // Fechar popup clicando no fundo escuro
        window.onclick = function(event) {
            let modal = document.getElementById('modalEdicao');
            if (event.target == modal) {
                fecharModalEdit();
            }
        }
    </script>
</body>
</html>
