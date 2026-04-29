<?php
require_once 'config.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $total = $stmt->fetchColumn();
    echo "✅ Conexão OK! Total de usuários no banco: " . $total;
} catch (Exception $e) {
    echo "❌ Erro de conexão: " . $e->getMessage();
}
