<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $confronto = $_POST['p_confronto'];
    $placar = $_POST['p_placar'];
    $odd = str_replace(',', '.', $_POST['p_odd']); // Tratando a vírgula aqui também!

    try {
        $sql = "UPDATE sinais SET p_confronto = ?, p_placar = ?, p_odd = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$confronto, $placar, $odd, $id]);
        
        header("Location: gestao_sinais.php?editado=1");
    } catch (PDOException $e) {
        die("Erro ao atualizar: " . $e->getMessage());
    }
}
