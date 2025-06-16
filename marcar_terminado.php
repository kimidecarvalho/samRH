<?php
include 'protect.php';
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_fun'])) {
    $id_fun = $_POST['id_fun'];
    $data_termino = date('Y-m-d H:i:s');
    
    // Log para debug
    error_log("Tentando marcar funcionário ID: " . $id_fun . " como terminado");
    
    // Verificar se o funcionário existe
    $check_sql = "SELECT id_fun, estado FROM funcionario WHERE id_fun = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id_fun);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $funcionario = $result->fetch_assoc();
        error_log("Funcionário encontrado. Estado atual: " . $funcionario['estado']);
        
        // Atualizar o estado do funcionário para "Terminado" e registrar a data de término
        $sql = "UPDATE funcionario SET estado = 'Terminado', data_termino = ? WHERE id_fun = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $data_termino, $id_fun);
        
        if ($stmt->execute()) {
            error_log("Funcionário marcado como terminado com sucesso");
            // Verificar se a atualização foi realmente feita
            $verify_sql = "SELECT estado, data_termino FROM funcionario WHERE id_fun = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("i", $id_fun);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $verify_data = $verify_result->fetch_assoc();
            
            error_log("Verificação pós-atualização - Estado: " . $verify_data['estado'] . ", Data término: " . $verify_data['data_termino']);
            
            // Redirecionar de volta para a página de funcionários com mensagem de sucesso
            header("Location: funcionarios.php?estado_filtro=terminados&success=1");
            exit();
        } else {
            error_log("Erro ao marcar funcionário como terminado: " . $stmt->error);
            header("Location: funcionarios.php?error=1");
            exit();
        }
    } else {
        error_log("Funcionário não encontrado com ID: " . $id_fun);
        header("Location: funcionarios.php?error=2");
        exit();
    }
} else {
    error_log("Requisição inválida para marcar_terminado.php");
    header("Location: funcionarios.php");
    exit();
}
?> 