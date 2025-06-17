<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION["empresa_id"]) || !isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE candidaturas SET status = ? WHERE id = ? AND EXISTS (SELECT 1 FROM vagas WHERE id = candidaturas.vaga_id AND empresa_id = ?)");
    $stmt->execute([$_POST['status'], $_POST['id'], $_SESSION['empresa_id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Candidatura não encontrada ou sem permissão']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()]);
}
