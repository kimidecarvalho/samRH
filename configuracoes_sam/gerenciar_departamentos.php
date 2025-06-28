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
            case 'adicionar_departamento':
                $nome = $_POST['nome_departamento'];
                
                $sql = "INSERT INTO departamentos (nome, empresa_id) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $nome, $empresa_id);
                
                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Departamento adicionado com sucesso!";
                } else {
                    $_SESSION['erro'] = "Erro ao adicionar departamento: " . $conn->error;
                }
                break;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['acao']) && $_GET['acao'] == 'excluir' && isset($_GET['id'])) {
        $id = $_GET['id'];
        
        // Buscar nome do departamento
        $sql_nome = "SELECT nome FROM departamentos WHERE id = ? AND empresa_id = ?";
        $stmt_nome = $conn->prepare($sql_nome);
        $stmt_nome->bind_param("ii", $id, $empresa_id);
        $stmt_nome->execute();
        $result_nome = $stmt_nome->get_result();
        $departamento = $result_nome->fetch_assoc();
        
        if (!$departamento) {
            $_SESSION['erro'] = "Departamento não encontrado.";
            header("Location: rh_config.php");
            exit;
        }
        
        // Verificar se existem cargos vinculados
        $sql = "SELECT COUNT(*) as total FROM cargos WHERE departamento_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total'] > 0) {
            // Buscar detalhes dos cargos vinculados
            $sql_cargos = "SELECT nome FROM cargos WHERE departamento_id = ? ORDER BY nome";
            $stmt_cargos = $conn->prepare($sql_cargos);
            $stmt_cargos->bind_param("i", $id);
            $stmt_cargos->execute();
            $result_cargos = $stmt_cargos->get_result();
            
            $cargos_lista = [];
            while ($cargo = $result_cargos->fetch_assoc()) {
                $cargos_lista[] = $cargo['nome'];
            }
            
            $cargos_texto = implode(', ', $cargos_lista);
            $_SESSION['erro'] = "Não é possível excluir o departamento <strong>'{$departamento['nome']}'</strong> pois existem <strong>{$row['total']}</strong> cargo(s) vinculado(s): <strong>{$cargos_texto}</strong>. Remova primeiro todos os cargos deste departamento.";
        } else {
            $sql = "DELETE FROM departamentos WHERE id = ? AND empresa_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $empresa_id);
            
            if ($stmt->execute()) {
                $_SESSION['mensagem'] = "Departamento <strong>'{$departamento['nome']}'</strong> excluído com sucesso!";
            } else {
                $_SESSION['erro'] = "Erro ao excluir departamento: " . $conn->error;
            }
        }
    }
}

header("Location: rh_config.php");
exit; 