<?php
session_start();
include('../config.php');

if (!isset($_SESSION['id_adm'])) {
    die("Acesso negado");
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
    die("Nenhuma empresa encontrada para este administrador.");
}

$empresa_id = $admin['id_empresa'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'adicionar_cargo':
                $nome = $_POST['nome_cargo'];
                $departamento_id = $_POST['departamento_id'];
                $salario_base = $_POST['salario_base'];
                
                // Verificar se o departamento pertence à empresa
                $sql = "SELECT id FROM departamentos WHERE id = ? AND empresa_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $departamento_id, $empresa_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    $_SESSION['erro'] = "Departamento inválido.";
                    header("Location: rh_config.php");
                    exit;
                }
                
                $sql = "INSERT INTO cargos (nome, departamento_id, salario_base, empresa_id) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sidi", $nome, $departamento_id, $salario_base, $empresa_id);
                
                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Cargo adicionado com sucesso!";
                } else {
                    $_SESSION['erro'] = "Erro ao adicionar cargo: " . $conn->error;
                }
                break;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['acao']) && $_GET['acao'] == 'excluir' && isset($_GET['id'])) {
        $id = $_GET['id'];
        
        // Verificar se existem funcionários vinculados
        $sql = "SELECT COUNT(*) as total FROM funcionario WHERE cargo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total'] > 0) {
            $_SESSION['erro'] = "Não é possível excluir o cargo pois existem funcionários vinculados a ele.";
        } else {
            $sql = "DELETE FROM cargos WHERE id = ? AND empresa_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $empresa_id);
            
            if ($stmt->execute()) {
                $_SESSION['mensagem'] = "Cargo excluído com sucesso!";
            } else {
                $_SESSION['erro'] = "Erro ao excluir cargo: " . $conn->error;
            }
        }
    }
}

header("Location: rh_config.php");
exit; 