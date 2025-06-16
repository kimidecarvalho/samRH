<?php
include 'config.php';

// Criar a view
$sql_create_view = file_get_contents('criar_view_terminados.sql');
if ($conn->query($sql_create_view)) {
    echo "View criada/atualizada com sucesso!<br>";
} else {
    echo "Erro ao criar/atualizar view: " . $conn->error . "<br>";
}

// Verificar funcionários terminados
$sql = "SELECT * FROM vw_funcionarios_terminados";
$result = $conn->query($sql);

if ($result) {
    echo "<h2>Funcionários Terminados:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Estado</th><th>Data Término</th><th>Dias Terminado</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id_fun'] . "</td>";
        echo "<td>" . $row['nome'] . "</td>";
        echo "<td>" . $row['estado'] . "</td>";
        echo "<td>" . $row['data_termino'] . "</td>";
        echo "<td>" . $row['dias_terminado'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "Erro ao consultar funcionários terminados: " . $conn->error;
}

// Verificar diretamente na tabela funcionario
$sql_direct = "SELECT id_fun, nome, estado, data_termino FROM funcionario WHERE estado = 'Terminado'";
$result_direct = $conn->query($sql_direct);

if ($result_direct) {
    echo "<h2>Verificação Direta na Tabela Funcionário:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Estado</th><th>Data Término</th></tr>";
    
    while ($row = $result_direct->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id_fun'] . "</td>";
        echo "<td>" . $row['nome'] . "</td>";
        echo "<td>" . $row['estado'] . "</td>";
        echo "<td>" . $row['data_termino'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "Erro ao consultar tabela funcionario: " . $conn->error;
}
?> 