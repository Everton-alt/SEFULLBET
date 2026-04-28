<?php
// modules/palpites.php

function buscarPalpites($pdo, $categoria = 'Grátis', $limite = 10, $offset = 0) {
    $sql = "SELECT * FROM palpites 
            WHERE categoria = ? AND exclusao_pendente = FALSE 
            ORDER BY data DESC, hora DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoria, $limite, $offset]);
    return $stmt->fetchAll();
}

function calcularEstatisticas($pdo, $categoria) {
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Green' THEN 1 ELSE 0 END) as greens,
                SUM(CASE WHEN status = 'Red' THEN 1 ELSE 0 END) as reds
            FROM palpites WHERE categoria = ? AND status != 'Pendente'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoria]);
    $res = $stmt->fetch();
    
    $win_rate = ($res['total'] > 0) ? ($res['greens'] / $res['total']) * 100 : 0;
    
    return [
        'total' => $res['total'],
        'greens' => $res['greens'],
        'reds' => $res['reds'],
        'win_rate' => round($win_rate, 2)
    ];
}
?>