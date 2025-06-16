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

// Verificar se a tabela registros_ponto existe, senão criar
$sql_check_table = "SHOW TABLES LIKE 'registros_ponto'";
$result_check_table = mysqli_query($conn, $sql_check_table);

if (mysqli_num_rows($result_check_table) == 0) {
    // Tabela não existe, vamos criar
    $sql_create_table = "CREATE TABLE registros_ponto (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        funcionario_id INT NOT NULL,
        data DATE NOT NULL,
        entrada DATETIME NULL,
        saida DATETIME NULL,
        observacao TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (funcionario_id) REFERENCES funcionario(id_fun),
        INDEX (empresa_id),
        INDEX (data),
        UNIQUE KEY (funcionario_id, data)
    )";
    
    if (!mysqli_query($conn, $sql_create_table)) {
        // Se houver um erro na criação, continuamos com o código mesmo assim
        // Apenas registramos o erro
        error_log("Erro ao criar tabela registros_ponto: " . mysqli_error($conn));
    }
    
    // Inserir alguns registros de exemplo para teste (opcional)
    // Vamos buscar alguns funcionários para criar registros de exemplo
    $sql_funcionarios = "SELECT id_fun FROM funcionario WHERE empresa_id = $empresa_id LIMIT 5";
    $result_funcionarios = mysqli_query($conn, $sql_funcionarios);
    
    if ($result_funcionarios && mysqli_num_rows($result_funcionarios) > 0) {
        // Datas para os registros de exemplo (últimos 7 dias)
        $dias = 7;
        while ($row = mysqli_fetch_assoc($result_funcionarios)) {
            $funcionario_id = $row['id_fun'];
            
            for ($i = 0; $i < $dias; $i++) {
                $data = date('Y-m-d', strtotime("-$i days"));
                
                // Só criar registros para dias de semana (1-5)
                $dia_semana = date('N', strtotime($data));
                if ($dia_semana <= 5) {
                    // Gerar hora de entrada aleatória entre 7:45 e 9:00
                    $hora_entrada = rand(7, 8);
                    $minuto_entrada = rand(0, 59);
                    if ($hora_entrada == 7) {
                        $minuto_entrada = rand(45, 59);
                    } elseif ($hora_entrada == 9) {
                        $minuto_entrada = 0;
                    }
                    
                    // Formatar entrada e saída
                    $entrada = $data . ' ' . sprintf('%02d:%02d:00', $hora_entrada, $minuto_entrada);
                    
                    // Gerar hora de saída aleatória entre 17:00 e 18:30
                    $hora_saida = rand(17, 18);
                    $minuto_saida = rand(0, 59);
                    if ($hora_saida == 18) {
                        $minuto_saida = rand(0, 30);
                    }
                    
                    $saida = $data . ' ' . sprintf('%02d:%02d:00', $hora_saida, $minuto_saida);
                    
                    // 10% de chance do funcionário não ter registro no dia (ausente)
                    $presente = (rand(1, 10) > 1);
                    
                    if ($presente) {
                        $sql_insert = "INSERT IGNORE INTO registros_ponto (empresa_id, funcionario_id, data, entrada, saida) 
                                       VALUES ($empresa_id, $funcionario_id, '$data', '$entrada', '$saida')";
                        mysqli_query($conn, $sql_insert);
                    }
                }
            }
        }
    }
}

