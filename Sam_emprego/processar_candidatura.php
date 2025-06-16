<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado como candidato
if (!isset($_SESSION['candidato_id'])) {
    // Redirecionar para a página de login com mensagem de erro
    header('Location: login.php?error=login_required&redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Verificar se o ID da vaga foi fornecido
$vaga_id = $_GET['vaga_id'] ?? null;

if (!$vaga_id) {
    // Redirecionar para a página de busca de vagas se nenhum ID de vaga foi fornecido
    header('Location: job_search_page.php?error=missing_job_id');
    exit;
}

// Verificar se a vaga existe e está aberta
try {
    $stmt = $pdo->prepare("SELECT * FROM vagas WHERE id = ? AND status = 'Aberta'");
    $stmt->execute([$vaga_id]);
    $vaga = $stmt->fetch();
    
    if (!$vaga) {
        // Redirecionar para a página de busca de vagas se a vaga não existir ou estiver fechada
        header('Location: job_search_page.php?error=job_not_available');
        exit;
    }
} catch (PDOException $e) {
    // Erro ao verificar a vaga
    header('Location: job_search_page.php?error=database_error');
    exit;
}

// Verificar se o candidato já se candidatou a esta vaga
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidaturas WHERE candidato_id = ? AND vaga_id = ?");
    $stmt->execute([$_SESSION['candidato_id'], $vaga_id]);
    $jaCandidatou = ($stmt->fetchColumn() > 0);
    
    if ($jaCandidatou) {
        // Redirecionar para a página da vaga com mensagem de erro
        header('Location: job_view_page.php?id=' . $vaga_id . '&error=already_applied');
        exit;
    }
} catch (PDOException $e) {
    // Erro ao verificar candidatura existente
    header('Location: job_view_page.php?id=' . $vaga_id . '&error=database_error');
    exit;
}

// Registrar a candidatura
try {
    $stmt = $pdo->prepare("INSERT INTO candidaturas (candidato_id, vaga_id, data_candidatura, status) VALUES (?, ?, NOW(), 'Pendente')");
    $resultado = $stmt->execute([
        $_SESSION['candidato_id'],
        $vaga_id
    ]);
    
    if ($resultado) {
        // Redirecionar para a página da vaga com mensagem de sucesso
        header('Location: job_view_page.php?id=' . $vaga_id . '&success=application_submitted');
        exit;
    } else {
        // Redirecionar para a página da vaga com mensagem de erro
        header('Location: job_view_page.php?id=' . $vaga_id . '&error=application_failed');
        exit;
    }
} catch (PDOException $e) {
    // Erro ao inserir candidatura
    header('Location: job_view_page.php?id=' . $vaga_id . '&error=database_error');
    exit;
} 