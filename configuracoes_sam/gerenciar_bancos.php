<?php
session_start();
include('../conexao.php');

// Verifica se o usuário está logado
if (!isset($_SESSION['id_adm'])) {
    header('Location: ../login.php');
    exit();
}

// Obter o id_empresa do administrador
$admin_id = $_SESSION['id_adm'];
$sql_admin = "SELECT e.id_empresa FROM empresa e WHERE e.adm_id = ?";
$stmt_admin = $mysqli->prepare($sql_admin);
$stmt_admin->bind_param("i", $admin_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$admin = $result_admin->fetch_assoc();

if (!$admin) {
    die("Nenhuma empresa encontrada para este administrador.");
}

$empresa_id = $admin['id_empresa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'excluir' && isset($_POST['id'])) {
        $id = $_POST['id'];
        
        // Verifica se o banco existe e pertence ao usuário
        $sql_check = "SELECT id FROM bancos_ativos WHERE id = ? AND empresa_id = ?";
        $stmt_check = $mysqli->prepare($sql_check);
        $stmt_check->bind_param("ii", $id, $empresa_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows > 0) {
            // Verifica se existem funcionários usando este banco
            $sql_check_funcionarios = "SELECT COUNT(*) as total FROM funcionario WHERE banco = (SELECT banco_codigo FROM bancos_ativos WHERE id = ?) AND empresa_id = ?";
            $stmt_check_funcionarios = $mysqli->prepare($sql_check_funcionarios);
            $stmt_check_funcionarios->bind_param("ii", $id, $empresa_id);
            $stmt_check_funcionarios->execute();
            $result_funcionarios = $stmt_check_funcionarios->get_result();
            $row_funcionarios = $result_funcionarios->fetch_assoc();
            
            if ($row_funcionarios['total'] > 0) {
                $_SESSION['erro'] = "Não é possível excluir o banco pois existem funcionários vinculados a ele.";
            } else {
                // Exclui o banco
                $sql_delete = "DELETE FROM bancos_ativos WHERE id = ? AND empresa_id = ?";
                $stmt_delete = $mysqli->prepare($sql_delete);
                $stmt_delete->bind_param("ii", $id, $empresa_id);
                
                if ($stmt_delete->execute()) {
                    $_SESSION['mensagem'] = "Banco excluído com sucesso!";
                } else {
                    $_SESSION['erro'] = "Erro ao excluir banco: " . $mysqli->error;
                }
            }
        } else {
            $_SESSION['erro'] = "Banco não encontrado ou você não tem permissão para excluí-lo.";
        }
    }
}

header("Location: rh_config.php");
exit();
?> 