<?php
session_start();
include('../config.php');

if (!isset($_SESSION['id_adm'])) {
    die(json_encode(['success' => false, 'error' => 'Acesso negado']));
}

// Obter o id_empresa do administrador
$admin_id = $_SESSION['id_adm'];
$sql_admin = "SELECT e.id_empresa FROM empresa e WHERE e.adm_id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $admin_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$admin = $result_admin->fetch_assoc();

if (!$admin) {
    die(json_encode(['success' => false, 'error' => 'Nenhuma empresa encontrada']));
}

$empresa_id = $admin['id_empresa'];

// Receber dados do POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    die(json_encode(['success' => false, 'error' => 'Dados inválidos']));
}

$funcionario_id = $data['funcionario_id'] ?? null;
$tipo = $data['tipo'] ?? '';
$ativo = $data['ativo'] ?? null;

if (!$funcionario_id || empty($tipo) || $ativo === null) {
    die(json_encode(['success' => false, 'error' => 'Parâmetros inválidos']));
}

// Verificar se o funcionário pertence à empresa
$sql_check = "SELECT id_fun FROM funcionario WHERE id_fun = ? AND empresa_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $funcionario_id, $empresa_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    die(json_encode(['success' => false, 'error' => 'Funcionário não encontrado']));
}

// Verificar se o subsídio existe
$sql_check_subsidio = "SELECT id FROM subsidios_padrao WHERE empresa_id = ? AND nome = ?";
$stmt_check_subsidio = $conn->prepare($sql_check_subsidio);
$stmt_check_subsidio->bind_param("is", $empresa_id, $tipo);
$stmt_check_subsidio->execute();
$result_check_subsidio = $stmt_check_subsidio->get_result();

if ($result_check_subsidio->num_rows === 0) {
    die(json_encode(['success' => false, 'error' => 'Subsídio não encontrado']));
}

// Atualizar ou inserir o subsídio do funcionário
$sql = "INSERT INTO subsidios_funcionario (funcionario_id, tipo, ativo) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE ativo = VALUES(ativo)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $funcionario_id, $tipo, $ativo);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?> 