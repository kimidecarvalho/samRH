<?php
session_start();
require_once '../config.php';

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
function calcularValorNoturno($salario_base, $horas_noturnas, $percentual) {
    $valor_hora_normal = floatval($salario_base) / 160; // 160 horas mensais
    $valor_hora_noturna = $valor_hora_normal * ($percentual / 100);
    return round($valor_hora_noturna * $horas_noturnas, 2);
}

// Função para calcular valor das horas extras
function calcularValorHorasExtras($salario_base, $horas_extras, $percentual) {
    $valor_hora_normal = floatval($salario_base) / 160; // 160 horas mensais
    $valor_hora_extra = $valor_hora_normal * (1 + $percentual / 100);
    return round($valor_hora_extra * $horas_extras, 2);
}

// Receber parâmetros
$funcionario_id = isset($_GET['funcionario_id']) ? intval($_GET['funcionario_id']) : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

if (!$funcionario_id || !in_array($tipo, ['noturno', 'horas_extras'])) {
    die(json_encode(['success' => false, 'error' => 'Parâmetros inválidos']));
}

// Buscar salário base do funcionário
$sql_salario = "SELECT salario_base FROM funcionarios WHERE id = ?";
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

if ($tipo === 'noturno') {
    // Buscar horas noturnas
    $sql_horas = "SELECT TIME_TO_SEC(TIMEDIFF(hora_saida, hora_entrada)) / 3600 as horas_trabalhadas 
                  FROM registros_ponto 
                  WHERE funcionario_id = ? 
                  AND MONTH(data) = MONTH(CURRENT_DATE()) 
                  AND YEAR(data) = YEAR(CURRENT_DATE())
                  AND hora_entrada IS NOT NULL 
                  AND hora_saida IS NOT NULL";
    $stmt_horas = $conn->prepare($sql_horas);
    $stmt_horas->bind_param("i", $funcionario_id);
    $stmt_horas->execute();
    $result_horas = $stmt_horas->get_result();
    
    $horas = 0;
    while ($row = $result_horas->fetch_assoc()) {
        $horas += floatval($row['horas_trabalhadas']);
    }
    
    // Buscar o percentual atual do subsídio
    $percentual = obterPercentualSubsidio($conn, $empresa_id, 'noturno');
    
    // Calcular o valor do subsídio
    $valor_calculado = calcularValorNoturno($salario_base, $horas, $percentual);
    
    echo json_encode([
        'success' => true,
        'horas' => round($horas, 2),
        'valor_calculado' => $valor_calculado,
        'percentual' => $percentual
    ]);
} else if ($tipo === 'horas_extras') {
    // Buscar horas extras
    $sql_horas = "SELECT TIME_TO_SEC(TIMEDIFF(hora_saida, hora_entrada)) / 3600 as horas_trabalhadas 
                  FROM registros_ponto 
                  WHERE funcionario_id = ? 
                  AND MONTH(data) = MONTH(CURRENT_DATE()) 
                  AND YEAR(data) = YEAR(CURRENT_DATE())
                  AND hora_entrada IS NOT NULL 
                  AND hora_saida IS NOT NULL";
    $stmt_horas = $conn->prepare($sql_horas);
    $stmt_horas->bind_param("i", $funcionario_id);
    $stmt_horas->execute();
    $result_horas = $stmt_horas->get_result();
    
    while ($row = $result_horas->fetch_assoc()) {
        $horas += floatval($row['horas_trabalhadas']);
    }
    
    $percentual = obterPercentualSubsidio($conn, $empresa_id, 'horas_extras');
    $valor_calculado = calcularValorHorasExtras($salario_base, $horas, $percentual);
} 