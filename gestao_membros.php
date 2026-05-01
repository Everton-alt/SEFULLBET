<?php
session_start();
require_once 'config.php';

// 1. Proteção de Acesso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

if (!in_array($user['perfil'], ['Supervisor', 'Admin'])) {
    header("Location: dashboard.php");
    exit();
}

// 2. Lógica de Atualização (Processada na própria página)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_member') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $login = $_POST['login'];
    $email = $_POST['email'];
    $creditos = $_POST['creditos'];
    $perfil = $_POST['perfil'];
    $senha = $_POST['senha'];

    if (!empty($senha)) {
        // Se houver nova senha, atualiza tudo (recomenda-se password_hash se o seu sistema suportar)
        $sql = "UPDATE usuarios SET nome=?, login=?, email=?, senha=?, creditos=?, perfil=? WHERE id=?";
        $stmt_upd = $pdo->prepare($sql);
        $stmt_upd->execute([$nome, $login, $email, $senha, $creditos, $perfil, $id]);
    } else {
        // Se a senha estiver vazia, ignora a coluna de senha no update
        $sql = "UPDATE usuarios SET nome=?, login=?, email=?, creditos=?, perfil=? WHERE id=?";
        $stmt_upd = $pdo->prepare($sql);
        $stmt_upd->execute([$nome, $login, $email, $creditos, $perfil, $id]);
    }
    
    header("Location: gestao_membros.php?status=updated");
    exit();
}

// 3. Filtro de Busca
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = "";
$params = [];

if (!empty($search)) {
    $where_clause = " WHERE nome LIKE ? OR email LIKE ? OR login LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// 4. Paginação (10 por tela)
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$sql_count = "SELECT COUNT(*) FROM usuarios" . $where_clause;
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $itens_por_pagina);

$sql_membros = "SELECT * FROM usuarios" . $where_clause . " ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt_membros = $pdo->prepare($sql_membros);

// Bind dos parâmetros de busca + paginação
$idx = 1;
foreach ($params as $p) { $stmt_membros->bindValue($idx++, $p); }
$stmt_membros->bindValue($idx++, $itens_por_pagina, PDO::PARAM_INT);
$stmt_membros->bindValue($idx++, $offset, PDO::PARAM_INT);
$stmt_membros->execute();
$lista_membros = $stmt_membros->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Membros | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00ff88;
            --bg: #0d1117;
            --card: #161b22;
            --border: #30363d;
            --text-main: #f0f6fc;
            --text-dim: #8b949e;
            --vip: #ffd700;
            --danger: #ff4d4d;
            --info: #3498db;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text-main); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* SIDEBAR COMPLETA */
        nav { 
            width: 280px; background: rgba(22, 27, 34, 0.8); backdrop-filter: blur(10px);
            border-right: 1px solid var(--border); padding: 30px 15px;
            display: flex; flex-direction: column; position: fixed; height: 100vh;
            overflow-y: auto; z-index: 1000;
        }
        nav::-webkit-scrollbar { width: 4px; }
        nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

        .nav-logo { font-weight: 800; font-size: 1.6rem; letter-spacing: -1px; margin-bottom: 30px; text-align: center; }
        .nav-logo span { color: var(--primary); }
        .nav-group { margin-bottom: 25px; }
        .nav-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-left: 15px; margin-bottom: 8px; display: block; font-weight: 700; }
        .nav-btn { 
            color: var(--text-dim); padding: 12px 18px; border-radius: 12px; text-decoration: none; 
            display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 500;
            transition: 0.3s; margin-bottom: 2px;
        }
        .nav-btn:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .nav-btn.active { background: #065f46; color: var(--primary); border: 1px solid rgba(0, 255, 136, 0.2); }

        /* CONTEÚDO */
        main { flex: 1; margin-left: 280px; padding: 40px 60px; width: calc(100% - 280px); }

        .search-container { background: var(--card); border: 1px solid var(--border); padding: 25px; border-radius: 20px; margin-bottom: 30px; }
        .search-grid { display: flex; gap: 15px; align-items: flex-end; }
        .input-group { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .input-group label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; font-weight: 700; }
        .input-group input { 
            background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; outline: none; font-size: 13px;
        }

        .btn-pub { background: var(--primary); color: #0d1117; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-pub:hover { filter: brightness(1.1); box-shadow: 0 0 15px rgba(0, 255, 136, 0.3); }

        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(255,255,255,0.02); padding: 15px 20px; text-align: left; font-size: 11px; color: var(--text-dim); text-transform: uppercase; }
        td { padding: 15px 20px; border-bottom: 1px solid var(--border); font-size: 13px; }

        .pagination { display: flex; justify-content: center; gap: 10px; margin-top: 25px; }
        .page-link { padding: 8px 16px; background: var(--card); border: 1px solid var(--border); color: var(--text-main); text-decoration: none; border-radius: 8px; font-size: 13px; }
        .page-link.active { background: var(--primary); color: #000; font-weight: 700; border-color: var(--primary); }

        /* MODAL */
        #modalEditar {
            display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
            background:rgba(0,0,0,0.85); z-index:3000; align-items:center; justify-content:center;
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    <div class="nav-group">
        <span class="nav-label">Menu Principal</span>
        <a class="nav-btn" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
        <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
        <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
        <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>
        <hr style="border: 0; border-top: 1px solid var(--border); margin: 15px 10px;">
        <span class="nav-label">Gestão Administrativa</span>
        <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
        <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
        <a class="nav-btn" href="base_dados_ai.php"><i class="fas fa-file-import"></i> <span>Verificar Dados importados</span></a>
        <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
        <a class="nav-btn active" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
        <a class="nav-btn" href="gestao_noticias.php"><i class="fas fa-newspaper"></i> <span>Gestão de Notícias</span></a>
        <a class="nav-btn" href="gestao_notas.php"><i class="fas fa-edit"></i> <span>Gestão de Notas</span></a>
    </div>
    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <h1 style="font-weight: 800; margin-bottom: 30px;">Gestão de Membros</h1>

    <section class="search-container">
        <form method="GET" class="search-grid">
            <div class="input-group">
                <label>Buscar Membro</label>
                <input type="text" name="search" placeholder="Nome, E-mail ou Usuário..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-pub">Pesquisar</button>
            <?php if(!empty($search)): ?>
                <a href="gestao_membros.php" class="btn-pub" style="background:var(--border); color:#fff; text-decoration:none; display:flex; align-items:center;">Limpar</a>
            <?php endif; ?>
        </form>
    </section>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nome Completo</th>
                    <th>Usuário</th>
                    <th>E-mail</th>
                    <th>Senha</th>
                    <th>Data/Hora</th>
                    <th>Créditos</th>
                    <th>Perfil</th>
                    <th style="text-align:right">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($lista_membros as $m): ?>
                <tr>
                    <td><b><?= $m['nome'] ?></b></td>
                    <td><?= $m['login'] ?></td>
                    <td><?= $m['email'] ?></td>
                    <td style="color:var(--text-dim); font-size: 10px;">••••••••</td>
                    <td><?= date('d/m/Y', strtotime($m['data_cadastro'])) ?><br><small style="color:var(--text-dim)"><?= $m['hora_cadastro'] ?></small></td>
                    <td style="text-align:center"><b><?= $m['creditos'] ?></b></td>
                    <td><span style="color: <?= $m['perfil']=='VIP' ? 'var(--vip)' : 'var(--primary)' ?>; font-weight:700;"><?= $m['perfil'] ?></span></td>
                    <td style="text-align:right">
                        <a href="javascript:void(0)" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($m)) ?>)" title="Editar"><i class="fas fa-edit" style="color: var(--info); font-size:16px;"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <div class="pagination">
        <?php if($pagina_atual > 1): ?>
            <a href="?p=<?= $pagina_atual - 1 ?>&search=<?= $search ?>" class="page-link">Anterior</a>
        <?php endif; ?>
        
        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?p=<?= $i ?>&search=<?= $search ?>" class="page-link <?= ($i == $pagina_atual) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if($pagina_atual < $total_paginas): ?>
            <a href="?p=<?= $pagina_atual + 1 ?>&search=<?= $search ?>" class="page-link">Próxima</a>
        <?php endif; ?>
    </div>
