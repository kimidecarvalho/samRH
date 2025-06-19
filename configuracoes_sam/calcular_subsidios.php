<?php
session_start();
require_once '../config.php';
require_once 'funcoes_calculo_subsidios.php';

if (!isset($_SESSION['id_adm'])) {
    die(json_encode(['success' => false, 'error' => 'Acesso negado']));
}

// Obter o id_empresa do administrador
$admin_id = $_SESSION['id_adm'];
$sql_admin = "SELECT e.id_empresa FROM empresa e WHERE e.adm_id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $admin_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$admin = $result_admin->fetch_assoc();

if (!$admin) {
    die(json_encode(['success' => false, 'error' => 'Nenhuma empresa encontrada']));
}

$empresa_id = $admin['id_empresa'];

// Função para obter o percentual atual do subsídio
function obterPercentualSubsidio($conn, $empresa_id, $tipo) {
    $sql = "SELECT valor_padrao FROM subsidios_padrao WHERE empresa_id = ? AND nome = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $empresa_id, $tipo);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row) {
        return floatval($row['valor_padrao']);
    }
    
    // Valores padrão caso não encontre no banco
    return $tipo === 'noturno' ? 35.00 : 50.00;
}

// Função para calcular valor do noturno
function calcularValorNoturno($salario_base, $horas_noturnas, $percentual, $jornada_diaria, $funcionario_id) {
    // Buscar jornada diária do funcionário se não for fornecida
    if (!$jornada_diaria) {
        $sql_jornada = "SELECT TIMESTAMPDIFF(HOUR, hora_entrada, hora_saida) as horas_por_dia 
                        FROM horarios_funcionarios 
                        WHERE funcionario_id = ?";
        $stmt_jornada = $conn->prepare($sql_jornada);
        $stmt_jornada->bind_param("i", $funcionario_id);
        $stmt_jornada->execute();
        $result_jornada = $stmt_jornada->get_result();
        $jornada = $result_jornada->fetch_assoc();
        $jornada_diaria = $jornada ? $jornada['horas_por_dia'] : 8; // 8 horas como fallback
    }

    $valor_hora_normal = floatval($salario_base) / ($jornada_diaria * 22); // 22 dias úteis no mês
    $valor_hora_noturna = $valor_hora_normal * ($percentual / 100);
    return round($valor_hora_noturna * $horas_noturnas, 2);
}

// Função para calcular valor das horas extras
function calcularValorHorasExtras($salario_base, $horas_extras, $percentual, $jornada_diaria) {
    $valor_hora_normal = floatval($salario_base) / ($jornada_diaria * 22); // 22 dias úteis no mês
    $valor_hora_extra = $valor_hora_normal * (1 + $percentual / 100);
    return round($valor_hora_extra * $horas_extras, 2);
}

// Receber parâmetros
$funcionario_id = isset($_GET['funcionario_id']) ? intval($_GET['funcionario_id']) : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$jornada_diaria = isset($_GET['jornada_diaria']) ? floatval($_GET['jornada_diaria']) : null;
$percentual = null;
if (isset($_GET['percentual'])) {
    $percentual = floatval($_GET['percentual']);
}

if (!$funcionario_id || !in_array($tipo, ['noturno', 'horas_extras'])) {
    die(json_encode(['success' => false, 'error' => 'Parâmetros inválidos']));
}

// Buscar salário base do funcionário
$sql_salario = "SELECT salario_base FROM funcionario WHERE id_fun = ?";
$stmt_salario = $conn->prepare($sql_salario);
$stmt_salario->bind_param("i", $funcionario_id);
$stmt_salario->execute();
$result_salario = $stmt_salario->get_result();
$funcionario = $result_salario->fetch_assoc();

if (!$funcionario) {
    die(json_encode(['success' => false, 'error' => 'Funcionário não encontrado']));
}

$salario_base = floatval($funcionario['salario_base']);
$valor_calculado = 0;
$horas = 0;

