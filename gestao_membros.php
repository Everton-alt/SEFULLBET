<?php
session_start();
require_once 'config.php';

// 1. Proteção de Acesso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT perfil FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();

if (!$user || !in_array($user['perfil'], ['Supervisor', 'Admin'])) {
    header("Location: dashboard.php");
    exit();
}

// 2. Lógica de Ações (Update e Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // AÇÃO: ATUALIZAR
    if ($_POST['action'] === 'update_member') {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $login = $_POST['login'];
        $email = $_POST['email'];
        $creditos = (int)$_POST['saldo_creditos']; // Ajustado para bater com o HTML
        $perfil = $_POST['perfil'];
        $plano_interesse = $_POST['plano_interesse'];
        $senha = $_POST['senha'];

        if (!empty($senha)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nome=?, login=?, email=?, senha=?, saldo_creditos=?, perfil=?, plano_interesse=? WHERE id=?";
            $stmt_upd = $pdo->prepare($sql);
            $stmt_upd->execute([$nome, $login, $email, $senha_hash, $creditos, $perfil, $plano_interesse, $id]);
        } else {
            $sql = "UPDATE usuarios SET nome=?, login=?, email=?, saldo_creditos=?, perfil=?, plano_interesse=? WHERE id=?";
            $stmt_upd = $pdo->prepare($sql);
            $stmt_upd->execute([$nome, $login, $email, $creditos, $perfil, $plano_interesse, $id]);
        }
        header("Location: gestao_membros.php?status=updated");
        exit();
    }

    // AÇÃO: EXCLUIR
    if ($_POST['action'] === 'delete_member') {
        $id = $_POST['id'];
        if ($id != $_SESSION['usuario_id']) {
            $stmt_del = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt_del->execute([$id]);
            header("Location: gestao_membros.php?status=deleted");
        } else {
            header("Location: gestao_membros.php?status=error_self");
        }
        exit();
    }
}

