<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_fun'])) {
    $id_fun = $_POST['id_fun'];
    
    // Atualizar o estado do funcionário para 'Ativo'
    $sql = "UPDATE funcionario SET estado = 'Ativo', data_termino = NULL WHERE id_fun = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id_fun);
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Funcionário restaurado com sucesso!";
            $_SESSION['tipo_mensagem'] = "sucesso";
        } else {
            $_SESSION['mensagem'] = "Erro ao restaurar funcionário.";
            $_SESSION['tipo_mensagem'] = "erro";
        }
        $stmt->close();
    } else {
        $_SESSION['mensagem'] = "Erro ao preparar a consulta.";
        $_SESSION['tipo_mensagem'] = "erro";
    }
}

header("Location: funcionarios.php");
exit;
?> 