<?php
session_start();
require_once '../config.php';

// Verificar se é admin
if (!isset($_SESSION['id_adm'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

if (!isset($_GET['funcionario_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do funcionário não fornecido']);
    exit;
}

$funcionario_id = intval($_GET['funcionario_id']);

// Buscar horário do funcionário
$sql = "SELECT hora_entrada, hora_saida 
        FROM horarios_funcionarios 
        WHERE funcionario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $funcionario_id);
$stmt->execute();
$result = $stmt->get_result();
$horario = $result->fetch_assoc();

if ($horario) {
    // Calcular horas por dia
    $entrada = strtotime($horario['hora_entrada']);
    $saida = strtotime($horario['hora_saida']);
    if ($saida < $entrada) {
        $saida = strtotime('+1 day', $saida);
    }
    $horas_por_dia = ($saida - $entrada) / 3600;
    
    echo json_encode([
        'success' => true,
        'horas_por_dia' => round($horas_por_dia, 2)
    ]);
} else {
    // Se não encontrar horário, usar 8 horas como padrão
    echo json_encode([
        'success' => true,
        'horas_por_dia' => 8
    ]);
}