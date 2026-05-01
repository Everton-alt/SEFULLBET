<?php
require_once 'config.php';
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }
$total_base = $pdo->query("SELECT COUNT(*) FROM base_analisador")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Importar Base AI | SeFull Bet</title>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00ff88; --bg: #0d1117; --card: #161b22; --border: #30363d; --text: #f0f6fc; --text-dim: #8b949e; --warning: #ffd700; --danger: #ff4d4d; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
        
        /* Sidebar */
        nav { width: 280px; background: var(--card); border-right: 1px solid var(--border); padding: 30px 15px; position: fixed; height: 100vh; }
        .nav-logo { font-weight: 800; font-size: 1.6rem; text-align: center; margin-bottom: 30px; }
        .nav-logo span { color: var(--primary); }
        .nav-btn { color: var(--text-dim); padding: 12px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 14px; transition: 0.3s; }
        .nav-btn:hover, .nav-btn.active { background: rgba(0,255,136,0.1); color: var(--primary); }

        /* Main Content */
        main { flex: 1; margin-left: 280px; padding: 40px; }
        .upload-area { background: var(--card); border: 2px dashed var(--border); border-radius: 20px; padding: 60px; text-align: center; cursor: pointer; transition: 0.3s; }
        .upload-area:hover { border-color: var(--primary); }
        
        #loading { display: none; margin-top: 20px; color: var(--primary); font-weight: bold; animation: pulse 1.5s infinite; }
        @keyframes pulse { 50% { opacity: 0.4; } }

        #conflict-panel { display: none; background: var(--card); border: 1px solid var(--border); border-radius: 15px; margin-top: 30px; overflow: hidden; }
        .conf-header { padding: 20px; background: rgba(255,215,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .table-wrapper { max-height: 350px; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #000; padding: 12px; color: var(--text-dim); text-align: left; position: sticky; top: 0; }
        td { padding: 10px; border-bottom: 1px solid var(--border); }

        .btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: bold; cursor: pointer; }
        .btn-primary { background: var(--primary); color: #000; }
        .btn-warn { background: var(--warning); color: #000; }
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
    <h1 style="margin-bottom: 10px;">Importar Base de Dados AI</h1>
    <p style="color: var(--text-dim); margin-bottom: 30px;">Total atual no banco: <strong><?= $total_base ?></strong> registros.</p>

    <div class="upload-area" onclick="document.getElementById('file-input').click()">
        <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: var(--primary);"></i>
        <h2 style="margin-top: 15px;">Clique para selecionar a Planilha (.xlsx)</h2>
        <input type="file" id="file-input" accept=".xlsx, .xls" style="display: none;">
        <div id="loading">PROCESSANDO DADOS... POR FAVOR AGUARDE...</div>
    </div>

    <div id="conflict-panel">
        <div class="conf-header">
            <div>
                <h3 style="color: var(--warning)">⚠️ Conflitos de ID Detectados (<span id="count-c">0</span>)</h3>
                <p style="font-size: 12px;">Estes registros já existem. Como deseja proceder?</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-primary" onclick="salvarFinal('ignore')">PULAR EXISTENTES</button>
                <button class="btn btn-warn" onclick="salvarFinal('replace')">SUBSTITUIR TODOS</button>
            </div>
        </div>
        <div class="table-wrapper">
            <table><thead id="thead"></thead><tbody id="tbody"></tbody></table>
        </div>
    </div>
</main>

<script>
    let tempDados = [];

    document.getElementById('file-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        document.getElementById('loading').style.display = 'block';
        const reader = new FileReader();
        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            tempDados = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]]);
            
            fetch('verificar_conflitos_ai.php', {
                method: 'POST',
                body: JSON.stringify(tempDados)
            })
            .then(res => res.json())
            .then(res => {
                document.getElementById('loading').style.display = 'none';
                if (res.conflitos.length > 0) {
                    document.getElementById('conflict-panel').style.display = 'block';
                    document.getElementById('count-c').innerText = res.conflitos.length;
                    renderPreview(res.conflitos);
                } else {
                    salvarFinal('ignore');
                }
            });
        };
        reader.readAsArrayBuffer(file);
    });

    function renderPreview(lista) {
        const cols = Object.keys(lista[0]);
        document.getElementById('thead').innerHTML = `<tr>${cols.map(c => `<th>${c}</th>`).join('')}</tr>`;
        document.getElementById('tbody').innerHTML = lista.slice(0, 5).map(l => `<tr>${cols.map(c => `<td>${l[c]}</td>`).join('')}</tr>`).join('');
    }

    function salvarFinal(acao) {
        document.getElementById('loading').innerText = "GRAVANDO NO BANCO... AGUARDE...";
        document.getElementById('loading').style.display = 'block';

        fetch('salvar_base_ai.php', {
            method: 'POST',
            body: JSON.stringify({ acao: acao, dados: tempDados })
        })
        .then(res => res.json())
        .then(res => {
            alert(res.mensagem);
            location.reload();
        })
        .catch(err => alert("Erro ao salvar. Verifique o console."));
    }
</script>
</body>
</html>