// Processamento do formulário de registro de ponto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_ponto'])) {
    $funcionario_id = mysqli_real_escape_string($conn, $_POST['funcionario_id']);
    $data = mysqli_real_escape_string($conn, $_POST['data']);
    $tipo_registro = mysqli_real_escape_string($conn, $_POST['tipo_registro']);
    $hora = mysqli_real_escape_string($conn, $_POST['hora']);
    $observacao = mysqli_real_escape_string($conn, $_POST['observacao']);
    
    // Buscar o horário personalizado do funcionário
    $sql_horario = "SELECT hora_entrada FROM horarios_funcionarios WHERE funcionario_id = ?";
    $stmt_horario = $conn->prepare($sql_horario);
    $stmt_horario->bind_param("i", $funcionario_id);
    $stmt_horario->execute();
    $result_horario = $stmt_horario->get_result();
    $horario = $result_horario->fetch_assoc();

    if ($tipo_registro === 'entrada') {
        if ($horario) {
            $hora_limite = $horario['hora_entrada'];
            // Adiciona 10 minutos de tolerância ao horário limite
            $hora_limite_com_tolerancia = date('H:i:s', strtotime($hora_limite . ' +10 minutes'));
            
            if (strtotime($hora) > strtotime($hora_limite_com_tolerancia)) {
        $status = 'atrasado';
            } else {
                $status = 'presente';
            }
        } else {
            // Se não houver horário cadastrado, considerar presente
            $status = 'presente';
        }
    }
    
    // Verificar se já existe um registro para este funcionário nesta data
    $sql_check = "SELECT * FROM registros_ponto WHERE funcionario_id = '$funcionario_id' AND data = '$data'";
    $result_check = mysqli_query($conn, $sql_check);
    
    // Buscar o id da empresa do funcionário caso não esteja na sessão
    $empresa_id_func = $empresa_id;
    if (empty($empresa_id_func) || $empresa_id_func == 0) {
        $sql_empresa_func = "SELECT empresa_id FROM funcionario WHERE id_fun = '$funcionario_id' LIMIT 1";
        $result_empresa_func = mysqli_query($conn, $sql_empresa_func);
        if ($result_empresa_func && mysqli_num_rows($result_empresa_func) > 0) {
            $empresa_id_func = mysqli_fetch_assoc($result_empresa_func)['empresa_id'];
        } else {
            $empresa_id_func = 0;
        }
    }
    
    if (mysqli_num_rows($result_check) > 0) {
        // Já existe um registro, atualizar
        $registro = mysqli_fetch_assoc($result_check);
        
        if ($tipo_registro === 'entrada') {
            $sql_update = "UPDATE registros_ponto SET 
                          hora_entrada = '$hora', 
                          tipo_registro = '$tipo_registro', 
                          status = '$status', 
                          observacao = '$observacao' 
                          WHERE id = '{$registro['id']}'";
        } else {
            $sql_update = "UPDATE registros_ponto SET 
                          hora_saida = '$hora', 
                          tipo_registro = '$tipo_registro', 
                          observacao = '$observacao' 
                          WHERE id = '{$registro['id']}'";
        }
        
        if (mysqli_query($conn, $sql_update)) {
            echo "<script>alert('Registro de ponto atualizado com sucesso!');</script>";
        } else {
            echo "<script>alert('Erro ao atualizar registro: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        // Não existe registro, criar novo
        if ($tipo_registro === 'entrada') {
            $sql_insert = "INSERT INTO registros_ponto (
                          empresa_id, funcionario_id, data, hora_entrada, 
                          tipo_registro, status, observacao) 
                          VALUES (
                          '$empresa_id_func', '$funcionario_id', '$data', '$hora', 
                          '$tipo_registro', '$status', '$observacao')";
        } else {
            $sql_insert = "INSERT INTO registros_ponto (
                          empresa_id, funcionario_id, data, hora_saida, 
                          tipo_registro, status, observacao) 
                          VALUES (
                          '$empresa_id_func', '$funcionario_id', '$data', '$hora', 
                          '$tipo_registro', 'presente', '$observacao')";
        }
        
        if (mysqli_query($conn, $sql_insert)) {
            echo "<script>alert('Registro de ponto criado com sucesso!');</script>";
        } else {
            echo "<script>alert('Erro ao criar registro: " . mysqli_error($conn) . "');</script>";
        }
    }
    
    // Redirecionar para a mesma página para atualizar os dados
    header("Location: registro_ponto.php");
    exit;
}

// Consulta para obter o total de funcionários
$sql_total_funcionarios = "SELECT COUNT(*) AS total FROM funcionario WHERE empresa_id = $empresa_id AND estado = 'Ativo'";
$result_total_funcionarios = mysqli_query($conn, $sql_total_funcionarios);
$total_funcionarios = mysqli_fetch_assoc($result_total_funcionarios)['total'];

// Obter a data atual no formato YYYY-MM-DD
$data_atual = date('Y-m-d');

// Consulta para obter o total de funcionários presentes hoje na tabela registros_ponto
$sql_presentes = "SELECT COUNT(DISTINCT funcionario_id) AS total FROM registros_ponto 
                 WHERE data = '$data_atual' AND status = 'presente'";
$result_presentes = mysqli_query($conn, $sql_presentes);
$total_presentes = 0;
if ($result_presentes && mysqli_num_rows($result_presentes) > 0) {
    $total_presentes = mysqli_fetch_assoc($result_presentes)['total'];
}

// Consulta para obter o total de ausentes hoje
$sql_ausentes = "SELECT COUNT(DISTINCT funcionario_id) AS total FROM registros_ponto 
                 WHERE data = '$data_atual' AND status = 'ausente'";
$result_ausentes = mysqli_query($conn, $sql_ausentes);
$total_ausentes = 0;
if ($result_ausentes && mysqli_num_rows($result_ausentes) > 0) {
    $total_ausentes = mysqli_fetch_assoc($result_ausentes)['total'];
}

// Se não tiver funcionários registrados como ausentes, calcular a diferença entre total e presentes
if ($total_ausentes == 0) {
    // Consultar todos os funcionários ativos que não têm registro hoje
    $sql_ausentes_calc = "SELECT COUNT(*) AS total FROM funcionario f 
                         WHERE f.empresa_id = $empresa_id 
                         AND f.estado = 'Ativo'
                         AND NOT EXISTS (
                             SELECT 1 FROM registros_ponto r 
                             WHERE r.funcionario_id = f.id_fun 
                             AND r.data = '$data_atual'
                         )";
    $result_ausentes_calc = mysqli_query($conn, $sql_ausentes_calc);
    if ($result_ausentes_calc && mysqli_num_rows($result_ausentes_calc) > 0) {
        $total_ausentes = mysqli_fetch_assoc($result_ausentes_calc)['total'];
    }
}

