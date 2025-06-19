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

function padronizar_nome_subsidio($nome) {
    return strtolower(str_replace(['-', ' '], '_', trim($nome)));
}

$tipo = isset($data['tipo']) ? padronizar_nome_subsidio($data['tipo']) : '';
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

// Buscar subsídio pelo nome padronizado, independente do tipo
$sql_check = "SELECT id, tipo, unidade FROM subsidios_padrao WHERE empresa_id = ? AND nome = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("is", $empresa_id, $tipo);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($row = $result_check->fetch_assoc()) {
    // Atualizar subsídio existente, mantendo tipo e unidade originais
    $sql_update = "UPDATE subsidios_padrao SET valor_padrao = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("di", $valor_padrao, $row['id']);
    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'valor_padrao' => $valor_padrao]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar subsídio']);
    }
} else {
    // Não criar novo subsídio!
    echo json_encode(['success' => false, 'error' => 'Subsídio não encontrado para atualização.']);
}

$stmt->close();
$conn->close(); 