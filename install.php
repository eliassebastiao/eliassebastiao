
<?php
require_once 'database.php';

// Inicializar a classe Database
$db = new Database();

$installed = false;
$error = null;

if (isset($_POST['install'])) {
    try {
        $db->criarEstruturaBanco();
        $installed = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - KEIMADURA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0499e2;
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 200px;
        }
        .steps {
            margin-bottom: 30px;
        }
        .step {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .step.success {
            background-color: #e6f7e6;
            border-left: 4px solid #4CAF50;
        }
        .step.error {
            background-color: #ffecec;
            border-left: 4px solid #f44336;
        }
        .step h3 {
            margin-top: 0;
            color: #333;
        }
        .buttons {
            text-align: center;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            color: white;
            background-color: #0499e2;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0378b3;
        }
        .btn-success {
            background-color: #4CAF50;
        }
        .btn-success:hover {
            background-color: #3e8e41;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="attached_assets/Logo Keimadura 2.png" alt="KEIMADURA Logo">
        </div>
        <h1>Instalação do Sistema KEIMADURA</h1>
        
        <?php if ($installed): ?>
            <div class="step success">
                <h3><i class="fas fa-check-circle"></i> Instalação Concluída</h3>
                <p>O sistema KEIMADURA foi instalado com sucesso! Todas as tabelas foram criadas no banco de dados e os usuários iniciais foram configurados.</p>
                <p>Você pode agora acessar o sistema usando as seguintes credenciais:</p>
                <ul>
                    <li><strong>Administrador:</strong> Usuário: Keimadura / Senha: keimaduraadmin</li>
                    <li><strong>Funcionário 1:</strong> Usuário: keimaduraserviço1 / Senha: serviço1</li>
                    <li><strong>Funcionário 2:</strong> Usuário: keimaduraserviço2 / Senha: serviço2</li>
                </ul>
            </div>
            <div class="buttons">
                <a href="index.php" class="btn btn-success">Acessar o Sistema</a>
            </div>
        <?php elseif ($error): ?>
            <div class="step error">
                <h3><i class="fas fa-exclamation-triangle"></i> Erro na Instalação</h3>
                <p>Ocorreu um erro durante o processo de instalação:</p>
                <pre><?php echo htmlspecialchars($error); ?></pre>
                <p>Verifique as configurações no arquivo config.php e tente novamente.</p>
            </div>
            <div class="buttons">
                <form method="post">
                    <button type="submit" name="install" class="btn">Tentar Novamente</button>
                </form>
            </div>
        <?php else: ?>
            <div class="steps">
                <div class="step">
                    <h3><i class="fas fa-database"></i> Instalação do Banco de Dados</h3>
                    <p>Este processo irá:</p>
                    <ul>
                        <li>Criar todas as tabelas necessárias no banco de dados</li>
                        <li>Configurar usuários iniciais do sistema</li>
                        <li>Preparar o ambiente para uso imediato</li>
                    </ul>
                    <p>Certifique-se de que as configurações do banco de dados estão corretas no arquivo <code>config.php</code>.</p>
                </div>
                
                <div class="step">
                    <h3><i class="fas fa-user-shield"></i> Configurações de Segurança</h3>
                    <p>Após a instalação, é recomendado:</p>
                    <ul>
                        <li>Alterar as senhas padrão dos usuários</li>
                        <li>Verificar as permissões de arquivos e diretórios</li>
                        <li>Configurar um backup regular do banco de dados</li>
                    </ul>
                </div>
            </div>
            
            <div class="buttons">
                <form method="post">
                    <button type="submit" name="install" class="btn">Instalar Sistema</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
