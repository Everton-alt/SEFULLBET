<?php
require_once 'config/db.php';

if (isset($pdo)) {
    echo "✅ Sucesso! O PHP conseguiu conectar ao banco de dados.";
} else {
    echo "❌ A variável de conexão não foi definida.";
}
?>
