<?php
require_once 'config.php';

// Evita o erro de sessão já ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensagem = ""; 
$erro = "";

// Ajuste Cirúrgico 1: Captura o plano da URL para manter a experiência do usuário
$plano_pre_selecionado = $_GET['plano'] ?? 'Grátis';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $login = trim($_POST['login']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $plano_escolhido = $_POST['plano'];

    // LÓGICA DE ACESSO IMEDIATO:
    $perfil_inicial = 'Grátis';
    $creditos_iniciais = 1;
    $status_inicial = 'Ativo';

    try {
        // Ajuste Cirúrgico 2: SQL alinhado com a coluna saldo_creditos da Dashboard
        $sql = "INSERT INTO usuarios (nome, login, email, senha, perfil, saldo_creditos, status, plano_interesse) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $login, $email, $senha, $perfil_inicial, $creditos_iniciais, $status_inicial, $plano_escolhido]);
        
        $_SESSION['usuario_id'] = $pdo->lastInsertId();
        $_SESSION['usuario_nome'] = $nome;

        header("Location: dashboard.php");
        exit();
        
    } catch (PDOException $e) { 
        if ($e->getCode() == 23000) {
            $erro = "Este Login ou E-mail já está em uso.";
        } else {
            $erro = "Erro no banco: " . $e->getMessage(); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta | SeFull Bet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00ff88; --bg: #0d1117; --sidebar: #161b22; --border: #30363d; --error: #ff4d4d; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--bg); color: #c9d1d9; display: flex; flex-direction: column; min-height: 100vh; }

        header { background: var(--sidebar); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }
        .logo { font-weight: 900; font-size: 1.2rem; text-decoration: none; color: #fff; }
        .logo span { color: var(--primary); }
        .btn-voltar { color: #8b949e; text-decoration: none; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        .main-content { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; }
        .auth-card { background: var(--sidebar); width: 100%; max-width: 450px; padding: 40px; border-radius: 20px; border: 1px solid var(--border); }
        .auth-header { text-align: center; margin-bottom: 30px; }
        .auth-header h1 { font-size: 1.8rem; color: #fff; font-weight: 900; }
        .auth-header h1 span { color: var(--primary); }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.70rem; font-weight: 700; color: #8b949e; margin-bottom: 6px; text-transform: uppercase; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #484f58; }
        .form-group input, .form-group select { width: 100%; padding: 12px 15px 12px 45px; background: #0d1117; border: 1px solid var(--border); border-radius: 10px; color: #fff; outline: none; }
        
        .msg { padding: 12px; border-radius: 10px; font-size: 0.85rem; text-align: center; margin-bottom: 20px; border: 1px solid; }
        .msg-success { background: rgba(0, 255, 136, 0.1); color: var(--primary); border-color: var(--primary); }
        .msg-error { background: rgba(255, 77, 77, 0.1); color: var(--error); border-color: var(--error); }

        .terms-box { display: flex; align-items: flex-start; gap: 10px; margin: 20px 0; }
        .terms-box input { margin-top: 3px; accent-color: var(--primary); }
        .terms-box label { font-size: 0.75rem; color: #8b949e; line-height: 1.4; }
        .terms-box span { color: var(--primary); text-decoration: underline; font-weight: 600; cursor: pointer; }

        .btn-registrar { width: 100%; padding: 15px; background: var(--primary); color: #0b0e14; border: none; border-radius: 10px; font-weight: 900; cursor: pointer; text-transform: uppercase; opacity: 0.5; pointer-events: none; transition: 0.3s; }
        .btn-registrar.active { opacity: 1; pointer-events: all; }

        #modal-terms { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: var(--sidebar); max-width: 500px; width: 90%; border-radius: 20px; border: 1px solid var(--border); max-height: 80vh; display: flex; flex-direction: column; }
        .modal-body { padding: 30px; overflow-y: auto; color: #b1bac4; font-size: 0.85rem; }
        .modal-body h2 { color: #fff; margin-bottom: 15px; font-size: 1.2rem; }
        .modal-body b { color: var(--primary); }
        .modal-footer { padding: 20px; border-top: 1px solid var(--border); }
    </style>
</head>
<body>

    <header>
        <a href="index.html" class="logo">SEFULL <span>BET</span></a>
        <a href="index.html" class="btn-voltar"><i class="fa-solid fa-arrow-left"></i> Início</a>
    </header>

    <div class="main-content">
        <div class="auth-card">
            <div class="auth-header">
                <h1>SEFULL <span>BET</span></h1>
                <p>Crie sua conta e ganhe acesso imediato</p>
            </div>

            <?php if($mensagem): ?> <div class="msg msg-success"><?= $mensagem ?></div> <?php endif; ?>
            <?php if($erro): ?> <div class="msg msg-error"><?= $erro ?></div> <?php endif; ?>

            <form id="formCadastro" method="POST">
                <div class="form-group">
                    <label>Nome Completo</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="nome" placeholder="João Silva" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Login</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-at"></i>
                        <input type="text" name="login" placeholder="joao_bet" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>E-mail</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" placeholder="seu@email.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Plano de Interesse</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-crown"></i>
                        <select name="plano" required>
                            <option value="Grátis" <?= $plano_pre_selecionado == 'Grátis' ? 'selected' : '' ?>>Grátis (1 Crédito)</option>
                            <option value="VIP" <?= $plano_pre_selecionado == 'VIP' ? 'selected' : '' ?>>VIP (30 Créditos/Mês)</option>
                            <option value="Platinum" <?= $plano_pre_selecionado == 'Platinum' ? 'selected' : '' ?>>Platinum (Ilimitado)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Senha</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="senha" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="terms-box">
                    <input type="checkbox" id="checkTermos" onchange="toggleBotao()">
                    <label for="checkTermos">Li e concordo com os <span onclick="abrirTermos()">Termos e Condições de Uso</span>.</label>
                </div>

                <button type="submit" id="btnSubmit" class="btn-registrar">Registrar Agora</button>
            </form>
        </div>
    </div>

    <div id="modal-terms">
        <div class="modal-content">
            <div class="modal-body">
                <h2>Termos e Condições de Uso</h2>
                <p><b>1. Natureza do Serviço:</b> A SeFull Bet é uma plataforma de fornecimento de conteúdo informativo, estatístico e de análise probabilística baseada em algoritmos e dados históricos. Não somos uma casa de apostas, não aceitamos depósitos de valores e não intermediamos transações financeiras.</p><br>
                <p><b>2. Isenção de Responsabilidade:</b> O usuário declara estar ciente de que o mercado de apostas esportivas envolve risco financeiro real. Ausência de Garantia: A SeFull Bet não garante lucros. A decisão final é de responsabilidade exclusiva do usuário.</p><br>
                <p><b>3. Responsabilidade do Usuário:</b> Ao utilizar o site, o usuário compromete-se a: Ser maior de 18 anos; utilizar os dados apenas para fins informativos; assumir total responsabilidade civil e técnica por suas ações externas.</p><br>
                <p><b>4. Limitação Jurídica:</b> En nenhuma circunstância a SeFull Bet será responsabilizada por Danos Indiretos, Falhas Técnicas ou Sanções Jurídicas locais aplicadas ao usuário.</p><br>
                <p><b>5. Propriedade Intelectual:</b> Todo o conteúdo e algoritmos são de propriedade da SeFull Bet.</p><br>
                <p><b>6. Modificações:</b> Reservamo-nos o direito de alterar estes termos a qualquer momento.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-registrar active" onclick="concordarTermos()">Eu concordo com os termos e registrar</button>
            </div>
        </div>
    </div>

    <script>
        function abrirTermos() { document.getElementById('modal-terms').style.display = 'flex'; }
        function concordarTermos() {
            document.getElementById('checkTermos').checked = true;
            document.getElementById('modal-terms').style.display = 'none';
            toggleBotao();
        }
        function toggleBotao() {
            const check = document.getElementById('checkTermos').checked;
            document.getElementById('btnSubmit').className = check ? 'btn-registrar active' : 'btn-registrar';
        }
    </script>
</body>
</html>
