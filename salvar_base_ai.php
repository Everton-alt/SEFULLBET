<?php
require_once 'config.php';
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$input = json_decode($json, true);

$acao = $input['acao'] ?? 'ignore';
$dados = $input['dados'] ?? [];

if (empty($dados)) {
    echo json_encode(['mensagem' => 'Nenhum dado para salvar.']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($dados as $r) {
        // Mapeia os campos da planilha para as colunas do banco
        // Tratamos id, liga, casa, fora e as odds/gols
        $params = [
            $r['id'] ?? $r['ID'],
            $r['liga'] ?? '',
            $r['casa'] ?? '',
            $r['fora'] ?? '',
            $r['odd_casa'] ?? 0,
            $r['odd_empate'] ?? 0,
            $r['odd_fora'] ?? 0,
            $r['gol_casa'] ?? 0,
            $r['gol_fora'] ?? 0,
            $r['gols_total'] ?? 0,
            $r['resultado'] ?? '',
            $r['ambos_marcam'] ?? '',
            $r['over_05'] ?? '',
            $r['over_15'] ?? '',
            $r['over_25'] ?? '',
            $r['over_35'] ?? '',
            $r['over_45'] ?? ''
        ];

        if ($acao === 'replace') {
            // Se o ID existir, ele atualiza os dados (UPDATE)
            $sql = "INSERT INTO base_analisador (
                        id, liga, casa, fora, odd_casa, odd_empate, odd_fora, 
                        gol_casa, gol_fora, gols_total, resultado, ambos_marcam, 
                        over_05, over_15, over_25, over_35, over_45
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON CONFLICT (id) DO UPDATE SET 
                        liga = EXCLUDED.liga, casa = EXCLUDED.casa, fora = EXCLUDED.fora,
                        odd_casa = EXCLUDED.odd_casa, odd_empate = EXCLUDED.odd_empate, 
                        odd_fora = EXCLUDED.odd_fora, gol_casa = EXCLUDED.gol_casa,
                        gol_fora = EXCLUDED.gol_fora, gols_total = EXCLUDED.gols_total,
                        resultado = EXCLUDED.resultado, ambos_marcam = EXCLUDED.ambos_marcam,
                        over_05 = EXCLUDED.over_05, over_15 = EXCLUDED.over_15,
                        over_25 = EXCLUDED.over_25, over_35 = EXCLUDED.over_35,
                        over_45 = EXCLUDED.over_45";
        } else {
            // Se o ID existir, ele ignora e pula para o próximo (IGNORE)
            $sql = "INSERT INTO base_analisador (
                        id, liga, casa, fora, odd_casa, odd_empate, odd_fora, 
                        gol_casa, gol_fora, gols_total, resultado, ambos_marcam, 
                        over_05, over_15, over_25, over_35, over_45
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON CONFLICT (id) DO NOTHING";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    $pdo->commit();
    echo json_encode(['mensagem' => 'Importação concluída com sucesso!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['mensagem' => 'Erro crítico: ' . $e->getMessage()]);
}
