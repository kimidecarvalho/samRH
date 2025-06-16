<?php
// Handler global para erros e exceptions
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "Erro interno: [$errno] $errstr em $errfile:$errline"]);
    exit;
});
ob_start();

require_once '../config.php';
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['id_adm'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Receber dados do POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['funcionario_id']) || !isset($data['tipo']) || !isset($data['ativo'])) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

$funcionario_id = $data['funcionario_id'];
$tipo = $data['tipo'];
$ativo = $data['ativo'] ? 1 : 0;

try {
    // Buscar ID da empresa do administrador
    $sql_empresa = "SELECT id_empresa FROM empresa WHERE adm_id = ?";
    $stmt_empresa = $conn->prepare($sql_empresa);
    $stmt_empresa->bind_param("i", $_SESSION['id_adm']);
    $stmt_empresa->execute();
    $result_empresa = $stmt_empresa->get_result();
    $empresa = $result_empresa->fetch_assoc();

    if (!$empresa) {
        throw new Exception('Empresa não encontrada');
    }

    $empresa_id = $empresa['id_empresa'];

    // Verificar se o funcionário pertence à empresa
    $sql_func = "SELECT id_fun FROM funcionario WHERE id_fun = ? AND empresa_id = ?";
    $stmt_func = $conn->prepare($sql_func);
    $stmt_func->bind_param("ii", $funcionario_id, $empresa_id);
    $stmt_func->execute();
    $result_func = $stmt_func->get_result();

    if ($result_func->num_rows === 0) {
        throw new Exception('Funcionário não encontrado');
    }

    // Verificar se o subsídio está ativo na empresa
    $sql_subsidio = "SELECT id, ativo FROM subsidios_padrao WHERE empresa_id = ? AND nome = ?";
    $stmt_subsidio = $conn->prepare($sql_subsidio);
    $stmt_subsidio->bind_param("is", $empresa_id, $tipo);
    $stmt_subsidio->execute();
    $result_subsidio = $stmt_subsidio->get_result();

    if ($result_subsidio->num_rows === 0) {
        throw new Exception('Subsídio não encontrado');
    }

    $subsidio = $result_subsidio->fetch_assoc();

    // Se tentar ativar um subsídio que está inativo na empresa
    if ($ativo === 1 && $subsidio['ativo'] === 0) {
        throw new Exception('Não é possível ativar um subsídio que está inativo na empresa');
    }

    // Verificar se já existe um registro para este funcionário e subsídio
    $sql_check = "SELECT id FROM subsidios_funcionarios WHERE funcionario_id = ? AND subsidio_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $funcionario_id, $subsidio['id']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Atualizar registro existente
        $row = $result_check->fetch_assoc();
        $sql_update = "UPDATE subsidios_funcionarios SET ativo = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $ativo, $row['id']);
        
        if (!$stmt_update->execute()) {
            throw new Exception('Erro ao atualizar subsídio');
        }
    } else {
        // Inserir novo registro
        $sql_insert = "INSERT INTO subsidios_funcionarios (funcionario_id, subsidio_id, tipo_subsidio, valor, ativo) 
                       VALUES (?, ?, 'opcional', 0.00, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iii", $funcionario_id, $subsidio['id'], $ativo);
        
        if (!$stmt_insert->execute()) {
            throw new Exception('Erro ao inserir subsídio');
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
if (ob_get_level()) ob_end_flush();
?> 