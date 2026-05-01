<?php
session_start();
require_once 'config.php';

// Verifica se os parâmetros necessários existem
if (isset($_GET['id']) && isset($_GET['set'])) {
    $id = (int)$_GET['id'];
    $novoStatus = $_GET['set']; // Recebe 'Green' ou 'Red'

    try {
        // Atualiza o status no banco de dados
        $stmt = $pdo->prepare("UPDATE sinais SET p_status = ? WHERE id = ?");
        $stmt->execute([$novoStatus, $id]);

        // Redireciona de volta para a gestão
        header("Location: gestao_sinais.php?atualizado=1");
        exit;
    } catch (PDOException $e) {
        die("Erro ao atualizar status: " . $e->getMessage());
    }
} else {
    header("Location: gestao_sinais.php");
    exit;
}
