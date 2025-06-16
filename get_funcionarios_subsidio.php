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
$tipo = $_GET['tipo'] ?? '';

if (empty($tipo)) {
    die(json_encode(['success' => false, 'error' => 'Tipo de subsídio não especificado']));
}

// Buscar funcionários e seus subsídios
$sql = "SELECT id_fun as id, nome, num_mecanografico, estado, salario_base, data_admissao FROM funcionario WHERE empresa_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

$funcionarios = [];
while ($row = $result->fetch_assoc()) {
    $funcionarios[] = $row;
}
echo json_encode(['success' => true, 'funcionarios' => $funcionarios]);
exit;

$stmt->close();
$conn->close();
?> 