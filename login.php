
<?php
// Iniciar sessão PHP se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir arquivos necessários
require_once('database.php');

// Inicializar o objeto Database
global $db;
$db = new Database();

// Verificar se já está logado
if (isset($_SESSION['usuario'])) {
    // Redirecionar para a página principal
    header('Location: index.html');
    exit;
}

// Processar login
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    if (empty($usuario) || empty($senha)) {
        $mensagem = 'Por favor, preencha todos os campos.';
    } else {
        // Verificar credenciais
        $usuarioLogado = verificarLogin($usuario, $senha);
        
        if ($usuarioLogado) {
            // Login bem-sucedido, salvar na sessão
            $_SESSION['usuario'] = $usuarioLogado;
            
            // Redirecionar para a página principal
            header('Location: index.html');
            exit;
        } else {
            $mensagem = 'Usuário ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>KEIMADURA - Login</title>
    <style>
        .login-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }
        
        .login-logo {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .logo-img {
            width: 200px;
            margin-bottom: 10px;
        }
        
        .logo-text {
            font-size: 16px;
            color: var(--primary-color);
        }
        
        .login-form {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .login-form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .login-button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .login-button:hover {
            background-color: var(--primary-dark);
        }
        
        .error-message {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .server-status {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <div class="logo-container">
                <img src="attached_assets/Logo Keimadura 2.png" alt="KEIMADURA Logo" class="logo-img">
                <div class="logo-text">Sua Melhor Companhia</div>
            </div>
        </div>
        <div class="login-form">
            <form method="post" action="login.php">
                <?php if (!empty($mensagem)): ?>
                    <div class="error-message"><?php echo $mensagem; ?></div>
                <?php endif; ?>
                
                <input type="text" name="usuario" placeholder="Nome de usuário" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <button type="submit" class="login-button">Entrar</button>
            </form>
            <div class="server-status">
                Sistema KEIMADURA v1.0
            </div>
        </div>
    </div>
</body>
</html>
