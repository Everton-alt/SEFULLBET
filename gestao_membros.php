<?php
session_start();
require_once 'config.php';

// 1. Verificação de Acesso (Apenas Admin/Supervisor)
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

// 2. Lógica de Processamento (Update e Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_member') {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $login = $_POST['login'];
        $email = $_POST['email'];
        $saldo = $_POST['saldo_creditos'];
        $perf = $_POST['perfil'];
        $plano = $_POST['plano_interesse'];

        if (!empty($_POST['senha'])) {
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE usuarios SET nome=?, login=?, email=?, senha=?, saldo_creditos=?, perfil=?, plano_interesse=? WHERE id=?");
            $upd->execute([$nome, $login, $email, $senha, $saldo, $perf, $plano, $id]);
        } else {
            $upd = $pdo->prepare("UPDATE usuarios SET nome=?, login=?, email=?, saldo_creditos=?, perfil=?, plano_interesse=? WHERE id=?");
            $upd->execute([$nome, $login, $email, $saldo, $perf, $plano, $id]);
        }
        header("Location: gestao_membros.php?msg=updated");
        exit();
    }

    if ($_POST['action'] === 'delete_member') {
        $id = $_POST['id'];
        $del = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $del->execute([$id]);
        header("Location: gestao_membros.php?msg=deleted");
        exit();
    }
}

