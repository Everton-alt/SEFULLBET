<?php
session_start();
require_once 'config.php';

// Proteção simples: Verifica se é Admin ou Supervisor antes de processar
// (Opcional, mas recomendado se você já tem a sessão do usuário disponível)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Coleta e sanitiza os dados
    $categoria = $_POST['p_categoria'] ?? 'Grátis';
    $confronto = $_POST['p_confronto'] ?? 'Indefinido';
    $data      = $_POST['p_data']      ?? date('Y-m-d');
    $hora      = $_POST['p_hora']      ?? date('H:i');
    $mercado   = $_POST['p_mercado']   ?? '';
    $placar    = $_POST['p_placar']    ?? '0-0';
    $status    = 'Pendente';

    // --- TRATAMENTO DA ODD (CORREÇÃO PARA O ERRO SQL) ---
    // Remove qualquer caractere que não seja número, ponto ou vírgula
    $odd_bruta = $_POST['p_odd'] ?? '0.00';
    // Substitui a vírgula pelo ponto para que o banco (numeric/decimal) aceite
    $odd_formatada = str_replace(',', '.', $odd_bruta);
    // ----------------------------------------------------

    try {
        // 2. Query de Inserção
        // Certifique-se de que as colunas p_data, p_hora e p_odd existam na sua tabela
        $sql = "INSERT INTO sinais (p_categoria, p_confronto, p_data, p_hora, p_mercado, p_odd, p_placar, p_status) 
                VALUES (:cat, :conf, :data_ev, :hora_ev, :merc, :odd, :placar, :status)";
        
        $stmt = $pdo->prepare($sql);
        
        // 3. Execução com os dados tratados
        $stmt->execute([
            ':cat'     => $categoria,
            ':conf'    => $confronto,
            ':data_ev' => $data,
            ':hora_ev' => $hora,
            ':merc'    => $mercado,
            ':odd'     => $odd_formatada, // Envia o valor com ponto decimal
            ':placar'  => $placar,
            ':status'  => $status
        ]);

        // Redireciona com sucesso
        header("Location: gestao_sinais.php?sucesso=1");
        exit;

    } catch (PDOException $e) {
        // Exibe o erro de forma clara para depuração
        die("Erro SQL ao salvar sinal: " . $e->getMessage());
    }
} else {
    // Se tentarem acessar o arquivo diretamente via URL
    header("Location: gestao_sinais.php");
    exit;
}
