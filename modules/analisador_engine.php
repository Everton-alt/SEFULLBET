<?php
// modules/analisador_engine.php

function processarAnaliseSefullbet($pdo, $usuario_id) {
    // 1. Verificar créditos e perfil
    $stmt = $pdo->prepare("SELECT perfil, creditos, status_aprovacao FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $user = $stmt->fetch();

    if ($user['status_aprovacao'] !== 'Ativo') return ['erro' => 'Conta aguardando aprovação.'];
    if ($user['perfil'] !== 'Platinum' && $user['creditos'] <= 0) return ['erro' => 'Saldo insuficiente.'];

    // 2. Lógica de Análise (Espelho da versão anterior)
    // Busca na tabela dados_analisador os padrões de maior odd vs maior probabilidade
    $sql = "SELECT casa, fora, resultado, over_25, odd_casa, odd_fora 
            FROM dados_analisador 
            ORDER BY RANDOM() LIMIT 3"; // Aqui entra o seu algoritmo específico de cálculo
    $analises = $pdo->query($sql)->fetchAll();

    // 3. Debitar Crédito se não for Platinum
    if ($user['perfil'] !== 'Platinum') {
        $update = $pdo->prepare("UPDATE usuarios SET creditos = creditos - 1 WHERE id = ?");
        $update->execute([$usuario_id]);
    }

    return ['sucesso' => true, 'resultados' => $analises];
}
?>