<?php
include 'config.php';

// Buscar funcionários terminados há mais de 30 dias
$sql = "SELECT id_fun FROM funcionario 
        WHERE estado = 'Terminado' 
        AND data_termino < DATE_SUB(NOW(), INTERVAL 30 DAY)";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $id_fun = $row['id_fun'];
        
        // Excluir o funcionário
        $delete_sql = "DELETE FROM funcionario WHERE id_fun = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $id_fun);
        $stmt->execute();
    }
}

$conn->close();
?> 