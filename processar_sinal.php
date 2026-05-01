<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Coleta e sanitiza os dados do formulário
    $categoria = $_POST['p_categoria'] ?? 'Grátis';
    $confronto = $_POST['p_confronto'] ?? 'Indefinido';
    $data      = $_POST['p_data']      ?? date('Y-m-d');
    $hora      = $_POST['p_hora']      ?? date('H:i');
    $mercado   = $_POST['p_mercado']   ?? '';
    $odd       = $_POST['p_odd']       ?? '0.00';
    $placar    = $_POST['p_placar']    ?? '- x -';
    $status    = 'Pendente'; // Todo sinal novo nasce pendente

    // 2. Gera um código único (Ex: S-84291)
    $codigo = 'S-' . rand(10000, 99999);

    try {
        // 3. Prepara a Query SQL para inserção
        $sql = "INSERT INTO sinais (p_codigo, p_categoria, p_confronto, p_data, p_hora, p_mercado, p_odd, p_placar, p_status) 
                VALUES (:codigo, :cat, :conf, :data_ev, :hora_ev, :merc, :odd, :placar, :status)";
        
        $stmt = $pdo->prepare($sql);
        
        // 4. Executa a inserção vinculando os valores
        $stmt->execute([
            ':codigo'  => $codigo,
            ':cat'     => $categoria,
            ':conf'    => $confronto,
            ':data_ev' => $data,
            ':hora_ev' => $hora,
            ':merc'    => $mercado,
            ':odd'     => $odd,
            ':placar'  => $placar,
            ':status'  => $status
        ]);

        // 5. Redireciona de volta para a gestão com sucesso
        header("Location: gestao_sinais.php?sucesso=1");
        exit;

    } catch (PDOException $e) {
        // Em caso de erro, você pode tratar aqui
        die("Erro ao salvar no banco de dados: " . $e->getMessage());
    }
} else {
    // Se tentarem acessar o arquivo diretamente sem POST, manda de volta
    header("Location: gestao_sinais.php");
    exit;
}
