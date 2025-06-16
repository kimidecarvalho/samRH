<?php
// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'protect.php'; // Protege a página para usuários autenticados
include 'config.php'; // Conexão com o banco de dados

// Verifica se o usuário está logado e tem um ID válido
if (!isset($_SESSION['id_adm'])) {
    echo "Erro: Usuário não autenticado.";
    exit;
}

// Verifica se o administrador está associado a uma empresa
if (!isset($_SESSION['id_empresa'])) {
    echo "<script>alert('Você precisa criar uma empresa antes de acessar esta página.'); window.location.href='Registro_adm.php';</script>";
    exit;
}

$empresa_id = $_SESSION['id_empresa']; // Recupera o id_empresa da sessão

// Configuração da paginação
$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Filtro de busca
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$departamento = isset($_GET['departamento']) ? mysqli_real_escape_string($conn, $_GET['departamento']) : '';
$tipo_ausencia = isset($_GET['tipo_ausencia']) ? mysqli_real_escape_string($conn, $_GET['tipo_ausencia']) : '';
$periodo = isset($_GET['periodo']) ? mysqli_real_escape_string($conn, $_GET['periodo']) : 'mes';

// Processamento do formulário de registro de ausência
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_ausencia'])) {
    $funcionario_id = mysqli_real_escape_string($conn, $_POST['funcionario_id']);
    $tipo_ausencia = mysqli_real_escape_string($conn, $_POST['tipo_ausencia']);
    $data_inicio = mysqli_real_escape_string($conn, $_POST['data_inicio']);
    $data_fim = mysqli_real_escape_string($conn, $_POST['data_fim']);
    $justificacao = mysqli_real_escape_string($conn, $_POST['justificacao']);
    $observacoes = mysqli_real_escape_string($conn, $_POST['observacoes']);
    
    // Cálculo de dias úteis entre as datas
    $dias_uteis = calcularDiasUteis($data_inicio, $data_fim);
    
    // Inserir nova ausência
    $sql_insert = "INSERT INTO ausencias (funcionario_id, empresa_id, tipo_ausencia, data_inicio, data_fim, 
                   dias_uteis, justificacao, observacoes, data_registro) 
                   VALUES ('$funcionario_id', '$empresa_id', '$tipo_ausencia', '$data_inicio', '$data_fim', 
                   '$dias_uteis', '$justificacao', '$observacoes', NOW())";
    
    if (mysqli_query($conn, $sql_insert)) {
        echo "<script>alert('Ausência registrada com sucesso!');</script>";
    } else {
        echo "<script>alert('Erro ao registrar ausência: " . mysqli_error($conn) . "');</script>";
    }
}

// Função para calcular dias úteis (excluindo fins de semana)
function calcularDiasUteis($data_inicio, $data_fim) {
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    $fim->modify('+1 day');
    
    $dias_uteis = 0;
    $intervalo = new DateInterval('P1D');
    $periodo = new DatePeriod($inicio, $intervalo, $fim);
    
    foreach ($periodo as $data) {
        $dia_semana = $data->format('N');
        if ($dia_semana < 6) { // 1 (segunda) até 5 (sexta)
            $dias_uteis++;
        }
    }
    
    return $dias_uteis;
}

// Consulta para trazer as ausências com nomes dos funcionários
$where_clauses = ["a.empresa_id = $empresa_id"];

if (!empty($search)) {
    $where_clauses[] = "f.nome LIKE '%$search%'";
}

if (!empty($departamento)) {
    $where_clauses[] = "f.departamento = '$departamento'";
}

if (!empty($tipo_ausencia)) {
    $where_clauses[] = "a.tipo_ausencia = '$tipo_ausencia'";
}

// Filtro por período
$data_atual = date('Y-m-d');
if ($periodo == 'mes') {
    $where_clauses[] = "a.data_inicio >= DATE_SUB('$data_atual', INTERVAL 1 MONTH)";
} elseif ($periodo == 'trimestre') {
    $where_clauses[] = "a.data_inicio >= DATE_SUB('$data_atual', INTERVAL 3 MONTH)";
} elseif ($periodo == 'semestre') {
    $where_clauses[] = "a.data_inicio >= DATE_SUB('$data_atual', INTERVAL 6 MONTH)";
} elseif ($periodo == 'ano') {
    $where_clauses[] = "a.data_inicio >= DATE_SUB('$data_atual', INTERVAL 1 YEAR)";
}

$where_sql = implode(" AND ", $where_clauses);

// Consulta para contar o total de ausências filtradas
$result_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ausencias a
                                     JOIN funcionario f ON a.funcionario_id = f.id_fun
                                     WHERE $where_sql");
