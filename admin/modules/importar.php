<?php
// admin/modules/importar.php
// Requer biblioteca PHPSpreadsheet para processar o arquivo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['planilha'])) {
    $acao = $_POST['acao_duplicado']; // 'substituir' ou 'pular'
    
    // Lógica de loop nas linhas da planilha
    // Exemplo de execução para cada linha:
    $sql = "INSERT INTO dados_analisador (id_planilha, liga, casa, fora, odd_casa, odd_empate, odd_fora, gol_casa, gol_fora, gols_total, resultado, ambos_marcam, over_05, over_15, over_25, over_35, over_45) 
            VALUES (:id, :liga, :casa, :fora, :oc, :oe, :of, :gc, :gf, :gt, :res, :am, :o05, :o15, :o25, :o35, :o45)
            ON CONFLICT (id_planilha) DO " . ($acao == 'substituir' ? "UPDATE SET liga = EXCLUDED.liga, casa = EXCLUDED.casa..." : "NOTHING");
    
    $stmt = $pdo->prepare($sql);
    // BindParams e Execute aqui...
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="planilha" accept=".xlsx, .xls" required>
    <label><input type="radio" name="acao_duplicado" value="substituir" checked> Substituir Duplicados</label>
    <label><input type="radio" name="acao_duplicado" value="pular"> Pular Duplicados</label>
    <button type="submit">Importar Dados</button>
</form>