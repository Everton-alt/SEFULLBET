<?php
require_once 'config.php';
header('Content-Type: application/json');

set_time_limit(300); // 5 minutos de limite
ini_set('memory_limit', '512M');

$input = json_decode(file_get_contents('php://input'), true);
$acao = $input['acao'] ?? 'ignore';
$dados = $input['dados'] ?? [];

if (empty($dados)) { echo json_encode(['mensagem' => 'Vazio']); exit; }

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO base_analisador (id, liga, casa, fora, odd_casa, odd_empate, odd_fora, gol_casa, gol_fora, gols_total, resultado, ambos_marcam, over_05, over_15, over_25, over_35, over_45) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($acao === 'replace') {
        $sql .= " ON CONFLICT (id) DO UPDATE SET 
                  liga=EXCLUDED.liga, casa=EXCLUDED.casa, fora=EXCLUDED.fora, odd_casa=EXCLUDED.odd_casa, 
                  odd_empate=EXCLUDED.odd_empate, odd_fora=EXCLUDED.odd_fora, gol_casa=EXCLUDED.gol_casa, 
                  gol_fora=EXCLUDED.gol_fora, gols_total=EXCLUDED.gols_total, resultado=EXCLUDED.resultado, 
                  ambos_marcam=EXCLUDED.ambos_marcam, over_05=EXCLUDED.over_05, over_15=EXCLUDED.over_15, 
                  over_25=EXCLUDED.over_25, over_35=EXCLUDED.over_35, over_45=EXCLUDED.over_45";
    } else {
        $sql .= " ON CONFLICT (id) DO NOTHING";
    }

    $stmt = $pdo->prepare($sql);

    foreach ($dados as $r) {
        $id = $r['id'] ?? $r['ID'] ?? null;
        if (!$id) continue;

        $stmt->execute([
            $id,
            $r['liga'] ?? '',
            $r['casa'] ?? '',
            $r['fora'] ?? '',
            (float)($r['odd_casa'] ?? 0),
            (float)($r['odd_empate'] ?? 0),
            (float)($r['odd_fora'] ?? 0),
            (int)($r['gol_casa'] ?? 0),
            (int)($r['gol_fora'] ?? 0),
            (int)($r['gols_total'] ?? 0),
            $r['resultado'] ?? '',
            $r['ambos_marcam'] ?? '',
            $r['over_05'] ?? '',
            $r['over_15'] ?? '',
            $r['over_25'] ?? '',
            $r['over_35'] ?? '',
            $r['over_45'] ?? ''
        ]);
    }

    $pdo->commit();
    echo json_encode(['mensagem' => 'Importação concluída: ' . count($dados) . ' registros.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['mensagem' => 'Erro: ' . $e->getMessage()]);
}
