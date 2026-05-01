<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

// --- LÓGICA DE EXCLUSÃO (Processada na própria página) ---
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'clear_all') {
        $pdo->query("TRUNCATE TABLE base_analisador");
        header("Location: base_dados_ai.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    if (!empty($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt_del = $pdo->prepare("DELETE FROM base_analisador WHERE id IN ($placeholders)");
        $stmt_del->execute($ids);
        header("Location: base_dados_ai.php?msg=deleted");
        exit();
    }
}

// --- Estatísticas Superiores ---
$total_registros = $pdo->query("SELECT COUNT(*) FROM base_analisador")->fetchColumn();
$ultima_importacao = "Nenhuma registrada"; 
$inconsistencias = 0; 

// --- Paginação e Busca ---
$itens_por_pagina = 50; 
$pagina_atual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$search = isset($_GET['f']) ? $_GET['f'] : '';
$query_str = "SELECT * FROM base_analisador";
if($search) {
    $query_str .= " WHERE liga ILIKE :s OR casa ILIKE :s OR fora ILIKE :s";
}
$query_str .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query_str);
if($search) $stmt->bindValue(':s', "%$search%");
$stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$dados = $stmt->fetchAll();

$total_paginas = ceil($total_registros / $itens_por_pagina);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Base de Dados AI | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00ff88;
            --bg: #0b0e14;
            --card: #151921;
            --border: #262c36;
            --text: #ffffff;
            --text-dim: #a0aec0;
            --danger: #f56565;
        }

        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; display: flex; margin: 0; }
        
        /* Menu Lateral */
        nav { width: 260px; background: var(--card); border-right: 1px solid var(--border); height: 100vh; position: fixed; padding: 20px; display: flex; flex-direction: column; overflow-y: auto; z-index: 100; }
        .nav-logo { font-weight: 800; font-size: 1.6rem; color: white; margin-bottom: 30px; }
        .nav-logo span { color: var(--primary); }
        .nav-group { display: flex; flex-direction: column; gap: 2px; }
        .nav-label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin: 15px 0 8px 5px; font-weight: 700; }
        .nav-btn { color: var(--text-dim); padding: 12px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 13px; transition: 0.3s; }
        .nav-btn i { width: 20px; text-align: center; }
        .nav-btn:hover, .nav-btn.active { background: rgba(255,255,255,0.05); color: white; }
        .nav-btn.active { color: var(--primary); font-weight: 700; }

        /* Área Principal */
        main { flex: 1; margin-left: 260px; padding: 30px; }

        .top-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card); padding: 20px; border-radius: 12px; border: 1px solid var(--border); position: relative; }
        .stat-card span { font-size: 11px; color: var(--text-dim); text-transform: uppercase; font-weight: 700; }
        .stat-card h2 { margin: 10px 0 0; font-size: 28px; font-weight: 900; }
        .text-green { color: var(--primary); }

        .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 15px; }
        .search-box { background: #1a202c; border: 1px solid var(--border); padding: 10px 15px; border-radius: 8px; flex: 1; color: white; display: flex; align-items: center; }
        .search-box input { background: none; border: none; color: white; margin-left: 10px; outline: none; width: 100%; }
        
        .btn-action { padding: 10px 20px; border-radius: 8px; border: 1px solid var(--border); background: var(--card); color: var(--text); cursor: pointer; font-size: 12px; font-weight: 700; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .btn-danger { border-color: var(--danger); color: var(--danger); }
        .btn-danger:hover { background: var(--danger); color: white; }

        /* Tabela */
        .excel-table-container { background: var(--card); border-radius: 8px; border: 1px solid var(--border); overflow-x: auto; max-height: 65vh; }
        table { width: 100%; border-collapse: collapse; font-family: 'Inter', sans-serif; font-size: 12.5px; }
        thead th { position: sticky; top: 0; background: #1a202c; color: var(--primary); text-transform: uppercase; font-size: 11px; padding: 12px 10px; text-align: left; border-bottom: 2px solid var(--border); z-index: 10; }
        tbody tr { border-bottom: 1px solid #262c36; transition: 0.1s; }
        tbody tr:hover { background: rgba(255,255,255,0.03); }
        tbody td { padding: 8px 10px; color: #cbd5e0; white-space: nowrap; }
        .td-id { color: #718096; font-family: 'Roboto Mono', monospace; }
        .td-number { font-family: 'Roboto Mono', monospace; }
        
        input[type="checkbox"] { cursor: pointer; accent-color: var(--primary); }

        .pagination { display: flex; justify-content: center; margin-top: 20px; gap: 5px; }
        .page-btn { padding: 8px 12px; background: var(--card); border: 1px solid var(--border); color: white; text-decoration: none; border-radius: 4px; font-size: 12px; }
        .page-btn.active { background: var(--primary); color: black; border-color: var(--primary); }
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

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 15px 0;">
        
        <span class="nav-label">Gestão Administrativa</span>
        <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
        <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
        <a class="nav-btn active" href="base_dados_ai.php"><i class="fas fa-database"></i> <span>Verificar Dados importados</span></a>
        <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
        <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
        <a class="nav-btn" href="gestao_noticias.php"><i class="fas fa-newspaper"></i> <span>Gestão de Notícias</span></a>
        <a class="nav-btn" href="gestao_notas.php"><i class="fas fa-edit"></i> <span>Gestão de Notas</span></a>
    </div>

    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <div class="top-grid">
        <div class="stat-card">
            <span>Total de Registros</span>
            <h2 class="text-green"><?= number_format($total_registros, 0, '', '.') ?></h2>
        </div>
        <div class="stat-card">
            <span>Última Importação</span>
            <h2><?= $ultima_importacao ?></h2>
        </div>
        <div class="stat-card">
            <span>Linhas com Inconsistência</span>
            <h2 style="color: #ecc94b;"><?= $inconsistencias ?></h2>
        </div>
    </div>

    <form method="POST" id="form-delete">
        <div class="actions-bar">
            <div class="search-box">
                <i class="fas fa-search" style="color: #4a5568;"></i>
                <input type="text" name="f" placeholder="Filtrar por liga ou time..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <button type="submit" name="delete_selected" class="btn-action" onclick="return confirm('Excluir registros selecionados?')">
                <i class="fas fa-trash"></i> Excluir Selecionados (<span id="count-selected">0</span>)
            </button>
            
            <button type="button" class="btn-action btn-danger" onclick="if(confirm('ATENÇÃO: Limpar toda a base?')) window.location.href='base_dados_ai.php?action=clear_all'">
                Limpar Base Toda
            </button>
        </div>

        <div class="excel-table-container">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>ID</th>
                        <th>Liga</th>
                        <th>Casa</th>
                        <th>Fora</th>
                        <th>Odd_C</th>
                        <th>Odd_E</th>
                        <th>Odd_F</th>
                        <th>G_C</th>
                        <th>G_F</th>
                        <th>Total</th>
                        <th>Res</th>
                        <th>Ambos</th>
                        <th>O0.5</th>
                        <th>O1.5</th>
                        <th>O2.5</th>
                        <th>O3.5</th>
                        <th>O4.5</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dados as $d): ?>
                    <tr>
                        <td><input type="checkbox" name="selected_ids[]" value="<?= $d['id'] ?>" class="row-select" onclick="updateCount()"></td>
                        <td class="td-id"><?= $d['id'] ?></td>
                        <td style="color: white; font-weight: 600;"><?= $d['liga'] ?></td>
                        <td><?= $d['casa'] ?></td>
                        <td><?= $d['fora'] ?></td>
                        <td class="td-number"><?= $d['odd_casa'] ?></td>
                        <td class="td-number"><?= $d['odd_empate'] ?></td>
                        <td class="td-number"><?= $d['odd_fora'] ?></td>
                        <td class="td-number"><?= $d['gol_casa'] ?></td>
                        <td class="td-number"><?= $d['gol_fora'] ?></td>
                        <td class="td-number" style="font-weight: 700; color: white;"><?= $d['gols_total'] ?></td>
                        <td><?= substr($d['resultado'], 0, 4) ?></td>
                        <td><?= $d['ambos_marcam'] ?></td>
                        <td><?= $d['over_05'] ?></td>
                        <td><?= $d['over_15'] ?></td>
                        <td><?= $d['over_25'] ?></td>
                        <td><?= $d['over_35'] ?></td>
                        <td><?= $d['over_45'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>

    <div class="pagination">
        <?php for($i = 1; $i <= min($total_paginas, 10); $i++): ?>
            <a href="?p=<?= $i ?>&f=<?= urlencode($search) ?>" class="page-btn <?= ($i == $pagina_atual) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</main>

<script>
    document.getElementById('select-all').onclick = function() {
        let checkboxes = document.querySelectorAll('.row-select');
        for (let checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
        updateCount();
    }

    function updateCount() {
        let count = document.querySelectorAll('.row-select:checked').length;
        document.getElementById('count-selected').innerText = count;
    }
</script>

</body>
</html>
