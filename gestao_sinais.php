<?php
session_start();
require_once 'config.php';

// 1. Lógica de Paginação (10 por página)
$itens_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $itens_por_pagina;

// 2. Processar Publicação de Palpite
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publicar'])) {
    $sql = "INSERT INTO sinais (p_confronto, p_mercado, p_valor, p_placar, p_odd, p_categoria, p_status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pendente')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['confronto'], 
        $_POST['mercado'], 
        $_POST['valor'] ?: 0, 
        $_POST['placar'] ?: '0-0', 
        $_POST['odd'], 
        $_POST['categoria']
    ]);
    header("Location: gestao_sinais.php?sucesso=1");
    exit();
}

// 3. Buscar os últimos 10 palpites
$stmt = $pdo->prepare("SELECT * FROM sinais ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->execute([$itens_por_pagina, $offset]);
$palpites = $stmt->fetchAll();

// 4. Função para Estilizar o Status
function getStatusBadge($status) {
    $style = "";
    switch($status) {
        case 'Green': $style = "background:#00ff8822; color:#00ff88; border:1px solid #00ff88;"; break;
        case 'Red': $style = "background:#ff4d4d22; color:#ff4d4d; border:1px solid #ff4d4d;"; break;
        default: $style = "background:#30363d; color:#8b949e;"; break;
    }
    return "<span style='padding:4px 8px; border-radius:5px; font-size:11px; font-weight:800; $style'>$status</span>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão SeFull Bet | Palpites</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg: #0d1117; --card: #161b22; --border: #30363d; --primary: #00ff88; --text: #f0f6fc; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; padding: 40px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        input, select { background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 10px; border-radius: 6px; }
        .btn-pub { background: var(--primary); color: #000; border: none; padding: 12px; border-radius: 6px; font-weight: 800; cursor: pointer; margin-top: 15px; width: 100%; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; color: #8b949e; font-size: 12px; padding: 10px; border-bottom: 2px solid var(--border); }
        td { padding: 12px 10px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .p-id { color: var(--primary); font-weight: 800; }
        .pagination { display: flex; gap: 10px; margin-top: 20px; }
        .page-btn { background: transparent; border: 1px solid var(--border); color: var(--text); padding: 5px 12px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>

    <h2>Módulo Publicação (_P)</h2>
    
    <div class="card">
        <form method="POST">
            <div class="form-grid">
                <input type="text" name="confronto" placeholder="Confronto (Ex: Real vs Barça)" required>
                <input type="text" name="mercado" placeholder="Mercado (Ex: Ambas Marcam)" required>
                <input type="number" step="0.01" name="valor" placeholder="Valor (Opcional)">
                <input type="text" name="placar" placeholder="Placar (Opcional)">
                <input type="number" step="0.01" name="odd" placeholder="Odd" required>
                <select name="categoria">
                    <option value="Grátis">Grátis</option>
                    <option value="VIP">VIP</option>
                </select>
            </div>
            <button type="submit" name="publicar" class="btn-pub">PUBLICAR PALPITE AGORA</button>
        </form>
    </div>

    <div class="card">
        <h3>Últimos 10 Palpites</h3>
        <table>
            <thead>
                <tr>
                    <th>ID_P</th>
                    <th>DATA</th>
                    <th>CONFRONTO</th>
                    <th>MERCADO</th>
                    <th>ODD</th>
                    <th>STATUS</th>
                    <th>AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($palpites as $p): ?>
                <tr>
                    <td class="p-id"><?php echo $p['p_codigo']; ?></td>
                    <td><?php echo date('d/m', strtotime($p['p_data'])); ?></td>
                    <td><?php echo $p['p_confronto']; ?></td>
                    <td><?php echo $p['p_mercado']; ?></td>
                    <td><b>@<?php echo $p['p_odd']; ?></b></td>
                    <td><?php echo getStatusBadge($p['p_status']); ?></td>
                    <td>
                        <a href="editar_p.php?id=<?php echo $p['id']; ?>" style="color: #00e5ff; text-decoration:none;"><i class="fas fa-edit"></i></a>
                        <a href="apagar_p.php?id=<?php echo $p['id']; ?>" style="color: #ff4d4d; margin-left:10px;"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="?pagina=<?php echo $pagina-1; ?>" class="page-btn">Anterior</a>
            <?php endif; ?>
            <a href="?pagina=<?php echo $pagina+1; ?>" class="page-btn">Próxima</a>
        </div>
    </div>

</body>
</html>
