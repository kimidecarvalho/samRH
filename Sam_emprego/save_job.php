<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado como empresa
if (!isset($_SESSION["empresa_id"])) {
    header("Location: login.php");
    exit;
}

try {
    // Validar dados do formulário
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Dados básicos da vaga
        $titulo = $_POST['job_title'];
        $categoria = $_POST['job_category'];
        $status = $_POST['job_status'] == 'open' ? 'Aberta' : 'Fechada';
        
        // Localização e salário
        $localizacao = $_POST['job_location'];
        $tipo_contrato = $_POST['job_type'];
        $salario_min = $_POST['salary_min'];
        $salario_max = $_POST['salary_max'];
        
        // Descrição
        $descricao = $_POST['job_description'];
        
        // Inserir vaga no banco de dados
        $stmt = $pdo->prepare("INSERT INTO vagas (
            empresa_id, 
            titulo, 
            categoria,
            status,
            localizacao,
            tipo_contrato,
            salario_min,
            salario_max,
            descricao,
            data_publicacao
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )");

        $stmt->execute([
            $_SESSION['empresa_id'],
            $titulo,
            $categoria,
            $status,
            $localizacao,
            $tipo_contrato,
            $salario_min,
            $salario_max,
            $descricao
        ]);

        // Redirecionar com mensagem de sucesso
        $_SESSION['success_message'] = "Vaga publicada com sucesso!";
        header("Location: job_search_page_emp.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erro ao publicar vaga: " . $e->getMessage();
    header("Location: registro_vagas.php");
    exit;
}
?>
