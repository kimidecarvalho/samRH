<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
// Incluir arquivo de sincronização
include_once('../includes/sync_app.php');

// Se for uma requisição OPTIONS, apenas retorne os cabeçalhos e encerre
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Conexão com o banco de dados do app
function getAppDbConnection() {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'app_empresas';

    $conn = new mysqli($host, $username, $password, $database);

    // Verificar conexão
    if ($conn->connect_error) {
        die(json_encode(['status' => 'error', 'message' => 'Conexão falhou: ' . $conn->connect_error]));

    }

    // Configurar para UTF-8
    $conn->set_charset("utf8mb4");

    return $conn;
}

// Conexão com o banco de dados do site
function getSiteDbConnection() {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'sam'; // Banco de dados do site

    $conn = new mysqli($host, $username, $password, $database);

    // Verificar conexão
    if ($conn->connect_error) {
        die(json_encode(['status' => 'error', 'message' => 'Conexão falhou: ' . $conn->connect_error]));

    }

    // Configurar para UTF-8
    $conn->set_charset("utf8mb4");

    return $conn;
}

// Receber dados do body
$data = json_decode(file_get_contents('php://input'), true);

// Se não houver dados ou action
if (!$data || !isset($data['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos ou ação não especificada']);

    exit;
}

$action = $data['action'];

// Realizar ação com base no parâmetro 'action'
switch ($action) {
    case 'getEmployees':
        getEmployees($data);
        break;
    case 'getNextId':
        getNextId($data);
        break;
    case 'registerEmployee':
        // Funcionalidade desativada, retornar mensagem informativa
        echo json_encode([
            'status' => 'error', 
            'message' => 'O cadastro de funcionários pelo aplicativo foi desativado. Por favor, utilize o site para cadastrar funcionários.'
        ]);
        break;
    case 'updateEmployee':
        updateEmployee($data);
        break;
    case 'deleteEmployee':
        deleteEmployee($data);
        break;
    case 'deleteEmpresa':
        deleteEmpresa($data);
        break;
    case 'syncDeletedEmpresa':
        syncDeletedEmpresa($data);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Ação desconhecida']);
        break;
}

// Obter funcionários de uma empresa específica
function getEmployees($data) {
    if (!isset($data['empresa_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID da empresa não fornecido']);
        return;
    }

    error_log("getEmployees: Recebido request para empresa_id: " . $data['empresa_id']);
    
    $empresa_id = $data['empresa_id'];
    $conn = getAppDbConnection();

    if ($conn->connect_error) {
        error_log("getEmployees: Erro de conexão: " . $conn->connect_error);
        echo json_encode(['status' => 'error', 'message' => 'Erro de conexão com o banco de dados']);
        return;
    }

    // Preparar consulta com parâmetro de empresa
    $stmt = $conn->prepare("SELECT * FROM employees WHERE empresa_id = ?");
    if (!$stmt) {
        error_log("getEmployees: Erro ao preparar query: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao preparar consulta']);
        $conn->close();
        return;
    }
    
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $num_rows = $result->num_rows;
    error_log("getEmployees: Encontrados $num_rows funcionários para empresa_id: $empresa_id");

    if ($num_rows > 0) {
        $employees = [];
        while ($row = $result->fetch_assoc()) {
            // Garantir que a digital_signature seja um booleano para o frontend
            $row['digitalSignature'] = (bool)$row['digital_signature'];
            unset($row['digital_signature']); // Remover o campo antigo
            
            $employees[] = $row;
        }
        echo json_encode(['status' => 'success', 'employees' => $employees]);
    } else {
        echo json_encode(['status' => 'success', 'employees' => [], 'message' => 'Nenhum funcionário encontrado']);
    }

    $stmt->close();
    $conn->close();
}

// Obter o próximo ID de funcionário para uma empresa específica
function getNextId($data) {
    if (!isset($data['empresa_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID da empresa não fornecido']);
        return;
    }

    $empresa_id = $data['empresa_id'];
    $conn = getAppDbConnection();

    // Obter o maior ID atual
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(id, 4) AS UNSIGNED)) as max_id FROM employees WHERE empresa_id = ? AND id LIKE 'EMP%'");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $maxId = $row['max_id'] ?? 0;
    $nextId = $maxId + 1;
    $nextIdFormatted = "EMP" . $nextId;

    $stmt->close();
    $conn->close();

    echo json_encode(['status' => 'success', 'nextId' => $nextIdFormatted]);
}

