<?php
session_start();
include('../config.php');

if (!isset($_SESSION['id_adm'])) {
    die("Acesso negado");
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
    die("Nenhuma empresa encontrada para este administrador.");
}

$empresa_id = $admin['id_empresa'];

// Buscar todos os departamentos da empresa
$sql_departamentos = "SELECT id, nome FROM departamentos WHERE empresa_id = ? ORDER BY nome";
$stmt_departamentos = $conn->prepare($sql_departamentos);
$stmt_departamentos->bind_param("i", $empresa_id);
$stmt_departamentos->execute();
$result_departamentos = $stmt_departamentos->get_result();

$departamentos = array();
while ($row = $result_departamentos->fetch_assoc()) {
    $departamentos[] = $row;
}

// Buscar todos os cargos da empresa
$sql_cargos = "SELECT c.id, c.nome, c.salario_base, d.nome as departamento_nome 
               FROM cargos c 
               JOIN departamentos d ON c.departamento_id = d.id 
               WHERE c.empresa_id = ? 
               ORDER BY d.nome, c.nome";
$stmt_cargos = $conn->prepare($sql_cargos);
$stmt_cargos->bind_param("i", $empresa_id);
$stmt_cargos->execute();
$result_cargos = $stmt_cargos->get_result();

$cargos = array();
while ($row = $result_cargos->fetch_assoc()) {
    $cargos[] = $row;
}

// Retornar os dados em formato JSON
header('Content-Type: application/json');
echo json_encode(array(
    'departamentos' => $departamentos,
    'cargos' => $cargos
)); 