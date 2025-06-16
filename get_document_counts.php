<?php
session_start();
include 'config.php'; // Conexão com o banco de dados

// Verifica se o usuário está logado e tem uma empresa associada
if (!isset($_SESSION['id_empresa'])) {
    echo json_encode([]);
    exit;
}

$empresa_id = $_SESSION['id_empresa']; // Define $empresa_id a partir da sessão

// Verifica se o ID do funcionário foi passado
if (isset($_GET['employeeId'])) {
    $employeeId = $_GET['employeeId'];
    
    // Consulta para contar documentos em cada pasta
    $sql = "SELECT folder, COUNT(*) as count 
            FROM documentos 
            WHERE num_funcionario = ? 
            GROUP BY folder";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Inicializa o array com contagem zero para todas as pastas
    $counts = [
        'documentacao' => 0,
        'frequencia' => 0,
        'solicitacoes' => 0,
        'outros' => 0
    ];
    
    // Atualiza as contagens com os valores do banco
    while ($row = mysqli_fetch_assoc($result)) {
        $counts[$row['folder']] = $row['count'];
    }
    
    // Retorna as contagens em formato JSON
    echo json_encode($counts);
} else {
    echo json_encode([]);
}
?>