// 3. Lógica de Busca e Listagem
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $stmt_m = $pdo->prepare("SELECT * FROM usuarios WHERE nome LIKE ? OR email LIKE ? OR login LIKE ? ORDER BY id DESC");
    $stmt_m->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt_m = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
}
$lista_membros = $stmt_m->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefullbet - Gestão de Membros</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2ECC71; 
            --bg-body: #ffffff; 
            --bg-secondary: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #2d3436;
            --text-dim: #636e72;
            --danger: #d63031;
            --warning: #f1c40f;
            --vip: #f1c40f;
            --border: #f1f1f1;
            --info: #0984e3;
        }

        body { font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; background-color: var(--bg-body); color: var(--text-main); }

        /* SIDEBAR ESPELHADA */
        .sidebar { height: 100%; width: 280px; position: fixed; z-index: 2000; top: 0; left: -280px; background-color: #2d3436; transition: 0.4s; padding-top: 20px; box-shadow: 5px 0 15px rgba(0,0,0,0.1); overflow-y: auto; }
        .sidebar .nav-btn { padding: 12px 25px; text-decoration: none; font-size: 15px; color: #b2bec3; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #3d4648; transition: 0.3s; }
        .sidebar .nav-btn:hover, .sidebar .nav-btn.active { background: #3d4648; color: var(--primary); }
        .sidebar .close-btn { position: absolute; top: 10px; right: 25px; font-size: 30px; cursor: pointer; color: var(--primary); }
        .nav-label { color: var(--primary); font-size: 11px; text-transform: uppercase; padding: 15px 25px 5px; display: block; font-weight: 800; letter-spacing: 1px; }
        .overlay { display: none; position: fixed; width: 100%; height: 100%; top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 1500; }

        /* HEADER */
        header { background: #fff; padding: 15px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #eee; }
        .menu-icon { font-size: 24px; cursor: pointer; color: #2d3436; }
        .logo { font-weight: 900; font-size: 1.3rem; color: #2d3436; }
        .logo span { color: var(--primary); }

        /* CONTEÚDO */
        main { padding: 20px; max-width: 1200px; margin: 0 auto; min-height: 80vh; }
        
        .input-group { margin-bottom: 15px; flex: 1; }
        .input-group label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 5px; color: var(--text-dim); }
        .input-group input, .input-group select { width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #ddd; box-sizing: border-box; font-family: inherit; }

        .btn-pub { background: var(--primary); color: #fff; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-pub:hover { opacity: 0.9; transform: translateY(-2px); }

        .table-wrapper { background: #fff; border-radius: 20px; border: 1px solid var(--border); overflow-x: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th { background: #fcfcfc; padding: 18px; text-align: left; font-size: 11px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--border); }
        td { padding: 18px; font-size: 14px; border-bottom: 1px solid var(--border); }

        .btn-action { width: 35px; height: 35px; border-radius: 10px; border: none; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; margin-left: 5px; }
        .btn-edit { background: #e3f2fd; color: var(--info); }
        .btn-delete { background: #ffebee; color: var(--danger); }
        .btn-action:hover { transform: scale(1.1); }

        /* MODAL */
        #modalEditar { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:3000; align-items:center; justify-content:center; backdrop-filter: blur(4px); }

        footer { text-align: center; padding: 40px 20px; font-size: 0.75rem; color: #b2bec3; background: #f8f9fa; margin-top: 50px; line-height: 1.6; border-top: 1px solid #eee; }
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

<main>
    <h1 style="font-weight: 800; margin-bottom: 30px;">Gestão de Membros</h1>

    <section class="search-container" style="background: #fff; border: 1px solid var(--border); padding: 25px; border-radius: 20px; margin-bottom: 30px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <div class="input-group" style="margin-bottom:0; min-width: 250px;">
                <label>Busca rápida (Nome, E-mail ou Login)</label>
                <input type="text" name="search" placeholder="Ex: João Silva..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-pub">Filtrar</button>
            <?php if(!empty($search)): ?>
                <a href="gestao_membros.php" style="font-size: 12px; color: var(--danger); text-decoration: none; font-weight: bold;">Limpar busca</a>
            <?php endif; ?>
        </form>
    </section>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Membro</th>
                    <th>E-mail</th>
                    <th>Plano</th>
                    <th style="text-align:center">Créditos</th>
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
                    <td><span style="color:var(--info); font-weight: 600;"><?= htmlspecialchars($m['plano_interesse'] ?? 'Grátis') ?></span></td>
                    <td style="text-align:center"><b style="color: var(--primary)"><?= $m['saldo_creditos'] ?></b></td>
                    <td>
                        <span style="color:<?= $m['perfil']=='VIP'?'var(--vip)':'var(--primary)'?>; font-weight:800;">
                            <?= strtoupper($m['perfil']) ?>
                        </span>
                    </td>
                    <td style="text-align:right; white-space:nowrap;">
                        <button onclick='abrirModalEditar(<?= json_encode($m) ?>)' class="btn-action btn-edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" style="display:inline;" onsubmit="return confirmarExclusao('<?= addslashes($m['nome']) ?>')">
                            <input type="hidden" name="action" value="delete_member">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($lista_membros)): ?>
                    <tr><td colspan="6" style="text-align:center; padding: 40px; color: var(--text-dim);">Nenhum membro encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<footer>
    <b style="color: var(--text-main); letter-spacing: 1px;">SEFULLBET</b><br>
    © 2026 SeFullBet - Inteligência de Dados aplicada ao Esporte.<br>
    Apostas são para maiores de 18 anos. Jogue com responsabilidade.
</footer>

<!-- MODAL EDITAR -->
<div id="modalEditar">
    <div style="background:#fff; width:95%; max-width:600px; padding:35px; border-radius:24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <h2 style="margin-bottom:25px; font-weight: 800;">Editar Membro</h2>
        <form action="gestao_membros.php" method="POST">
            <input type="hidden" name="action" value="update_member">
            <input type="hidden" name="id" id="edit_id">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="input-group" style="grid-column: span 2;"><label>Nome Completo</label><input type="text" name="nome" id="edit_nome" required></div>
                <div class="input-group"><label>Login (Username)</label><input type="text" name="login" id="edit_login" required></div>
                <div class="input-group"><label>E-mail</label><input type="email" name="email" id="edit_email" required></div>
                <div class="input-group"><label>Nova Senha (deixe vazio p/ manter)</label><input type="password" name="senha"></div>
                <div class="input-group"><label>Saldo de Créditos</label><input type="number" name="saldo_creditos" id="edit_saldo_creditos" required></div>
                
                <div class="input-group">
                    <label>Perfil de Acesso</label>
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
            <div style="margin-top:30px; display:flex; gap:12px;">
                <button type="submit" class="btn-pub" style="flex:2;">Salvar Alterações</button>
                <button type="button" onclick="fecharModal()" style="flex:1; background:#eee; color:#666; border:none; padding:12px; border-radius:12px; cursor:pointer; font-weight:bold;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNav() { document.getElementById("mySidebar").style.left = "0"; document.getElementById("overlay").style.display = "block"; }
function closeNav() { document.getElementById("mySidebar").style.left = "-280px"; document.getElementById("overlay").style.display = "none"; }

function abrirModalEditar(dados) {
    document.getElementById('edit_id').value = dados.id;
    document.getElementById('edit_nome').value = dados.nome;
    document.getElementById('edit_login').value = dados.login;
    document.getElementById('edit_email').value = dados.email;
    document.getElementById('edit_saldo_creditos').value = dados.saldo_creditos;
    document.getElementById('edit_perfil').value = dados.perfil;
    document.getElementById('edit_plano_interesse').value = dados.plano_interesse || 'Grátis';
    document.getElementById('modalEditar').style.display = 'flex';
}
function fecharModal() { document.getElementById('modalEditar').style.display = 'none'; }
function confirmarExclusao(nome) { return confirm("⚠️ ATENÇÃO: Deseja realmente excluir permanentemente o membro " + nome + "?"); }

// Fechar modal ao clicar fora
window.onclick = function(event) {
    if (event.target == document.getElementById('modalEditar')) fecharModal();
}
</script>

</body>
</html>
