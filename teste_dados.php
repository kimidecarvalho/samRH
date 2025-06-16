<?php
session_start();
include('config.php');

if (!isset($_SESSION['id_adm'])) {
    die("Acesso negado");
}

$admin_id = $_SESSION['id_adm'];

// Buscar o id_empresa do administrador
$sql_empresa = "SELECT e.id_empresa FROM empresa e WHERE e.adm_id = ?";
$stmt_empresa = $conn->prepare($sql_empresa);
$stmt_empresa->bind_param("i", $admin_id);
$stmt_empresa->execute();
$result_empresa = $stmt_empresa->get_result();
$empresa = $result_empresa->fetch_assoc();

if (!$empresa) {
    die("Nenhuma empresa encontrada para este administrador");
}

$empresa_id = $empresa['id_empresa'];

// Verificar departamentos
echo "<h2>Departamentos da Empresa ID: " . $empresa_id . "</h2>";
$sql_departamentos = "SELECT * FROM departamentos WHERE empresa_id = ?";
$stmt_departamentos = $conn->prepare($sql_departamentos);
$stmt_departamentos->bind_param("i", $empresa_id);
$stmt_departamentos->execute();
$result_departamentos = $stmt_departamentos->get_result();

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Nome</th><th>Empresa ID</th></tr>";
while ($departamento = $result_departamentos->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $departamento['id'] . "</td>";
    echo "<td>" . $departamento['nome'] . "</td>";
    echo "<td>" . $departamento['empresa_id'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Verificar cargos
echo "<h2>Cargos</h2>";
$sql_cargos = "SELECT c.*, d.nome as departamento_nome 
               FROM cargos c 
               JOIN departamentos d ON c.departamento_id = d.id 
               WHERE c.empresa_id = ?";
$stmt_cargos = $conn->prepare($sql_cargos);
$stmt_cargos->bind_param("i", $empresa_id);
$stmt_cargos->execute();
$result_cargos = $stmt_cargos->get_result();

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Nome</th><th>Departamento</th><th>Salário Base</th><th>Empresa ID</th></tr>";
while ($cargo = $result_cargos->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $cargo['id'] . "</td>";
    echo "<td>" . $cargo['nome'] . "</td>";
    echo "<td>" . $cargo['departamento_nome'] . "</td>";
    echo "<td>" . $cargo['salario_base'] . "</td>";
    echo "<td>" . $cargo['empresa_id'] . "</td>";
    echo "</tr>";
}
echo "</table>"; 