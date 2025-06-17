<?php
require_once '../config.php';
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['adm_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Buscar subsídios padrão
$sql_padrao = "SELECT id, nome, tipo, valor_padrao, unidade 
               FROM subsidios_padrao 
               ORDER BY tipo, nome";
$result_padrao = $conn->query($sql_padrao);

$subsidios_padrao = [];
if ($result_padrao) {
    while ($row = $result_padrao->fetch_assoc()) {
        $subsidios_padrao[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'tipo' => $row['tipo'],
            'valor_padrao' => $row['valor_padrao'],
            'unidade' => $row['unidade'],
            'tipo_subsidio' => $row['tipo'] // obrigatorio ou opcional
        ];
    }
}

// Buscar subsídios personalizados da empresa
$sql_personalizado = "SELECT id, nome, tipo, valor_padrao, unidade, permitir_personalizacao 
                     FROM subsidios_personalizados 
                     WHERE empresa_id = ? 
                     ORDER BY nome";
$stmt_personalizado = $conn->prepare($sql_personalizado);
$stmt_personalizado->bind_param("i", $_SESSION['empresa_id']);
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