// Consultar os registros da tabela ausencias para complementar
$sql_ausencias_hoje = "SELECT COUNT(DISTINCT funcionario_id) AS total FROM ausencias 
                      WHERE empresa_id = $empresa_id 
                      AND '$data_atual' BETWEEN data_inicio AND data_fim";
$result_ausencias_hoje = mysqli_query($conn, $sql_ausencias_hoje);
if ($result_ausencias_hoje && mysqli_num_rows($result_ausencias_hoje) > 0) {
    $total_ausencias = mysqli_fetch_assoc($result_ausencias_hoje)['total'];
    // Somar ao total de ausentes, mas evitar duplicação
    $total_ausentes += $total_ausencias;
    // Garantir que não ultrapasse o total de funcionários
    if ($total_ausentes > $total_funcionarios) {
        $total_ausentes = $total_funcionarios;
    }
}

// Cálculo de funcionários atrasados (chegaram após as 8:30)
$sql_atrasados = "SELECT COUNT(DISTINCT funcionario_id) AS total FROM registros_ponto 
                 WHERE data = '$data_atual' 
                 AND status = 'atrasado'";
$result_atrasados = mysqli_query($conn, $sql_atrasados);
$total_atrasados = 0;
if ($result_atrasados && mysqli_num_rows($result_atrasados) > 0) {
    $total_atrasados = mysqli_fetch_assoc($result_atrasados)['total'];
}

// Consulta para obter os registros de ponto mais recentes
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'hoje';
$data_inicio = '';
$data_fim = date('Y-m-d');

switch ($filtro_periodo) {
    case 'hoje':
        $data_inicio = date('Y-m-d');
        break;
    case 'semana':
        $data_inicio = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'mes':
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'trimestre':
        $data_inicio = date('Y-m-d', strtotime('-90 days'));
        break;
    case 'todos':
        $data_inicio = '1970-01-01'; // Data inicial para mostrar todos os registros
        break;
    case 'personalizado':
        $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-7 days'));
        $data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
        break;
    default:
        $data_inicio = date('Y-m-d');
}

$sql_registros_recentes = "SELECT r.*, f.nome, f.num_mecanografico, f.foto, d.nome as departamento_nome 
                          FROM registros_ponto r
                          JOIN funcionario f ON r.funcionario_id = f.id_fun
                          LEFT JOIN departamentos d ON f.departamento = d.id
                          WHERE f.empresa_id = $empresa_id
                          AND r.data BETWEEN '$data_inicio' AND '$data_fim'
                          ORDER BY r.data DESC, r.hora_entrada DESC";
$result_registros_recentes = mysqli_query($conn, $sql_registros_recentes);

// Obter dados dos últimos 7 dias para o gráfico de presença
$labels_dias = [];
$dados_presentes = [];
$dados_ausentes = [];
$dados_atrasados = [];

for ($i = 6; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime("-$i days"));
    $dia_semana = date('D', strtotime($data));
    
    // Traduzir o dia da semana para português
    $dias_semana = [
        'Mon' => 'Seg',
        'Tue' => 'Ter',
        'Wed' => 'Qua',
        'Thu' => 'Qui',
        'Fri' => 'Sex',
        'Sat' => 'Sáb',
        'Sun' => 'Dom'
    ];
    
    $labels_dias[] = $dias_semana[$dia_semana];
    
    // Consultar presentes neste dia
    $sql_dia_presentes = "SELECT COUNT(DISTINCT funcionario_id) AS total FROM registros_ponto 
                         WHERE data = '$data' AND status = 'presente'";
    $result_dia_presentes = mysqli_query($conn, $sql_dia_presentes);
    $presentes_dia = 0;
    if ($result_dia_presentes && mysqli_num_rows($result_dia_presentes) > 0) {
        $presentes_dia = mysqli_fetch_assoc($result_dia_presentes)['total'];
    }
    $dados_presentes[] = $presentes_dia;
    
    // Consultar ausentes neste dia
    $sql_dia_ausentes = "SELECT COUNT(DISTINCT funcionario_id) AS total FROM registros_ponto 
                         WHERE data = '$data' AND status = 'ausente'";
    $result_dia_ausentes = mysqli_query($conn, $sql_dia_ausentes);
    $ausentes_dia = 0;
    if ($result_dia_ausentes && mysqli_num_rows($result_dia_ausentes) > 0) {
        $ausentes_dia = mysqli_fetch_assoc($result_dia_ausentes)['total'];
    }
    
    // Se não tiver funcionários registrados como ausentes, calcular a diferença
    if ($ausentes_dia == 0) {
        // Calcular funcionários que não têm registro neste dia
        $sql_dia_ausentes_calc = "SELECT COUNT(*) AS total FROM funcionario f 
                                 WHERE f.empresa_id = $empresa_id 
                                 AND f.estado = 'Ativo'
                                 AND NOT EXISTS (
                                     SELECT 1 FROM registros_ponto r 
                                     WHERE r.funcionario_id = f.id_fun 
                                     AND r.data = '$data'
                                 )";
        $result_dia_ausentes_calc = mysqli_query($conn, $sql_dia_ausentes_calc);
        if ($result_dia_ausentes_calc && mysqli_num_rows($result_dia_ausentes_calc) > 0) {
            $ausentes_dia = mysqli_fetch_assoc($result_dia_ausentes_calc)['total'];
        }
    }
    
    // Consultar ausências na tabela ausencias para complementar
    $sql_dia_ausencias = "SELECT COUNT(DISTINCT funcionario_id) AS total FROM ausencias 
                         WHERE empresa_id = $empresa_id 
                         AND '$data' BETWEEN data_inicio AND data_fim";
    $result_dia_ausencias = mysqli_query($conn, $sql_dia_ausencias);
    if ($result_dia_ausencias && mysqli_num_rows($result_dia_ausencias) > 0) {
        $ausencias_dia = mysqli_fetch_assoc($result_dia_ausencias)['total'];
        // Somar ao total de ausentes
        $ausentes_dia += $ausencias_dia;
        // Garantir que não ultrapasse o total de funcionários
        if ($ausentes_dia > $total_funcionarios) {
            $ausentes_dia = $total_funcionarios;
        }
    }
    
    $dados_ausentes[] = $ausentes_dia;
    
    // Consultar atrasados neste dia
    $sql_dia_atrasados = "SELECT COUNT(DISTINCT funcionario_id) AS total FROM registros_ponto 
                         WHERE data = '$data' AND status = 'atrasado'";
    $result_dia_atrasados = mysqli_query($conn, $sql_dia_atrasados);
    $atrasados_dia = 0;
    if ($result_dia_atrasados && mysqli_num_rows($result_dia_atrasados) > 0) {
        $atrasados_dia = mysqli_fetch_assoc($result_dia_atrasados)['total'];
    }
    $dados_atrasados[] = $atrasados_dia;
}

