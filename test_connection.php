
<?php
// Teste de diagnóstico de conexão para XAMPP
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Diagnóstico de Conexão - Sistema KEIMADURA</h1>";

// Informações do servidor
echo "<h2>Informações do Servidor:</h2>";
echo "<ul>";
echo "<li><strong>PHP versão:</strong> " . phpversion() . "</li>";
echo "<li><strong>Servidor:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>Sistema operacional:</strong> " . PHP_OS . "</li>";
echo "<li><strong>Nome do host:</strong> " . $_SERVER['HTTP_HOST'] . "</li>";
echo "<li><strong>Diretório atual:</strong> " . __DIR__ . "</li>";
echo "</ul>";

// Verificar extensões
echo "<h2>Extensões Necessárias:</h2>";
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
echo "<ul>";
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li style='color:green'>✓ {$ext} - Instalada</li>";
    } else {
        echo "<li style='color:red'>✗ {$ext} - Não instalada!</li>";
    }
}
echo "</ul>"; "<ul>";
foreach ($extensions as $ext) {
    echo "<li>" . $ext . ": " . (extension_loaded($ext) ? "<span style='color:green'>OK</span>" : "<span style='color:red'>NÃO DISPONÍVEL</span>") . "</li>";
}
echo "</ul>";

// Testar conexão com o banco
echo "<h2>Conexão com o Banco de Dados:</h2>";
try {
    $conn = getConnection();
    echo "<p style='color:green'>✓ Conexão com o banco de dados estabelecida com sucesso!</p>";
    
    // Testar se o banco de dados foi criado
    try {
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Tabelas encontradas: " . count($tables) . "</p>";
        if (count($tables) > 0) {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . $table . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange'>Nenhuma tabela encontrada. Execute a instalação.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:orange'>Banco de dados existe mas ocorreu um erro ao listar tabelas: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Erro na conexão com o banco de dados: " . $e->getMessage() . "</p>";
    echo "<h3>Verifique:</h3>";
    echo "<ul>";
    echo "<li>Se o XAMPP está ligado (Apache e MySQL)</li>";
    echo "<li>Se as credenciais no arquivo config.php estão corretas</li>";
    echo "<li>Se o banco de dados 'keimadura_db' existe (você pode criá-lo no phpMyAdmin)</li>";
    echo "</ul>";
}

echo "<p><a href='install.php'>Ir para página de instalação</a> | <a href='index.php'>Ir para a página inicial</a></p>";
?>
