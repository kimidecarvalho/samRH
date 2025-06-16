<?php
session_start();
include('config.php');

if (!isset($_SESSION['id_adm']) || !isset($_GET['departamento_id'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit;
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
    http_response_code(400);
    echo json_encode(['erro' => 'Nenhuma empresa encontrada para este administrador']);
    exit;
}

$empresa_id = $admin['id_empresa'];
$departamento_id = $_GET['departamento_id'];

// Debug: Verificar se o departamento existe
$sql_debug = "SELECT COUNT(*) as total FROM departamentos WHERE id = ? AND empresa_id = ?";
$stmt_debug = $conn->prepare($sql_debug);
$stmt_debug->bind_param("ii", $departamento_id, $empresa_id);
$stmt_debug->execute();
$result_debug = $stmt_debug->get_result();
$debug = $result_debug->fetch_assoc();

if ($debug['total'] == 0) {
    http_response_code(400);
    echo json_encode(['erro' => 'Departamento não encontrado']);
    exit;
}

// Buscar os cargos do departamento
$sql = "SELECT id, nome, salario_base FROM cargos WHERE departamento_id = ? AND empresa_id = ? ORDER BY nome";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $departamento_id, $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

$cargos = [];
while ($row = $result->fetch_assoc()) {
    $cargos[] = [
        'id' => $row['id'],
        'nome' => $row['nome'],
        'salario_base' => $row['salario_base']
    ];
}

// Debug: Verificar se há cargos
if (empty($cargos)) {
    echo json_encode(['erro' => 'Nenhum cargo encontrado para este departamento']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($cargos); 