$total_registros = mysqli_fetch_assoc($result_total)['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta para recuperar as ausências com paginação
$sql = "SELECT a.id, a.funcionario_id, f.nome AS nome_funcionario, f.num_mecanografico, 
        f.foto, f.departamento, a.tipo_ausencia, a.data_inicio, a.data_fim, 
        a.dias_uteis, a.justificacao, a.observacoes
        FROM ausencias a
        JOIN funcionario f ON a.funcionario_id = f.id_fun
        WHERE $where_sql
        ORDER BY a.data_inicio DESC
        LIMIT $registros_por_pagina OFFSET $offset";

$result = mysqli_query($conn, $sql);

// Consulta para obter dados para o gráfico de ausências por departamento
$sql_grafico_dept = "SELECT f.departamento, COUNT(*) as total, SUM(a.dias_uteis) as dias_total
                     FROM ausencias a
                     JOIN funcionario f ON a.funcionario_id = f.id_fun
                     WHERE a.empresa_id = $empresa_id
                     AND a.data_inicio >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY f.departamento
                     ORDER BY dias_total DESC";
                     
$result_grafico_dept = mysqli_query($conn, $sql_grafico_dept);

$departamentos = [];
$total_ausencias_dept = [];
$total_dias_dept = [];

while ($row = mysqli_fetch_assoc($result_grafico_dept)) {
    $departamentos[] = $row['departamento'];
    $total_ausencias_dept[] = $row['total'];
    $total_dias_dept[] = $row['dias_total'];
}

// Consulta para obter dados para o gráfico de ausências por tipo
$sql_grafico_tipo = "SELECT a.tipo_ausencia, COUNT(*) as total, SUM(a.dias_uteis) as dias_total
                     FROM ausencias a
                     WHERE a.empresa_id = $empresa_id
                     AND a.data_inicio >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY a.tipo_ausencia
                     ORDER BY dias_total DESC";
                     
$result_grafico_tipo = mysqli_query($conn, $sql_grafico_tipo);

$tipos_ausencia = [];
$total_ausencias_tipo = [];
$total_dias_tipo = [];

while ($row = mysqli_fetch_assoc($result_grafico_tipo)) {
    $tipos_ausencia[] = $row['tipo_ausencia'];
    $total_ausencias_tipo[] = $row['total'];
    $total_dias_tipo[] = $row['dias_total'];
}

// Consulta para trazer funcionários para o select
$sql_funcionarios = "SELECT id_fun, nome, num_mecanografico FROM funcionario 
                     WHERE empresa_id = $empresa_id AND estado = 'Ativo'
                     ORDER BY nome ASC";
$result_funcionarios = mysqli_query($conn, $sql_funcionarios);

// Consulta para obter dados para o gráfico de evolução mensal
$sql_evolucao = "SELECT 
                    DATE_FORMAT(a.data_inicio, '%Y-%m') as mes,
                    COUNT(*) as total_ausencias,
                    SUM(a.dias_uteis) as total_dias
                 FROM ausencias a
                 WHERE a.empresa_id = $empresa_id
                 AND a.data_inicio >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                 GROUP BY DATE_FORMAT(a.data_inicio, '%Y-%m')
                 ORDER BY mes ASC";
                 
$result_evolucao = mysqli_query($conn, $sql_evolucao);

$meses = [];
$total_ausencias_mes = [];
$total_dias_mes = [];

while ($row = mysqli_fetch_assoc($result_evolucao)) {
    // Formatar o mês para exibição (ex: 2024-04 para Abr/24)
    $data = DateTime::createFromFormat('Y-m', $row['mes']);
    $meses[] = $data->format('M/y');
    $total_ausencias_mes[] = $row['total_ausencias'];
    $total_dias_mes[] = $row['total_dias'];
}

// Departamentos disponíveis
$sql_departamentos = "SELECT DISTINCT departamento FROM funcionario WHERE empresa_id = $empresa_id ORDER BY departamento";
$result_departamentos = mysqli_query($conn, $sql_departamentos);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="all.css/registro3.css">
    <link rel="stylesheet" href="all.css/timer.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        /* Estilos gerais */
        :root {
            --primary-color: #64c2a7;
            --primary-light: rgba(100, 194, 167, 0.15);
            --primary-dark: #4aa18a;
            --secondary-color: #ff9900;
            --secondary-light: rgba(255, 153, 0, 0.15);
            --purple-color: #9966ff;
            --purple-light: rgba(153, 102, 255, 0.15);
            --blue-color: #0099ff;
            --blue-light: rgba(0, 153, 255, 0.15);
            --gray-color: #999999;
            --gray-light: rgba(153, 153, 153, 0.15);
            --dark-bg: #1A1A1A;
            --dark-element: #1E1E1E;
            --dark-input: #2C2C2C;
            --dark-border: #444;
            --dark-text: #e0e0e0;
            --light-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            --dark-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            --border-radius-sm: 8px;
            --border-radius-md: 15px;
            --border-radius-lg: 25px;
            --transition: all 0.3s ease;
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-select {
            background-color: white;
            border: 1px solid #e8e8e8;
            padding: 10px 15px;
            border-radius: var(--border-radius-lg);
            color: #333;
            font-size: 14px;
            width: 180px;
            transition: var(--transition);
            box-shadow: var(--light-shadow);
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .filter-select:hover, .filter-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-light);
            outline: none;
        }

        .search-bar {
            flex-grow: 1;
            max-width: 300px;
            background-color: white;
            border: 1px solid #e8e8e8;
            padding: 0;
            border-radius: var(--border-radius-lg);
            display: flex;
            align-items: center;
            height: 42px;
            box-shadow: var(--light-shadow);
            transition: var(--transition);
            margin-top: 19px;
        }

        .search-bar:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        .search-bar form {
            display: flex;
            width: 100%;
            align-items: center;
            padding: 0 15px;
        }

        .search-bar input {
            border: none;
            background: transparent;
            width: 100%;
            outline: none;
            color: #333;
            font-size: 14px;
            height: 100%;
            padding: 10;
            font-family: 'Poppins', sans-serif;
            margin-left:20px;
        }

        .search-bar button {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            margin-right: 10px;
            display: flex;
            align-items: center;
        }

        .search-icon {
            color: #777;
            transition: var(--transition);
        }

        .search-bar:focus-within .search-icon {
            color: var(--primary-color);
        }

        /* Dashboard container */
        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background-color: white;
            border-radius: var(--border-radius-md);
            padding: 24px;
            box-shadow: var(--light-shadow);
            display: flex;
            flex-direction: column;
            height: 100%;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--dark-shadow);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .card-icon {
            width: 42px;
            height: 42px;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            transition: var(--transition);
        }

        .card-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .card-stat {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .card-description {
            font-size: 14px;
            color: #666;
        }

        /* Estilos para gráficos */
        .chart-container {
            width: 100%;
            height: 250px;
            margin-top: 10px;
        }

        /* Container de tabela com rolagem */
        .table-container {
            width: 100%;
            overflow-x: auto;
            position: relative;
            background-color: white;
            border-radius: var(--border-radius-md);
            box-shadow: var(--light-shadow);
            margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.03);
        }

        /* Barra de rolagem estilizada */
        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 0 0 8px 8px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Tabela de ausências */
        .tabela-ausencias {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            white-space: nowrap;
        }

        .tabela-ausencias th {
            background-color: rgb(255, 255, 255);
            color: #555;
            font-weight: 600;
            text-align: center;
            padding: 18px 15px;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 10;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tabela-ausencias td {
            padding: 14px 12px;
            border-bottom: 1px solid #eee;
            color: #333;
            text-align: center;
            transition: var(--transition);
            font-size: 14px;
        }

        .tabela-ausencias tr:last-child td {
            border-bottom: none;
        }

        .tabela-ausencias tr {
            transition: var(--transition);
        }

        .tabela-ausencias tr:hover {
            background-color: #f9f9f9;
        }

        th, td {
            padding: 14px 12px;
            text-align: center;
            font-size: 14px;
            border-bottom: 1px solid #eee; 
            border-right: 1px solid #f2f2f2; 
        }

        tr:nth-child(odd) {
            background-color: #fcfcfc;
        }

        .tabela-ausencias tbody tr:hover {
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.08); 
            border-left: 5px solid var(--primary-color); 
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            transform: translateX(3px);
        }

        /* User avatar */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            margin: 25px 0;
            align-items: center;
            gap: 8px;
        }

        .pagination-item {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            color: #333;
            transition: var(--transition);
            background-color: white;
            box-shadow: var(--light-shadow);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .pagination-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .pagination-item.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 3px 10px rgba(100, 194, 167, 0.3);
        }

        /* Modal de registro de ausência */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border-radius: var(--border-radius-md);
            width: 80%;
            max-width: 600px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.25);
            position: relative;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 10px;
            transition: var(--transition);
        }

        .close:hover,
        .close:focus {
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-light);
            outline: none;
        }

        .btn-registrar {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: var(--border-radius-lg);
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
            width: 100%;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 3px 10px rgba(100, 194, 167, 0.3);
        }

        .btn-registrar:hover {
            background-color: var(--primary-dark);
            box-shadow: 0 5px 15px rgba(100, 194, 167, 0.4);
            transform: translateY(-2px);
        }

        .btn-nova-ausencia {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--border-radius-lg);
            cursor: pointer;
            font-weight: 500;
            margin-bottom: 25px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 3px 10px rgba(100, 194, 167, 0.3);
        }

        .btn-nova-ausencia:hover {
            background-color: var(--primary-dark);
            box-shadow: 0 5px 15px rgba(100, 194, 167, 0.4);
            transform: translateY(-2px);
        }

        /* Tag de tipo de ausência */
        .tag {
            display: inline-block;
            padding: 6px 12px;
            border-radius: var(--border-radius-lg);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .tag-ferias {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .tag-doenca {
            background-color: var(--secondary-light);
            color: var(--secondary-color);
        }

        .tag-pessoal {
            background-color: var(--purple-light);
            color: var(--purple-color);
        }

        .tag-formacao {
            background-color: var(--blue-light);
            color: var(--blue-color);
        }

        .tag-outro {
            background-color: var(--gray-light);
            color: var(--gray-color);
        }

        /* Cards de resumo */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .summary-card {
            background-color: white;
            border-radius: var(--border-radius-md);
            padding: 24px;
            box-shadow: var(--light-shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--dark-shadow);
        }

        .summary-card-icon {
            width: 54px;
            height: 54px;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 20px;
            transition: var(--transition);
        }

        .summary-card:hover .summary-card-icon {
            transform: scale(1.1);
        }

        .summary-card-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1px;
            font-weight: 500;
        }

        .summary-card-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        /* Seção de gráficos */
        .charts-section {
            margin-bottom: 35px;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
        }

        .chart-card {
            background-color: white;
            border-radius: var(--border-radius-md);
            padding: 24px;
            box-shadow: var(--light-shadow);
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--dark-shadow);
        }

        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            position: relative;
            padding-bottom: 12px;
        }

        .chart-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 3px;
        }

        .chart-card canvas {
            height: 300px !important;
            width: 100% !important;
        }

        /* Dark mode styles */
        body.dark {
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }

        body.dark .sidebar {
            background-color: var(--dark-element);
            border-right: 1px solid var(--dark-border);
        }

        body.dark .nav-menu a {
            color: #b0b0b0;
        }

        body.dark .nav-menu a:hover,
        body.dark .nav-menu a.active {
            color: var(--primary-color);
            background-color: rgba(100, 194, 167, 0.1);
        }

        body.dark .nav-select {
            background-color: var(--dark-input);
            color: var(--dark-text);
            border-color: var(--dark-border);
        }

        body.dark .main-content {
            background-color: var(--dark-bg);
        }

        body.dark .page-title {
            color: var(--dark-text);
        }

        body.dark .filter-select {
            background-color: var(--dark-input);
            color: var(--dark-text);
            border: 1px solid var(--dark-border);
        }

        body.dark .search-bar {
            background-color: var(--dark-input);
            border: 1px solid var(--dark-border);
        }

        body.dark .search-bar input {
            color: var(--dark-text);
        }

        body.dark .search-icon {
            color: #999;
        }

        body.dark .dashboard-card,
        body.dark .chart-card,
        body.dark .summary-card,
        body.dark .table-container,
        body.dark .modal-content,
        body.dark .pagination-item {
            background-color: var(--dark-element);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            border-color: var(--dark-border);
        }

        body.dark .card-title,
        body.dark .chart-title,
        body.dark .card-stat,
        body.dark .summary-card-value {
            color: var(--dark-text);
        }

        body.dark .card-description,
        body.dark .summary-card-title {
            color: #b0b0b0;
        }

        body.dark .tabela-ausencias th {
            background-color: var(--dark-input);
            color: var(--dark-text);
            border-bottom: 1px solid var(--dark-border);
        }

        body.dark .tabela-ausencias td {
            color: #d0d0d0;
            border-bottom: 1px solid var(--dark-border);
            border-right: 1px solid var(--dark-border);
        }

        body.dark .tabela-ausencias tr:nth-child(odd) {
            background-color: rgba(255, 255, 255, 0.03);
        }

        body.dark .tabela-ausencias tr:hover {
            background-color: rgba(100, 194, 167, 0.1);
        }

        body.dark .form-control {
            background-color: var(--dark-input);
            border-color: var(--dark-border);
            color: var(--dark-text);
        }

        body.dark .close {
            color: #888;
        }

        body.dark .close:hover {
            color: var(--dark-text);
        }

        body.dark .pagination-item {
            color: var(--dark-text);
            background-color: var(--dark-element);
        }

        body.dark .pagination-item:hover {
            background-color: rgba(100, 194, 167, 0.2);
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .summary-cards {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
            
            .filter-select, .search-bar {
                width: 100%;
                max-width: none;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }

        /* Responsive chart height adjustments */
        @media (max-width: 1200px) {
            .chart-card canvas {
                height: 250px !important;
            }
        }

        @media (max-width: 768px) {
            .chart-card canvas {
                height: 220px !important;
            }
        }
    </style>
    <title>SAM - Ausências</title>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <a href="UI.php">
                <img src="img/sam2logo-32.png" alt="SAM Logo">
            </a>
        </div>
        <select class="nav-select">
            <option>sam</option>
        </select>
        <ul class="nav-menu">           
            <a href="funcionarios.php"><li>Funcionários</li></a>
            <a href="registro.php"><li>Novo Funcionário</li></a>
            <a href="processamento_salarial.php"><li>Processamento Salarial</li></a>
            <a href="docs.php"><li>Documentos</li></a>
            <a href="registro_ponto.php"><li>Registro de Ponto</li></a>
            <a href="ausencias.php"><li class="active">Ausências</li></a>
            <a href="recrutamento.php"><li>Recrutamento</li></a>
        </ul>
    </div>

    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Gestão de Ausências</h1>
            <div class="header-buttons">
                <div class="time" id="current-time"></div>
                <a class="exit-tag" href="logout.php">Sair</a>
                <a href="./configuracoes_sam/perfil_adm.php" class="perfil_img">                
                    <div class="user-profile">
                        <img src="icones/icons-sam-18.svg" alt="User" width="20">
                        <span><?php echo $_SESSION['nome']; ?></span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </div>
                </a>
            </div>
        </header>

        <button id="btnNovaAusencia" class="btn-nova-ausencia">
            <i class="fas fa-plus"></i> Registrar Nova Ausência
        </button>
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-card-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="summary-card-title">Total de Ausências</div>
                <div class="summary-card-value">
                    <?php 
                    // Consulta para obter total de ausências
                    $sql_total = "SELECT COUNT(*) as total FROM ausencias WHERE empresa_id = $empresa_id";
                    $result_total_ausencias = mysqli_query($conn, $sql_total);
                    $total_ausencias = mysqli_fetch_assoc($result_total_ausencias)['total'];
                    echo $total_ausencias;
                    ?>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-icon" style="background-color: rgba(255, 153, 0, 0.2); color: #ff9900;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="summary-card-title">Dias Perdidos</div>
                <div class="summary-card-value">
                    <?php 
                    // Consulta para obter total de dias perdidos
                    $sql_dias = "SELECT SUM(dias_uteis) as total_dias FROM ausencias WHERE empresa_id = $empresa_id";
                    $result_dias = mysqli_query($conn, $sql_dias);
                    $total_dias = mysqli_fetch_assoc($result_dias)['total_dias'] ?: 0;
                    echo $total_dias;
                    ?>
                </div>
            </div>
            
            <div class="summary-card">
<div class="summary-card-icon" style="background-color: rgba(153, 102, 255, 0.2); color: #9966ff;">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="summary-card-title">Taxa de Absentismo</div>
                <div class="summary-card-value">
                    <?php 
                    // Consulta para calcular taxa de absentismo (dias perdidos / dias úteis totais * 100)
                    // Primeiro, obter o total de funcionários ativos
                    $sql_func = "SELECT COUNT(*) as total_func FROM funcionario WHERE empresa_id = $empresa_id AND estado = 'Ativo'";
                    $result_func = mysqli_query($conn, $sql_func);
                    $total_func = mysqli_fetch_assoc($result_func)['total_func'];
                    
                    // Calcular os dias úteis nos últimos 30 dias (aproximadamente 22 dias úteis por mês)
                    $dias_uteis_mes = 22;
                    $dias_trabalho_potencial = $total_func * $dias_uteis_mes;
                    
                    // Obter dias perdidos nos últimos 30 dias
                    $sql_dias_recentes = "SELECT SUM(dias_uteis) as dias_perdidos FROM ausencias 
                                         WHERE empresa_id = $empresa_id 
                                         AND data_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    $result_dias_recentes = mysqli_query($conn, $sql_dias_recentes);
                    $dias_perdidos = mysqli_fetch_assoc($result_dias_recentes)['dias_perdidos'] ?: 0;
                    
                    // Calcular taxa de absentismo
                    $taxa_absentismo = ($dias_trabalho_potencial > 0) ? ($dias_perdidos / $dias_trabalho_potencial) * 100 : 0;
                    echo number_format($taxa_absentismo, 1) . '%';
                    ?>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-card-icon" style="background-color: rgba(0, 153, 255, 0.2); color: #0099ff;">
                    <i class="fas fa-umbrella-beach"></i>
                </div>
                <div class="summary-card-title">Férias em Curso</div>
                <div class="summary-card-value">
                    <?php 
                    // Consulta para obter férias em curso
                    $sql_ferias = "SELECT COUNT(*) as total_ferias FROM ausencias 
                                  WHERE empresa_id = $empresa_id 
                                  AND tipo_ausencia = 'Férias' 
                                  AND data_inicio <= CURDATE() 
                                  AND data_fim >= CURDATE()";
                    $result_ferias = mysqli_query($conn, $sql_ferias);
                    $ferias_curso = mysqli_fetch_assoc($result_ferias)['total_ferias'];
                    echo $ferias_curso;
                    ?>
                </div>
            </div>
        </div>

        <!-- Seção de gráficos -->
        <div class="charts-section">
            <div class="charts-container">
                <div class="chart-card">
                    <div class="chart-title">Ausências por Departamento</div>
                    <canvas id="departamentosChart"></canvas>
                </div>
                
                <div class="chart-card">
                    <div class="chart-title">Ausências por Tipo</div>
                    <canvas id="tiposChart"></canvas>
                </div>
                
                <div class="chart-card" style="grid-column: 1 / -1;">
                    <div class="chart-title">Evolução de Ausências (12 meses)</div>
                    <canvas id="evolucaoChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <form method="GET" action="ausencias.php" id="filtroForm">
                <select name="departamento" class="filter-select" onchange="document.getElementById('filtroForm').submit()">
                    <option value="" <?php echo empty($departamento) ? 'selected' : ''; ?>>Todos os departamentos</option>
                    <?php while ($dept = mysqli_fetch_assoc($result_departamentos)) { ?>
                        <option value="<?php echo $dept['departamento']; ?>" <?php echo $departamento == $dept['departamento'] ? 'selected' : ''; ?>>
                            <?php echo $dept['departamento']; ?>
                        </option>
                    <?php } ?>
                </select>
                
                <select name="tipo_ausencia" class="filter-select" onchange="document.getElementById('filtroForm').submit()">
                    <option value="" <?php echo empty($tipo_ausencia) ? 'selected' : ''; ?>>Todos os tipos</option>
                    <option value="Férias" <?php echo $tipo_ausencia == 'Férias' ? 'selected' : ''; ?>>Férias</option>
                    <option value="Doença" <?php echo $tipo_ausencia == 'Doença' ? 'selected' : ''; ?>>Doença</option>
                    <option value="Pessoal" <?php echo $tipo_ausencia == 'Pessoal' ? 'selected' : ''; ?>>Pessoal</option>
                    <option value="Formação" <?php echo $tipo_ausencia == 'Formação' ? 'selected' : ''; ?>>Formação</option>
                    <option value="Outro" <?php echo $tipo_ausencia == 'Outro' ? 'selected' : ''; ?>>Outro</option>
                </select>
                
                <select name="periodo" class="filter-select" onchange="document.getElementById('filtroForm').submit()">
                    <option value="mes" <?php echo $periodo == 'mes' ? 'selected' : ''; ?>>Último mês</option>
                    <option value="trimestre" <?php echo $periodo == 'trimestre' ? 'selected' : ''; ?>>Último trimestre</option>
                    <option value="semestre" <?php echo $periodo == 'semestre' ? 'selected' : ''; ?>>Último semestre</option>
                    <option value="ano" <?php echo $periodo == 'ano' ? 'selected' : ''; ?>>Último ano</option>
                </select>
                
                <div class="search-bar">
                    <input type="text" name="search" id="search-input" placeholder="Pesquisar funcionário..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    <button type="submit"><i class="fas fa-search search-icon"></i></button>
                </div>
            </form>
        </div>
        
        <!-- Tabela de ausências -->
        <div class="table-container">
            <table class="tabela-ausencias">
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>Departamento</th>
                        <th>Tipo</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th>Dias Úteis</th>
                        <th>Justificação</th>
                        <th>Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) { 
                            // Determinar a classe CSS para o tipo de ausência
                            $tipo_class = '';
                            switch($row['tipo_ausencia']) {
                                case 'Férias':
                                    $tipo_class = 'tag-ferias';
                                    break;
                                case 'Doença':
                                    $tipo_class = 'tag-doenca';
                                    break;
                                case 'Pessoal':
                                    $tipo_class = 'tag-pessoal';
                                    break;
                                case 'Formação':
                                    $tipo_class = 'tag-formacao';
                                    break;
                                default:
                                    $tipo_class = 'tag-outro';
                            }
                            
                            // Formatar as datas
                            $data_inicio = date('d/m/Y', strtotime($row['data_inicio']));
                            $data_fim = date('d/m/Y', strtotime($row['data_fim']));
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px; margin-left:5px;">
                                <div class="user-avatar">
                                <?php if (!empty($row['foto']) && file_exists($row['foto'])): ?>
                                    <img src="<?php echo $row['foto']; ?>" alt="" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <img src="icones/icons-sam-18.svg" alt="Avatar Padrão" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?php echo $row['nome_funcionario']; ?></strong>
                                    <div style="font-size: 12px; color: #666;">
                                        <?php echo str_pad($row['num_mecanografico'], 3, '0', STR_PAD_LEFT); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $row['departamento']; ?></td>
                        <td><span class="tag <?php echo $tipo_class; ?>"><?php echo $row['tipo_ausencia']; ?></span></td>
                        <td><?php echo $data_inicio; ?></td>
                        <td><?php echo $data_fim; ?></td>
                        <td><?php echo $row['dias_uteis']; ?></td>
                        <td><?php echo $row['justificacao']; ?></td>
                        <td><?php echo $row['observacoes']; ?></td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo '<tr><td colspan="8" style="text-align: center; padding: 20px;">Nenhuma ausência encontrada</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
        <div class="pagination">
            <?php if ($pagina_atual > 1): ?>
                <a href="?pagina=<?php echo $pagina_atual - 1; ?>&search=<?php echo urlencode($search); ?>&departamento=<?php echo urlencode($departamento); ?>&tipo_ausencia=<?php echo urlencode($tipo_ausencia); ?>&periodo=<?php echo $periodo; ?>" class="pagination-item">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&departamento=<?php echo urlencode($departamento); ?>&tipo_ausencia=<?php echo urlencode($tipo_ausencia); ?>&periodo=<?php echo $periodo; ?>" class="pagination-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($pagina_atual < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina_atual + 1; ?>&search=<?php echo urlencode($search); ?>&departamento=<?php echo urlencode($departamento); ?>&tipo_ausencia=<?php echo urlencode($tipo_ausencia); ?>&periodo=<?php echo $periodo; ?>" class="pagination-item">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal de registro de ausência -->
    <div id="modalAusencia" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 style="margin-top: 0; margin-bottom: 20px;">Registrar Nova Ausência</h2>
            
            <form method="POST" action="ausencias.php">
                <div class="form-group">
                    <label for="funcionario_id">Funcionário:</label>
                    <select id="funcionario_id" name="funcionario_id" class="form-control" required>
                        <option value="">Selecione um funcionário</option>
                        <?php 
                        mysqli_data_seek($result_funcionarios, 0);
                        while ($funcionario = mysqli_fetch_assoc($result_funcionarios)) {
                            echo '<option value="' . $funcionario['id_fun'] . '">' . $funcionario['nome'] . ' (' . str_pad($funcionario['num_mecanografico'], 3, '0', STR_PAD_LEFT) . ')</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tipo_ausencia">Tipo de Ausência:</label>
                    <select id="tipo_ausencia" name="tipo_ausencia" class="form-control" required>
                        <option value="">Selecione o tipo</option>
                        <option value="Férias">Férias</option>
                        <option value="Doença">Doença</option>
                        <option value="Pessoal">Pessoal</option>
                        <option value="Formação">Formação</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="data_inicio">Data de Início:</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="data_fim">Data de Fim:</label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="justificacao">Justificação:</label>
                    <input type="text" id="justificacao" name="justificacao" class="form-control" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="observacoes">Observações:</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="3"></textarea>
                </div>
                
                <button type="submit" name="registrar_ausencia" class="btn-registrar">Registrar Ausência</button>
            </form>
        </div>
    </div>

    <script>
        // Atualizar relógio
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
        }
        updateTime();
        setInterval(updateTime, 1000);
        
        // Modal
        const modal = document.getElementById("modalAusencia");
        const btnNovaAusencia = document.getElementById("btnNovaAusencia");
        const span = document.getElementsByClassName("close")[0];
        
        btnNovaAusencia.onclick = function() {
            modal.style.display = "block";
        }
        
        span.onclick = function() {
            modal.style.display = "none";
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        // Validação de datas
        document.getElementById('data_inicio').addEventListener('change', validarDatas);
        document.getElementById('data_fim').addEventListener('change', validarDatas);
        
        function validarDatas() {
            const dataInicio = document.getElementById('data_inicio').value;
            const dataFim = document.getElementById('data_fim').value;
            
            if (dataInicio && dataFim && dataInicio > dataFim) {
                alert('A data de início não pode ser posterior à data de fim.');
                document.getElementById('data_fim').value = dataInicio;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
    // Cores consistentes para os gráficos
    const primaryColor = '#64c2a7';
    const secondaryColor = '#ff9900';
    const purpleColor = '#9966ff';
    const blueColor = '#0099ff';
    const grayColor = '#999999';
    
    // Arrays de cores para gráficos
    const backgroundColors = [
        'rgba(100, 194, 167, 0.7)', // verde
        'rgba(255, 153, 0, 0.7)',   // laranja
        'rgba(153, 102, 255, 0.7)', // roxo
        'rgba(0, 153, 255, 0.7)',   // azul
        'rgba(153, 153, 153, 0.7)'  // cinza
    ];
    
    const borderColors = [
        'rgba(100, 194, 167, 1)',
        'rgba(255, 153, 0, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(0, 153, 255, 1)',
        'rgba(153, 153, 153, 1)'
    ];
    
    // Verificar tema atual para ajustar cores
    const isDarkMode = document.body.classList.contains('dark');
    const textColor = isDarkMode ? '#e0e0e0' : '#333';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    
    // Opções globais do Chart.js
    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.font.size = 13;
    Chart.defaults.color = textColor;
    Chart.defaults.plugins.tooltip.backgroundColor = isDarkMode ? '#333' : 'rgba(0, 0, 0, 0.8)';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.titleFont = { weight: 'bold', size: 14 };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 13 };
    Chart.defaults.plugins.tooltip.displayColors = true;
    Chart.defaults.plugins.tooltip.boxPadding = 5;
    
    // Configurações de escalas comuns
    const commonScaleOptions = {
        grid: {
            color: gridColor,
            borderColor: gridColor,
            tickColor: gridColor
        },
        ticks: {
            color: textColor,
            font: {
                size: 12
            }
        }
    };
    
    // Gráfico de departamentos (melhorado)
    const ctxDepartamentos = document.getElementById('departamentosChart').getContext('2d');
    const departamentosChart = new Chart(ctxDepartamentos, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($departamentos); ?>,
            datasets: [{
                label: 'Dias de Ausência',
                data: <?php echo json_encode($total_dias_dept); ?>,
                backgroundColor: backgroundColors[0],
                borderColor: borderColors[0],
                borderWidth: 2,
                borderRadius: 6,
                hoverBackgroundColor: 'rgba(100, 194, 167, 0.9)',
                barPercentage: 0.7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 10,
                    right: 16,
                    bottom: 10,
                    left: 16
                }
            },
            scales: {
                y: {
                    ...commonScaleOptions,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Número de Dias',
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        color: textColor
                    }
                },
                x: {
                    ...commonScaleOptions,
                    title: {
                        display: true,
                        text: 'Departamento',
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        color: textColor
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(tooltipItem) {
                            return tooltipItem[0].label;
                        },
                        label: function(context) {
                            return `Dias de ausência: ${context.parsed.y}`;
                        }
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
    
    // Gráfico de tipos de ausência (melhorado)
    const ctxTipos = document.getElementById('tiposChart').getContext('2d');
    const tiposChart = new Chart(ctxTipos, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($tipos_ausencia); ?>,
            datasets: [{
                label: 'Dias de Ausência',
                data: <?php echo json_encode($total_dias_tipo); ?>,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 2,
                hoverBackgroundColor: backgroundColors.map(color => color.replace('0.7', '0.9')),
                hoverBorderWidth: 3,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            layout: {
                padding: 16
            },
            plugins: {
                legend: {
                    position: 'right',
                    align: 'center',
                    labels: {
                        boxWidth: 15,
                        padding: 15,
                        font: {
                            size: 13
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.formattedValue;
                            let total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                            let percentage = Math.round((context.parsed / total) * 100);
                            return `${label}: ${value} dias (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
    
    // Gráfico de evolução (melhorado)
    const ctxEvolucao = document.getElementById('evolucaoChart').getContext('2d');
    const evolucaoChart = new Chart(ctxEvolucao, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($meses); ?>,
            datasets: [{
                label: 'Número de Ausências',
                data: <?php echo json_encode($total_ausencias_mes); ?>,
                borderColor: primaryColor,
                backgroundColor: 'rgba(100, 194, 167, 0.2)',
                fill: true,
                tension: 0.3,
                borderWidth: 3,
                pointBackgroundColor: primaryColor,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: primaryColor,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2
            }, {
                label: 'Dias Perdidos',
                data: <?php echo json_encode($total_dias_mes); ?>,
                borderColor: secondaryColor,
                backgroundColor: 'rgba(255, 153, 0, 0.2)',
                fill: true,
                tension: 0.3,
                borderWidth: 3,
                pointBackgroundColor: secondaryColor,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: secondaryColor,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 10,
                    right: 16,
                    bottom: 10,
                    left: 16
                }
            },
            scales: {
                y: {
                    ...commonScaleOptions,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantidade',
                        font: {
                            size: 14,
                        },
                        color: textColor
                    }
                },
                x: {
                    ...commonScaleOptions,
                    title: {
                        display: true,
                        text: 'Mês',
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        color: textColor
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'center',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let value = context.formattedValue;
                            return `${label}: ${value}`;
                        }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
    
    // Função para atualização de tema
    function updateChartsTheme() {
        const isDarkMode = document.body.classList.contains('dark');
        const textColor = isDarkMode ? '#e0e0e0' : '#333';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        
        // Atualizar configurações globais
        Chart.defaults.color = textColor;
        Chart.defaults.plugins.tooltip.backgroundColor = isDarkMode ? '#333' : 'rgba(0, 0, 0, 0.8)';
        
        // Atualizar configurações de escalas para todos os gráficos
        const charts = [departamentosChart, tiposChart, evolucaoChart];
        charts.forEach(chart => {
            if (chart.options.scales) {
                Object.keys(chart.options.scales).forEach(scaleKey => {
                    chart.options.scales[scaleKey].grid.color = gridColor;
                    chart.options.scales[scaleKey].grid.borderColor = gridColor;
                    chart.options.scales[scaleKey].ticks.color = textColor;
                    if (chart.options.scales[scaleKey].title) {
                        chart.options.scales[scaleKey].title.color = textColor;
                    }
                });
            }
            
            chart.update();
        });
    }
    
    // Detectar mudanças no tema (opcional)
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('change', updateChartsTheme);
    }
    
    // Opcional: animação ao entrar na visualização
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };
    
    const chartObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const chartId = entry.target.id;
                if (chartId === 'departamentosChart') departamentosChart.update();
                if (chartId === 'tiposChart') tiposChart.update();
                if (chartId === 'evolucaoChart') evolucaoChart.update();
            }
        });
    }, observerOptions);
    
    // Observar os elementos dos gráficos
    document.querySelectorAll('canvas').forEach(canvas => {
        chartObserver.observe(canvas);
    });
});
    </script>
    <script src="./js/theme.js"></script>
</body>
</html>