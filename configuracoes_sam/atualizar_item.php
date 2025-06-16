<?php
session_start();
include('../config.php');

if (!isset($_SESSION['id_adm'])) {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

$tipo = $_POST['tipo'] ?? '';
$id = $_POST['id'] ?? 0;
$nome = $_POST['nome'] ?? '';

if (!$tipo || !$id || !$nome) {
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
    $sql = "UPDATE departamentos SET nome = ? WHERE id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $nome, $id, $empresa_id);
} else if ($tipo === 'cargo') {
    $departamento_id = $_POST['departamento_id'] ?? 0;
    $salario_base = $_POST['salario_base'] ?? 0;

    if (!$departamento_id || !$salario_base) {
        die(json_encode(['success' => false, 'message' => 'Parâmetros inválidos para cargo']));
    }

    $sql = "UPDATE cargos SET nome = ?, departamento_id = ?, salario_base = ? WHERE id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sidsi", $nome, $departamento_id, $salario_base, $id, $empresa_id);
} else {
    die(json_encode(['success' => false, 'message' => 'Tipo inválido']));
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $conn->error]);
}
?> 