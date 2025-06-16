<?php
// Desativa a exibição de erros no output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Inicia a sessão
session_start();

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

try {
    // Inclui o arquivo de conexão
    require_once '../config.php';
    
    // Verifica se o usuário está logado
    if (!isset($_SESSION['id_adm'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Não autorizado']);
        exit;
    }

    // Busca o ID da empresa do administrador logado
    $sql_empresa = "SELECT id_empresa FROM empresa WHERE adm_id = ?";
    $stmt_empresa = $conn->prepare($sql_empresa);
    
    if (!$stmt_empresa) {
        throw new Exception('Erro ao preparar consulta da empresa: ' . $conn->error);
    }

    $stmt_empresa->bind_param("i", $_SESSION['id_adm']);
    $stmt_empresa->execute();
    $result_empresa = $stmt_empresa->get_result();
    $empresa = $result_empresa->fetch_assoc();

    if (!$empresa) {
        echo json_encode(['success' => false, 'error' => 'Empresa não encontrada']);
        exit;
    }

    $empresa_id = $empresa['id_empresa'];

    // Busca os funcionários da empresa com o status do subsídio, mostrando nome do cargo e departamento
    $sql = "SELECT 
                f.id_fun as id,
                f.nome,
                f.num_mecanografico,
                COALESCE(c.nome, 'N/D') as cargo,
                COALESCE(d.nome, 'N/D') as departamento,
                f.estado,
                GROUP_CONCAT(
                    CONCAT(
                        sp.nome, ':', 
                        COALESCE(sf.ativo, 0)
                    ) 
                    ORDER BY sp.nome
                ) as subsidios,
                f.salario_base,
                f.data_admissao
            FROM funcionario f
            LEFT JOIN cargos c ON f.cargo = c.id
            LEFT JOIN departamentos d ON f.departamento = d.id
            LEFT JOIN subsidios_padrao sp ON sp.empresa_id = ?
            LEFT JOIN subsidios_funcionarios sf ON sf.funcionario_id = f.id_fun AND sf.subsidio_id = sp.id
            WHERE f.empresa_id = ?
            GROUP BY f.id_fun, f.nome, f.num_mecanografico, c.nome, d.nome, f.estado
            ORDER BY CAST(SUBSTRING(f.num_mecanografico, 5) AS UNSIGNED)";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Erro ao preparar consulta de funcionários: ' . $conn->error);
    }

    $stmt->bind_param("ii", $empresa_id, $empresa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $funcionarios = [];
    while ($row = $result->fetch_assoc()) {
        $subsidios = [];
        if ($row['subsidios']) {
            foreach (explode(',', $row['subsidios']) as $subsidio) {
                list($nome, $ativo) = explode(':', $subsidio);
                $subsidios[$nome] = (bool)$ativo;
            }
        }
        
        $funcionarios[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'num_mecanografico' => $row['num_mecanografico'],
            'cargo' => $row['cargo'],
            'departamento' => $row['departamento'],
            'estado' => $row['estado'],
            'subsidios' => $subsidios,
            'salario_base' => isset($row['salario_base']) ? $row['salario_base'] : null,
            'data_admissao' => isset($row['data_admissao']) ? $row['data_admissao'] : null
        ];
    }

    echo json_encode(['success' => true, 'funcionarios' => $funcionarios]);

} catch (Exception $e) {
    error_log("Erro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Error $e) {
    error_log("Erro PHP: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
} finally {
    // Fecha as conexões
    if (isset($stmt_empresa)) {
        $stmt_empresa->close();
    }
    if (isset($stmt)) {
        $stmt->close();
    }
}
?> 