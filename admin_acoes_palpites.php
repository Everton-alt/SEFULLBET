<?php
include 'config.php';
session_start();

$id_item = $_GET['id'];
$acao = $_GET['acao']; // 'excluir'
$perfil = $_SESSION['perfil'];

if ($acao === 'excluir') {
    if ($perfil === 'Supervisor') {
        // A TRAVA: Marca como pendente de exclusão (conforme escopo 4)
        $stmt = $pdo->prepare("UPDATE palpites SET pendente_exclusao = TRUE WHERE id = ?");
        $stmt->execute([$id_item]);
        echo "Item ocultado. Aguardando decisão final do Admin.";
    } elseif ($perfil === 'Admin') {
        // EXCLUSÃO TOTAL: Só o Admin apaga do banco
        $stmt = $pdo->prepare("DELETE FROM palpites WHERE id = ?");
        $stmt->execute([$id_item]);
        echo "Item deletado permanentemente.";
    }
}
header("Location: dashboard.php");
