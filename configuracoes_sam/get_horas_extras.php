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
$mes_atual = date('m');
$ano_atual = date('Y');

// Buscar jornada diária do funcionário
$sql_horario = "SELECT hora_entrada, hora_saida FROM horarios_funcionarios WHERE funcionario_id = ?";
$stmt_horario = $conn->prepare($sql_horario);
$stmt_horario->bind_param("i", $funcionario_id);
$stmt_horario->execute();
$res_horario = $stmt_horario->get_result();
$horario = $res_horario->fetch_assoc();
$hora_entrada = $horario ? $horario['hora_entrada'] : '08:00:00';
$hora_saida = $horario ? $horario['hora_saida'] : '16:00:00';
$jornada_diaria = (strtotime($hora_saida) - strtotime($hora_entrada)) / 3600;
if ($jornada_diaria <= 0) $jornada_diaria = 8; // fallback

// Buscar registros de ponto do funcionário no mês atual
$sql = "SELECT 
            TIME_TO_SEC(TIMEDIFF(hora_saida, hora_entrada)) / 3600 as horas_trabalhadas
        FROM registros_ponto 
        WHERE funcionario_id = ? 
        AND MONTH(data) = ? 
        AND YEAR(data) = ?
        AND hora_entrada IS NOT NULL 
        AND hora_saida IS NOT NULL";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $funcionario_id, $mes_atual, $ano_atual);
$stmt->execute();
$result = $stmt->get_result();

$horas_extras = 0;

while ($row = $result->fetch_assoc()) {
    $horas_trabalhadas = $row['horas_trabalhadas'];
    // Só considera como extra o que ultrapassar a jornada diária real
    if ($horas_trabalhadas > $jornada_diaria) {
        $horas_extras += ($horas_trabalhadas - $jornada_diaria);
    }
}

echo json_encode([
    'success' => true,
    'horas_extras' => $horas_extras
]);
?> 