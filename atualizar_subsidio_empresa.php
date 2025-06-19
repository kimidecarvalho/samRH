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

$tipo = $data['tipo'] ?? '';
$valor_padrao = $data['valor_padrao'] ?? null;

if (empty($tipo)) {
    die(json_encode(['success' => false, 'error' => 'Tipo de subsídio não especificado']));
}

    // Verificar se o subsídio existe
$sql_check = "SELECT id FROM subsidios_padrao WHERE empresa_id = ? AND nome = ? FOR UPDATE";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("is", $empresa_id, $tipo);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $subsidio = $result_check->fetch_assoc();

    if (!$subsidio) {
    die(json_encode(['success' => false, 'error' => 'Subsídio não encontrado. Apenas subsídios existentes podem ser atualizados.']));
}

// Atualizar o subsídio existente
$sql = "UPDATE subsidios_padrao SET valor_padrao = ? WHERE id = ? AND empresa_id = ?";
        $stmt = $conn->prepare($sql);
$stmt->bind_param("dii", $valor_padrao, $subsidio['id'], $empresa_id);

if ($stmt->execute()) {
    // Buscar o valor atualizado
    $sql_get = "SELECT valor_padrao FROM subsidios_padrao WHERE id = ?";
    $stmt_get = $conn->prepare($sql_get);
    $stmt_get->bind_param("i", $subsidio['id']);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();
    $updated = $result_get->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'valor_padrao' => floatval($updated['valor_padrao'])
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao atualizar subsídio: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>