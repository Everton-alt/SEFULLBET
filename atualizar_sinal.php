<?php
session_start();
require_once 'config.php';

// 1. Verificação de segurança (Opcional, mas recomendado)
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 2. Coleta os dados do Modal
    $id        = $_POST['id'] ?? null;
    $confronto = $_POST['p_confronto'] ?? '';
    $placar    = $_POST['p_placar']    ?? '0-0';
    $mercado   = $_POST['p_mercado']   ?? '';
    
    // 3. Tratamento da Odd (Aceita ponto ou vírgula)
    $odd_bruta = $_POST['p_odd'] ?? '0.00';
    $odd_final = str_replace(',', '.', $odd_bruta);

    // Verificação básica se o ID existe
    if (!$id) {
        die("Erro: ID do sinal não encontrado.");
    }

    try {
        // 4. SQL para atualizar todos os campos editáveis
        $sql = "UPDATE sinais 
                SET p_confronto = :conf, 
                    p_placar    = :plac, 
                    p_odd       = :odd, 
                    p_mercado   = :merc 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':conf' => $confronto,
            ':plac' => $placar,
            ':odd'  => $odd_final,
            ':merc' => $mercado,
            ':id'   => $id
        ]);

        // 5. Redireciona de volta com sinal de sucesso
        header("Location: gestao_sinais.php?editado=1");
        exit();

    } catch (PDOException $e) {
        // Em caso de erro, exibe a mensagem para depuração
        die("Erro ao atualizar sinal: " . $e->getMessage());
    }

} else {
    // Se tentarem acessar o arquivo via URL direta
    header("Location: gestao_sinais.php");
    exit();
}
