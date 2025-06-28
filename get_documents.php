<?php
session_start();
include 'config.php'; // Conexão com o banco de dados

if (isset($_GET['folder']) && isset($_GET['employeeId'])) {
    $folder = $_GET['folder'];
    $employeeId = $_GET['employeeId'];

    $sql = "SELECT d.titulo, d.tipo, d.data, d.descricao, d.anexo, SUBSTRING(f.num_mecanografico, 5) as numero_id 
            FROM documentos d 
            INNER JOIN funcionario f ON d.num_funcionario = f.id_fun 
            WHERE d.folder = ? AND d.num_funcionario = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $folder, $employeeId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $documents = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $documents[] = $row;
    }

    echo json_encode($documents);
} else {
    echo json_encode([]);
}
?>