// Função para calcular horas trabalhadas
function calcularHorasTrabalhadas($entrada, $saida) {
    if (empty($entrada) || empty($saida)) {
        return '-';
    }
    
    // Verificar o formato da data/hora
    if (strpos($entrada, ' ') !== false) {
        // Formato datetime completo (YYYY-MM-DD HH:MM:SS)
        $entrada_time = strtotime($entrada);
        $saida_time = strtotime($saida);
    } else {
        // Formato apenas hora (HH:MM:SS)
        $entrada_time = strtotime("1970-01-01 " . $entrada);
        $saida_time = strtotime("1970-01-01 " . $saida);
    }
    
    // Se a saída for menor que a entrada, provavelmente é do dia seguinte
    if ($saida_time < $entrada_time) {
        $saida_time += 86400; // Adiciona 24 horas
    }
    
    $diferenca = $saida_time - $entrada_time;
    $horas = floor($diferenca / 3600);
    $minutos = floor(($diferenca % 3600) / 60);
    
    return sprintf('%02d:%02d', $horas, $minutos);
}

// Obter resumo mensal de presença
$inicio_mes = date('Y-m-01');
$fim_mes = date('Y-m-t');
$dias_uteis_mes = 0;

// Calcular dias úteis no mês (excluindo fins de semana)
$inicio = new DateTime($inicio_mes);
$fim = new DateTime($fim_mes);
$fim->modify('+1 day');
$intervalo = new DateInterval('P1D');
$periodo = new DatePeriod($inicio, $intervalo, $fim);

foreach ($periodo as $data) {
    $dia_semana = $data->format('N');
    if ($dia_semana < 6) { // 1 (segunda) até 5 (sexta)
        $dias_uteis_mes++;
    }
}

// Estatísticas do mês atual
$sql_presencas_mes = "SELECT 
    COUNT(DISTINCT CASE WHEN status IN ('presente', 'atrasado') THEN data END) as dias_presentes,
    COUNT(DISTINCT CASE WHEN status = 'presente' THEN data END) as dias_presentes_no_hora,
    COUNT(DISTINCT CASE WHEN status = 'atrasado' THEN data END) as dias_atrasados
    FROM registros_ponto
                      WHERE funcionario_id IN (SELECT id_fun FROM funcionario WHERE empresa_id = $empresa_id AND estado = 'Ativo')
    AND data BETWEEN '$inicio_mes' AND '$fim_mes'";
$result_presencas_mes = mysqli_query($conn, $sql_presencas_mes);
$presencas_mes = mysqli_fetch_assoc($result_presencas_mes);

$dias_presentes = $presencas_mes['dias_presentes'] ?? 0;
$dias_presentes_no_hora = $presencas_mes['dias_presentes_no_hora'] ?? 0;
$dias_atrasados = $presencas_mes['dias_atrasados'] ?? 0;

// Calcular média de horas trabalhadas no mês
$sql_media_horas = "SELECT AVG(
                    TIME_TO_SEC(TIMEDIFF(hora_saida, hora_entrada))/3600
                  ) as media_horas 
                  FROM registros_ponto
                  WHERE funcionario_id IN (SELECT id_fun FROM funcionario WHERE empresa_id = $empresa_id)
                  AND data BETWEEN '$inicio_mes' AND '$fim_mes'
                  AND hora_entrada IS NOT NULL 
                  AND hora_saida IS NOT NULL";