</main>

<!-- MODAL DE EDIÇÃO -->
<div id="modalEditar">
    <div style="background:var(--card); width:95%; max-width:550px; padding:30px; border-radius:20px; border:1px solid var(--border);">
        <h2 style="margin-bottom:20px; display:flex; align-items:center; gap:10px;"><i class="fas fa-user-edit" style="color:var(--primary)"></i> Editar Membro</h2>
        
        <form action="gestao_membros.php" method="POST">
            <input type="hidden" name="action" value="update_member">
            <input type="hidden" name="id" id="edit_id">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="input-group" style="grid-column: span 2;">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" id="edit_nome" required>
                </div>
                <div class="input-group">
                    <label>Login (Usuário)</label>
                    <input type="text" name="login" id="edit_login" required>
                </div>
                <div class="input-group">
                    <label>E-mail</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="input-group">
                    <label>Alterar Senha (em branco para manter)</label>
                    <input type="text" name="senha" id="edit_senha" placeholder="Nova senha...">
                </div>
                <div class="input-group">
                    <label>Créditos</label>
                    <input type="number" name="creditos" id="edit_creditos" required>
                </div>
                <div class="input-group" style="grid-column: span 2;">
                    <label>Tipo de Perfil</label>
                    <select name="perfil" id="edit_perfil" style="width: 100%; background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px;">
                        <option value="Grátis">Grátis</option>
                        <option value="VIP">VIP</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:25px; display:flex; gap:10px;">
                <button type="submit" class="btn-pub" style="flex:1;">Salvar Alterações</button>
                <button type="button" onclick="fecharModal()" style="background:transparent; color:var(--text-dim); border:1px solid var(--border); padding:10px 20px; border-radius:12px; cursor:pointer;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalEditar(dados) {
    document.getElementById('edit_id').value = dados.id;
    document.getElementById('edit_nome').value = dados.nome;
    document.getElementById('edit_login').value = dados.login;
    document.getElementById('edit_email').value = dados.email;
    document.getElementById('edit_creditos').value = dados.creditos;
    document.getElementById('edit_perfil').value = dados.perfil;
    document.getElementById('edit_senha').value = ""; // Limpa campo de senha por segurança
    document.getElementById('modalEditar').style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modalEditar').style.display = 'none';
}

window.onclick = function(e) {
    if (e.target == document.getElementById('modalEditar')) fecharModal();
}
</script>

</body>
</html>
