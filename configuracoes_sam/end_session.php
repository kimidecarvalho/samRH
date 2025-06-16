<?php
session_start();
require_once '../config';

header('Content-Type: application/json');

if (!isset($_SESSION['id_adm'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

if (!isset($_POST['session_id'])) {
    echo json_encode(['error' => 'ID da sessão não fornecido']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Não permitir encerrar a sessão atual
    if ($_POST['session_id'] === session_id()) {
        echo json_encode(['error' => 'Você não pode encerrar a sessão atual desta forma']);
        exit;
    }
    
    $query = "DELETE FROM adm_sessions 
              WHERE session_id = :session_id AND adm_id = :adm_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':session_id', $_POST['session_id']);
    $stmt->bindParam(':adm_id', $_SESSION['id_adm']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Sessão não encontrada ou já encerrada']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>