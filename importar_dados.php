<?php
require_once 'config.php';

// Proteção de Acesso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// --- Lógica de Ações ---

// 1. Apagar Linha Única
if (isset($_GET['apagar_id'])) {
    $stmt = $pdo->prepare("DELETE FROM base_analisador WHERE id = ?");
    $stmt->execute([$_GET['apagar_id']]);
    header("Location: importar_dados.php?status=apagado");
    exit();
}

// 2. Esvaziar Base Total
if (isset($_GET['limpar_tudo']) && $_GET['limpar_tudo'] == 'confirmado') {
    $pdo->query("TRUNCATE TABLE base_analisador");
    header("Location: importar_dados.php?status=limpo");
    exit();
}

// 3. Importação Massiva (CSV)
if (isset($_POST['importar'])) {
    $arquivo = $_FILES['csv_file']['tmp_name'];
    if (!empty($arquivo)) {
        $handle = fopen($arquivo, "r");
        fgetcsv($handle, 1000, ","); // Pula o cabeçalho
        
        $importados = 0;
        $erros = 0;

        $pdo->beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Previne erro se a linha for vazia ou incompleta
                if (count($row) < 17) continue;

                $sql = "INSERT INTO base_analisador (id, liga, casa, fora, odd_casa, odd_empate, odd_fora, gol_casa, gol_fora, gols_total, resultado, ambos_marcam, over_05, over_15, over_25, over_35, over_45) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON CONFLICT (id) DO NOTHING"; // Trava de ID Duplicado no PostgreSQL
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($row);
                
                if ($stmt->rowCount() > 0) $importados++;
                else $erros++;
            }
            $pdo->commit();
            header("Location: importar_dados.php?sucesso=$importados&avisos=$erros");
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Erro crítico na importação: " . $e->getMessage());
        }
        exit();
    }
}

// --- Paginação e Dados ---
$limit = 10;
$pag = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pag < 1) $pag = 1;
$offset = ($pag - 1) * $limit;

$total_registros = $pdo->query("SELECT COUNT(*) FROM base_analisador")->fetchColumn();
$total_paginas = ceil($total_registros / $limit);

