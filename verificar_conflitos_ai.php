<?php
require_once 'config.php';
header('Content-Type: application/json');

// Recebe os dados enviados via Fetch
$json = file_get_contents('php://input');
$dadosPlanilha = json_decode($json, true);

if (empty($dadosPlanilha)) {
    echo json_encode(['conflitos' => []]);
    exit;
}

// Coleta todos os IDs da planilha (tenta 'id' ou 'ID')
$idsEnviados = array_map(function($item) {
    return $item['id'] ?? $item['ID'] ?? null;
}, $dadosPlanilha);

$idsEnviados = array_filter($idsEnviados); // Remove nulos

if (empty($idsEnviados)) {
    echo json_encode(['conflitos' => []]);
    exit;
}

try {
    // Cria uma lista de interrogações para o SQL (ex: ?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($idsEnviados), '?'));
    
    // Busca na tabela base_analisador quais desses IDs já existem
    $sql = "SELECT id FROM base_analisador WHERE id::text IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($idsEnviados));
    $idsNoBanco = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Filtra os dados da planilha que estão em conflito
    $conflitos = array_filter($dadosPlanilha, function($linha) use ($idsNoBanco) {
        $idAtual = $linha['id'] ?? $linha['ID'];
        return in_array((string)$idAtual, array_map('strval', $idsNoBanco));
    });

    echo json_encode(['conflitos' => array_values($conflitos)]);

} catch (PDOException $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
