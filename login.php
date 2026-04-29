<?php
// Tente colocar isso antes de qualquer outra linha
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Arquivo login.php iniciado -->";

if (!file_exists('config/db.php')) {
    die("Erro: O arquivo config/db.php não foi encontrado na raiz!");
}

require_once 'config/db.php';
echo "<!-- Debug: db.php carregado com sucesso -->";

// Vamos comentar o auth_logic por um momento para isolar o erro
// require_once 'modules/auth_logic.php';

<?php
// 1. Inicia a sessão (obrigatório para usar $_SESSION)
session_start();

// 2. Habilita exibição de erros para sabermos exatamente o que acontece
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 3. Conexão com o banco (ajustado para a raiz)
require_once 'config/db.php';

// (Opcional) Se você quiser tentar usar o auth_logic depois, 
// verifique se o caminho dentro dele também está correto.
// require_once 'modules/auth_logic.php'; 

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = $_POST['login'] ?? '';
    $senha_input = $_POST['senha'] ?? '';

    if (!empty($login_input) && !empty($senha_input)) {
        try {
            // Busca o usuário no banco
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ? OR email = ?");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha_input, $user['senha'])) {
                // Verifica status (Grátis entra direto)
                if ($user['status_aprovacao'] == 'Ativo' || $user['perfil'] == 'Grátis') {
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['usuario_nome'] = $user['nome'];
                    $_SESSION['usuario_perfil'] = $user['perfil'];
                    $_SESSION['usuario_creditos'] = $user['creditos'];
                    
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $erro = "Sua conta (" . $user['perfil'] . ") ainda aguarda aprovação.";
                }
            } else {
                // Validação Admin via Environment Variables
                if ($login_input == getenv('ADMIN_EMAIL') && $senha_input == getenv('ADMIN_PASSWORD')) {
                    $erro = "Admin reconhecido, mas precisa ser criado no Banco de Dados primeiro.";
                } else {
                    $erro = "Login ou senha incorretos.";
                }
            }
        } catch (PDOException $e) {
            $erro = "Erro na consulta: " . $e->getMessage();
        }
    } else {
        $erro = "Por favor, preencha todos os campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Sefullbet</title>
</head>
<body>
    <h2>Login Sefullbet</h2>
    <?php if ($erro): ?>
        <p style="color: red;"><?php echo $erro; ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input type="text" name="login" placeholder="Login ou E-mail" required><br><br>
        <input type="password" name="senha" placeholder="Senha" required><br><br>
        <button type="submit">Entrar</button>
    </form>
</body>
</html>
