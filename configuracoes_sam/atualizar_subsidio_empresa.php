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
    echo json_encode(['success' => false, 'error' => 'Dados incompletos: tipo do subsídio é obrigatório.']);
    exit;
}

function padronizar_nome_subsidio($nome) {
    return strtolower(str_replace(['-', ' '], '_', trim($nome)));
}

$tipo = padronizar_nome_subsidio($data['tipo']);
$empresa_id = null;

// Buscar ID da empresa do administrador
$sql_empresa = "SELECT id_empresa FROM empresa WHERE adm_id = ?";
$stmt_empresa = $conn->prepare($sql_empresa);
$stmt_empresa->bind_param("i", $_SESSION['id_adm']);
$stmt_empresa->execute();
$result_empresa = $stmt_empresa->get_result();

if ($empresa = $result_empresa->fetch_assoc()) {
    $empresa_id = $empresa['id_empresa'];
} else {
    echo json_encode(['success' => false, 'error' => 'Empresa não encontrada para este administrador.']);
    exit;
}

// Lógica de atualização
$sql = "UPDATE subsidios_padrao SET ";
$params = [];
$types = "";

// Verifica se o campo 'ativo' foi enviado e o adiciona à query
if (isset($data['ativo'])) {
    $sql .= "ativo = ?";
    $params[] = $data['ativo'] ? 1 : 0;
    $types .= "i";
}

// Verifica se o campo 'valor_padrao' foi enviado
if (isset($data['valor_padrao'])) {
    // Adiciona uma vírgula se o 'ativo' também estiver sendo atualizado
    if (isset($data['ativo'])) {
        $sql .= ", ";
    }
    $sql .= "valor_padrao = ?";
    $params[] = floatval($data['valor_padrao']);
    $types .= "d";
}

// Se nenhum campo foi enviado para atualizar, retorna erro
if (empty($params)) {
    echo json_encode(['success' => false, 'error' => 'Nenhum dado para atualizar foi fornecido.']);
    exit;
}

// Finaliza a query com a condição WHERE
$sql .= " WHERE empresa_id = ? AND nome = ?";
$params[] = $empresa_id;
$types .= "is";
$params[] = $tipo;

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erro ao preparar a query: ' . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Subsídio atualizado com sucesso.']);
    } else {
        // Isso acontece se o subsídio não for encontrado
        echo json_encode(['success' => false, 'error' => 'Subsídio não encontrado para atualização.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao executar a atualização: ' . $stmt->error]);
}

$stmt->close();
$conn->close(); 