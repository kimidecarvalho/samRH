<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['candidato_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_POST['candidatura_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da candidatura não fornecido']);
    exit;
}

$candidatura_id = $_POST['candidatura_id'];
$candidato_id = $_SESSION['candidato_id'];

try {
    // Primeiro, verifica se a candidatura pertence ao candidato
    $stmt = $conn->prepare("SELECT id FROM candidaturas WHERE id = ? AND candidato_id = ?");
    $stmt->bind_param("ii", $candidatura_id, $candidato_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Candidatura não encontrada ou não pertence ao candidato']);
        exit;
    }

    // Se pertence, deleta a candidatura
    $stmt = $conn->prepare("DELETE FROM candidaturas WHERE id = ? AND candidato_id = ?");
    $stmt->bind_param("ii", $candidatura_id, $candidato_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao deletar candidatura']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}
