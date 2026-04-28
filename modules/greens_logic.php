<?php
// modules/greens_logic.php

function buscarGreensGrid($pdo, $limite = 4, $offset = 0) {
    $sql = "SELECT * FROM greens 
            WHERE exclusao_pendente = FALSE 
            ORDER BY criado_em DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limite, $offset]);
    return $stmt->fetchAll();
}

// Esta função será chamada via AJAX pelo scripts.js para o Popup
if (isset($_GET['acao']) && $_GET['acao'] == 'detalhes_green') {
    require_once '../config/db.php';
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM greens WHERE codigo_green = ?");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch());
    exit();
}
?>