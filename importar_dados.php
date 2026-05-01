<?php
require_once 'config.php';

// 1. LÓGICA DE IMPORTAÇÃO (Executada ao clicar no botão)
if (isset($_POST['importar']) && isset($_FILES['planilha'])) {
    $arquivo = $_FILES['planilha']['tmp_name'];
    $extensao = pathinfo($_FILES['planilha']['name'], PATHINFO_EXTENSION);

    if ($extensao === 'csv') {
        $handle = fopen($arquivo, "r");
        fgetcsv($handle, 1000, ","); // Pula o cabeçalho

        $pdo->beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty($row[0])) continue;

                // ON CONFLICT (id) DO NOTHING -> Trava para não duplicar ID no Postgres
                $sql = "INSERT INTO base_analisador (
                            id, liga, casa, fora, odd_casa, odd_empate, odd_fora, 
                            gol_casa, gol_fora, gols_total, resultado, ambos_marcam, 
                            over_05, over_15, over_25, over_35, over_45
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON CONFLICT (id) DO NOTHING";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($row);
            }
            $pdo->commit();
            $msg = "Importação concluída com sucesso!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao processar: " . $e->getMessage();
        }
    } else {
        $erro = "Por favor, salve seu Excel como .CSV antes de importar.";
    }
}

// 2. LÓGICA DE EXCLUSÃO
if (isset($_GET['apagar_id'])) {
    $stmt = $pdo->prepare("DELETE FROM base_analisador WHERE id = ?");
    $stmt->execute([$_GET['apagar_id']]);
    header("Location: importar_dados.php");
    exit();
}

// 3. CONSULTA DOS DADOS PARA A TABELA
$limit = 10;
$pag = isset($_GET['p']) ? (int)$GET['p'] : 1;
$offset = ($pag - 1) * $limit;
$dados = $pdo->query("SELECT * FROM base_analisador ORDER BY id DESC LIMIT $limit OFFSET $offset")->fetchAll();
$total = $pdo->query("SELECT COUNT(*) FROM base_analisador")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Importar Base | SeFull Bet</title>
    <!-- Mantenha seus estilos CSS aqui -->
    <style>
        /* ... Seus estilos idênticos aos da Gestão de Sinais ... */
    </style>
</head>
<body>

<nav>
    <!-- Sua Sidebar -->
</nav>

<main>
    <h1>Importar Base do Analisador</h1>

    <?php if(isset($msg)) echo "<p style='color:var(--primary)'>$msg</p>"; ?>
    <?php if(isset($erro)) echo "<p style='color:var(--danger)'>$erro</p>"; ?>

    <section class="form-container">
        <!-- O formulário envia para a própria página (vazio no action) -->
        <form action="" method="POST" enctype="multipart/form-data" class="upload-wrapper">
            <div style="flex: 1;">
                <label>Selecione a Planilha (Formato CSV)</label>
                <input type="file" name="planilha" accept=".csv" required style="width:100%; background:#0d1117; border:1px solid var(--border); color:#fff; padding:10px; border-radius:10px;">
            </div>
            <button type="submit" name="importar" class="btn-pub">Importar Agora</button>
        </form>
        <p style="font-size: 11px; color: var(--text-dim); margin-top: 10px;">
            Dica: No Excel, vá em <b>Arquivo > Salvar Como > CSV (Separado por vírgulas)</b>.
        </p>
    </section>

    <!-- Tabela de visualização abaixo -->
    <div class="table-wrapper">
        <table>
            <!-- Tabela com os mesmos campos da planilha -->
            <?php foreach($dados as $d): ?>
            <tr>
                <td><?= $d['id'] ?></td>
                <td><?= $d['liga'] ?></td>
                <td><?= $d['casa'] ?> x <?= $d['fora'] ?></td>
                <td>
                    <a href="?apagar_id=<?= $d['id'] ?>" onclick="return confirm('Apagar?')">Apagar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</main>

</body>
</html>
