<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
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

if (!isset($data['tipo'])) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

$tipo = $data['tipo'];
$valor_padrao = isset($data['valor_padrao']) ? floatval($data['valor_padrao']) : null;

// Buscar ID da empresa do administrador
$sql_empresa = "SELECT id_empresa FROM empresa WHERE adm_id = ?";
$stmt_empresa = $conn->prepare($sql_empresa);
$stmt_empresa->bind_param("i", $_SESSION['id_adm']);
$stmt_empresa->execute();
$result_empresa = $stmt_empresa->get_result();
$empresa = $result_empresa->fetch_assoc();

if (!$empresa) {
    echo json_encode(['success' => false, 'error' => 'Empresa não encontrada']);
    exit;
}

$empresa_id = $empresa['id_empresa'];

// Verificar se o subsídio já existe
$sql_check = "SELECT id FROM subsidios_padrao WHERE empresa_id = ? AND nome = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("is", $empresa_id, $tipo);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Atualizar subsídio existente
    $row = $result_check->fetch_assoc();
    $sql_update = "UPDATE subsidios_padrao SET valor_padrao = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("di", $valor_padrao, $row['id']);
    
    if ($stmt_update->execute()) {
        // Buscar o valor atualizado
        $sql_get = "SELECT valor_padrao FROM subsidios_padrao WHERE id = ?";
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->bind_param("i", $row['id']);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        $updated = $result_get->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'valor_padrao' => floatval($updated['valor_padrao'])
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar subsídio']);
    }
} else {
    // Inserir novo subsídio
    $sql_insert = "INSERT INTO subsidios_padrao (empresa_id, nome, tipo, valor_padrao, unidade, ativo) 
                   VALUES (?, ?, 'obrigatorio', ?, 'percentual', 1)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("isd", $empresa_id, $tipo, $valor_padrao);
    
    if ($stmt_insert->execute()) {
        echo json_encode([
            'success' => true,
            'valor_padrao' => floatval($valor_padrao)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao inserir subsídio']);
    }
}

$stmt->close();
$conn->close();