// Registrar novo funcionário
function registerEmployee($data) {
    // Verificar se todos os campos necessários estão presentes
    if (!isset($data['id']) || !isset($data['name']) || !isset($data['position']) || 
        !isset($data['department']) || !isset($data['empresa_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos']);
        return;
    }
    
    $id = $data['id'];
    $name = $data['name'];
    $position = $data['position'];
    $department = $data['department'];
    $digitalSignature = isset($data['digitalSignature']) ? $data['digitalSignature'] : 0;
    $empresa_id = $data['empresa_id'];
    
    $conn = getAppDbConnection();

    // Verificar se a empresa existe
    $stmt = $conn->prepare("SELECT id FROM empresas WHERE id = ?");
    $stmt->bind_param("i", $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Empresa não encontrada']);
        $stmt->close();
        $conn->close();
        return;
    }

    // Iniciar transação
    $conn->begin_transaction();

    try {
        // Inserir funcionário no app
        $stmt = $conn->prepare("INSERT INTO employees (id, name, position, department, digital_signature, empresa_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $id, $name, $position, $department, $digitalSignature, $empresa_id);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao cadastrar funcionário no app: " . $stmt->error);
        }

        // Agora, vamos sincronizar com o banco de dados do site (sam.sql)
        // Primeiro, obter o site_empresa_id relacionado a esta empresa do app
        $stmt_site_id = $conn->prepare("SELECT site_empresa_id FROM empresas WHERE id = ?");
        $stmt_site_id->bind_param("i", $empresa_id);
        $stmt_site_id->execute();
        $site_result = $stmt_site_id->get_result();

        if ($site_result->num_rows > 0) {
            $row = $site_result->fetch_assoc();
            $site_empresa_id = $row['site_empresa_id'];

            if ($site_empresa_id) {
                // Conectar ao banco de dados do site
                $site_conn = getSiteDbConnection();

                // Verificar se a conexão foi bem-sucedida
                if ($site_conn) {
                    // Inserir funcionário no banco de dados do site
                    // Construimos uma consulta simplificada com os campos mínimos obrigatórios
                    $site_stmt = $site_conn->prepare("INSERT INTO funcionario
                                                    (nome, cargo, departamento, empresa_id, num_mecanografico, email, estado)
                                                    VALUES (?, ?, ?, ?, ?, ?, 'Ativo')");

                    // Email temporário baseado no nome do funcionário (já que o app pode não coletar email)
                    $temp_email = strtolower(str_replace(' ', '.', $name)) . '@exemplo.com';

                    $site_stmt->bind_param("sssiss", $name, $position, $department, $site_empresa_id, $id, $temp_email);

                    // Se falhar ao inserir no site, continuamos mesmo assim, pois o funcionário já está no app
                    $site_result = $site_stmt->execute();
                    if (!$site_result) {
                        error_log("Erro ao inserir funcionário no site: " . $site_stmt->error);
                    }
                    $site_stmt->close();
                    $site_conn->close();
                }
            }
        }

        $stmt_site_id->close();

        // Verificar se deve sincronizar com o site usando a nova função
        if (isset($data['sync_with_site']) && $data['sync_with_site'] === true) {
            // Usar a função de sincronização dedicada
            sincronizarFuncionarioAppParaSite($id, $empresa_id);
        }

        // Commit da transação
        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => 'Funcionário cadastrado com sucesso']);

    } catch (Exception $e) {
        // Rollback em caso de erro
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Erro ao cadastrar funcionário: ' . $e->getMessage()]);
    }

    $stmt->close();
    $conn->close();
} 