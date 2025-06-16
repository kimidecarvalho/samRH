<?php
require_once '../config.php';
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['adm_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Receber dados do POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['funcionario_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID do funcionário não fornecido']);
    exit;
}

$funcionario_id = intval($data['funcionario_id']);

// Verificar se o funcionário pertence à empresa do usuário
$sql_check = "SELECT 1 FROM funcionario WHERE id_fun = ? AND empresa_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $funcionario_id, $_SESSION['empresa_id']);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Funcionário não encontrado']);
    exit;
}

// Buscar subsídios padrão do funcionário
$sql_padrao = "SELECT sp.id, sp.nome, sp.tipo, sp.valor_padrao, sp.unidade, sf.ativo
               FROM subsidios_padrao sp
               LEFT JOIN subsidios_funcionarios sf ON sf.subsidio_padrao_id = sp.id 
               AND sf.funcionario_id = ? AND sf.tipo_subsidio IN ('obrigatorio', 'opcional')
               ORDER BY sp.tipo, sp.nome";
$stmt_padrao = $conn->prepare($sql_padrao);
$stmt_padrao->bind_param("i", $funcionario_id);
$stmt_padrao->execute();
$result_padrao = $stmt_padrao->get_result();

$subsidios_padrao = [];
if ($result_padrao) {
    while ($row = $result_padrao->fetch_assoc()) {
        $subsidios_padrao[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'tipo' => $row['tipo'],
            'valor_padrao' => $row['valor_padrao'],
            'unidade' => $row['unidade'],
            'ativo' => $row['ativo'] ?? 0,
            'tipo_subsidio' => $row['tipo']
        ];
    }
}

// Buscar subsídios personalizados do funcionário
$sql_personalizado = "SELECT sp.id, sp.nome, sp.tipo, sp.valor_padrao, sp.unidade, 
                     sp.permitir_personalizacao, sf.ativo
                     FROM subsidios_personalizados sp
                     LEFT JOIN subsidios_funcionarios sf ON sf.subsidio_id = sp.id 
                     AND sf.funcionario_id = ? AND sf.tipo_subsidio = 'personalizado'
                     WHERE sp.empresa_id = ?
                     ORDER BY sp.nome";
$stmt_personalizado = $conn->prepare($sql_personalizado);
$stmt_personalizado->bind_param("ii", $funcionario_id, $_SESSION['empresa_id']);
$stmt_personalizado->execute();
$result_personalizado = $stmt_personalizado->get_result();

$subsidios_personalizados = [];
if ($result_personalizado) {
    while ($row = $result_personalizado->fetch_assoc()) {
        $subsidios_personalizados[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'tipo' => $row['tipo'],
            'valor_padrao' => $row['valor_padrao'],
            'unidade' => $row['unidade'],
            'permitir_personalizacao' => $row['permitir_personalizacao'],
            'ativo' => $row['ativo'] ?? 0,
            'tipo_subsidio' => 'personalizado'
        ];
    }
}

// Combinar os resultados
$subsidios = [
    'padrao' => $subsidios_padrao,
    'personalizados' => $subsidios_personalizados
];

echo json_encode(['success' => true, 'data' => $subsidios]); 