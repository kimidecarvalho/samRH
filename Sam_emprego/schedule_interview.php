<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION["empresa_id"]) || !isset($_POST['candidaturaId'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE candidaturas 
        SET 
            status = 'Entrevista',
            entrevista_data = ?,
            entrevista_hora = ?,
            entrevista_tipo = ?,
            entrevista_local = ?
        WHERE id = ? AND id IN (
            SELECT c.id 
            FROM candidaturas c 
            JOIN vagas v ON c.vaga_id = v.id 
            WHERE v.empresa_id = ?
        )
    ");

    $local = $_POST['tipo'] === 'presencial' ? $_POST['local'] : $_POST['link'];
    
    $stmt->execute([
        $_POST['data'],
        $_POST['hora'],
        $_POST['tipo'],
        $local,
        $_POST['candidaturaId'],
        $_SESSION['empresa_id']
    ]);

    if ($stmt->rowCount() > 0) {
        // Get candidatura details for the message
        $stmt = $pdo->prepare("
            SELECT c.candidato_id, v.titulo as vaga_titulo, e.nome as empresa_nome 
            FROM candidaturas c 
            JOIN vagas v ON c.vaga_id = v.id 
            JOIN empresas_recrutamento e ON v.empresa_id = e.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$_POST['candidaturaId']]);
        $candidatura = $stmt->fetch();

        // Format the interview details message
        $data_formatada = date('d/m/Y', strtotime($_POST['data']));
        $hora_formatada = date('H:i', strtotime($_POST['hora']));
        
        $mensagem = "Você foi convidado para uma entrevista para a vaga de {$candidatura['vaga_titulo']} na empresa {$candidatura['empresa_nome']}.\n\n";
        $mensagem .= "Data: {$data_formatada}\n";
        $mensagem .= "Hora: {$hora_formatada}\n";
        $mensagem .= "Tipo: " . ($_POST['tipo'] === 'presencial' ? 'Presencial' : 'Remota') . "\n";
        $mensagem .= "Local: " . ($_POST['tipo'] === 'presencial' ? $local : "Link: {$local}");

        // Insert the message
        $stmt = $pdo->prepare("
            INSERT INTO mensagens (candidatura_id, remetente_tipo, remetente_id, mensagem)
            VALUES (?, 'empresa', ?, ?)
        ");
        $stmt->execute([$_POST['candidaturaId'], $_SESSION['empresa_id'], $mensagem]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Candidatura não encontrada ou sem permissão']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao agendar entrevista']);
}