$dados = $pdo->query("SELECT * FROM base_analisador ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Dados AI | SeFull Bet</title>
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
            --danger: #ff4d4d;
            --info: #3498db;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { background-color: var(--bg); color: var(--text-main); display: flex; min-height: 100vh; }

        /* SIDEBAR (IGUAL GESTÃO DE SINAIS) */
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

        .form-container { background: var(--card); border: 1px solid var(--border); padding: 30px; border-radius: 20px; margin-bottom: 40px; }
        
        .upload-wrapper {
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
        }

        .btn-pub { 
            background: var(--primary); color: #0d1117; border: none; padding: 15px 25px; 
            border-radius: 12px; font-weight: 800; cursor: pointer; text-transform: uppercase; transition: 0.3s;
        }

        .btn-danger-outline {
            background: transparent; border: 1px solid var(--danger); color: var(--danger);
            padding: 10px 20px; border-radius: 10px; cursor: pointer; text-decoration: none; font-size: 12px;
        }

        .table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background: rgba(255,255,255,0.02); padding: 15px; text-align: left; color: var(--text-dim); text-transform: uppercase; }
        td { padding: 12px 15px; border-bottom: 1px solid var(--border); }

        .pagination { display: flex; justify-content: center; gap: 10px; margin-top: 25px; }
        .page-link { padding: 8px 16px; background: var(--card); border: 1px solid var(--border); color: var(--text-main); text-decoration: none; border-radius: 8px; font-size: 13px; }
        .page-link.active { background: var(--primary); color: #000; font-weight: 700; border-color: var(--primary); }

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
        <a class="nav-btn active" href="dashboard.php"><i class="fas fa-th-large"></i> <span>Feed Usuário</span></a>
        
        <!-- Novos itens adicionados -->
        <a class="nav-btn" href="palpites.php"><i class="fas fa-list-ul"></i> <span>Palpites</span></a>
        <a class="nav-btn" href="vitorias.php"><i class="fas fa-award"></i> <span>Vitórias</span></a>
        <a class="nav-btn" href="notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a>
        <a class="nav-btn" href="perfil.php"><i class="fas fa-user-circle"></i> <span>Minha Conta</span></a>
        
        <!-- Itens mantidos dos grupos anteriores -->
        <a class="nav-btn" href="analisador.php"><i class="fas fa-microchip"></i> <span>Analisador AI</span></a>
        <a class="nav-btn" href="gestao.php"><i class="fas fa-wallet"></i> <span>Minha Banca</span></a>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 15px 10px;">
        
        <!-- Gestão Administrativa -->
        <a class="nav-btn" href="gestao_sinais.php"><i class="fas fa-signal"></i> <span>Gestão de Sinais</span></a>
        <a class="nav-btn" href="importar_dados.php"><i class="fas fa-file-import"></i> <span>Importar Dados</span></a>
        <a class="nav-btn" href="gestao_vitorias.php"><i class="fas fa-trophy"></i> <span>Gestão de Vitórias</span></a>
        <a class="nav-btn" href="gestao_membros.php"><i class="fas fa-users-cog"></i> <span>Gestão de Membros</span></a>
        <a class="nav-btn" href="gestao_noticias.php"><i class="fas fa-newspaper"></i> <span>Gestão de Notícias</span></a>
        <a class="nav-btn" href="gestao_notas.php"><i class="fas fa-edit"></i> <span>Gestão de Notas</span></a>
    </div>

    <a class="nav-btn" style="margin-top:auto; color: var(--danger)" href="logout.php"><i class="fas fa-power-off"></i> <span>Sair</span></a>
</nav>

<main>
    <div style="display:flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
        <div>
            <h1 style="font-weight: 800;">Importar Dados AI</h1>
            <p style="color:var(--text-dim)">Base total: <b><?= $total_registros ?></b> registros</p>
        </div>
        <a href="?limpar_tudo=confirmado" onclick="return confirm('Deseja APAGAR TODA a base de dados?')" class="btn-danger-outline">
            <i class="fas fa-trash-alt"></i> Esvaziar Base
        </a>
    </div>

    <section class="form-container">
        <form action="" method="POST" enctype="multipart/form-data" class="upload-wrapper">
            <div style="flex: 1;">
                <label style="display:block; font-size:10px; color:var(--text-dim); text-transform:uppercase; margin-bottom:8px; font-weight:700;">Selecione o arquivo CSV</label>
                <input type="file" name="csv_file" accept=".csv" required style="width:100%; background:#0d1117; border:1px solid var(--border); color:#fff; padding:10px; border-radius:10px;">
            </div>
            <button type="submit" name="importar" class="btn-pub">Fazer Upload</button>
        </form>
    </section>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Liga</th>
                    <th>Confronto</th>
                    <th>Odds (C/E/F)</th>
                    <th>Placar</th>
                    <th>Res</th>
                    <th>Amb</th>
                    <th>O2.5</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($dados as $d): ?>
                <tr>
                    <td style="color:var(--primary); font-weight:700;"><?= $d['id'] ?></td>
                    <td><?= $d['liga'] ?></td>
                    <td><b><?= $d['casa'] ?> x <?= $d['fora'] ?></b></td>
                    <td style="color:var(--text-dim)"><?= $d['odd_casa'] ?> | <?= $d['odd_empate'] ?> | <?= $d['odd_fora'] ?></td>
                    <td style="color:var(--primary); font-weight:800;"><?= $d['gol_casa'] ?> - <?= $d['gol_fora'] ?></td>
                    <td><?= $d['resultado'] ?></td>
                    <td><?= $d['ambos_marcam'] ?></td>
                    <td><?= $d['over_25'] ?></td>
                    <td style="text-align:right">
                        <a href="javascript:void(0)" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($d)) ?>)" title="Editar"><i class="fas fa-edit" style="color: var(--info); margin-left: 12px;"></i></a>
                        <a href="?apagar_id=<?= $d['id'] ?>" onclick="return confirm('Apagar este registro?')" title="Excluir"><i class="fas fa-trash" style="color: var(--text-dim); margin-left: 12px;"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if($pag > 1): ?><a href="?p=<?= $pag - 1 ?>" class="page-link">Anterior</a><?php endif; ?>
        <?php for($i = 1; $i <= $total_paginas; $i++): 
            if($i <= 3 || $i > $total_paginas - 3 || ($i >= $pag - 1 && $i <= $pag + 1)): ?>
                <a href="?p=<?= $i ?>" class="page-link <?= ($i == $pag) ? 'active' : '' ?>"><?= $i ?></a>
            <?php elseif($i == 4 || $i == $total_paginas - 3): ?>
                <span style="color:var(--text-dim)">...</span>
            <?php endif; 
        endfor; ?>
        <?php if($pag < $total_paginas): ?><a href="?p=<?= $pag + 1 ?>" class="page-link">Próxima</a><?php endif; ?>
    </div>
</main>

<!-- MODAL DE EDIÇÃO (Pop-up) -->
<div id="modalEditar">
    <div style="background:var(--card); width:90%; max-width:600px; padding:30px; border-radius:20px; border:1px solid var(--border);">
        <h2 style="margin-bottom:20px;">Editar Registro AI</h2>
        <form action="atualizar_base.php" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div style="grid-column: span 2;">
                    <label style="font-size:10px; color:var(--text-dim); text-transform:uppercase;">Liga</label>
                    <input type="text" name="liga" id="edit_liga" style="width:100%; background:#0d1117; border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:10px; color:var(--text-dim); text-transform:uppercase;">Time Casa</label>
                    <input type="text" name="casa" id="edit_casa" style="width:100%; background:#0d1117; border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:10px; color:var(--text-dim); text-transform:uppercase;">Time Fora</label>
                    <input type="text" name="fora" id="edit_fora" style="width:100%; background:#0d1117; border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px;">
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
    document.getElementById('edit_liga').value = dados.liga;
    document.getElementById('edit_casa').value = dados.casa;
    document.getElementById('edit_fora').value = dados.fora;
    document.getElementById('modalEditar').style.display = 'flex';
}
function fecharModal() { document.getElementById('modalEditar').style.display = 'none'; }
</script>

</body>
</html>
