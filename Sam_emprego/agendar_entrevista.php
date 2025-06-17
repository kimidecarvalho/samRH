<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION["empresa_id"]) || !isset($_POST['candidaturaId'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    // Primeiro, atualiza o status da candidatura para "Entrevista"
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
        // Busca os detalhes da candidatura para a mensagem
        $stmt = $pdo->prepare("
            SELECT c.candidato_id, v.titulo as vaga_titulo, e.nome as empresa_nome 
            FROM candidaturas c 
            JOIN vagas v ON c.vaga_id = v.id 
            JOIN empresas_recrutamento e ON v.empresa_id = e.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$_POST['candidaturaId']]);
        $candidatura = $stmt->fetch();

        // Formata a mensagem com os detalhes da entrevista
        $data_formatada = date('d/m/Y', strtotime($_POST['data']));
        $hora_formatada = date('H:i', strtotime($_POST['hora']));
        
        $mensagem = "Você foi convidado para uma entrevista para a vaga de {$candidatura['vaga_titulo']} na empresa {$candidatura['empresa_nome']}.\n\n";
        $mensagem .= "Data: {$data_formatada}\n";
        $mensagem .= "Hora: {$hora_formatada}\n";
        $mensagem .= "Tipo: " . ($_POST['tipo'] === 'presencial' ? 'Presencial' : 'Remota') . "\n";
        
        if ($_POST['tipo'] === 'presencial') {
            $mensagem .= "Local: {$local}";
        } else {
            $mensagem .= "Link: {$local}\n\n";
            $mensagem .= "Instruções:\n";
            $mensagem .= "1. Clique no link acima para acessar a sala de entrevista\n";
            $mensagem .= "2. Certifique-se de ter uma boa conexão com a internet\n";
            $mensagem .= "3. Teste seu microfone e câmera antes da entrevista\n";
            $mensagem .= "4. Entre na sala 5 minutos antes do horário marcado";
        }

        // Insere a mensagem
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
    echo json_encode(['success' => false, 'message' => 'Erro ao agendar entrevista: ' . $e->getMessage()]);
} 