// Buscar registros de ponto do mês
$sql_ponto = "SELECT * FROM registros_ponto WHERE funcionario_id = ? AND MONTH(data) = MONTH(CURRENT_DATE()) AND YEAR(data) = YEAR(CURRENT_DATE())";
$stmt_ponto = $conn->prepare($sql_ponto);
$stmt_ponto->bind_param('i', $funcionario_id);
$stmt_ponto->execute();
$result_ponto = $stmt_ponto->get_result();
$registros_ponto = [];
while ($p = $result_ponto->fetch_assoc()) {
    $registros_ponto[] = $p;
}

if ($tipo === 'noturno') {
    if ($percentual === null) {
    $percentual = obterPercentualSubsidio($conn, $empresa_id, 'noturno');
    }
    
    // Calcular horas noturnas
    $horas = calcularHorasNoturnasFuncionario($registros_ponto);
    
    // Se não tiver jornada diária, buscar do banco
    if (!$jornada_diaria) {
        $sql_jornada = "SELECT TIMESTAMPDIFF(HOUR, hora_entrada, hora_saida) as horas_por_dia 
                        FROM horarios_funcionarios 
                        WHERE funcionario_id = ?";
        $stmt_jornada = $conn->prepare($sql_jornada);
        $stmt_jornada->bind_param("i", $funcionario_id);
        $stmt_jornada->execute();
        $result_jornada = $stmt_jornada->get_result();
        $jornada = $result_jornada->fetch_assoc();
        $jornada_diaria = $jornada ? $jornada['horas_por_dia'] : 8; // 8 horas como fallback
    }
    
    // Calcular valor do subsídio noturno
    $valor_calculado = calcularValorNoturno($salario_base, $horas, $percentual, $jornada_diaria, $funcionario_id);
    
    // Log para debug
    error_log("Cálculo do subsídio noturno - Funcionário ID: $funcionario_id");
    error_log("Salário base: $salario_base");
    error_log("Horas noturnas: $horas");
    error_log("Percentual: $percentual");
    error_log("Jornada diária: $jornada_diaria");
    error_log("Valor calculado: $valor_calculado");
    
    echo json_encode([
        'success' => true,
        'horas' => round($horas, 2),
        'valor_calculado' => round($valor_calculado, 2),
        'percentual' => $percentual,
        'debug' => [
            'salario_base' => $salario_base,
            'jornada_diaria' => $jornada_diaria,
            'horas_noturnas' => $horas
        ]
    ]);
} else if ($tipo === 'horas_extras') {
    if ($percentual === null) {
    $percentual = obterPercentualSubsidio($conn, $empresa_id, 'horas_extras');
    }
    // Buscar jornada diária do funcionário
    $sql_horario = "SELECT hora_entrada, hora_saida FROM horarios_funcionarios WHERE funcionario_id = ?";
    $stmt_horario = $conn->prepare($sql_horario);
    $stmt_horario->bind_param('i', $funcionario_id);
    $stmt_horario->execute();
    $res_horario = $stmt_horario->get_result();
    $horario = $res_horario->fetch_assoc();
    $hora_entrada = $horario ? $horario['hora_entrada'] : '08:00:00';
    $hora_saida = $horario ? $horario['hora_saida'] : '16:00:00';
    $jornada_diaria = (strtotime($hora_saida) - strtotime($hora_entrada)) / 3600;
    if ($jornada_diaria <= 0) $jornada_diaria = 8;
    $horas = calcularHorasExtrasFuncionario($registros_ponto, $jornada_diaria);
    $valor_hora_extra = calcularValorHoraExtra($salario_base, $percentual, $jornada_diaria);
    $valor_calculado = calcularValorTotalHorasExtras($valor_hora_extra, $horas);
    echo json_encode([
        'success' => true,
        'horas' => round($horas, 2),
        'valor_calculado' => round($valor_calculado, 2),
        'percentual' => $percentual
    ]);
}