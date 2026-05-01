<?php
session_start();
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        // Deleta o registro pelo ID único
        $stmt = $pdo->prepare("DELETE FROM sinais WHERE id = ?");
        $stmt->execute([$id]);

        // Redireciona de volta com confirmação de exclusão
        header("Location: gestao_sinais.php?excluido=1");
        exit;
    } catch (PDOException $e) {
        die("Erro ao excluir sinal: " . $e->getMessage());
    }
} else {
    header("Location: gestao_sinais.php");
    exit;
}
