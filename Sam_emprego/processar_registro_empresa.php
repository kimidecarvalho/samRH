<?php
session_start();
require_once 'config/database.php';

// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmarSenha'] ?? '';
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $tamanho = trim($_POST['tamanho'] ?? '');
    $website = trim($_POST['website'] ?? '');
    
    // Validação básica dos dados
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome da empresa é obrigatório";
    }
    
    if (empty($email)) {
        $erros[] = "O email é obrigatório";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "O email fornecido não é válido";
    }
    
    if (empty($senha)) {
        $erros[] = "A senha é obrigatória";
    } elseif (strlen($senha) < 6) {
        $erros[] = "A senha deve ter pelo menos 6 caracteres";
    }
    
    if ($senha !== $confirmarSenha) {
        $erros[] = "As senhas não coincidem";
    }
    
    // Verifica se já existe um email cadastrado
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas_recrutamento WHERE email = ?");
        $stmt->execute([$email]);
        $contagem = $stmt->fetchColumn();
        
        if ($contagem > 0) {
            $erros[] = "Este email já está cadastrado";
        }
    } catch (PDOException $e) {
        $erros[] = "Erro ao verificar email: " . $e->getMessage();
    }
    
    // Se não houver erros, insere os dados no banco
    if (empty($erros)) {
        try {
            // Criptografar a senha
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            
            // Inicia a transação
            $pdo->beginTransaction();
            
            // Verificar a estrutura da tabela para determinar as colunas disponíveis
            $stmt = $pdo->prepare("SHOW COLUMNS FROM empresas_recrutamento");
            $stmt->execute();
            $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $temSiteEmpresaId = in_array('site_empresa_id', $colunas);
            $temDataCadastro = in_array('data_cadastro', $colunas);
            
            // Prepara o SQL de acordo com a estrutura da tabela
            if ($temSiteEmpresaId) {
                // Schema antigo
                $stmt = $pdo->prepare("
                    INSERT INTO empresas_recrutamento (nome, descricao, email, senha, site, site_empresa_id, data_cadastro) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$nome, $descricao, $email, $senhaHash, $website]);
            } else {
                // Schema novo
                $dataField = $temDataCadastro ? 'data_cadastro' : 'data_registro';
                
                $stmt = $pdo->prepare("
                    INSERT INTO empresas_recrutamento (
                        nome, email, senha, telefone, endereco, 
                        descricao, setor, tamanho, website, 
                        $dataField, status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Ativo')
                ");
                
                $stmt->execute([
                    $nome, $email, $senhaHash, $telefone, $endereco,
                    $descricao, $setor, $tamanho, $website
                ]);
            }
            
            $empresaId = $pdo->lastInsertId();
            
            $pdo->commit();
            
            // Redireciona para página de sucesso ou login
            $_SESSION['mensagem_sucesso'] = "Empresa cadastrada com sucesso! Agora você pode fazer login.";
            header('Location: login.php');
            exit;
            
        } catch (PDOException $e) {
            // Em caso de erro, desfaz as alterações
            $pdo->rollBack();
            $erros[] = "Erro ao cadastrar empresa: " . $e->getMessage();
        }
    }
    
    // Se houver erros, armazena na sessão para exibir na página de registro
    if (!empty($erros)) {
        $_SESSION['erros_registro'] = $erros;
        $_SESSION['dados_form'] = [
            'nome' => $nome,
            'descricao' => $descricao,
            'email' => $email,
            'telefone' => $telefone,
            'endereco' => $endereco,
            'setor' => $setor,
            'tamanho' => $tamanho,
            'website' => $website
        ];
        header('Location: registro_empresa.php');
        exit;
    }
}

// Se o método não for POST, redireciona para a página de registro
header('Location: registro_empresa.php');
exit; 