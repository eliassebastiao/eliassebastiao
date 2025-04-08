<?php
require_once 'config.php';
require_once 'database.php';

// Configurar cabeçalhos para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Função para responder com erro
function responderErro($mensagem, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode([
        'success' => false,
        'message' => $mensagem
    ]);
    exit;
}

// Tratar requisições OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar se a base de dados está acessível
if (!isset($conn) || $conn === null) {
    try {
        // Tentar inicializar a conexão
        $conn = getConnection();
    } catch (Exception $e) {
        // Se falhar, responder com erro apropriado
        responderErro('Erro de conexão com o banco de dados: ' . ($e->getMessage()), 500);
    }
}

// Ponto de entrada da API
$acao = isset($_REQUEST['acao']) ? $_REQUEST['acao'] : '';

// Endpoint de teste para verificar se o servidor está funcionando
if ($acao === 'ping') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    try {
        // Verificar se o banco de dados está respondendo
        $conexaoTest = getConnection();
        
        // Tentar fazer uma consulta simples no banco
        $stmt = $conexaoTest->query("SELECT 1");
        $querySuccess = ($stmt !== false);
        
        echo json_encode([
            'success' => true, 
            'database' => true,
            'message' => 'Servidor ativo e conectado ao banco de dados',
            'query_success' => $querySuccess,
            'server_info' => [
                'php_version' => phpversion(),
                'os' => PHP_OS,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido'
            ],
            'timestamp' => time()
        ]);
    } catch (Exception $e) {
        // Retorna sucesso parcial - servidor web está funcionando, mas o banco não
        error_log("Erro no ping do servidor: " . $e->getMessage());
        
        echo json_encode([
            'success' => false, 
            'database' => false,
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'message' => 'Servidor web está funcionando, mas há problema com o banco de dados',
            'server_info' => [
                'php_version' => phpversion(),
                'os' => PHP_OS,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido'
            ],
            'timestamp' => time()
        ]);
    }
    exit;
}

// Obter dados do corpo da requisição (para métodos POST)
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Se não conseguir decodificar o JSON, usar $_POST
if ($data === null && isset($_POST) && !empty($_POST)) {
    $data = $_POST;
}

// Resposta padrão
$resposta = [
    'status' => 'erro',
    'mensagem' => 'Ação não especificada'
];

