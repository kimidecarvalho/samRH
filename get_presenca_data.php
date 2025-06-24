<?php
// Arquivo para retornar dados de presença em formato JSON
session_start();
include 'protect.php';
include 'config.php';

// Verificar se o usuário está autenticado
if (!isset($_SESSION['id_empresa'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$id_empresa = $_SESSION['id_empresa'];
$data_atual = date('Y-m-d');

try {
    // 1. Funcionários a trabalhar (presentes hoje)
    $sql_trabalhando = "
        SELECT COUNT(DISTINCT f.id_fun) as total
        FROM funcionario f
        INNER JOIN registros_ponto rp ON f.id_fun = rp.funcionario_id
        WHERE f.empresa_id = ? 
        AND f.estado = 'Ativo'
        AND rp.data = ?
        AND rp.status = 'presente'";

    $stmt_trabalhando = $conn->prepare($sql_trabalhando);
    if ($stmt_trabalhando) {
        $stmt_trabalhando->bind_param("is", $id_empresa, $data_atual);
        $stmt_trabalhando->execute();
        $result_trabalhando = $stmt_trabalhando->get_result();
        $total_trabalhando = $result_trabalhando->fetch_assoc()['total'];
        $stmt_trabalhando->close();
    } else {
        $total_trabalhando = 0;
    }

    // 2. Funcionários com licenças (ausências registradas - médicas, pessoais, etc.)
    $sql_licencas = "
        SELECT COUNT(DISTINCT f.id_fun) as total
        FROM funcionario f
        INNER JOIN ausencias a ON f.id_fun = a.funcionario_id
        WHERE f.empresa_id = ? 
        AND f.estado = 'Ativo'
        AND a.tipo_ausencia IN ('Doença', 'Pessoal', 'Formação', 'Outro')
        AND ? BETWEEN a.data_inicio AND a.data_fim";

    $stmt_licencas = $conn->prepare($sql_licencas);
    if ($stmt_licencas) {
        $stmt_licencas->bind_param("is", $id_empresa, $data_atual);
        $stmt_licencas->execute();
        $result_licencas = $stmt_licencas->get_result();
        $total_licencas = $result_licencas->fetch_assoc()['total'];
        $stmt_licencas->close();
    } else {
        $total_licencas = 0;
    }

    // 3. Funcionários de férias
    $sql_ferias = "
        SELECT COUNT(DISTINCT f.id_fun) as total
        FROM funcionario f
        INNER JOIN ausencias a ON f.id_fun = a.funcionario_id
        WHERE f.empresa_id = ? 
        AND f.estado = 'Ativo'
        AND a.tipo_ausencia = 'Férias'
        AND ? BETWEEN a.data_inicio AND a.data_fim";

    $stmt_ferias = $conn->prepare($sql_ferias);
    if ($stmt_ferias) {
        $stmt_ferias->bind_param("is", $id_empresa, $data_atual);
        $stmt_ferias->execute();
        $result_ferias = $stmt_ferias->get_result();
        $total_ferias = $result_ferias->fetch_assoc()['total'];
        $stmt_ferias->close();
    } else {
        $total_ferias = 0;
    }

    // 4. Funcionários ausentes
    $sql_ausentes = "
        SELECT COUNT(DISTINCT f.id_fun) as total
        FROM funcionario f
        WHERE f.empresa_id = ? 
        AND f.estado = 'Ativo'
        AND DAYOFWEEK(?) NOT IN (1, 7) -- Não é fim de semana
        AND f.id_fun NOT IN (
            SELECT DISTINCT funcionario_id 
            FROM registros_ponto 
            WHERE empresa_id = ? AND data = ? AND status IN ('presente', 'atrasado')
        )
        AND f.id_fun NOT IN (
            SELECT DISTINCT funcionario_id 
            FROM ausencias 
            WHERE empresa_id = ? AND ? BETWEEN data_inicio AND data_fim
        )";

    $stmt_ausentes = $conn->prepare($sql_ausentes);
    if ($stmt_ausentes) {
        $stmt_ausentes->bind_param("isisis", $id_empresa, $data_atual, $id_empresa, $data_atual, $id_empresa, $data_atual);
        $stmt_ausentes->execute();
        $result_ausentes = $stmt_ausentes->get_result();
        $total_ausentes = $result_ausentes->fetch_assoc()['total'];
        $stmt_ausentes->close();
    } else {
        $total_ausentes = 0;
    }

    // 5. Total de funcionários ativos
    $sql_total = "SELECT COUNT(*) as total FROM funcionario WHERE empresa_id = ? AND estado = 'Ativo'";
    $stmt_total = $conn->prepare($sql_total);
    if ($stmt_total) {
        $stmt_total->bind_param("i", $id_empresa);
        $stmt_total->execute();
        $result_total = $stmt_total->get_result();
        $total_funcionarios = $result_total->fetch_assoc()['total'];
        $stmt_total->close();
    } else {
        $total_funcionarios = 0;
    }

    // Retornar dados em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'total_trabalhando' => (int)$total_trabalhando,
        'total_licencas' => (int)$total_licencas,
        'total_ferias' => (int)$total_ferias,
        'total_ausentes' => (int)$total_ausentes,
        'total_funcionarios' => (int)$total_funcionarios,
        'data_atual' => $data_atual
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?> 