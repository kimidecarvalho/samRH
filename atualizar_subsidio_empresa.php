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

$id = $data['id'] ?? null;
$tipo = $data['tipo'] ?? '';
$valor_padrao = $data['valor_padrao'] ?? null;
$ativo = $data['ativo'] ?? null;

if (empty($tipo)) {
    die(json_encode(['success' => false, 'error' => 'Tipo de subsídio não especificado']));
}

if ($id) {
    // Atualizar usando o ID
    $sql = "UPDATE subsidios_padrao SET ";
    $params = [];
    $types = "";
    
    if ($valor_padrao !== null) {
        $sql .= "valor_padrao = ?, ";
        $params[] = $valor_padrao;
        $types .= "d";
    }
    
    if ($ativo !== null) {
        $sql .= "ativo = ?, ";
        $params[] = $ativo;
        $types .= "i";
    }
    
    $sql = rtrim($sql, ", ");
    $sql .= " WHERE id = ? AND empresa_id = ?";
    
    $params[] = $id;
    $params[] = $empresa_id;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
} else {
    // Verificar se o subsídio existe
    $sql_check = "SELECT id FROM subsidios_padrao WHERE empresa_id = ? AND nome = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("is", $empresa_id, $tipo);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $subsidio = $result_check->fetch_assoc();

    if (!$subsidio) {
        // Se não existir, criar o subsídio
        $sql = "INSERT INTO subsidios_padrao (empresa_id, nome, tipo, valor_padrao, unidade, ativo) VALUES (?, ?, 'obrigatorio', ?, 'percentual', 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isd", $empresa_id, $tipo, $valor_padrao);
    } else {
        // Se existir, atualizar
        $sql = "UPDATE subsidios_padrao SET ";
        $params = [];
        $types = "";
        
        if ($valor_padrao !== null) {
            $sql .= "valor_padrao = ?, ";
            $params[] = $valor_padrao;
            $types .= "d";
        }
        
        if ($ativo !== null) {
            $sql .= "ativo = ?, ";
            $params[] = $ativo;
            $types .= "i";
        }
        
        $sql = rtrim($sql, ", ");
        $sql .= " WHERE empresa_id = ? AND nome = ?";
        
        $params[] = $empresa_id;
        $params[] = $tipo;
        $types .= "is";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
    }
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?> 