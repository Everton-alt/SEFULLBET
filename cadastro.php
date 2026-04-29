<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Sefullbet</title>
    <style>
        :root {
            --primary: #00ff88;
            --bg: #080a0f;
            --card: #12151c;
            --border: #262c3a;
            --text: #a0aec0;
        }

        body {
            background-color: var(--bg);
            color: white;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .cadastro-card {
            background: var(--card);
            border: 1px solid var(--border);
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }

        h2 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .input-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.85rem;
            color: var(--text);
        }

        input, select {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: white;
            box-sizing: border-box;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
        }

        button {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }

        button:hover {
            background: #00cc6e;
            transform: translateY(-2px);
        }

        .footer-links {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
            color: var(--text);
        }

        .footer-links a {
            color: var(--primary);
            text-decoration: none;
        }

        .termos-box {
            font-size: 0.75rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .termos-box input {
            width: auto;
        }
    </style>
</head>
<body>

<div class="cadastro-card">
    <h2>Sefullbet</h2>
    
    <form action="processa_cadastro.php" method="POST">
        
        <div class="input-group">
            <label>Nome Completo</label>
            <input type="text" name="nome" placeholder="Ex: João Silva" required>
        </div>

        <div class="input-group">
            <label>Usuário (Login)</label>
            <input type="text" name="login" placeholder="Ex: joao_bet" required>
        </div>

        <div class="input-group">
            <label>E-mail</label>
            <input type="email" name="email" placeholder="seu@email.com" required>
        </div>

        <div class="input-group">
            <label>Senha</label>
            <input type="password" name="senha" placeholder="Minimo 6 caracteres" required>
        </div>

        <div class="input-group">
            <label>Plano Desejado</label>
            <select name="perfil">
                <option value="Grátis">Plano Grátis</option>
                <option value="VIP">Plano VIP</option>
                <option value="Platinum">Plano Platinum</option>
            </select>
        </div>

        <div class="termos-box">
            <input type="checkbox" required>
            <span>Eu li e aceito os <a href="termos.php" target="_blank">Termos de Uso</a></span>
        </div>

        <button type="submit">Solicitar Acesso</button>
    </form>

    <div class="footer-links">
        Já tem uma conta? <a href="login.php">Faça Login</a>
    </div>
</div>

</body>
</html>