// Processa a ação solicitada
switch ($acao) {
    // ================ AUTENTICAÇÃO ================
    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Log para debug
        error_log("Tentativa de login para usuário: $username");

        try {
            $usuario = autenticarUsuario($username, $password);

            if ($usuario) {
                session_start();
                $_SESSION['usuario'] = $usuario;

                $resposta = [
                    'status' => 'sucesso',
                    'usuario' => $usuario,
                    'success' => true,
                    'perfil' => obterPerfilUsuario($usuario['id'])
                ];
                error_log("Login bem-sucedido para: $username");
            } else {
                $resposta = [
                    'status' => 'erro',
                    'success' => false,
                    'mensagem' => 'Usuário ou senha incorretos'
                ];
                error_log("Falha no login para: $username - Credenciais inválidas");
            }
        } catch (Exception $e) {
            $resposta = [
                'status' => 'erro',
                'success' => false,
                'mensagem' => 'Erro interno no servidor: ' . $e->getMessage(),
                'debug' => 'Erro processando login'
            ];
            error_log("Exceção no login para $username: " . $e->getMessage());
        }
        break;

    case 'logout':
        session_start();
        session_destroy();
        $resposta = [
            'status' => 'sucesso', 
            'mensagem' => 'Sessão encerrada com sucesso'
        ];
        break;

    // ================ PRODUTOS ================
    case 'obter_produtos':
        $categoria = $_REQUEST['categoria'] ?? '';
        $produtos = obterProdutos($categoria);

        $resposta = [
            'status' => 'sucesso',
            'produtos' => $produtos
        ];
        break;

    case 'adicionar_produto':
        // Verificar se o usuário é admin
        session_start();
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'admin') {
            $resposta = ['status' => 'erro', 'mensagem' => 'Permissão negada'];
            break;
        }

        // Receber dados do produto (formato JSON)
        $dados = json_decode(file_get_contents('php://input'), true);
        if ($dados) {
            $resultado = adicionarProduto($dados);
            if ($resultado) {
                $resposta = [
                    'status' => 'sucesso',
                    'mensagem' => 'Produto adicionado com sucesso',
                    'id' => $resultado
                ];
            } else {
                $resposta = [
                    'status' => 'erro',
                    'mensagem' => 'Falha ao adicionar produto'
                ];
            }
        } else {
            $resposta = ['status' => 'erro', 'mensagem' => 'Dados inválidos'];
        }
        break;

    case 'atualizar_produto':
        // Verificar se o usuário é admin
        session_start();
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'admin') {
            $resposta = ['status' => 'erro', 'mensagem' => 'Permissão negada'];
            break;
        }

        // Receber dados do produto (formato JSON)
        $dados = json_decode(file_get_contents('php://input'), true);
        $id = $dados['id'] ?? 0;

        if ($id && $dados) {
            // Remover o ID dos dados para não atualizar a PK
            unset($dados['id']);

            $resultado = atualizarProduto($id, $dados);
            if ($resultado) {
                $resposta = [
                    'status' => 'sucesso',
                    'mensagem' => 'Produto atualizado com sucesso'
                ];
            } else {
                $resposta = [
                    'status' => 'erro', 
                    'mensagem' => 'Falha ao atualizar produto'
                ];
            }
        } else {
            $resposta = ['status' => 'erro', 'mensagem' => 'Dados inválidos'];
        }
        break;

    // ================ VENDAS ================
    case 'registrar_venda':
        $dados = json_decode(file_get_contents('php://input'), true);
        if ($dados) {
            $resultado = registrarVenda($dados);
            if ($resultado) {
                $resposta = [
                    'status' => 'sucesso',
                    'mensagem' => 'Venda registrada com sucesso',
                    'id' => $resultado
                ];
            } else {
                $resposta = [
                    'status' => 'erro', 
                    'mensagem' => 'Falha ao registrar venda'
                ];
            }
        } else {
            $resposta = ['status' => 'erro', 'mensagem' => 'Dados inválidos'];
        }
        break;

    case 'obter_vendas':
        $periodo = $_REQUEST['periodo'] ?? 'todos';
        $vendas = obterVendas($periodo);

        $resposta = [
            'status' => 'sucesso',
            'vendas' => $vendas
        ];
        break;

    // ================ SESSÃO DE CAIXA ================
    case 'iniciar_sessao_caixa':
        session_start();
        if (!isset($_SESSION['usuario'])) {
            $resposta = ['status' => 'erro', 'mensagem' => 'Usuário não autenticado'];
            break;
        }

        $resultado = iniciarSessaoCaixa($_SESSION['usuario']['id']);
        if ($resultado) {
            $resposta = [
                'status' => 'sucesso',
                'sessao' => $resultado
            ];
        } else {
            $resposta = [
                'status' => 'erro', 
                'mensagem' => 'Falha ao iniciar sessão de caixa'
            ];
        }
        break;

    case 'fechar_sessao_caixa':
        $dados = json_decode(file_get_contents('php://input'), true);
        $sessaoId = $dados['id'] ?? 0;

        if ($sessaoId) {
            $resultado = fecharSessaoCaixa($sessaoId, $dados);
            if ($resultado) {
                $resposta = [
                    'status' => 'sucesso',
                    'mensagem' => 'Sessão de caixa fechada com sucesso'
                ];
            } else {
                $resposta = [
                    'status' => 'erro', 
                    'mensagem' => 'Falha ao fechar sessão de caixa'
                ];
            }
        } else {
            $resposta = ['status' => 'erro', 'mensagem' => 'ID de sessão inválido'];
        }
        break;

    // ================ ESTOQUE ================
    case 'obter_estoque':
        $categoria = $_REQUEST['categoria'] ?? '';
        $status = $_REQUEST['status'] ?? '';
        $produtos = obterEstoqueProdutos($categoria, $status);

        $resposta = [
            'status' => 'sucesso',
            'produtos' => $produtos
        ];
        break;

    case 'ajustar_estoque':
        // Verificar se o usuário está autenticado
        session_start();
        if (!isset($_SESSION['usuario'])) {
            $resposta = ['status' => 'erro', 'mensagem' => 'Usuário não autenticado'];
            break;
        }

        // Receber dados do ajuste (formato JSON)
        $dados = json_decode(file_get_contents('php://input'), true);
        if ($dados) {
            // Recuperar dados do produto
            global $db;
            $produto = $db->fetch("SELECT * FROM produtos WHERE id = :id", ['id' => $dados['produto_id']]);

            if (!$produto) {
                $resposta = ['status' => 'erro', 'mensagem' => 'Produto não encontrado'];
                break;
            }

            // Calcular novo estoque
            $estoqueAtual = $produto['estoque'];
            $estoqueDepois = $estoqueAtual;

            switch ($dados['tipo']) {
                case 'entrada':
                    $estoqueDepois = $estoqueAtual + $dados['quantidade'];
                    break;
                case 'saida':
                    $estoqueDepois = max(0, $estoqueAtual - $dados['quantidade']);
                    break;
                case 'ajuste':
                    $estoqueDepois = $dados['quantidade'];
                    break;
            }

            // Preparar dados da movimentação
            $dadosMovimentacao = [
                'produto_id' => $dados['produto_id'],
                'produto_nome' => $produto['nome'],
                'categoria' => $produto['categoria'],
                'tipo' => $dados['tipo'],
                'quantidade' => $dados['quantidade'],
                'estoque_antes' => $estoqueAtual,
                'estoque_depois' => $estoqueDepois,
                'data' => date('Y-m-d H:i:s'),
                'data_formatada' => date('d/m/Y'),
                'hora_formatada' => date('H:i:s'),
                'usuario' => $_SESSION['usuario']['username'],
                'motivo' => $dados['motivo'],
                'observacao' => $dados['observacao'] ?? ''
            ];

            $resultado = registrarMovimentacaoEstoque($dadosMovimentacao);

            if ($resultado) {
                $resposta = [
                    'status' => 'sucesso',
                    'mensagem' => 'Estoque ajustado com sucesso',
                    'id' => $resultado,
                    'novo_estoque' => $estoqueDepois
                ];
            } else {
                $resposta = [
                    'status' => 'erro',
                    'mensagem' => 'Falha ao ajustar estoque'
                ];
            }
        } else {
            $resposta = ['status' => 'erro', 'mensagem' => 'Dados inválidos'];
        }
        break;

    case 'obter_historico_estoque':
        $periodo = $_REQUEST['periodo'] ?? 'todos';
        $tipo = $_REQUEST['tipo'] ?? '';
        $movimentacoes = obterHistoricoMovimentacoes($periodo, $tipo);

        $resposta = [
            'status' => 'sucesso',
            'movimentacoes' => $movimentacoes
        ];
        break;

    // ================ CONFIGURAÇÕES ================
    case 'obter_usuarios':
        // Verificar se o usuário é admin ou gerente
        session_start();
        if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['tipo'] !== 'admin' && 
            $_SESSION['usuario']['username'] !== 'gerente1' && 
            $_SESSION['usuario']['username'] !== 'gerente2')) {
            $resposta = ['status' => 'erro', 'mensagem' => 'Permissão negada'];
            break;
        }

        $usuarios = obterUsuarios();

        $resposta = [
            'status' => 'sucesso',
            'usuarios' => $usuarios
        ];
        break;

    case 'verificar_usuario':
        $username = $_REQUEST['username'] ?? '';

        $usuario = verificarUsuarioExiste($username);

        $resposta = [
            'status' => 'sucesso',
            'existe' => $usuario !== false
        ];
        break;

    case 'adicionar_usuario':
        // Verificar se o usuário é admin ou gerente
        session_start();
        if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['tipo'] !== 'admin' && 
            $_SESSION['usuario']['username'] !== 'gerente1' && 
            $_SESSION['usuario']['username'] !== 'gerente2')) {
            $resposta = ['status' => 'erro', 'mensagem' => 'Permissão negada'];
            break;
        }

        // Receber dados do usuário (formato JSON)
        $dados = json_decode(file_get_contents('php://input'), true);
        if ($dados) {
            $resultado = adicionarUsuario($dados);
            if ($resultado) {
                $resposta = [
                    'status' => 'sucesso',
                    'mensagem' => 'Usuário adicionado com sucesso',
                    'id' => $resultado
                ];
            } else {
                $resposta = [
                    'status' => 'erro',
                    'mensagem' => 'Falha ao adicionar usuário'
                ];
            }
        } else {
            $resposta = ['status' => 'erro', 'mensagem' => 'Dados inválidos'];
        }
        break;

    case 'atualizar_usuario':
        // Verificar se o usuário é admin ou gerente
        session_start();
        if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['tipo'] !== 'admin' && 
            $_SESSION['usuario']['username'] !== 'gerente1' && 
            $_SESSION['usuario']['username'] !== 'gerente2')) {
            $resposta = ['status' => 'erro', 'mensagem' => 'Permissão negada'];
            break;
        }

        // Receber dados do usuário (formato JSON)
        $dados = json_decode(file_get_contents('php://input'), true);
        $id = $dados['id'] ?? 0;

        if ($id && $dados) {
            // Remover o ID dos dados para não atualizar a PK
            unset($dados['id']);

            $resultado = atualizarUsuario($id, $dados);
            if ($resultado) {
                $resposta = [
                    'status' => 'sucesso',
                    'mensagem' => 'Usuário atualizado com sucesso'
                ];
            } else {
                $resposta = [
                    'status' => 'erro', 
                    'mensagem' => 'Falha ao atualizar usuário'
                ];
            }
        } else {
            $resposta = ['status' => 'erro', 'mensagem' => 'Dados inválidos'];
        }
        break;

    case 'excluir_usuario':
        // Verificar se o usuário é admin ou gerente
        session_start();
        if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['tipo'] !== 'admin' && 
            $_SESSION['usuario']['username'] !== 'gerente1' && 
            $_SESSION['usuario']['username'] !== 'gerente2')) {
            $resposta = ['status' => 'erro', 'mensagem' => 'Permissão negada'];
            break;
        }

        // Receber dados do usuário (formato JSON)
        $dados = json_decode(file_get_contents('php://input'), true);
        $id = $dados['id'] ?? 0;

        if ($id) {
            $resultado = excluirUsuario($id);
            if ($resultado) {
                $resposta = [
                    'status' => 'sucesso',
                    'mensagem' => 'Usuário excluído com sucesso'
                ];
            } else {
                $resposta = [
                    'status' => 'erro', 
                    'mensagem' => 'Falha ao excluir usuário'
                ];
            }
        } else {
            $resposta = ['status' => 'erro', 'mensagem' => 'ID inválido'];
        }
        break;

    case 'atualizar_perfil':
        session_start();
        if (!isset($_SESSION['usuario'])) {
            $resposta = ['status' => 'erro', 'mensagem' => 'Usuário não autenticado'];
            break;
        }

        // Receber dados do perfil (formato JSON)
        $dados = json_decode(file_get_contents('php://input'), true);

        // Verificar se é necessário validar a senha atual
        if (isset($dados['nova_senha'])) {
            // Verificar se a senha atual está correta
            $usuarioAtual = obterUsuarioPorId($_SESSION['usuario']['id']);
            if (!$usuarioAtual || !password_verify($dados['senha_atual'], $usuarioAtual['senha'])) {
                $resposta = ['status' => 'erro', 'mensagem' => 'Senha atual incorreta'];
                break;
            }

            // Atualizar senha
            $dados['senha'] = password_hash($dados['nova_senha'], PASSWORD_DEFAULT);
            unset($dados['senha_atual']);
            unset($dados['nova_senha']);
        }

        // Atualizar perfil
        $resultado = atualizarUsuario($_SESSION['usuario']['id'], $dados);

        if ($resultado) {
            // Obter usuário atualizado
            $usuarioAtualizado = obterUsuarioPorId($_SESSION['usuario']['id']);
            unset($usuarioAtualizado['senha']);

            // Atualizar sessão
            $_SESSION['usuario'] = $usuarioAtualizado;

            $resposta = [
                'status' => 'sucesso',
                'mensagem' => 'Perfil atualizado com sucesso',
                'perfil' => $usuarioAtualizado
            ];
        } else {
            $resposta = [
                'status' => 'erro',
                'mensagem' => 'Falha ao atualizar perfil'
            ];
        }
        break;

    case 'salvar_config_sistema':
    case 'salvar_config_aparencia':
    case 'salvar_config_impressora':
    case 'salvar_config_seguranca':
        // Simular salvamento de configurações
        $resposta = [
            'status' => 'sucesso',
            'mensagem' => 'Configurações salvas com sucesso'
        ];
        break;

    case 'criar_backup':
        // Simular criação de backup
        $resposta = [
            'status' => 'sucesso',
            'mensagem' => 'Backup criado com sucesso',
            'arquivo' => './backups/backup_' . date('Y-m-d_H-i-s') . '.sql'
        ];
        break;

    // Endpoints para Produtos
    case 'listar_produtos':
        $categoria = $_REQUEST['categoria'] ?? '';
        $produtos = obterProdutos($categoria);
        $resposta = [
            'status' => 'sucesso',
            'success' => true,
            'produtos' => $produtos
        ];
        break;
        
    // Endpoints para Estoque
    case 'listar_estoque':
        $categoria = $_REQUEST['categoria'] ?? '';
        $status = $_REQUEST['status'] ?? '';
        $produtos = obterEstoqueProdutos($categoria, $status);
        $resposta = [
            'status' => 'sucesso',
            'success' => true,
            'produtos' => $produtos
        ];
        break;
        
    // Endpoints para Finanças
    case 'dashboard_vendas':
        $periodo = $_REQUEST['periodo'] ?? 'hoje';
        $stats = obterEstatisticasVendas($periodo);
        $resposta = [
            'status' => 'sucesso',
            'success' => true,
            'vendas_hoje' => $stats['valor_total'] ?? 0,
            'vendas_mes' => $stats['valor_total'] ?? 0,
            'qtd_vendas_hoje' => $stats['total_vendas'] ?? 0,
            'ticket_medio' => $stats['ticket_medio'] ?? 0
        ];
        break;
        
    // Adicione outros endpoints conforme necessário
    default:
        $resposta = ['status' => 'erro', 'mensagem' => 'Ação desconhecida'];
}

