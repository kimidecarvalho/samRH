<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id_adm'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

if (!isset($_GET['funcionario_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do funcionário não fornecido']);
    exit;
}

$funcionario_id = intval($_GET['funcionario_id']);
$mes_atual = date('m');
$ano_atual = date('Y');

// Buscar registros de ponto do funcionário no mês atual
$sql = "SELECT data, hora_entrada, hora_saida FROM registros_ponto WHERE funcionario_id = ? AND MONTH(data) = ? AND YEAR(data) = ? AND hora_entrada IS NOT NULL AND hora_saida IS NOT NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $funcionario_id, $mes_atual, $ano_atual);
$stmt->execute();
$result = $stmt->get_result();

function calcularHorasNoturnas($entrada, $saida) {
    $inicio_noturno = strtotime('20:00:00');
    $fim_noturno = strtotime('06:00:00');
    $entrada = strtotime($entrada);
    $saida = strtotime($saida);
    
    // Se a saída for no dia seguinte
    if ($saida < $entrada) {
        $saida = strtotime('+1 day', $saida);
    }
    
    $horas_noturnas = 0;
    
    // Se trabalhou todo o período noturno
    if ($entrada <= $inicio_noturno && $saida >= strtotime('+1 day', $fim_noturno)) {
        $horas_noturnas = 10; // 20h às 6h = 10 horas
    } else {
        // Calcula as horas parciais
        $inicio_periodo = max($entrada, $inicio_noturno);
        $fim_periodo = min($saida, strtotime('+1 day', $fim_noturno));
        
        if ($inicio_periodo < $fim_periodo) {
            $horas_noturnas = ($fim_periodo - $inicio_periodo) / 3600;
        }
        
        // Se entrada for antes das 6h
        if ($entrada < $fim_noturno && $saida > $entrada) {
            $horas_noturnas += (min($saida, $fim_noturno) - $entrada) / 3600;
        }
    }
    
    // Garantir que não retorne valor negativo
    return max(0, round($horas_noturnas, 2));
}

$total_horas_noturnas = 0;
while ($row = $result->fetch_assoc()) {
    $total_horas_noturnas += calcularHorasNoturnas($row['hora_entrada'], $row['hora_saida']);
}

echo json_encode([
    'success' => true,
    'horas_noturnas' => round($total_horas_noturnas, 2)
]); 