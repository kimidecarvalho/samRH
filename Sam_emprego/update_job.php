<?php
session_start();
if (!isset($_SESSION["empresa_id"])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar se a vaga pertence à empresa
        $stmt = $pdo->prepare("SELECT id FROM vagas WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$_POST['vaga_id'], $_SESSION['empresa_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Vaga não encontrada ou não pertence à sua empresa.");
        }

        // Preparar os dados para atualização
        $dados = [
            'id' => $_POST['vaga_id'],
            'empresa_id' => $_SESSION['empresa_id'],
            'titulo' => $_POST['job_title'],
            'descricao' => $_POST['job_description'],
            'requisitos' => $_POST['job_requirements'],
            'categoria' => $_POST['job_category'],
            'localizacao' => $_POST['job_location'],
            'tipo_contrato' => $_POST['job_type'],
            'salario_min' => $_POST['salary_min'],
            'salario_max' => $_POST['salary_max'],
            'data_expiracao' => $_POST['data_expiracao'] ?: null,
            'status' => $_POST['job_status'],
            'localizacao_tipo' => $_POST['localizacao_tipo'],
            'periodo_salario' => $_POST['salary_period'],
            'horas_semanais_min' => $_POST['weekly_hours_min'],
            'horas_semanais_max' => $_POST['weekly_hours_max'],
            'horas_diarias_min' => $_POST['daily_hours_min'],
            'horas_diarias_max' => $_POST['daily_hours_max'],
            'hora_inicio' => $_POST['work_start_time'],
            'hora_fim' => $_POST['work_end_time']
        ];

        // Preparar a query SQL
        $sql = "UPDATE vagas SET 
            titulo = :titulo,
            descricao = :descricao,
            requisitos = :requisitos,
            categoria = :categoria,
            localizacao = :localizacao,
            tipo_contrato = :tipo_contrato,
            salario_min = :salario_min,
            salario_max = :salario_max,
            data_expiracao = :data_expiracao,
            status = :status,
            localizacao_tipo = :localizacao_tipo,
            periodo_salario = :periodo_salario,
            horas_semanais_min = :horas_semanais_min,
            horas_semanais_max = :horas_semanais_max,
            horas_diarias_min = :horas_diarias_min,
            horas_diarias_max = :horas_diarias_max,
            hora_inicio = :hora_inicio,
            hora_fim = :hora_fim
            WHERE id = :id AND empresa_id = :empresa_id";

        // Executar a query
        $stmt = $pdo->prepare($sql);
        $stmt->execute($dados);

        // Redirecionar com mensagem de sucesso
        $_SESSION['mensagem_sucesso'] = "Vaga atualizada com sucesso!";
        header("Location: painel_empresa.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['mensagem_erro'] = "Erro ao atualizar vaga: " . $e->getMessage();
        header("Location: editar_vaga.php?id=" . $_POST['vaga_id']);
        exit;
    }
} else {
    header("Location: painel_empresa.php");
    exit;
}
?>