$result_media_horas = mysqli_query($conn, $sql_media_horas);
$media_horas = 0;
if ($result_media_horas && mysqli_num_rows($result_media_horas) > 0) {
    $media_horas = mysqli_fetch_assoc($result_media_horas)['media_horas'];
    $media_horas = round($media_horas, 1); // Arredondar para 1 casa decimal
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="all.css/registro3.css">
    <link rel="stylesheet" href="all.css/timer.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAM - Registro de Ponto</title>
    <style>
        /* Estilos específicos para a página de registro de ponto */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #64c2a7;
        }
        
        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #777;
            font-size: 0.9rem;
        }
        
        /* Estilos para o resumo mensal */
        .month-summary {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .month-summary h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
        }
        
        .summary-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 15px 10px;
            border-radius: 10px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }
        
        .summary-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .summary-item i {
            font-size: 1.8rem;
            color: #64c2a7;
            margin-bottom: 10px;
        }
        
        .summary-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .summary-label {
            color: #777;
            font-size: 0.8rem;
        }
        
        /* Responsividade para o resumo mensal */
        @media (max-width: 992px) {
            .summary-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            position: relative;
            min-height: 300px;
        }
        
        .chart-card h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.2rem;
        }
        
        .attendance-table {
            width: 100%;
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .attendance-table h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background-color: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 500;
            color: #555;
            border-bottom: 1px solid #ddd;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            text-align: center;
        }
        
        .status-presente {
            background-color: rgba(100, 194, 167, 0.2);
            color: #2e7d32;
        }
        
        .status-ausente {
            background-color: rgba(239, 83, 80, 0.2);
            color: #c62828;
        }
        
        .status-atrasado {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ff8f00;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 25px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: #64c2a7;
            color: white;
        }
        
        .btn-secondary {
            background-color: #f5f5f5;
            color: #555;
        }
        
        /* Estilos para a imagem do funcionário na tabela */
        .employee-avatar {
            object-fit: cover;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 2px solid white;
        }
        
        /* Estilos para os modais */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #888;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        .modal h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }

        /* Dark mode */
        body.dark {
            background-color: #1A1A1A;
            color: #e0e0e0;
        }
        
        body.dark .stat-card,
        body.dark .chart-card,
        body.dark .attendance-table,
        body.dark .month-summary {
            background-color: #1E1E1E;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }
        
        body.dark .stat-card .label,
        body.dark .summary-label {
            color: #b0b0b0;
        }
        
        body.dark .month-summary h3,
        body.dark .chart-card h3,
        body.dark .attendance-table h3 {
            color: #e0e0e0;
        }
        
        body.dark .summary-item {
            background-color: #2C2C2C;
        }
        
        body.dark table th {
            background-color: #2C2C2C;
            color: #b0b0b0;
            border-bottom: 1px solid #444;
        }
        
        body.dark table td {
            border-bottom: 1px solid #333;
        }
        
        body.dark .btn-secondary {
            background-color: #2C2C2C;
            color: #e0e0e0;
        }
        
        body.dark .modal-content {
            background-color: #1E1E1E;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        body.dark .modal h2 {
            color: #e0e0e0;
        }
        
        body.dark .form-group label {
            color: #b0b0b0;
        }
        
        body.dark .form-group input, 
        body.dark .form-group select, 
        body.dark .form-group textarea {
            background-color: #2C2C2C;
            border-color: #444;
            color: #e0e0e0;
        }
        
        body.dark .close {
            color: #999;
        }
        
        body.dark .close:hover {
            color: #e0e0e0;
        }
        
        /* Estilos para o formulário de filtro */
        .filter-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        .filter-form .form-group {
            margin: 0;
            position: relative;
        }
        
        .filter-form select {
            padding: 10px 35px 10px 15px;
            border-radius: 25px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 14px;
            color: #333;
            min-width: 150px;
            transition: all 0.3s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 15px;
        }
        
        .filter-form input[type="date"] {
            padding: 10px 15px;
            border-radius: 25px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 14px;
            color: #333;
            min-width: 150px;
            transition: all 0.3s ease;
        }
        
        .filter-form label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-size: 14px;
            font-weight: 500;
        }
        
        .filter-form .btn {
            margin-top: 24px;
            padding: 10px 25px;
        }
        
        .filter-form .date-range {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        
        /* Dark mode para o formulário de filtro */
        body.dark .filter-form {
            background-color: #2C2C2C;
        }
        
        body.dark .filter-form select,
        body.dark .filter-form input[type="date"] {
            background-color: #1E1E1E;
            border-color: #444;
            color: #e0e0e0;
        }
        
        body.dark .filter-form label {
            color: #b0b0b0;
        }
        
        body.dark .filter-form select:focus,
        body.dark .filter-form input[type="date"]:focus {
            border-color: #64c2a7;
            box-shadow: 0 0 0 2px rgba(100, 194, 167, 0.3);
        }
    </style>
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
            <li>Processamento Salarial</li>
            <a href="docs.php"><li>Documentos</li></a>
            <a href="registro_ponto.php"><li class="active">Registro de Ponto</li></a>
            <a href="ausencias.php"><li>Ausências</li></a>
            <a href="recrutamento.php"><li>Recrutamento</li></a>
        </ul>
    </div>

    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Registro de Ponto</h1>
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

        <div class="action-buttons">
            <button class="btn btn-primary" onclick="document.getElementById('modal-exportar').style.display='block'">
                <i class="fas fa-file-export"></i> Exportar Relatório
            </button>
            <button class="btn btn-secondary" onclick="document.getElementById('modal-registrar-ponto').style.display='block'">
                <i class="fas fa-clock"></i> Registrar Ponto
            </button>
        </div>

        <!-- Modal para Registro de Ponto -->
        <div id="modal-registrar-ponto" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('modal-registrar-ponto').style.display='none'">&times;</span>
                <h2>Registrar Ponto</h2>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="funcionario_id">Funcionário:</label>
                        <select name="funcionario_id" id="funcionario_id" required>
                            <option value="">Selecione um funcionário</option>
                            <?php
                            $sql_funcionarios = "SELECT id_fun, nome, num_mecanografico FROM funcionario 
                                               WHERE empresa_id = $empresa_id AND estado = 'Ativo'
                                               ORDER BY num_mecanografico ASC";
                            $result_funcionarios = mysqli_query($conn, $sql_funcionarios);
                            while ($funcionario = mysqli_fetch_assoc($result_funcionarios)) {
                                echo "<option value='{$funcionario['id_fun']}'>{$funcionario['nome']} (#{$funcionario['num_mecanografico']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="data">Data:</label>
                        <input type="date" name="data" id="data" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="tipo_registro">Tipo de Registro:</label>
                        <select name="tipo_registro" id="tipo_registro" required>
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="hora">Hora:</label>
                        <input type="time" name="hora" id="hora" value="<?php echo date('H:i'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="observacao">Observação:</label>
                        <textarea name="observacao" id="observacao" rows="3"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="registrar_ponto" class="btn btn-primary">Registrar</button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-registrar-ponto').style.display='none'">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal para Exportar Relatório -->
        <div id="modal-exportar" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('modal-exportar').style.display='none'">&times;</span>
                <h2>Exportar Relatório</h2>
                <form id="formExportarRelatorio" onsubmit="return exportarRelatorioPDF(event)">
                    <div class="form-group">
                        <label for="tipo_relatorio">Tipo de Relatório:</label>
                        <select name="tipo_relatorio" id="tipo_relatorio" onchange="toggleFuncionarioSelect()">
                            <option value="empresa">Relatório da Empresa</option>
                            <option value="funcionario">Relatório de Funcionário</option>
                        </select>
                    </div>
                    <div class="form-group" id="funcionario-select-group" style="display: none;">
                        <label for="funcionario_relatorio">Funcionário:</label>
                        <select name="funcionario_relatorio" id="funcionario_relatorio">
                            <option value="">Selecione um funcionário</option>
                            <?php
                            $sql_funcionarios = "SELECT id_fun, nome, num_mecanografico FROM funcionario 
                                               WHERE empresa_id = $empresa_id AND estado = 'Ativo'
                                               ORDER BY CAST(num_mecanografico AS UNSIGNED) ASC";
                            $result_funcionarios = mysqli_query($conn, $sql_funcionarios);
                            while ($funcionario = mysqli_fetch_assoc($result_funcionarios)) {
                                echo "<option value='{$funcionario['id_fun']}'>{$funcionario['nome']} (#{$funcionario['num_mecanografico']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="periodo_relatorio">Período:</label>
                        <select name="periodo_relatorio" id="periodo_relatorio">
                            <option value="semana">Última semana</option>
                            <option value="mes" selected>Último mês</option>
                            <option value="trimestre">Último trimestre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="formato">Formato:</label>
                        <select name="formato" id="formato">
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-info" onclick="exportarRelatorioPDF(event, 'visualizar')">
                            <i class="fas fa-eye"></i> Visualizar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-exportar').style.display='none'">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cards de estatísticas -->
        <div class="dashboard-cards">
            <div class="stat-card">
                <i class="fas fa-users icon"></i>
                <div class="number"><?php echo $total_funcionarios; ?></div>
                <div class="label">Total de Funcionários Ativos</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle icon"></i>
                <div class="number"><?php echo $total_presentes; ?></div>
                <div class="label">Presentes Hoje</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-times-circle icon"></i>
                <div class="number"><?php echo $total_ausentes; ?></div>
                <div class="label">Ausentes Hoje</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-exclamation-circle icon"></i>
                <div class="number"><?php echo $total_atrasados; ?></div>
                <div class="label">Atrasados Hoje</div>
            </div>
        </div>

        <!-- Resumo mensal -->
        <div class="month-summary">
            <h3>Resumo do Mês de <?php echo date('F Y'); ?></h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <i class="fas fa-calendar-check"></i>
                    <div class="summary-value"><?php echo $dias_uteis_mes; ?></div>
                    <div class="summary-label">Dias úteis</div>
                </div>
                <div class="summary-item">
                    <i class="fas fa-calendar-day"></i>
                    <div class="summary-value"><?php echo $dias_presentes; ?></div>
                    <div class="summary-label">Dias com registro</div>
                </div>
                <div class="summary-item">
                    <i class="fas fa-hourglass-half"></i>
                    <div class="summary-value"><?php echo $media_horas; ?></div>
                    <div class="summary-label">Média de horas por dia</div>
                </div>
                <div class="summary-item">
                    <i class="fas fa-clock"></i>
                    <div class="summary-value"><?php echo $dias_presentes_no_hora; ?></div>
                    <div class="summary-label">Registros no horário</div>
                </div>
                <div class="summary-item">
                    <i class="fas fa-running"></i>
                    <div class="summary-value"><?php echo $dias_atrasados; ?></div>
                    <div class="summary-label">Dias com atrasos</div>
                </div>
                <div class="summary-item">
                    <i class="fas fa-percentage"></i>
                    <div class="summary-value"><?php echo ($dias_uteis_mes > 0) ? round(($dias_presentes / $dias_uteis_mes) * 100) : 0; ?>%</div>
                    <div class="summary-label">Taxa de presença</div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="chart-container">
            <div class="chart-card">
                <h3>Presença Diária - Últimos 7 dias</h3>
                <canvas id="presencaChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Distribuição de Status - Hoje</h3>
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Tabela de registros -->
        <div class="attendance-table">
            <h3>Registros de Ponto Recentes</h3>
            
            <!-- Formulário de filtro -->
            <form method="GET" class="filter-form">
                <div style="display: flex; gap: 20px; align-items: flex-end;">
                    <div class="form-group">
                        <label for="periodo">Período:</label>
                        <select name="periodo" id="periodo" onchange="toggleDateInputs(this.value)">
                            <option value="hoje" <?php echo $filtro_periodo == 'hoje' ? 'selected' : ''; ?>>Hoje</option>
                            <option value="semana" <?php echo $filtro_periodo == 'semana' ? 'selected' : ''; ?>>Última semana</option>
                            <option value="mes" <?php echo $filtro_periodo == 'mes' ? 'selected' : ''; ?>>Último mês</option>
                            <option value="trimestre" <?php echo $filtro_periodo == 'trimestre' ? 'selected' : ''; ?>>Último trimestre</option>
                            <option value="todos" <?php echo $filtro_periodo == 'todos' ? 'selected' : ''; ?>>Todos os registros</option>
                            <option value="personalizado" <?php echo $filtro_periodo == 'personalizado' ? 'selected' : ''; ?>>Personalizado</option>
                        </select>
                    </div>
                    
                    <div id="date-inputs" class="date-range" style="display: <?php echo $filtro_periodo == 'personalizado' ? 'flex' : 'none'; ?>;">
                        <div class="form-group">
                            <label for="data_inicio">Data Inicial:</label>
                            <input type="date" name="data_inicio" id="data_inicio" value="<?php echo $data_inicio; ?>">
                        </div>
                        <div class="form-group">
                            <label for="data_fim">Data Final:</label>
                            <input type="date" name="data_fim" id="data_fim" value="<?php echo $data_fim; ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>Matrícula</th>
                        <th>Departamento</th>
                        <th>Data</th>
                        <th>Entrada</th>
                        <th>Saída</th>
                        <th>Horas Trabalhadas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_registros_recentes && mysqli_num_rows($result_registros_recentes) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_registros_recentes)): 
                            $data_formatada = date('d/m/Y', strtotime($row['data']));
                            $entrada = !empty($row['hora_entrada']) ? date('H:i', strtotime($row['hora_entrada'])) : '-';
                            $saida = !empty($row['hora_saida']) ? date('H:i', strtotime($row['hora_saida'])) : '-';
                            $horas_trabalhadas = calcularHorasTrabalhadas($row['hora_entrada'], $row['hora_saida']);
                            $status = ucfirst($row['status']);
                            $status_class = 'status-' . $row['status'];
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['foto'])): ?>
                                    <img src="<?php echo $row['foto']; ?>" alt="<?php echo $row['nome']; ?>" class="employee-avatar" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 8px; vertical-align: middle;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle" style="font-size: 30px; color: #aaa; margin-right: 8px; vertical-align: middle;"></i>
                                <?php endif; ?>
                                <?php echo $row['nome']; ?>
                            </td>
                            <td><?php echo $row['num_mecanografico'] ?? 'N/D'; ?></td>
                            <td><?php echo $row['departamento_nome'] ?? 'N/D'; ?></td>
                            <td><?php echo $data_formatada; ?></td>
                            <td><?php echo $entrada; ?></td>
                            <td><?php echo $saida; ?></td>
                            <td><?php echo $horas_trabalhadas; ?></td>
                            <td><span class="status <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">Nenhum registro encontrado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Função para atualizar o relógio
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Traduzir nomes de meses para português
        document.addEventListener('DOMContentLoaded', function() {
            // Traduzir nome do mês no título do resumo mensal
            const monthNames = {
                'January': 'Janeiro',
                'February': 'Fevereiro',
                'March': 'Março',
                'April': 'Abril',
                'May': 'Maio',
                'June': 'Junho',
                'July': 'Julho',
                'August': 'Agosto',
                'September': 'Setembro',
                'October': 'Outubro',
                'November': 'Novembro',
                'December': 'Dezembro'
            };
            
            const monthSummaryTitle = document.querySelector('.month-summary h3');
            if (monthSummaryTitle) {
                let titleText = monthSummaryTitle.textContent;
                Object.keys(monthNames).forEach(englishMonth => {
                    titleText = titleText.replace(englishMonth, monthNames[englishMonth]);
                });
                monthSummaryTitle.textContent = titleText;
            }
            
            // Configuração dos gráficos
            // Gráfico de presença diária (últimos 7 dias)
            const ctxPresenca = document.getElementById('presencaChart').getContext('2d');
            const presencaChart = new Chart(ctxPresenca, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labels_dias); ?>,
                    datasets: [{
                        label: 'Presentes',
                        data: <?php echo json_encode($dados_presentes); ?>,
                        borderColor: '#64c2a7',
                        backgroundColor: 'rgba(100, 194, 167, 0.1)',
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Ausentes',
                        data: <?php echo json_encode($dados_ausentes); ?>,
                        borderColor: '#ef5350',
                        backgroundColor: 'rgba(239, 83, 80, 0.1)',
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Atrasados',
                        data: <?php echo json_encode($dados_atrasados); ?>,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Gráfico de distribuição de status (hoje)
            const ctxStatus = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: ['Presentes', 'Ausentes', 'Atrasados'],
                    datasets: [{
                        data: [<?php echo $total_presentes; ?>, <?php echo $total_ausentes; ?>, <?php echo $total_atrasados; ?>],
                        backgroundColor: [
                            'rgba(100, 194, 167, 0.8)',
                            'rgba(239, 83, 80, 0.8)',
                            'rgba(255, 193, 7, 0.8)'
                        ],
                        borderColor: [
                            'rgba(100, 194, 167, 1)',
                            'rgba(239, 83, 80, 1)',
                            'rgba(255, 193, 7, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        });

        function toggleFuncionarioSelect() {
            const tipoRelatorio = document.getElementById('tipo_relatorio').value;
            const funcionarioGroup = document.getElementById('funcionario-select-group');
            const funcionarioSelect = document.getElementById('funcionario_relatorio');
            
            if (tipoRelatorio === 'funcionario') {
                funcionarioGroup.style.display = 'block';
                funcionarioSelect.required = true;
            } else {
                funcionarioGroup.style.display = 'none';
                funcionarioSelect.required = false;
            }
        }

        function exportarRelatorioPDF(event, acao) {
            event.preventDefault();
            var formato = document.getElementById('formato').value;
            var periodo = document.getElementById('periodo_relatorio').value;
            var tipoRelatorio = document.getElementById('tipo_relatorio').value;
            var funcionarioId = document.getElementById('funcionario_relatorio').value;

            if (formato === 'pdf') {
                let url = 'configuracoes_sam/generate_pdf_ponto.php?periodo=' + encodeURIComponent(periodo);
                if (tipoRelatorio === 'funcionario') {
                    if (!funcionarioId) {
                        alert('Por favor, selecione um funcionário.');
                        return false;
                    }
                    url += '&tipo=funcionario&funcionario_id=' + encodeURIComponent(funcionarioId);
                } else {
                    url += '&tipo=empresa';
                }
                if (acao === 'visualizar') {
                    url += '&acao=visualizar';
                }
                window.open(url, '_blank');
                document.getElementById('modal-exportar').style.display='none';
                return false;
            }
            alert('Exportação apenas em PDF está disponível no momento.');
            return false;
        }

        function toggleDateInputs(periodo) {
            const dateInputs = document.getElementById('date-inputs');
            const dataInicio = document.getElementById('data_inicio');
            const dataFim = document.getElementById('data_fim');
            
            if (periodo === 'personalizado') {
                dateInputs.style.display = 'flex';
                dataInicio.required = true;
                dataFim.required = true;
            } else {
                dateInputs.style.display = 'none';
                dataInicio.required = false;
                dataFim.required = false;
                
                // Atualizar as datas baseado no período selecionado
                const hoje = new Date();
                let dataInicioValue = new Date();
                
                switch (periodo) {
                    case 'hoje':
                        dataInicioValue = hoje;
                        break;
                    case 'semana':
                        dataInicioValue.setDate(hoje.getDate() - 7);
                        break;
                    case 'mes':
                        dataInicioValue.setDate(hoje.getDate() - 30);
                        break;
                    case 'trimestre':
                        dataInicioValue.setDate(hoje.getDate() - 90);
                        break;
                    case 'todos':
                        dataInicioValue = new Date('1970-01-01');
                        break;
                }
                
                dataInicio.value = dataInicioValue.toISOString().split('T')[0];
                dataFim.value = hoje.toISOString().split('T')[0];
            }
        }

        // Desabilita o aviso de reenvio de formulário
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <script src="./js/theme.js"></script>
</body>
</html> 