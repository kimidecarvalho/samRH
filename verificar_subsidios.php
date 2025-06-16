<?php
session_start();
include('../config.php');

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

// Buscar subsídios obrigatórios relevantes
$subs_requeridos = ['noturno', 'horas_extras', 'risco'];
$subs_placeholders = implode(",", array_fill(0, count($subs_requeridos), '?'));
$types = str_repeat('s', count($subs_requeridos));

$sql = "SELECT id, nome, tipo, valor_padrao, unidade, ativo FROM subsidios_padrao WHERE empresa_id = ? AND nome IN ($subs_placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i' . $types, $empresa_id, ...$subs_requeridos);
$stmt->execute();
$result = $stmt->get_result();

$subsidios = [];
while ($row = $result->fetch_assoc()) {
    $subsidios[$row['nome']] = $row;
}

// Se algum não existir, criar com valor padrão
foreach ($subs_requeridos as $nome) {
    if (!isset($subsidios[$nome])) {
        $valor_padrao = 35.00;
        if ($nome === 'horas_extras') $valor_padrao = 50.00;
        if ($nome === 'risco') $valor_padrao = 20.00;
        
        $sql_insert = "INSERT INTO subsidios_padrao (empresa_id, nome, tipo, valor_padrao, unidade, ativo) VALUES (?, ?, 'obrigatorio', ?, 'percentual', 1)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("isd", $empresa_id, $nome, $valor_padrao);
        $stmt_insert->execute();
        
        $subsidios[$nome] = [
            'id' => $conn->insert_id,
            'nome' => $nome,
            'tipo' => 'obrigatorio',
            'valor_padrao' => $valor_padrao,
            'unidade' => 'percentual',
            'ativo' => 1
        ];
    }
}

// Retornar todos os subsídios obrigatórios relevantes
$subs_final = array_values($subsidios);
echo json_encode([
    'success' => true,
    'subsidios' => $subs_final
]);

$stmt->close();
$conn->close();