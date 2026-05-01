<?php
// 1. Iniciar a sessão e configurar erros
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Carregar a conexão
require_once 'config.php'; 

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Captura e limpa espaços extras
    $login_input = isset($_POST['login']) ? trim($_POST['login']) : '';
    $senha_input = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if (!empty($login_input) && !empty($senha_input)) {
        try {
            // Busca o usuário pelo login ou e-mail
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ? OR email = ?");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch();

            // Verifica se o usuário existe e se a senha (hash) é compatível
            if ($user && password_verify($senha_input, $user['senha'])) {
                
                // VERIFICAÇÃO DE STATUS (Note que usei 'status' conforme sua lógica de banco anterior)
                if ($user['status'] !== 'Ativo') {
                    $erro = "Sua conta está: " . $user['status'] . ". Aguarde a liberação do Admin.";
                } else {
                    // Define as variáveis de sessão
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['nome']       = $user['nome'];
                    $_SESSION['perfil']     = $user['perfil'];
                    
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $erro = "Usuário ou senha inválidos.";
            }
        } catch (PDOException $e) {
            $erro = "Erro técnico. Por favor, tente novamente mais tarde.";
        }
    } else {
        $erro = "Preencha todos os campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acessar Conta | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00ff88; --bg: #0d1117; --sidebar: #161b22; --border: #30363d; --error: #ff4d4d; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body { background: var(--bg); color: #c9d1d9; display: flex; flex-direction: column; min-height: 100vh; }

        /* HEADER (IGUAL AO CADASTRO) */
        header { 
            background: var(--sidebar); padding: 15px 40px; display: flex; 
            justify-content: space-between; align-items: center; 
            border-bottom: 1px solid var(--border); box-shadow: 0 4px 15px rgba(0,0,0,0.3); 
            position: sticky; top: 0; z-index: 100;
        }
        .logo { font-weight: 900; font-size: 1.2rem; text-decoration: none; color: #fff; letter-spacing: 1px; }
        .logo span { color: var(--primary); }
        .btn-voltar { color: #8b949e; text-decoration: none; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 8px; transition: 0.3s; text-transform: uppercase; }
        .btn-voltar:hover { color: var(--primary); }

        /* CONTEÚDO CENTRAL */
        .main-content { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; }
        .login-card { background: var(--sidebar); width: 100%; max-width: 400px; padding: 40px; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        .auth-header { text-align: center; margin-bottom: 30px; }
        .auth-header h1 { font-size: 1.8rem; color: #fff; font-weight: 900; }
        .auth-header h1 span { color: var(--primary); }
        .auth-header p { font-size: 0.85rem; color: #8b949e; margin-top: 5px; }

        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; font-size: 0.70rem; font-weight: 700; color: #8b949e; margin-bottom: 6px; text-transform: uppercase; }
        
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #484f58; }
        
        input { width: 100%; padding: 12px 15px 12px 45px; background: #0d1117; border: 1px solid var(--border); border-radius: 10px; color: #fff; outline: none; transition: 0.3s; }
        input:focus { border-color: var(--primary); box-shadow: 0 0 10px rgba(0, 255, 136, 0.1); }

        button { width: 100%; padding: 15px; background: var(--primary); color: #0b0e14; border: none; border-radius: 10px; font-weight: 900; cursor: pointer; text-transform: uppercase; transition: 0.3s; margin-top: 10px; }
        button:hover { transform: scale(1.02); filter: brightness(1.1); }

        .error { background: rgba(255, 77, 77, 0.1); color: var(--error); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid var(--error); text-align: center; }
        
        .links { margin-top: 25px; text-align: center; font-size: 0.85rem; color: #8b949e; }
        .links a { color: var(--primary); text-decoration: none; font-weight: bold; }

        /* RODAPÉ */
        footer { background: var(--sidebar); border-top: 1px solid var(--border); padding: 30px 20px; text-align: center; }
        .footer-text { max-width: 600px; margin: 0 auto; font-size: 0.75rem; color: #8b949e; line-height: 1.6; }
        .footer-text b { color: #fff; display: block; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

    <header>
        <a href="index.html" class="logo">SEFULL <span>BET</span></a>
        <a href="index.html" class="btn-voltar"><i class="fa-solid fa-house"></i> Início</a>
    </header>

    <div class="main-content">
        <div class="login-card">
            <div class="auth-header">
                <h1>LOGIN <span>BET</span></h1>
                <p>Acesse sua inteligência de dados</p>
            </div>
            
            <?php if ($erro): ?>
                <div class="error"><?php echo $erro; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Usuário ou E-mail</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="login" placeholder="Digite seu acesso" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Sua Senha</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="senha" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit">Entrar no Sistema</button>
            </form>

            <div class="links">
                Não tem conta? <a href="cadastro.php">Registrar Agora</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-text">
            <b>SEFULLBET</b>
            © 2026 SeFullBet - Inteligência de Dados aplicada ao Esporte.<br>
            Apostas são para maiores de 18 anos. Jogue com responsabilidade.
        </div>
    </footer>

</body>
</html>
