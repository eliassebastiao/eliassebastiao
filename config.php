<?php
/**
 * Arquivo de configuração para o sistema KEIMADURA
 * Configuração de conexão com o banco de dados MySQL/MariaDB via XAMPP
 */

// Desativar exibição de erros em produção (mudar para false em produção)
ini_set('display_errors', true);
error_reporting(E_ALL);

// Configurações de conexão com o banco de dados (XAMPP)
define('DB_HOST', 'localhost');     // Host do banco de dados (padrão do XAMPP)
define('DB_USER', 'root');          // Usuário do banco (padrão do XAMPP)
define('DB_PASSWORD', '');          // Senha (padrão do XAMPP é vazia)
define('DB_NAME', 'keimadura_db');  // Nome do banco de dados
define('DB_PORT', 3306);            // Porta padrão do MySQL no XAMPP
define('DB_CHARSET', 'utf8mb4');    // Charset para suportar caracteres especiais

// Configurações adicionais do sistema
define('APP_NAME', 'KEIMADURA');
define('APP_VERSION', '2.0.0');
define('APP_DEBUG', true);          // Alterar para false em produção
define('TIMEZONE', 'Africa/Luanda'); // Fuso horário de Angola

// Inicializar fuso horário
date_default_timezone_set(TIMEZONE);

// Função para conectar ao banco de dados
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . 
               ";port=" . DB_PORT . 
               ";dbname=" . DB_NAME . 
               ";charset=" . DB_CHARSET;
               
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            // Adicionar timeout para evitar espera longa
            PDO::ATTR_TIMEOUT => 5, // 5 segundos
        ];
        
        // Verificar se o servidor MySQL está disponível
        $conn = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        
        // Tenta executar uma consulta simples para verificar se a conexão é válida
        $conn->query("SELECT 1");
        
        return $conn;
    } catch (PDOException $e) {
        // Log do erro para diagnóstico
        error_log("Erro de conexão com o banco: " . $e->getMessage());
        
        // Determinar o tipo de erro para mensagem mais informativa
        $errorMessage = "Erro na conexão com o banco de dados.";
        
        if (strpos($e->getMessage(), "Access denied") !== false) {
            $errorMessage = "Acesso negado ao banco de dados. Verifique usuário e senha.";
        } else if (strpos($e->getMessage(), "Unknown database") !== false) {
            $errorMessage = "Banco de dados '".DB_NAME."' não existe. Execute a instalação.";
        } else if (strpos($e->getMessage(), "Connection refused") !== false) {
            $errorMessage = "Conexão recusada. Verifique se o MySQL está rodando.";
        } else if (strpos($e->getMessage(), "Can't connect") !== false) {
            $errorMessage = "Impossível conectar ao MySQL. Verifique se o XAMPP está ativo.";
        }
        
        if (APP_DEBUG) {
            throw new PDOException($errorMessage . " Detalhes: " . $e->getMessage(), $e->getCode());
        } else {
            throw new PDOException($errorMessage, $e->getCode());
        }
    }
}

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}