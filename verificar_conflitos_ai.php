<?php
require_once 'config.php';
header('Content-Type: application/json');

$dados = json_decode(file_get_contents('php://input'), true);
if (empty($dados)) { echo json_encode(['conflitos' => []]); exit; }

$ids = array_filter(array_map(fn($d) => $d['id'] ?? $d['ID'] ?? null, $dados));

if (empty($ids)) { echo json_encode(['conflitos' => []]); exit; }

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id::text FROM base_analisador WHERE id::text IN ($placeholders)");
$stmt->execute(array_values($ids));
$existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

$conflitos = array_filter($dados, function($d) use ($existentes) {
    $id = (string)($d['id'] ?? $d['ID']);
    return in_array($id, $existentes);
});

echo json_encode(['conflitos' => array_values($conflitos)]);
