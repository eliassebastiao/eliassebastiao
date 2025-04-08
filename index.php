
<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar conexão com o banco de dados
require_once 'config.php';
require_once 'database.php';

try {
    $conn = getConnection();
    $dbOk = true;
} catch (Exception $e) {
    $dbOk = false;
    $dbError = $e->getMessage();
}

// Redirecionar para o instalador se necessário
if (!$dbOk) {
    header("Location: install.php");
    exit;
}

// Verificar se está logado, senão redirecionar para login.php
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Servir o HTML principal
include 'index.html';
?>
