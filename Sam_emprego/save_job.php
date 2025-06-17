<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado como empresa
if (!isset($_SESSION['empresa_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autorizado']);
    exit();
}

try {
    // Preparar os dados para inserção
    $dados = [
        'empresa_id' => $_SESSION['empresa_id'],
        'titulo' => $_POST['job_title'],
        'descricao' => $_POST['job_description'],
        'requisitos' => $_POST['requisitos'] ?? null,
        'departamento' => $_POST['job_category'],
        'localizacao' => $_POST['job_location'],
        'tipo_contrato' => $_POST['job_type'],
        'salario_min' => $_POST['salary_min'] ? floatval($_POST['salary_min']) : null,
        'salario_max' => $_POST['salary_max'] ? floatval($_POST['salary_max']) : null,
        'data_expiracao' => !empty($_POST['data_expiracao']) ? $_POST['data_expiracao'] : null,
        'status' => $_POST['job_status'] === 'open' ? 'Aberta' : 'Fechada',
        'categoria' => $_POST['job_category'],
        'localizacao_tipo' => $_POST['localizacao_tipo'],
        'periodo_salario' => $_POST['salary_period'],
        'metodo_pagamento' => $_POST['payment_method'],
        'idioma' => $_POST['language'],
        'fuso_horario' => $_POST['timezone'],
        'dias_uteis' => $_POST['workdays'],
        'horas_semanais_min' => $_POST['weekly_hours_min'] ? intval($_POST['weekly_hours_min']) : null,
        'horas_semanais_max' => $_POST['weekly_hours_max'] ? intval($_POST['weekly_hours_max']) : null,
        'horas_diarias_min' => $_POST['daily_hours_min'] ? intval($_POST['daily_hours_min']) : null,
        'horas_diarias_max' => $_POST['daily_hours_max'] ? intval($_POST['daily_hours_max']) : null,
        'hora_inicio' => $_POST['work_start_time'],
        'hora_fim' => $_POST['work_end_time']
    ];

    // Preparar a query SQL
    $sql = "INSERT INTO vagas (
        empresa_id, titulo, descricao, requisitos, departamento, 
        localizacao, tipo_contrato, salario_min, salario_max, 
        data_expiracao, status, categoria, localizacao_tipo, 
        periodo_salario, metodo_pagamento, idioma, fuso_horario, 
        dias_uteis, horas_semanais_min, horas_semanais_max, 
        horas_diarias_min, horas_diarias_max, hora_inicio, hora_fim
    ) VALUES (
        :empresa_id, :titulo, :descricao, :requisitos, :departamento,
        :localizacao, :tipo_contrato, :salario_min, :salario_max,
        :data_expiracao, :status, :categoria, :localizacao_tipo,
        :periodo_salario, :metodo_pagamento, :idioma, :fuso_horario,
        :dias_uteis, :horas_semanais_min, :horas_semanais_max,
        :horas_diarias_min, :horas_diarias_max, :hora_inicio, :hora_fim
    )";

    // Executar a query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($dados);

    // Retornar sucesso com redirecionamento para /sam/recrutamento.php
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Vaga cadastrada com sucesso!',
        'vaga_id' => $pdo->lastInsertId(),
        'redirect' => '/sam/recrutamento.php'
    ]);

} catch (PDOException $e) {
    // Retornar erro
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao cadastrar vaga: ' . $e->getMessage()
    ]);
}
?>