// 3. Filtro de Busca
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "";
$params = [];
if (!empty($search)) {
    $where_clause = " WHERE nome LIKE ? OR email LIKE ? OR login LIKE ? OR plano_interesse LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

// 4. Paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = (max(1, $pagina_atual) - 1) * $itens_por_pagina;

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM usuarios" . $where_clause);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $itens_por_pagina);

$sql_membros = "SELECT id, nome, login, email, saldo_creditos, perfil, plano_interesse FROM usuarios" . $where_clause . " ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt_membros = $pdo->prepare($sql_membros);
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
        :root { --primary: #00ff88; --bg: #0d1117; --card: #161b22; --border: #30363d; --text-main: #f0f6fc; --text-dim: #8b949e; --vip: #ffd700; --danger: #ff4d4d; --info: #3498db; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text-main); display: flex; min-height: 100vh; }
        nav { width: 280px; background: rgba(22, 27, 34, 0.95); backdrop-filter: blur(10px); border-right: 1px solid var(--border); padding: 30px 15px; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 1000; }
        .nav-logo { font-weight: 800; font-size: 1.6rem; letter-spacing: -1px; margin-bottom: 30px; text-align: center; }
        .nav-logo span { color: var(--primary); }
        .nav-btn { color: var(--text-dim); padding: 12px 18px; border-radius: 12px; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 13px; transition: 0.3s; }
        .nav-btn.active { background: #065f46; color: var(--primary); }
        main { flex: 1; margin-left: 280px; padding: 40px 60px; width: calc(100% - 280px); }
        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(255,255,255,0.02); padding: 18px 20px; text-align: left; font-size: 11px; color: var(--text-dim); text-transform: uppercase; }
        td { padding: 16px 20px; border-bottom: 1px solid var(--border); font-size: 13px; }
        .btn-action { background: none; border: none; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .btn-edit { color: var(--info); } .btn-delete { color: var(--danger); margin-left: 10px; }
        #modalEditar { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:3000; align-items:center; justify-content:center; backdrop-filter: blur(8px); }
        .input-group { flex: 1; display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .input-group label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; font-weight: 700; }
        .input-group input, .input-group select { background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; outline: none; }
        .btn-pub { background: var(--primary); color: #0d1117; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; cursor: pointer; }
    </style>
</head>
<body>

<nav>
    <div class="nav-logo">SEFULL<span>BET</span></div>
    <div class="nav-group">
        <span class="nav-label">Menu Principal</span>
        <a class="nav-btn active" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
        <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
        <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
        <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>
        <hr style="border: 0; border-top: 1px solid var(--border); margin: 15px 10px;">
        <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
        <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
        <a class="nav-btn" href="base_dados_ai.php"><i class="fas fa-file-import"></i> <span>Verificar Dados</span></a>
        <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
        <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
        <a class="nav-btn" href="gestao_noticias.php"><i class="fas fa-newspaper"></i> <span>Gestão de Notícias</span></a>
        <a class="nav-btn" href="gestao_notas.php"><i class="fas fa-edit"></i> <span>Gestão de Notas</span></a>
    </div>
    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <h1 style="font-weight: 800; margin-bottom: 30px;">Gestão de Membros</h1>

    <section class="search-container" style="background: var(--card); border: 1px solid var(--border); padding: 25px; border-radius: 20px; margin-bottom: 30px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
            <div class="input-group" style="margin-bottom:0;">
                <label>Busca rápida</label>
                <input type="text" name="search" placeholder="Buscar..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-pub">Filtrar</button>
        </form>
    </section>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Membro</th>
                    <th>E-mail</th>
                    <th>Plano</th>
                    <th>Créditos</th>
                    <th>Perfil</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($lista_membros as $m): ?>
                <tr>
                    <td>
                        <b><?= htmlspecialchars($m['nome']) ?></b><br>
                        <small style="color:var(--text-dim)">@<?= htmlspecialchars($m['login']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><span style="color:var(--primary)"><?= htmlspecialchars($m['plano_interesse'] ?? 'Não informado') ?></span></td>
                    <td style="text-align:center"><b><?= $m['saldo_creditos'] ?></b></td>
                    <td><span style="color:<?= $m['perfil']=='VIP'?'var(--vip)':'var(--primary)'?>; font-weight:700;"><?= $m['perfil'] ?></span></td>
                    <td style="text-align:right; white-space:nowrap;">
                        <button onclick='abrirModalEditar(<?= json_encode($m) ?>)' class="btn-action btn-edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmarExclusao('<?= $m['nome'] ?>')">
                            <input type="hidden" name="action" value="delete_member">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- MODAL EDITAR -->
<div id="modalEditar">
    <div style="background:var(--card); width:95%; max-width:600px; padding:30px; border-radius:24px; border:1px solid var(--border);">
        <h2 style="margin-bottom:20px;">Editar Membro</h2>
        <form action="gestao_membros.php" method="POST">
            <input type="hidden" name="action" value="update_member">
            <input type="hidden" name="id" id="edit_id">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="input-group" style="grid-column: span 2;"><label>Nome</label><input type="text" name="nome" id="edit_nome" required></div>
                <div class="input-group"><label>Login</label><input type="text" name="login" id="edit_login" required></div>
                <div class="input-group"><label>E-mail</label><input type="email" name="email" id="edit_email" required></div>
                <div class="input-group"><label>Senha (Vazio p/ manter)</label><input type="password" name="senha"></div>
                
                <!-- ID e Name Corrigidos aqui -->
                <div class="input-group"><label>Créditos</label><input type="number" name="saldo_creditos" id="edit_saldo_creditos" required></div>
                
                <div class="input-group">
                    <label>Perfil</label>
                    <select name="perfil" id="edit_perfil">
                        <option value="Grátis">Grátis</option>
                        <option value="VIP">VIP</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Plano de Interesse</label>
                    <select name="plano_interesse" id="edit_plano_interesse">
                        <option value="Grátis">Grátis</option>
                        <option value="VIP">VIP</option>
                        <option value="Platinum">Platinum</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:25px; display:flex; gap:10px;">
                <button type="submit" class="btn-pub" style="flex:1;">Salvar Alterações</button>
                <button type="button" onclick="fecharModal()" style="background:none; color:var(--text-dim); border:1px solid var(--border); padding:10px 20px; border-radius:12px; cursor:pointer;">Cancelar</button>
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
    // ID corrigido para bater com o HTML acima
    document.getElementById('edit_saldo_creditos').value = dados.saldo_creditos;
    document.getElementById('edit_perfil').value = dados.perfil;
    document.getElementById('edit_plano_interesse').value = dados.plano_interesse || 'Grátis';
    document.getElementById('modalEditar').style.display = 'flex';
}
function fecharModal() { document.getElementById('modalEditar').style.display = 'none'; }
function confirmarExclusao(nome) { return confirm("Deseja realmente excluir " + nome + "?"); }
</script>

</body>
</html>
