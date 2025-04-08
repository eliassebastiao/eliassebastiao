<?php
/**
 * Arquivo de funções para interação com banco de dados para o sistema KEIMADURA
 * Inclui funções para consultas, inserções, atualizações e outras operações
 */

require_once 'config.php';

/**
 * Classe Database - Gerencia operações com o banco de dados
 */
class Database {
    private $conn;

    /**
     * Construtor da classe Database
     */
    public function __construct() {
        $this->conn = getConnection();
    }

    /**
     * Executa uma consulta SQL
     * 
     * @param string $sql Consulta SQL
     * @param array $params Parâmetros para a consulta
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Obtém um único registro
     * 
     * @param string $sql Consulta SQL
     * @param array $params Parâmetros para a consulta
     * @return array|false Registro encontrado ou false
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Obtém múltiplos registros
     * 
     * @param string $sql Consulta SQL
     * @param array $params Parâmetros para a consulta
     * @return array Registros encontrados
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insere dados em uma tabela
     * 
     * @param string $table Nome da tabela
     * @param array $data Dados a serem inseridos
     * @return int ID do registro inserido
     */
    public function insert($table, $data) {
        $fields = array_keys($data);

        $sql = "INSERT INTO $table (" . implode(", ", $fields) . ") 
                VALUES (:" . implode(", :", $fields) . ")";

        $stmt = $this->conn->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
        return $this->conn->lastInsertId();
    }

