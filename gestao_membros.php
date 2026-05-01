<?php
session_start();
require_once 'config.php';

// Proteção de Acesso
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

// --- Lógica de Estatísticas de Membros ---
$total_membros = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_gratis = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'Grátis'")->fetchColumn();
$total_vip = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'VIP'")->fetchColumn();
$total_platinum = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'Platinum'")->fetchColumn();

// --- Lógica de Filtro e Busca ---
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = "";
$params = [];

if (!empty($search)) {
    $where_clause = " WHERE nome LIKE ? OR email LIKE ? OR login LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// --- Lógica de Paginação (10 por página) ---
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

// Bind dos parâmetros de busca se existirem
$idx = 1;
foreach ($params as $p) {
    $stmt_membros->bindValue($idx++, $p);
}
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
            --platinum: #e5e4e2;
            --danger: #ff4d4d;
            --info: #3498db;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        nav::-webkit-scrollbar { width: 4px; }
        nav::-webkit-scrollbar-track { background: transparent; }
        nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

        body { 
            background-color: var(--bg); 
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* SIDEBAR */
        nav { 
            width: 280px; background: rgba(22, 27, 34, 0.8); backdrop-filter: blur(10px);
            border-right: 1px solid var(--border); padding: 30px 15px;
            display: flex; flex-direction: column; position: fixed; height: 100vh;
            overflow-y: auto; z-index: 1000;
        }

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

        .perf-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .perf-card { background: var(--card); padding: 20px; border-radius: 20px; border: 1px solid var(--border); text-align: center; }
        .stat-box span { display: block; font-size: 10px; color: var(--text-dim); margin-bottom: 5px; text-transform: uppercase;}
        .stat-box b { font-size: 20px; font-weight: 800; }

        .search-container { background: var(--card); border: 1px solid var(--border); padding: 25px; border-radius: 20px; margin-bottom: 30px; }
        .search-form { display: flex; gap: 15px; }
        .search-input { flex: 1; background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; outline: none; }
        
        .btn-action { background: var(--primary); color: #0d1117; border: none; padding: 0 25px; border-radius: 12px; font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-action:hover { filter: brightness(1.1); box-shadow: 0 0 15px rgba(0, 255, 136, 0.3); }

        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        th { background: rgba(255,255,255,0.02); padding: 15px 20px; text-align: left; font-size: 11px; color: var(--text-dim); text-transform: uppercase; }
        td { padding: 15px 20px; border-bottom: 1px solid var(--border); font-size: 13px; }
        
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .badge-vip { background: rgba(255, 215, 0, 0.1); color: var(--vip); border: 1px solid var(--vip); }
        .badge-gratis { background: rgba(0, 255, 136, 0.1); color: var(--primary); border: 1px solid var(--primary); }
        .badge-platinum { background: rgba(229, 228, 226, 0.1); color: var(--platinum); border: 1px solid var(--platinum); }

        .pagination { display: flex; justify-content: center; gap: 10px; margin-top: 25px; }
        .page-link { padding: 8px 16px; background: var(--card); border: 1px solid var(--border); color: var(--text-main); text-decoration: none; border-radius: 8px; font-size: 13px; }
        .page-link.active { background: var(--primary); color: #000; font-weight: 700; border-color: var(--primary); }

        #modalEditar {
            display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
            background:rgba(0,0,0,0.85); z-index:3000; align-items:center; justify-content:center;
            backdrop-filter: blur(5px);
        }
        .input-group label { display: block; font-size: 10px; color: var(--text-dim); text-transform: uppercase; margin-bottom: 8px; font-weight: 700; }
        .input-group input, .input-group select { 
            width: 100%; background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; outline: none; font-size: 13px;
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

    <div class="perf-grid">
        <div class="perf-card"><div class="stat-box"><span>Total Membros</span><b><?= $total_membros ?></b></div></div>
        <div class="perf-card"><div class="stat-box"><span>Grátis</span><b style="color:var(--primary)"><?= $total_gratis ?></b></div></div>
        <div class="perf-card"><div class="stat-box"><span>VIP</span><b style="color:var(--vip)"><?= $total_vip ?></b></div></div>
        <div class="perf-card"><div class="stat-box"><span>Platinum</span><b style="color:var(--platinum)"><?= $total_platinum ?></b></div></div>
    </div>

    <section class="search-container">
        <form method="GET" class="search-form">
            <input type="text" name="search" class="search-input" placeholder="Buscar por Nome, E-mail ou Login..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-action">Buscar</button>
            <?php if(!empty($search)): ?>
                <a href="gestao_membros.php" class="btn-action" style="background:var(--border); color:#fff; display:flex; align-items:center; text-decoration:none;">Limpar</a>
            <?php endif; ?>
        </form>
    </section>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nome Completo</th>
                    <th>Login</th>
                    <th>E-mail</th>
                    <th>Senha</th>
                    <th>Cadastro</th>
                    <th>Créditos</th>
                    <th>Perfil</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($lista_membros as $m): ?>
                <tr>
                    <td><b><?= $m['nome'] ?></b></td>
                    <td><?= $m['login'] ?></td>
                    <td><?= $m['email'] ?></td>
                    <td style="color: var(--text-dim); font-family: monospace;">••••••</td>
                    <td><?= date('d/m/Y', strtotime($m['data_cadastro'])) ?><br><small style="color:var(--text-dim)"><?= $m['hora_cadastro'] ?></small></td>
                    <td style="text-align:center"><b><?= $m['creditos'] ?></b></td>
                    <td>
                        <span class="badge badge-<?= strtolower(str_replace('á','a',$m['perfil'])) ?>">
                            <?= $m['perfil'] ?>
                        </span>
                    </td>
                    <td style="text-align:right">
                        <a href="javascript:void(0)" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($m)) ?>)" title="Editar Membro"><i class="fas fa-user-edit" style="color: var(--info); font-size: 16px;"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if($pagina_atual > 1): ?><a href="?p=<?= $pagina_atual - 1 ?>&search=<?= $search ?>" class="page-link">Anterior</a><?php endif; ?>
        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?p=<?= $i ?>&search=<?= $search ?>" class="page-link <?= ($i == $pagina_atual) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if($pagina_atual < $total_paginas): ?><a href="?p=<?= $pagina_atual + 1 ?>&search=<?= $search ?>" class="page-link">Próxima</a><?php endif; ?>
    </div>
</main>

<!-- MODAL DE EDIÇÃO DE MEMBRO -->
<div id="modalEditar">
    <div style="background:var(--card); width:95%; max-width:600px; padding:30px; border-radius:20px; border:1px solid var(--border); max-height: 90vh; overflow-y: auto;">
        <h2 style="margin-bottom:20px;">Editar Membro</h2>
        <form action="atualizar_membro.php" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="input-group" style="grid-column: span 2;"><label>Nome Completo</label><input type="text" name="nome" id="edit_nome" required></div>
                <div class="input-group"><label>Login (Usuário)</label><input type="text" name="login" id="edit_login" required></div>
                <div class="input-group"><label>E-mail</label><input type="email" name="email" id="edit_email" required></div>
                <div class="input-group"><label>Nova Senha (deixe em branco para manter)</label><input type="text" name="senha" id="edit_senha" placeholder="Alterar senha..."></div>
                <div class="input-group"><label>Créditos</label><input type="number" name="creditos" id="edit_creditos" required></div>
                <div class="input-group" style="grid-column: span 2;">
                    <label>Tipo de Perfil</label>
                    <select name="perfil" id="edit_perfil">
                        <option value="Grátis">Grátis</option>
                        <option value="VIP">VIP</option>
                        <option value="Platinum">Platinum</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:25px; display:flex; gap:10px;">
                <button type="submit" class="btn-action" style="flex:1; padding:15px;">Salvar Alterações</button>
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
    document.getElementById('edit_senha').value = ""; // Senha sempre inicia vazia por segurança
    document.getElementById('modalEditar').style.display = 'flex';
}
function fecharModal() { document.getElementById('modalEditar').style.display = 'none'; }
window.onclick = function(e) { if (e.target == document.getElementById('modalEditar')) fecharModal(); }
</script>

</body>
</html>
