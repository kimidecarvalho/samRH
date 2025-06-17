<?php
require_once '../config.php';

class SubsidiosController {
    private $conn;
    private $debug = true;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->log("Controlador de Subsídios inicializado");
    }

    private function log($message, $type = 'DEBUG') {
        if ($this->debug) {
            error_log("[$type] $message");
        }
    }

    private function sendResponse($success, $message, $data = null) {
        $response = [
            'success' => $success,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!$success && $this->debug) {
            $response['debug_info'] = $data;
        }

        $this->log("Enviando resposta: " . json_encode($response));
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    private function validateInput($data) {
        $this->log("Validando dados de entrada: " . json_encode($data));
        
        $required = ['funcionario_id', 'subsidio_id', 'tipo_subsidio', 'ativo'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $this->log("Campo obrigatório ausente: $field", 'ERROR');
                return false;
            }
        }

        if (!in_array($data['tipo_subsidio'], ['obrigatorio', 'opcional', 'personalizado'])) {
            $this->log("Tipo de subsídio inválido: {$data['tipo_subsidio']}", 'ERROR');
            return false;
        }

        $this->log("Dados de entrada válidos");
        return true;
    }

    private function checkFuncionario($funcionario_id, $admin_id) {
        $this->log("Verificando funcionário - ID: $funcionario_id, Admin ID: $admin_id");
        
        $sql = "SELECT f.id_fun, f.nome, e.id_empresa 
                FROM funcionario f 
                INNER JOIN empresa e ON f.empresa_id = e.id_empresa 
                WHERE f.id_fun = ? AND e.adm_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->log("Erro ao preparar verificação de funcionário: " . $this->conn->error, 'ERROR');
            return false;
        }

        $stmt->bind_param("ii", $funcionario_id, $admin_id);
        if (!$stmt->execute()) {
            $this->log("Erro ao executar verificação de funcionário: " . $stmt->error, 'ERROR');
            return false;
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $this->log("Funcionário não encontrado - ID: $funcionario_id", 'ERROR');
            return false;
        }

        $funcionario = $result->fetch_assoc();
        $this->log("Funcionário encontrado: " . json_encode($funcionario));
        return $funcionario;
    }

    private function checkSubsidio($subsidio_id, $tipo_subsidio, $empresa_id = null) {
        $this->log("Verificando subsídio - ID: $subsidio_id, Tipo: $tipo_subsidio, Empresa ID: $empresa_id");
        
        if ($tipo_subsidio === 'personalizado') {
            $sql = "SELECT id, nome FROM subsidios_personalizados WHERE id = ? AND empresa_id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->log("Erro ao preparar verificação de subsídio personalizado: " . $this->conn->error, 'ERROR');
                return false;
            }
            $stmt->bind_param("ii", $subsidio_id, $empresa_id);
        } else {
            $sql = "SELECT id, nome FROM subsidios_padrao WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->log("Erro ao preparar verificação de subsídio padrão: " . $this->conn->error, 'ERROR');
                return false;
            }
            $stmt->bind_param("i", $subsidio_id);
        }

        if (!$stmt->execute()) {
            $this->log("Erro ao executar verificação de subsídio: " . $stmt->error, 'ERROR');
            return false;
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $this->log("Subsídio não encontrado - ID: $subsidio_id, Tipo: $tipo_subsidio", 'ERROR');
            return false;
        }

        $subsidio = $result->fetch_assoc();
        $this->log("Subsídio encontrado: " . json_encode($subsidio));
        return $subsidio;
    }

    private function checkSubsidioExistente($funcionario_id, $tipo_subsidio, $subsidio_id, $subsidio_padrao_id = null) {
        $this->log("Verificando subsídio existente - Funcionário ID: $funcionario_id, Tipo: $tipo_subsidio, Subsídio ID: $subsidio_id, Subsídio Padrão ID: $subsidio_padrao_id");
        
        $sql = "SELECT id, ativo FROM subsidios_funcionarios 
                WHERE funcionario_id = ? AND tipo_subsidio = ?";
        $params = [$funcionario_id, $tipo_subsidio];
        $types = "is";

        if ($tipo_subsidio === 'personalizado') {
            $sql .= " AND subsidio_id = ?";
            $params[] = $subsidio_id;
            $types .= "i";
        } else {
            $sql .= " AND subsidio_id = ? AND subsidio_padrao_id = ?";
            $params[] = 0; // Valor padrão para subsidio_id
            $params[] = $subsidio_padrao_id;
            $types .= "ii";
        }

        $this->log("SQL de verificação: $sql");
        $this->log("Parâmetros: " . json_encode($params));
        $this->log("Tipos: $types");

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->log("Erro ao preparar verificação de subsídio existente: " . $this->conn->error, 'ERROR');
            return false;
        }

        $bind_params = [$types];
        foreach ($params as &$param) {
            $bind_params[] = &$param;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_params);

        if (!$stmt->execute()) {
            $this->log("Erro ao executar verificação de subsídio existente: " . $stmt->error, 'ERROR');
            return false;
        }

        $result = $stmt->get_result();
        $existente = $result->num_rows > 0 ? $result->fetch_assoc() : false;
        $this->log("Resultado da verificação: " . ($existente ? json_encode($existente) : "Não encontrado"));
        return $existente;
    }

    public function toggleSubsidio($data) {
        try {
            $this->log("Iniciando toggleSubsidio com dados: " . json_encode($data));
            
            // Validação inicial
            if (!$this->validateInput($data)) {
                return $this->sendResponse(false, 'Dados inválidos', $data);
            }

            $funcionario_id = intval($data['funcionario_id']);
            $subsidio_id = intval($data['subsidio_id']);
            $tipo_subsidio = $data['tipo_subsidio'];
            $ativo = $data['ativo'] ? 1 : 0;

            $this->log("Dados processados - Funcionário ID: $funcionario_id, Subsídio ID: $subsidio_id, Tipo: $tipo_subsidio, Ativo: $ativo");

            // Verificar funcionário
            $funcionario = $this->checkFuncionario($funcionario_id, $_SESSION['id_adm']);
            if (!$funcionario) {
                return $this->sendResponse(false, 'Funcionário não encontrado');
            }

            // Verificar subsídio
            $subsidio = $this->checkSubsidio($subsidio_id, $tipo_subsidio, $funcionario['id_empresa']);
            if (!$subsidio) {
                return $this->sendResponse(false, 'Subsídio não encontrado');
            }

            // Preparar IDs para inserção/atualização
            $subsidio_id_para_db = $tipo_subsidio === 'personalizado' ? $subsidio_id : 0;
            $subsidio_padrao_id_para_db = $tipo_subsidio === 'personalizado' ? null : $subsidio_id;

            $this->log("IDs preparados - Subsídio ID para DB: $subsidio_id_para_db, Subsídio Padrão ID para DB: " . ($subsidio_padrao_id_para_db ?? 'NULL'));

            // Verificar se já existe
            $existente = $this->checkSubsidioExistente(
                $funcionario_id, 
                $tipo_subsidio, 
                $subsidio_id_para_db, 
                $subsidio_padrao_id_para_db
            );

            if ($existente) {
                $this->log("Atualizando registro existente - ID: " . $existente['id']);
                
                // Atualizar existente
                $sql = "UPDATE subsidios_funcionarios 
                        SET ativo = ?, data_atualizacao = CURRENT_TIMESTAMP 
                        WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar atualização: " . $this->conn->error);
                }

                $stmt->bind_param("ii", $ativo, $existente['id']);
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao executar atualização: " . $stmt->error);
                }
            } else {
                $this->log("Inserindo novo registro");
                
                // Inserir novo
                $sql = "INSERT INTO subsidios_funcionarios 
                        (funcionario_id, subsidio_id, subsidio_padrao_id, tipo_subsidio, ativo) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar inserção: " . $this->conn->error);
                }

                $this->log("Parâmetros para inserção - Funcionário ID: $funcionario_id, Subsídio ID: $subsidio_id_para_db, Subsídio Padrão ID: " . ($subsidio_padrao_id_para_db ?? 'NULL') . ", Tipo: $tipo_subsidio, Ativo: $ativo");

                $stmt->bind_param("iiisi", 
                    $funcionario_id, 
                    $subsidio_id_para_db, 
                    $subsidio_padrao_id_para_db, 
                    $tipo_subsidio, 
                    $ativo
                );
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao executar inserção: " . $stmt->error);
                }
            }

            $this->log("Operação concluída com sucesso");
            return $this->sendResponse(true, 'Status do subsídio atualizado com sucesso');

        } catch (Exception $e) {
            $this->log("Erro ao processar subsídio: " . $e->getMessage(), 'ERROR');
            $this->log("Stack trace: " . $e->getTraceAsString(), 'ERROR');
            return $this->sendResponse(false, 'Erro ao atualizar o status do subsídio', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 