    /**
     * Atualiza dados em uma tabela
     * 
     * @param string $table Nome da tabela
     * @param array $data Dados a serem atualizados
     * @param string $where Condição WHERE
     * @param array $whereParams Parâmetros para a condição WHERE
     * @return int Número de registros afetados
     */
    public function update($table, $data, $where, $whereParams = []) {
        $fields = [];

        foreach (array_keys($data) as $field) {
            $fields[] = "$field = :$field";
        }

        $sql = "UPDATE $table SET " . implode(", ", $fields) . " WHERE $where";

        $stmt = $this->conn->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        foreach ($whereParams as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Exclui registros de uma tabela
     * 
     * @param string $table Nome da tabela
     * @param string $where Condição WHERE
     * @param array $params Parâmetros para a condição WHERE
     * @return int Número de registros afetados
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction() {
        $this->conn->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit() {
        $this->conn->commit();
    }

    /**
     * Reverte uma transação
     */
    public function rollback() {
        $this->conn->rollBack();
    }

    /**
     * Verifica se uma tabela existe
     * 
     * @param string $table Nome da tabela
     * @return bool True se existir, False caso contrário
     */
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE :table";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['table' => $table]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Cria o banco de dados e as tabelas necessárias para o sistema KEIMADURA
     * Esta função deve ser executada na primeira instalação do sistema
     */
    public function criarEstruturaBanco() {
        // Criação de tabelas baseadas na estrutura do sistema

        // Tabela de categorias
        $sql = "CREATE TABLE IF NOT EXISTS categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->query($sql);

        // Tabela de produtos
        $sql = "CREATE TABLE IF NOT EXISTS produtos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categoria VARCHAR(100) NOT NULL,
            nome VARCHAR(100) NOT NULL,
            preco DECIMAL(10,2) NOT NULL,
            estoque INT NOT NULL DEFAULT 0,
            estoque_minimo INT NOT NULL DEFAULT 0,
            imagem_base64 LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->query($sql);

        // Tabela de usuários
        $sql = "CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            nome VARCHAR(100) NOT NULL,
            cargo VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            telefone VARCHAR(20),
            senha VARCHAR(255) NOT NULL,
            tipo ENUM('admin', 'funcionario') NOT NULL DEFAULT 'funcionario',
            cor VARCHAR(10) DEFAULT '#0499e2',
            imagem LONGTEXT,
            ultimo_acesso DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->query($sql);

        // Tabela de clientes
        $sql = "CREATE TABLE IF NOT EXISTS clientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            telefone VARCHAR(20),
            email VARCHAR(100),
            endereco TEXT,
            cidade VARCHAR(100),
            estado VARCHAR(50),
            cep VARCHAR(20),
            data_nascimento DATE,
            sexo ENUM('masculino', 'feminino'),
            pontos INT NOT NULL DEFAULT 0,
            nivel ENUM('bronze', 'prata', 'ouro', 'diamante') DEFAULT 'bronze',
            total_gasto DECIMAL(10,2) DEFAULT 0.00,
            ultima_compra DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->query($sql);

        // Tabela de sessões de caixa
        $sql = "CREATE TABLE IF NOT EXISTS sessoes_caixa (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            inicio DATETIME NOT NULL,
            fim DATETIME,
            data_formatada VARCHAR(10) NOT NULL,
            hora_formatada VARCHAR(10) NOT NULL,
            data_fim_formatada VARCHAR(10),
            hora_fim_formatada VARCHAR(10),
            vendas JSON,
            total_vendas DECIMAL(10,2) NOT NULL DEFAULT 0,
            status ENUM('ativa', 'fechada') NOT NULL DEFAULT 'ativa',
            tempo_ativo VARCHAR(10),
            duracao VARCHAR(10),
            duracao_ms BIGINT,
            ultima_atividade DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->query($sql);

        // Tabela de vendas
        $sql = "CREATE TABLE IF NOT EXISTS vendas (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            data DATETIME NOT NULL,
            data_formatada VARCHAR(10) NOT NULL,
            hora_formatada VARCHAR(10) NOT NULL,
            cliente VARCHAR(100) NOT NULL,
            telefone VARCHAR(20),
            mesa VARCHAR(10),
            itens JSON NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            desconto_fidelidade DECIMAL(10,2) DEFAULT 0,
            percentual_desconto DECIMAL(5,2) DEFAULT 0,
            nivel_fidelidade VARCHAR(20),
            total DECIMAL(10,2) NOT NULL,
            usuario VARCHAR(50) NOT NULL,
            codigo_consumo VARCHAR(7),
            total_bebidas INT DEFAULT 0,
            bebidas_consumidas INT DEFAULT 0,
            sessao_caixa_id BIGINT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sessao_caixa_id) REFERENCES sessoes_caixa(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->query($sql);

        // Tabela de movimentações de estoque
        $sql = "CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            produto_id INT NOT NULL,
            produto_nome VARCHAR(100) NOT NULL,
            categoria VARCHAR(100) NOT NULL,
            tipo ENUM('entrada', 'saida', 'ajuste') NOT NULL,
            quantidade INT NOT NULL,
            estoque_antes INT NOT NULL,
            estoque_depois INT NOT NULL,
            data DATETIME NOT NULL,
            data_formatada VARCHAR(10) NOT NULL,
            hora_formatada VARCHAR(10) NOT NULL,
            usuario VARCHAR(50) NOT NULL,
            motivo VARCHAR(50) NOT NULL,
            observacao TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->query($sql);

        // Tabela de cupons de desconto
        $sql = "CREATE TABLE IF NOT EXISTS cupons (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(14) NOT NULL UNIQUE,
            tipo ENUM('percentual', 'fixo') NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            cliente_id INT,
            cliente_nome VARCHAR(100),
            cliente_telefone VARCHAR(20),
            data_geracao DATETIME NOT NULL,
            data_validade DATETIME NOT NULL,
            data_formatada VARCHAR(10) NOT NULL,
            validade_formatada VARCHAR(10) NOT NULL,
            descricao TEXT,
            utilizado TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->query($sql);

        // Inserir usuário administrador padrão
        $usuarioExistente = $this->fetch("SELECT id FROM usuarios WHERE username = :username", ['username' => 'Keimadura']);
        if (!$usuarioExistente) {
            $this->insert('usuarios', [
                'username' => 'Keimadura',
                'nome' => 'Administrador',
                'cargo' => 'Gerente Geral',
                'email' => 'admin@keimadura.com',
                'telefone' => '+244 923 456 789',
                'senha' => password_hash('keimaduraadmin', PASSWORD_DEFAULT),
                'tipo' => 'admin'
            ]);

            // Inserir funcionários padrão
            $this->insert('usuarios', [
                'username' => 'keimaduraserviço1',
                'nome' => 'Funcionário 1',
                'cargo' => 'Atendente',
                'email' => 'atendente1@keimadura.com',
                'telefone' => '+244 923 123 456',
                'senha' => password_hash('serviço1', PASSWORD_DEFAULT),
                'tipo' => 'funcionario',
                'cor' => '#ff2f8e'
            ]);

            $this->insert('usuarios', [
                'username' => 'keimaduraserviço2',
                'nome' => 'Funcionário 2',
                'cargo' => 'Atendente',
                'email' => 'atendente2@keimadura.com',
                'telefone' => '+244 923 789 123',
                'senha' => password_hash('serviço2', PASSWORD_DEFAULT),
                'tipo' => 'funcionario',
                'cor' => '#ffaf3f'
            ]);

            // Inserir dois novos usuários
            $this->insert('usuarios', [
                'username' => 'keimaduraestoque',
                'nome' => 'Gerente de Estoque',
                'cargo' => 'Gerente de Estoque',
                'email' => 'estoque@keimadura.com',
                'telefone' => '+244 923 555 777',
                'senha' => password_hash('estoque123', PASSWORD_DEFAULT),
                'tipo' => 'admin',
                'cor' => '#4CAF50'
            ]);

            $this->insert('usuarios', [
                'username' => 'keimaduracaixa',
                'nome' => 'Operador de Caixa',
                'cargo' => 'Caixa',
                'email' => 'caixa@keimadura.com',
                'telefone' => '+244 923 888 999',
                'senha' => password_hash('caixa123', PASSWORD_DEFAULT),
                'tipo' => 'funcionario',
                'cor' => '#9C27B0'
            ]);
        }

        // Inserir categorias padrão
        if ($this->fetch("SELECT COUNT(*) as total FROM categorias", [])['total'] === 0) {
            $categorias = [
                ['nome' => 'Comidas', 'descricao' => 'Alimentos e refeições'],
                ['nome' => 'Bebidas', 'descricao' => 'Bebidas alcoólicas e não alcoólicas'],
                ['nome' => 'Acessorios', 'descricao' => 'Itens e acessórios diversos'],
                ['nome' => 'Outros', 'descricao' => 'Outros produtos']
            ];

            foreach ($categorias as $cat) {
                $this->insert('categorias', $cat);
            }
        }

        return true;
    }
}

// Funções auxiliares para interação com o banco de dados

/**
 * Obtém os produtos com filtro opcional por categoria
 * 
 * @param string $categoria Categoria para filtrar (opcional)
 * @return array Lista de produtos
 */
function obterProdutos($categoria = '') {
    global $db;

    $sql = "SELECT * FROM produtos";
    $params = [];

    if ($categoria) {
        $sql .= " WHERE categoria = :categoria";
        $params = ['categoria' => $categoria];
    }

    $sql .= " ORDER BY nome ASC";

    return $db->fetchAll($sql, $params);
}

/**
 * Busca produtos por termo de busca
 * 
 * @param string $termo Termo para busca
 * @param string $categoria Categoria opcional
 * @return array Lista de produtos encontrados
 */
function buscarProdutos($termo, $categoria = '') {
    global $db;

    $sql = "SELECT * FROM produtos WHERE 
            (nome LIKE :termo OR id = :id_termo)";

    $params = [
        'termo' => "%$termo%",
        'id_termo' => is_numeric($termo) ? $termo : 0
    ];

    if ($categoria) {
        $sql .= " AND categoria = :categoria";
        $params['categoria'] = $categoria;
    }

    $sql .= " ORDER BY nome ASC";

    return $db->fetchAll($sql, $params);
}

/**
 * Adiciona um novo produto ao banco de dados
 * 
 * @param array $dados Dados do produto
 * @return int ID do produto inserido
 */
function adicionarProduto($dados) {
    global $db;
    return $db->insert('produtos', $dados);
}

/**
 * Atualiza um produto existente
 * 
 * @param int $id ID do produto
 * @param array $dados Dados atualizados
 * @return bool Resultado da operação
 */
function atualizarProduto($id, $dados) {
    global $db;
    $result = $db->update('produtos', $dados, "id = :id", ['id' => $id]);
    return $result > 0;
}

/**
 * Verifica autenticação do usuário
 * 
 * @param string $username Nome de usuário
 * @param string $password Senha
 * @return array|false Dados do usuário ou false se falhar
 */
function autenticarUsuario($username, $password) {
    global $db;

    // Verificar se $db não foi inicializado e inicializá-lo
    if (!isset($db) || $db === null) {
        $db = new Database();
    }

    $usuario = $db->fetch(
        "SELECT * FROM usuarios WHERE username = :username",
        ['username' => $username]
    );

    if ($usuario && password_verify($password, $usuario['senha'])) {
        // Atualizar último acesso
        $db->update(
            'usuarios',
            ['ultimo_acesso' => date('Y-m-d H:i:s')],
            "id = :id",
            ['id' => $usuario['id']]
        );

        // Remover a senha antes de retornar
        unset($usuario['senha']);
        return $usuario;
    }

    return false;
}

/**
 * Verifica o login do usuário (para uso no arquivo login.php)
 * 
 * @param string $usuario Nome de usuário
 * @param string $senha Senha não criptografada
 * @return array|false Dados do usuário ou false se falhar
 */
function verificarLogin($usuario, $senha) {
    global $db;
    
    // Verificar se $db não foi inicializado e inicializá-lo
    if (!isset($db) || $db === null) {
        $db = new Database();
    }
    
    // Utilizamos a função autenticarUsuario que já existe
    return autenticarUsuario($usuario, $senha);
}

/**
 * Inicia uma sessão de caixa para o usuário
 * 
 * @param int $usuarioId ID do usuário
 * @return array|false Dados da sessão ou false
 */
function iniciarSessaoCaixa($usuarioId) {
    global $db;

    // Verificar se já existe uma sessão ativa
    $sessaoAtiva = $db->fetch(
        "SELECT * FROM sessoes_caixa WHERE usuario_id = :usuario_id AND status = 'ativa'",
        ['usuario_id' => $usuarioId]
    );

    if ($sessaoAtiva) {
        return $sessaoAtiva;
    }

    // Criar nova sessão
    $agora = date('Y-m-d H:i:s');
    $dataFormatada = date('d/m/Y');
    $horaFormatada = date('H:i:s');

    $dadosSessao = [
        'usuario_id' => $usuarioId,
        'inicio' => $agora,
        'data_formatada' => $dataFormatada,
        'hora_formatada' => $horaFormatada,
        'vendas' => json_encode([]),
        'total_vendas' => 0,
        'status' => 'ativa',
        'tempo_ativo' => '00:00:00',
        'ultima_atividade' => $agora
    ];

    $sessaoId = $db->insert('sessoes_caixa', $dadosSessao);

    if ($sessaoId) {
        return $db->fetch("SELECT * FROM sessoes_caixa WHERE id = :id", ['id' => $sessaoId]);
    }

    return false;
}

/**
 * Fecha uma sessão de caixa ativa
 * 
 * @param int $sessaoId ID da sessão
 * @param array $dadosFechamento Dados adicionais de fechamento
 * @return bool Resultado da operação
 */
function fecharSessaoCaixa($sessaoId, $dadosFechamento) {
    global $db;

    $agora = date('Y-m-d H:i:s');
    $dataFormatada = date('d/m/Y');
    $horaFormatada = date('H:i:s');

    // Calcular duração da sessão
    $sessao = $db->fetch("SELECT inicio FROM sessoes_caixa WHERE id = :id", ['id' => $sessaoId]);
    if (!$sessao) return false;

    $inicio = new DateTime($sessao['inicio']);
    $fim = new DateTime($agora);

    $duracaoMs = $fim->getTimestamp() - $inicio->getTimestamp();
    $duracaoFormatada = sprintf(
        "%02d:%02d:%02d",
        floor($duracaoMs / 3600),
        floor(($duracaoMs % 3600) / 60),
        $duracaoMs % 60
    );

    // Preparar dados para fechamento
    $dados = [
        'fim' => $agora,
        'data_fim_formatada' => $dataFormatada,
        'hora_fim_formatada' => $horaFormatada,
        'status' => 'fechada',
        'duracao' => $duracaoFormatada,
        'duracao_ms' => $duracaoMs * 1000 // Converter para milissegundos
    ];

    // Mesclar com dados adicionais
    if (is_array($dadosFechamento)) {
        $dados = array_merge($dados, $dadosFechamento);
    }

    // Atualizar sessão
    $result = $db->update('sessoes_caixa', $dados, "id = :id", ['id' => $sessaoId]);
    return $result > 0;
}

/**
 * Registra uma venda no sistema
 * 
 * @param array $dadosVenda Dados da venda
 * @return int|false ID da venda ou false
 */
function registrarVenda($dadosVenda) {
    global $db;

    $db->beginTransaction();

    try {
        // Inserir a venda
        $vendaId = $db->insert('vendas', $dadosVenda);

        if (!$vendaId) {
            $db->rollback();
            return false;
        }

        // Atualizar estoque dos produtos
        $itens = json_decode($dadosVenda['itens'], true);

        foreach ($itens as $item) {
            // Obter produto do banco
            $produto = $db->fetch(
                "SELECT * FROM produtos WHERE id = :id",
                ['id' => $item['id']]
            );

            if (!$produto) continue;

            // Calcular novo estoque
            $novoEstoque = max(0, $produto['estoque'] - $item['quantidade']);

            // Atualizar estoque
            $db->update(
                'produtos',
                ['estoque' => $novoEstoque],
                "id = :id",
                ['id' => $item['id']]
            );

            // Registrar movimentação de estoque
            $db->insert('movimentacoes_estoque', [
                'produto_id' => $item['id'],
                'produto_nome' => $item['nome'],
                'categoria' => $item['categoria'],
                'tipo' => 'saida',
                'quantidade' => $item['quantidade'],
                'estoque_antes' => $produto['estoque'],
                'estoque_depois' => $novoEstoque,
                'data' => date('Y-m-d H:i:s'),
                'data_formatada' => date('d/m/Y'),
                'hora_formatada' => date('H:i:s'),
                'usuario' => $dadosVenda['usuario'],
                'motivo' => 'venda',
                'observacao' => "Venda #$vendaId"
            ]);
        }

        // Atualizar sessão de caixa se houver
        if (!empty($dadosVenda['sessao_caixa_id'])) {
            // Obter sessão atual
            $sessao = $db->fetch(
                "SELECT * FROM sessoes_caixa WHERE id = :id",
                ['id' => $dadosVenda['sessao_caixa_id']]
            );

            if ($sessao) {
                // Atualizar vendas da sessão
                $vendas = json_decode($sessao['vendas'], true) ?: [];
                $vendas[] = $vendaId;

                // Atualizar total de vendas
                $totalVendas = $sessao['total_vendas'] + $dadosVenda['total'];

                // Atualizar sessão
                $db->update(
                    'sessoes_caixa',
                    [
                        'vendas' => json_encode($vendas),
                        'total_vendas' => $totalVendas,
                        'ultima_atividade' => date('Y-m-d H:i:s')
                    ],
                    "id = :id",
                    ['id' => $dadosVenda['sessao_caixa_id']]
                );
            }
        }

        // Atualizar pontos do cliente se tiver telefone
        if (!empty($dadosVenda['telefone'])) {
            $cliente = $db->fetch(
                "SELECT * FROM clientes WHERE telefone = :telefone",
                ['telefone' => $dadosVenda['telefone']]
            );

            if ($cliente) {
                // Calcular pontos a adicionar (1 ponto por 1000 AOA)
                $novoPontos = floor($dadosVenda['subtotal'] / 1000);
                $totalPontos = $cliente['pontos'] + $novoPontos;

                // Determinar novo nível
                $nivel = 'bronze';
                if ($totalPontos >= 200) $nivel = 'diamante';
                else if ($totalPontos >= 100) $nivel = 'ouro';
                else if ($totalPontos >= 50) $nivel = 'prata';

                // Atualizar cliente
                $db->update(
                    'clientes',
                    [
                        'pontos' => $totalPontos,
                        'nivel' => $nivel,
                        'total_gasto' => $cliente['total_gasto'] + $dadosVenda['total'],
                        'ultima_compra' => date('Y-m-d')
                    ],
                    "id = :id",
                    ['id' => $cliente['id']]
                );
            } else if (!empty($dadosVenda['cliente']) && $dadosVenda['cliente'] !== 'Cliente') {
                // Criar novo cliente
                $novoPontos = floor($dadosVenda['subtotal'] / 1000);

                $db->insert('clientes', [
                    'nome' => $dadosVenda['cliente'],
                    'telefone' => $dadosVenda['telefone'],
                    'pontos' => $novoPontos,
                    'total_gasto' => $dadosVenda['total'],
                    'ultima_compra' => date('Y-m-d')
                ]);
            }
        }

        $db->commit();
        return $vendaId;
    } catch (Exception $e) {
        $db->rollback();
        if (APP_DEBUG) {
            echo "Erro ao registrar venda: " . $e->getMessage();
        }
        return false;
    }
}

/**
 * Obtém estatísticas de vendas para o dashboard
 * 
 * @param string $periodo Período para estatísticas (hoje, semana, mes, ano)
 * @return array Estatísticas de vendas
 */
function obterEstatisticasVendas($periodo = 'hoje') {
    global $db;

    $dataInicio = null;
    $hoje = date('Y-m-d');

    switch ($periodo) {
        case 'hoje':
            $dataInicio = $hoje;
            break;
        case 'semana':
            $dataInicio = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'mes':
            $dataInicio = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'ano':
            $dataInicio = date('Y-m-d', strtotime('-1 year'));
            break;
        default:
            $dataInicio = $hoje;
    }

    // Total de vendas e valor
    $stats = $db->fetch(
        "SELECT 
            COUNT(*) as total_vendas,
            SUM(total) as valor_total,
            COUNT(DISTINCT cliente) as total_clientes
         FROM vendas 
         WHERE DATE(data) >= :data_inicio",
        ['data_inicio' => $dataInicio]
    );

    $stats['ticket_medio'] = $stats['total_vendas'] > 0 
        ? $stats['valor_total'] / $stats['total_vendas'] 
        : 0;

    // Vendas por categoria (TOP 5)
    $stats['vendas_por_categoria'] = [];

    // Esta consulta é mais complexa pois envolve dados em JSON
    // Uma implementação simplificada:
    $vendas = $db->fetchAll(
        "SELECT itens FROM vendas WHERE DATE(data) >= :data_inicio",
        ['data_inicio' => $dataInicio]
    );

    // Processar itens
    $categorias = [];
    foreach ($vendas as $venda) {
        $itens = json_decode($venda['itens'], true);
        foreach ($itens as $item) {
            if (!isset($categorias[$item['categoria']])) {
                $categorias[$item['categoria']] = [
                    'quantidade' => 0,
                    'valor' => 0
                ];
            }

            $categorias[$item['categoria']]['quantidade'] += $item['quantidade'];
            $categorias[$item['categoria']]['valor'] += $item['total'];
        }
    }

    // Ordenar por valor
    uasort($categorias, function($a, $b) {
        return $b['valor'] <=> $a['valor'];
    });

    // Pegar TOP 5
    $stats['vendas_por_categoria'] = array_slice($categorias, 0, 5, true);

    return $stats;
}

/**
 * Obtém lista de produtos para gerenciamento de estoque
 * 
 * @param string $filtro_categoria Categoria para filtrar
 * @param string $filtro_status Status do estoque (baixo, normal, esgotado)
 * @return array Lista de produtos
 */
function obterEstoqueProdutos($filtro_categoria = '', $filtro_status = '') {
    global $db;

    $sql = "SELECT * FROM produtos";
    $params = [];
    $condicoes = [];

    if ($filtro_categoria) {
        $condicoes[] = "categoria = :categoria";
        $params['categoria'] = $filtro_categoria;
    }

    if ($filtro_status) {
        switch($filtro_status) {
            case 'baixo':
                $condicoes[] = "estoque <= estoque_minimo AND estoque > 0";
                break;
            case 'normal':
                $condicoes[] = "estoque > estoque_minimo";
                break;
            case 'esgotado':
                $condicoes[] = "estoque = 0";
                break;
        }
    }

    if (!empty($condicoes)) {
        $sql .= " WHERE " . implode(" AND ", $condicoes);
    }

    $sql .= " ORDER BY nome ASC";

    return $db->fetchAll($sql, $params);
}

/**
 * Registra uma movimentação de estoque
 * 
 * @param array $dados Dados da movimentação
 * @return int|false ID da movimentação ou false
 */
function registrarMovimentacaoEstoque($dados) {
    global $db;

    try {
        $db->beginTransaction();

        // Inserir a movimentação
        $movimentacaoId = $db->insert('movimentacoes_estoque', $dados);

        if (!$movimentacaoId) {
            $db->rollback();
            return false;
        }

        // Atualizar estoque do produto
        $db->update(
            'produtos',
            ['estoque' => $dados['estoque_depois']],
            "id = :id",
            ['id' => $dados['produto_id']]
        );

        $db->commit();
        return $movimentacaoId;
    } catch (Exception $e) {
        $db->rollback();
        if (APP_DEBUG) {
            echo "Erro ao registrar movimentação: " . $e->getMessage();
        }
        return false;
    }
}

/**
* Obtém histórico de movimentações de estoque
 * 
 * @param string $periodo Período para filtrar (hoje, semana, mes, todos)
 * @param string $tipo Tipo de movimentação (entrada, saida, ajuste)
 * @return array Lista de movimentações
 */
function obterHistoricoMovimentacoes($periodo = 'todos', $tipo = '') {
    global $db;

    $sql = "SELECT * FROM movimentacoes_estoque";
    $where = [];
    $params = [];

    if ($periodo !== 'todos') {
        $dataInicio = null;

        switch ($periodo) {
            case 'hoje':
                $dataInicio = date('Y-m-d');
                break;
            case 'semana':
                $dataInicio = date('Y-m-d', strtotime('-7 days'));
                break;
                break;
            case 'mes':
                $dataInicio = date('Y-m-d', strtotime('-30 days'));
                break;
        }

        if ($dataInicio) {
            $where[] = "DATE(data) >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }
    }

    if ($tipo) {
        $where[] = "tipo = :tipo";
        $params['tipo'] = $tipo;
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY data DESC";

    return $db->fetchAll($sql, $params);
}
?>