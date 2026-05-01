<?php
require_once 'config.php';

// Proteção de Acesso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Consulta de estatísticas rápidas para o topo
$total_base = $pdo->query("SELECT COUNT(*) FROM base_analisador")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Base AI | SeFull Bet</title>
    
    <!-- Bibliotecas Necessárias -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
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
            --warning: #ffd700;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: var(--bg); 
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR IDENTICA */
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

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }

        .upload-container { 
            background: var(--card); border: 2px dashed var(--border); 
            padding: 50px; border-radius: 20px; text-align: center; transition: 0.3s;
        }
        .upload-container:hover { border-color: var(--primary); }

        #loading { display: none; margin-top: 20px; color: var(--primary); font-weight: 800; animation: pulse 1.5s infinite; }
        @keyframes pulse { 50% { opacity: 0.5; } }

        /* PAINEL DE CONFLITOS */
        #conflict-panel { 
            display: none; background: var(--card); border: 1px solid var(--border); 
            border-radius: 20px; margin-top: 30px; overflow: hidden;
        }
        .conflict-header { background: rgba(255, 215, 0, 0.1); padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }

        .table-wrapper { max-height: 400px; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background: #0d1117; padding: 12px; text-align: left; color: var(--text-dim); position: sticky; top: 0; }
        td { padding: 10px 12px; border-bottom: 1px solid var(--border); }

        .btn-action { 
            padding: 12px 20px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; text-transform: uppercase; font-size: 11px;
        }
        .btn-replace { background: var(--warning); color: #000; }
        .btn-ignore { background: var(--primary); color: #000; }
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
    <div class="header-flex">
        <div>
            <h1 style="font-weight: 800;">Importação Massiva AI</h1>
            <p style="color: var(--text-dim);">Registros atuais no banco: <b><?= $total_base ?></b></p>
        </div>
        <button onclick="window.location.href='base_dados_ai.php'" class="nav-btn" style="background: var(--card); border: 1px solid var(--border);">
            <i class="fas fa-database"></i> Visualizar Base
        </button>
    </div>

    <div class="upload-container">
        <i class="fas fa-file-excel" style="font-size: 3.5rem; color: var(--primary); margin-bottom: 20px;"></i>
        <h2>Importar Planilha (.xlsx)</h2>
        <p style="color: var(--text-dim); margin-bottom: 25px;">O sistema detectará duplicatas automaticamente baseando-se no ID.</p>
        
        <input type="file" id="file-input" accept=".xlsx, .xls" style="display: none;">
        <button onclick="document.getElementById('file-input').click()" class="btn-action btn-ignore" style="padding: 15px 40px; font-size: 14px;">
            Selecionar Arquivo
        </button>
        
        <div id="loading">LENDO PLANILHA E VERIFICANDO CONFLITOS...</div>
    </div>

    <!-- PAINEL DE CONFLITOS -->
    <div id="conflict-panel">
        <div class="conflict-header">
            <div>
                <h3 style="color: var(--warning);"><i class="fas fa-exclamation-triangle"></i> Conflitos Detectados (<span id="count-conflicts">0</span>)</h3>
                <p style="font-size: 12px; color: var(--text-main);">Estes registros já existem no banco. O que deseja fazer?</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn-action btn-ignore" onclick="processarFinal('ignore')">Ignorar Existentes</button>
                <button class="btn-action btn-replace" onclick="processarFinal('replace')">Substituir Todos</button>
            </div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead id="thead"></thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>
    </div>
</main>

<script>
    let dadosPlanilha = [];
    let IDsConflitantes = [];

    document.getElementById('file-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        document.getElementById('loading').style.display = 'block';
        document.getElementById('conflict-panel').style.display = 'none';

        const reader = new FileReader();
        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            dadosPlanilha = XLSX.utils.sheet_to_json(sheet);
            
            // Envia para o PHP verificar o que já existe no banco
            verificarConflitos(dadosPlanilha);
        };
        reader.readAsArrayBuffer(file);
    });

    function verificarConflitos(dados) {
        fetch('verificar_conflitos_ai.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(dados)
        })
        .then(res => res.json())
        .then(res => {
            document.getElementById('loading').style.display = 'none';
            IDsConflitantes = res.conflitos;

            if (IDsConflitantes.length > 0) {
                document.getElementById('conflict-panel').style.display = 'block';
                document.getElementById('count-conflicts').innerText = IDsConflitantes.length;
                
                // Renderiza prévia
                const head = document.getElementById('thead');
                const body = document.getElementById('tbody');
                const colunas = Object.keys(IDsConflitantes[0]);
                
                head.innerHTML = `<tr>${colunas.map(c => `<th>${c}</th>`).join('')}</tr>`;
                body.innerHTML = IDsConflitantes.slice(0, 10).map(linha => `
                    <tr>${colunas.map(c => `<td>${linha[c] || ''}</td>`).join('')}</tr>
                `).join('') + (IDsConflitantes.length > 10 ? '<tr><td colspan="100%">... e outros registros</td></tr>' : '');

            } else {
                processarFinal('ignore'); // Sem conflitos, salva direto
            }
        });
    }

    function processarFinal(acao) {
        document.getElementById('loading').innerText = "SALVANDO NO BANCO DE DADOS... AGUARDE...";
        document.getElementById('loading').style.display = 'block';

        fetch('salvar_base_ai.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ acao: acao, dados: dadosPlanilha })
        })
        .then(res => res.json())
        .then(res => {
            alert(res.mensagem);
            location.reload();
        });
    }
</script>

</body>
</html>