// Retorna a resposta em formato JSON
echo json_encode($resposta);

/**
 * Obtém todos os usuários do sistema
 * 
 * @return array Lista de usuários
 */
function obterUsuarios() {
    global $db;
    $sql = "SELECT id, username, nome, cargo, tipo, email, telefone, cor, ultimo_acesso FROM usuarios ORDER BY nome ASC";
    return $db->fetchAll($sql);
}

/**
 * Verifica se um usuário existe pelo username
 * 
 * @param string $username Nome de usuário
 * @return array|false Dados do usuário ou false
 */
function verificarUsuarioExiste($username) {
    global $db;
    return $db->fetch("SELECT id FROM usuarios WHERE username = :username", ['username' => $username]);
}

/**
 * Obtém um usuário pelo ID
 * 
 * @param int $id ID do usuário
 * @return array|false Dados do usuário ou false
 */
function obterUsuarioPorId($id) {
    global $db;
    return $db->fetch("SELECT * FROM usuarios WHERE id = :id", ['id' => $id]);
}

/**
 * Adiciona um novo usuário
 * 
 * @param array $dados Dados do usuário
 * @return int|false ID do usuário ou false
 */
function adicionarUsuario($dados) {
    global $db;

    // Verificar se username já existe
    $existente = verificarUsuarioExiste($dados['username']);
    if ($existente) {
        return false;
    }

    // Hash da senha
    $dados['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);

    return $db->insert('usuarios', $dados);
}

/**
 * Atualiza um usuário existente
 * 
 * @param int $id ID do usuário
 * @param array $dados Dados atualizados
 * @return bool Resultado da operação
 */
function atualizarUsuario($id, $dados) {
    global $db;
    $result = $db->update('usuarios', $dados, "id = :id", ['id' => $id]);
    return $result > 0;
}

/**
 * Exclui um usuário
 * 
 * @param int $id ID do usuário
 * @return bool Resultado da operação
 */
function excluirUsuario($id) {
    global $db;

    // Não permitir excluir o próprio usuário
    session_start();
    if (isset($_SESSION['usuario']) && $_SESSION['usuario']['id'] == $id) {
        return false;
    }

    // Não permitir excluir o único usuário admin
    $admins = $db->fetch("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'admin'", []);
    $usuario = $db->fetch("SELECT tipo FROM usuarios WHERE id = :id", ['id' => $id]);

    if ($admins['total'] <= 1 && $usuario['tipo'] === 'admin') {
        return false;
    }

    $result = $db->delete('usuarios', "id = :id", ['id' => $id]);
    return $result > 0;
}

function obterPerfilUsuario($id) {
    global $db;
    return $db->fetch("SELECT * FROM usuarios WHERE id = :id", ['id' => $id]);
}

/**
 * Função para obter vendas com filtro de período
 */
function obterVendas($periodo = 'todos') {
    global $db;

    $sql = "SELECT * FROM vendas";
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
            case 'mes':
                $dataInicio = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'ano':
                $dataInicio = date('Y-m-d', strtotime('-1 year'));
                break;
        }

        if ($dataInicio) {
            $sql .= " WHERE DATE(data) >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }
    }

    $sql .= " ORDER BY data DESC";

    return $db->fetchAll($sql, $params);
}

// Nota: As funções de estoque são implementadas no arquivo database.php


// Fim do arquivo PHP. Não adicione código JavaScript aqui.
?>