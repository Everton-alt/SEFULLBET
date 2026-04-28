<?php
// modules/auth_logic.php

session_start();

// Função para gerar o ID U-XXXXXX
function gerarCodigoUsuario($pdo) {
    do {
        $codigo = 'U-' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE codigo_usuario = ?");
        $stmt->execute([$codigo]);
    } while ($stmt->fetch());
    return $codigo;
}

// Verifica se o usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../public/login.php");
        exit();
    }
}

// Verifica se o usuário tem permissão de Admin/Supervisor
function verificarPermissaoAdmin() {
    $perfis_autorizados = ['Admin', 'Supervisor'];
    if (!in_array($_SESSION['usuario_perfil'], $perfis_autorizados)) {
        header("Location: ../public/dashboard.php?erro=sem_permissao");
        exit();
    }
}
?>