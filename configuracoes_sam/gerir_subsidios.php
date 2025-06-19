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

// Buscar subsídios obrigatórios
$sql = "SELECT id, nome, tipo, valor_padrao, unidade, ativo FROM subsidios_padrao WHERE empresa_id = ? AND tipo = 'obrigatorio'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();

function padronizar_nome_subsidio($nome) {
    return strtolower(str_replace(['-', ' '], '_', trim($nome)));
}

$subsidios = [];
while ($row = $result->fetch_assoc()) {
    $nome_padronizado = padronizar_nome_subsidio($row['nome']);
    $subsidios[$nome_padronizado] = $row;
}

// Definir valores padrão se não existirem
$subs_padrao = [
    'noturno' => ['valor' => 35.00, 'min' => 20, 'max' => 50],
    'horas_extras' => ['valor' => 50.00, 'min' => 20, 'max' => 100],
    'risco' => ['valor' => 20.00, 'min' => 10, 'max' => 30]
];

foreach ($subs_padrao as $nome => $config) {
    if (!isset($subsidios[$nome])) {
        $sql_insert = "INSERT INTO subsidios_padrao (empresa_id, nome, tipo, valor_padrao, unidade, ativo) 
                      VALUES (?, ?, 'obrigatorio', ?, 'percentual', 1)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("isd", $empresa_id, $nome, $config['valor']);
        $stmt_insert->execute();
        
        $subsidios[$nome] = [
            'id' => $conn->insert_id,
            'nome' => $nome,
            'tipo' => 'obrigatorio',
            'valor_padrao' => $config['valor'],
            'unidade' => 'percentual',
            'ativo' => 1
        ];
    }
}

// Retornar os subsídios
echo json_encode([
    'success' => true,
    'subsidios' => array_values($subsidios)
]);

$stmt->close();
$conn->close();
?>