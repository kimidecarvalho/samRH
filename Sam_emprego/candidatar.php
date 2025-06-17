<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar se o ID da vaga foi fornecido
if (!isset($_POST['vaga_id'])) {
    header("Location: job_search_page.php");
    exit();
}

$candidato_id = $_SESSION['candidato_id'];
$vaga_id = $_POST['vaga_id'];

try {
    // Verificar se o candidato já se candidatou para esta vaga
    $stmt = $pdo->prepare("SELECT id FROM candidaturas WHERE candidato_id = ? AND vaga_id = ?");
    $stmt->execute([$candidato_id, $vaga_id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['mensagem_erro'] = "Você já se candidatou para esta vaga.";
        header("Location: job_view_page.php?id=" . $vaga_id);
        exit();
    }

    // Buscar informações da vaga para obter o empresa_id
    $stmt = $pdo->prepare("SELECT empresa_id FROM vagas WHERE id = ?");
    $stmt->execute([$vaga_id]);
    $vaga = $stmt->fetch();

    if (!$vaga) {
        $_SESSION['mensagem_erro'] = "Vaga não encontrada.";
        header("Location: job_search_page.php");
        exit();
    }

    // Inserir a candidatura
    $stmt = $pdo->prepare("
        INSERT INTO candidaturas (candidato_id, vaga_id, status) 
        VALUES (?, ?, 'Pendente')
    ");
    $stmt->execute([$candidato_id, $vaga_id]);

    $_SESSION['mensagem_sucesso'] = "Candidatura realizada com sucesso!";
    header("Location: job_view_page.php?id=" . $vaga_id);
    exit();

} catch (PDOException $e) {
    $_SESSION['mensagem_erro'] = "Erro ao processar candidatura: " . $e->getMessage();
    header("Location: job_view_page.php?id=" . $vaga_id);
    exit();
} 