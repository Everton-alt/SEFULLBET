<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Coleta e sanitiza os dados
    $categoria = $_POST['p_categoria'] ?? 'Grátis';
    $confronto = $_POST['p_confronto'] ?? 'Indefinido';
    $data      = $_POST['p_data']      ?? date('Y-m-d');
    $hora      = $_POST['p_hora']      ?? date('H:i');
    $mercado   = $_POST['p_mercado']   ?? '';
    $odd       = $_POST['p_odd']       ?? '0.00';
    $placar    = $_POST['p_placar']    ?? '- x -';
    $status    = 'Pendente';

    try {
        // 2. Query limpa sem p_codigo (o banco gera sozinho)
        // Removi os espaços especiais que estavam causando o erro de sintaxe
        $sql = "INSERT INTO sinais (p_categoria, p_confronto, p_data, p_hora, p_mercado, p_odd, p_placar, p_status) 
                VALUES (:cat, :conf, :data_ev, :hora_ev, :merc, :odd, :placar, :status)";
        
        $stmt = $pdo->prepare($sql);
        
        // 3. Execução direta
        $stmt->execute([
            ':cat'     => $categoria,
            ':conf'    => $confronto,
            ':data_ev' => $data,
            ':hora_ev' => $hora,
            ':merc'    => $mercado,
            ':odd'     => $odd,
            ':placar'  => $placar,
            ':status'  => $status
        ]);

        header("Location: gestao_sinais.php?sucesso=1");
        exit;

    } catch (PDOException $e) {
        // Isso vai nos mostrar exatamente se sobrar algum erro de sintaxe
        die("Erro SQL: " . $e->getMessage());
    }
} else {
    header("Location: gestao_sinais.php");
    exit;
}
