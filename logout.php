<?php
// Inicia a sessão para poder destruí-la
session_start();

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Se desejar destruir o cookie da sessão também (mais seguro)
if (ini_get("session_use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão no servidor
session_destroy();

// Redireciona o usuário de volta para a página inicial (Index)
header("Location: index.html");
exit;
?>
