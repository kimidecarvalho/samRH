<?php
require_once('../config.php');
session_start();

if (!isset($_SESSION['id_adm'])) {
    die(json_encode(['error' => 'Não autorizado']));
}

// Buscar ID da empresa do administrador
$sql_empresa = "SELECT id_empresa FROM empresa WHERE adm_id = ?";
$stmt_empresa = $conn->prepare($sql_empresa);
$stmt_empresa->bind_param("i", $_SESSION['id_adm']);
$stmt_empresa->execute();
$result_empresa = $stmt_empresa->get_result();
$empresa = $result_empresa->fetch_assoc();

if (!$empresa) {
    die(json_encode(['error' => 'Empresa não encontrada']));
}

// Buscar funcionários com joins para cargos e departamentos
$sql = "SELECT f.id_fun, f.nome, f.num_mecanografico, c.nome as cargo, d.nome as departamento, f.estado 
        FROM funcionario f 
        LEFT JOIN cargos c ON f.cargo = c.id 
        LEFT JOIN departamentos d ON f.departamento = d.id 
        WHERE f.empresa_id = ? 
        ORDER BY f.nome";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa['id_empresa']);
$stmt->execute();
$result = $stmt->get_result();

$funcionarios = [];
while ($row = $result->fetch_assoc()) {
    $funcionarios[] = [
        'id' => $row['id_fun'],
        'nome' => $row['nome'],
        'matricula' => $row['num_mecanografico'],
        'cargo' => $row['cargo'] ?? 'N/D',
        'departamento' => $row['departamento'] ?? 'N/D',
        'estado' => $row['estado']
    ];
}

header('Content-Type: application/json');
echo json_encode($funcionarios); 