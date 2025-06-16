<?php
/**
 * Script para sincronização bidirecional entre o site e o aplicativo
 * Este arquivo deve ser copiado para C:\xampp\htdocs\interface\includes\sync_app.php
 */

/**
 * Sincroniza um funcionário do site para o aplicativo
 * @param int $site_funcionario_id ID do funcionário no banco de dados do site
 * @param int $site_empresa_id ID da empresa no banco de dados do site
 * @return bool True se a sincronização foi bem-sucedida, False caso contrário
 */
function sincronizarFuncionarioSiteParaApp($site_funcionario_id, $site_empresa_id) {
    try {
        // Conectar ao banco do site para obter os dados do funcionário
        $site_conn = new mysqli('localhost', 'root', '', 'sam');
        
        if ($site_conn->connect_error) {
            error_log("Erro ao conectar com o banco de dados do site: " . $site_conn->connect_error);
            return false;
        }
        
        // Configurar para UTF-8
        $site_conn->set_charset("utf8mb4");
        
        // Obter dados do funcionário
        $stmt = $site_conn->prepare("SELECT nome, cargo, departamento FROM funcionario WHERE id = ?");
        if (!$stmt) {
            error_log("Erro ao preparar consulta SQL: " . $site_conn->error);
            $site_conn->close();
            return false;
        }
        
        $stmt->bind_param("i", $site_funcionario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("Funcionário não encontrado no banco de dados do site: ID $site_funcionario_id");
            $stmt->close();
            $site_conn->close();
            return false;
        }
        
        $funcionario = $result->fetch_assoc();
        $nome = $funcionario['nome'];
        $cargo = $funcionario['cargo'];
        $departamento = $funcionario['departamento'];
        
        $stmt->close();
        $site_conn->close();
        
        // Conectar ao banco do app
        $app_conn = new mysqli('localhost', 'root', '', 'app_empresas');
        
        if ($app_conn->connect_error) {
            error_log("Erro ao conectar com o banco de dados do app: " . $app_conn->connect_error);
            return false;
        }
        
        // Configurar para UTF-8
        $app_conn->set_charset("utf8mb4");
        
        // Verificar se a empresa atual existe no app e obter o ID correspondente
        $app_empresa_query = "SELECT id FROM empresas WHERE site_empresa_id = ?";
        $app_empresa_stmt = $app_conn->prepare($app_empresa_query);
        if (!$app_empresa_stmt) {
            error_log("Erro ao preparar consulta SQL para empresas: " . $app_conn->error);
            $app_conn->close();
            return false;
        }
        
        $app_empresa_stmt->bind_param("i", $site_empresa_id);
        $app_empresa_stmt->execute();
        $app_empresa_result = $app_empresa_stmt->get_result();
        
        if ($app_empresa_result->num_rows === 0) {
            error_log("Empresa ID $site_empresa_id não encontrada no banco de dados do aplicativo");
            $app_empresa_stmt->close();
            $app_conn->close();
            return false;
        }
        
        $app_empresa = $app_empresa_result->fetch_assoc();
        $app_empresa_id = $app_empresa['id'];
        $app_empresa_stmt->close();
        
        // Gerar um ID para o funcionário no app (formato usado pelo app)
        $employee_app_id = "EMP" . $site_funcionario_id;
        
        // Verificar se o funcionário já existe no app
        $check_query = "SELECT id FROM employees WHERE id = ? OR (name = ? AND empresa_id = ?)";
        $check_stmt = $app_conn->prepare($check_query);
        if (!$check_stmt) {
            error_log("Erro ao preparar consulta de verificação: " . $app_conn->error);
            $app_conn->close();
            return false;
        }
        
        $check_stmt->bind_param("ssi", $employee_app_id, $nome, $app_empresa_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        $result = false;
        
        if ($check_result->num_rows > 0) {
            // Funcionário já existe, atualize os dados
            $app_update_query = "UPDATE employees SET name = ?, position = ?, department = ? WHERE id = ?";
            $app_update_stmt = $app_conn->prepare($app_update_query);
            if (!$app_update_stmt) {
                error_log("Erro ao preparar consulta de atualização: " . $app_conn->error);
                $check_stmt->close();
                $app_conn->close();
                return false;
            }
            
            $app_update_stmt->bind_param("ssss", $nome, $cargo, $departamento, $employee_app_id);
            
            $result = $app_update_stmt->execute();
            $app_update_stmt->close();
        } else {
            // Inserir funcionário no banco de dados do app
            $app_insert_query = "INSERT INTO employees (id, name, position, department, digital_signature, empresa_id) 
                                VALUES (?, ?, ?, ?, 0, ?)";
            $app_insert_stmt = $app_conn->prepare($app_insert_query);
            if (!$app_insert_stmt) {
                error_log("Erro ao preparar consulta de inserção: " . $app_conn->error);
                $check_stmt->close();
                $app_conn->close();
                return false;
            }
            
            $app_insert_stmt->bind_param("ssssi", $employee_app_id, $nome, $cargo, $departamento, $app_empresa_id);
            
            $result = $app_insert_stmt->execute();
            $app_insert_stmt->close();
        }
        
        $check_stmt->close();
        $app_conn->close();
        
        if (!$result) {
            error_log("Erro na operação com o banco de dados do app");
            return false;
        }
        
        error_log("Funcionário sincronizado com sucesso com o app. ID: " . $employee_app_id);
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao sincronizar com o app: " . $e->getMessage());
        return false;
    }
}

/**
 * Função para sincronizar funcionário do app para o site
 * Esta função é chamada pela API quando um funcionário é cadastrado pelo app
 */
function sincronizarFuncionarioAppParaSite($app_funcionario_id, $app_empresa_id) {
    try {
        // Conectar ao banco do app para obter os dados do funcionário
        $app_conn = new mysqli('localhost', 'root', '', 'app_empresas');
        
        if ($app_conn->connect_error) {
            error_log("Erro ao conectar com o banco de dados do app: " . $app_conn->connect_error);
            return false;
        }
        
        // Configurar para UTF-8
        $app_conn->set_charset("utf8mb4");
        
        // Obter dados do funcionário
        $stmt = $app_conn->prepare("SELECT name, position, department FROM employees WHERE id = ? AND empresa_id = ?");
        if (!$stmt) {
            error_log("Erro ao preparar consulta SQL: " . $app_conn->error);
            $app_conn->close();
            return false;
        }
        
        $stmt->bind_param("si", $app_funcionario_id, $app_empresa_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("Funcionário não encontrado no banco de dados do app: ID $app_funcionario_id");
            $stmt->close();
            $app_conn->close();
            return false;
        }
        
        $funcionario = $result->fetch_assoc();
        $nome = $funcionario['name'];
        $cargo = $funcionario['position'];
        $departamento = $funcionario['department'];
        
        // Obter o site_empresa_id relacionado a esta empresa do app
        $get_site_empresa_id = $app_conn->prepare("SELECT site_empresa_id FROM empresas WHERE id = ?");
        if (!$get_site_empresa_id) {
            error_log("Erro ao preparar consulta SQL para empresas: " . $app_conn->error);
            $stmt->close();
            $app_conn->close();
            return false;
        }
        
        $get_site_empresa_id->bind_param("i", $app_empresa_id);
        $get_site_empresa_id->execute();
        $site_empresa_result = $get_site_empresa_id->get_result();
        
        if ($site_empresa_result->num_rows === 0) {
            error_log("Empresa ID $app_empresa_id não tem equivalente no site");
            $get_site_empresa_id->close();
            $stmt->close();
            $app_conn->close();
            return false;
        }
        
        $row = $site_empresa_result->fetch_assoc();
        $site_empresa_id = $row['site_empresa_id'];
        
        $get_site_empresa_id->close();
        $stmt->close();
        $app_conn->close();
        
        // Conectar ao banco do site
        $site_conn = new mysqli('localhost', 'root', '', 'sam');
        
        if ($site_conn->connect_error) {
            error_log("Erro ao conectar com o banco de dados do site: " . $site_conn->connect_error);
            return false;
        }
        
        // Configurar para UTF-8
        $site_conn->set_charset("utf8mb4");
        
        // Email temporário baseado no nome do funcionário
        $temp_email = strtolower(str_replace(' ', '.', $nome)) . '@exemplo.com';
        
        // Verificar se o funcionário já existe no site
        // Extrair número do ID do app (formato EMP12345)
        $id_numerico = preg_replace('/[^0-9]/', '', $app_funcionario_id);
        
        $check_query = "SELECT id FROM funcionario WHERE id = ? OR (nome = ? AND empresa_id = ?)";
        $check_stmt = $site_conn->prepare($check_query);
        if (!$check_stmt) {
            error_log("Erro ao preparar consulta de verificação: " . $site_conn->error);
            $site_conn->close();
            return false;
        }
        
        $check_stmt->bind_param("isi", $id_numerico, $nome, $site_empresa_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        $result = false;
        
        if ($check_result->num_rows > 0) {
            // Funcionário já existe, atualize os dados
            $row = $check_result->fetch_assoc();
            $site_func_id = $row['id'];
            
            $site_update_query = "UPDATE funcionario SET nome = ?, cargo = ?, departamento = ? WHERE id = ?";
            $site_update_stmt = $site_conn->prepare($site_update_query);
            if (!$site_update_stmt) {
                error_log("Erro ao preparar consulta de atualização: " . $site_conn->error);
                $check_stmt->close();
                $site_conn->close();
                return false;
            }
            
            $site_update_stmt->bind_param("sssi", $nome, $cargo, $departamento, $site_func_id);
            
            $result = $site_update_stmt->execute();
            $site_update_stmt->close();
        } else {
            // Inserir funcionário no banco de dados do site
            $site_insert_query = "INSERT INTO funcionario
                                (nome, cargo, departamento, empresa_id, email, estado)
                                VALUES (?, ?, ?, ?, ?, 'Ativo')";
            $site_insert_stmt = $site_conn->prepare($site_insert_query);
            if (!$site_insert_stmt) {
                error_log("Erro ao preparar consulta de inserção: " . $site_conn->error);
                $check_stmt->close();
                $site_conn->close();
                return false;
            }
            
            $site_insert_stmt->bind_param("sssis", $nome, $cargo, $departamento, $site_empresa_id, $temp_email);
            
            $result = $site_insert_stmt->execute();
            $site_insert_stmt->close();
        }
        
        $check_stmt->close();
        $site_conn->close();
        
        if (!$result) {
            error_log("Erro na operação com o banco de dados do site");
            return false;
        }
        
        error_log("Funcionário sincronizado com sucesso com o site");
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao sincronizar com o site: " . $e->getMessage());
        return false;
    }
}
?> 