<?php
session_start();
include('../config.php');

if (!isset($_SESSION['id_adm'])) {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
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

// Verificar se é uma atualização de status ou edição de dados
if (isset($_POST['ativo'])) {
    // Atualização de status
    $id = $_POST['id'] ?? 0;
    $ativo = $_POST['ativo'] ?? 0;
    $isPredefinido = isset($_POST['predefinido']) && $_POST['predefinido'] == '1';

    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'ID do banco não fornecido']));
    }

    if ($isPredefinido) {
        // Para bancos predefinidos não cadastrados, criar novo registro
        $banco_nome = $_POST['banco_nome'] ?? '';
        $banco_codigo = $_POST['banco_codigo'] ?? '';
        
        if (!$banco_nome || !$banco_codigo) {
            die(json_encode(['success' => false, 'message' => 'Dados do banco predefinido incompletos']));
        }
        
        // Verificar se já existe um banco com o mesmo nome ou código
        $sql_check = "SELECT id FROM bancos_ativos WHERE (banco_nome = ? OR banco_codigo = ?) AND empresa_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ssi", $banco_nome, $banco_codigo, $empresa_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Se já existe, apenas atualizar o status
            $existing_banco = $result_check->fetch_assoc();
            $sql = "UPDATE bancos_ativos SET ativo = ? WHERE id = ? AND empresa_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $ativo, $existing_banco['id'], $empresa_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'banco_id' => $existing_banco['id']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar banco: ' . $conn->error]);
            }
            exit;
        } else {
            // Criar novo banco predefinido
            $sql = "INSERT INTO bancos_ativos (empresa_id, banco_nome, banco_codigo, ativo) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issi", $empresa_id, $banco_nome, $banco_codigo, $ativo);
            
            if ($stmt->execute()) {
                $novo_id = $conn->insert_id;
                echo json_encode(['success' => true, 'banco_id' => $novo_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar banco: ' . $conn->error]);
            }
            exit;
        }
    } else {
        // Para bancos já cadastrados, atualizar status normalmente
        $sql = "UPDATE bancos_ativos SET ativo = ? WHERE id = ? AND empresa_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $ativo, $id, $empresa_id);
    }
} else {
    // Edição de dados do banco
    $id = $_POST['id'] ?? 0;
    $banco_nome = $_POST['banco_nome'] ?? '';
    $banco_codigo = $_POST['banco_codigo'] ?? '';

    if (!$id || !$banco_nome || !$banco_codigo) {
        die(json_encode(['success' => false, 'message' => 'Dados incompletos']));
    }

    // Verificar se já existe um banco com o mesmo nome ou código
    $sql_check = "SELECT id FROM bancos_ativos WHERE (banco_nome = ? OR banco_codigo = ?) AND id != ? AND empresa_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ssii", $banco_nome, $banco_codigo, $id, $empresa_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        die(json_encode(['success' => false, 'message' => 'Já existe um banco com este nome ou código']));
    }

    $sql = "UPDATE bancos_ativos SET banco_nome = ?, banco_codigo = ? WHERE id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $banco_nome, $banco_codigo, $id, $empresa_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar banco: ' . $conn->error]);
}
?> 