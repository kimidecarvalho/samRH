<?php
session_start();
include('../config.php');

if (!isset($_SESSION['id_adm'])) {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

$tipo = $_GET['tipo'] ?? '';
$id = $_GET['id'] ?? 0;

if (!$tipo || !$id) {
    die(json_encode(['success' => false, 'message' => 'Parâmetros inválidos']));
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
    die(json_encode(['success' => false, 'message' => 'Nenhuma empresa encontrada']));
}

$empresa_id = $admin['id_empresa'];

if ($tipo === 'departamento') {
    $sql = "SELECT id, nome FROM departamentos WHERE id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $empresa_id);
} else if ($tipo === 'cargo') {
    $sql = "SELECT id, nome, departamento_id, salario_base FROM cargos WHERE id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $empresa_id);
} else {
    die(json_encode(['success' => false, 'message' => 'Tipo inválido']));
}

$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if ($item) {
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    echo json_encode(['success' => false, 'message' => 'Item não encontrado']);
}
?> 