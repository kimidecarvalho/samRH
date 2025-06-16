<?php
session_start();
require_once 'config/database.php';

// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmarSenha'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $endereco = trim($_POST['endereco'] ?? '');
    $formacao = trim($_POST['formacao'] ?? '');
    $experiencia = trim($_POST['experiencia'] ?? '');
    $habilidades = trim($_POST['habilidades'] ?? '');
    
    // Validação básica dos dados
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome completo é obrigatório";
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
    
    if (empty($data_nascimento)) {
        $erros[] = "A data de nascimento é obrigatória";
    }
    
    // Verifica se já existe um email cadastrado
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidatos WHERE email = ?");
        $stmt->execute([$email]);
        $contagem = $stmt->fetchColumn();
        
        if ($contagem > 0) {
            $erros[] = "Este email já está cadastrado";
        }
    } catch (PDOException $e) {
        $erros[] = "Erro ao verificar email: " . $e->getMessage();
    }
    
    // Processa o upload do currículo, se fornecido
    $curriculo_path = null;
    $curriculo_path_nome = null;
    
    if (isset($_FILES['curriculo_path']) && $_FILES['curriculo_path']['error'] == 0) {
        $arquivo = $_FILES['curriculo_path'];
        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $extensoes_permitidas = ['pdf', 'doc', 'docx'];
        
        if (!in_array(strtolower($extensao), $extensoes_permitidas)) {
            $erros[] = "Formato de arquivo não permitido. Use PDF, DOC ou DOCX.";
        } elseif ($arquivo['size'] > 5242880) { // 5MB
            $erros[] = "O arquivo é muito grande. Tamanho máximo: 5MB.";
        } else {
            // Cria o diretório para currículos se não existir
            $uploadDir = 'uploads/curriculos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Gera um nome único para o arquivo
            $novoNome = uniqid() . '_' . $arquivo['name'];
            $caminho = $uploadDir . $novoNome;
            
            // Move o arquivo para o diretório de destino
            if (!move_uploaded_file($arquivo['tmp_name'], $caminho)) {
                $erros[] = "Falha ao fazer upload do currículo.";
            } else {
                $curriculo_path = $caminho;
                $curriculo_path_nome = $arquivo['name'];
            }
        }
    }
    
    // Se não houver erros, insere os dados no banco
    if (empty($erros)) {
        try {
            // Criptografa a senha
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            
            // Inicia a transação
            $pdo->beginTransaction();
            
            // Verifica se a coluna data_registro existe na tabela candidatos
            $stmt = $pdo->prepare("SHOW COLUMNS FROM candidatos LIKE 'data_registro'");
            $stmt->execute();
            $data_registro_exists = $stmt->rowCount() > 0;
            
            // Verifica se as colunas formacao, experiencia, habilidades existem
            $stmt = $pdo->prepare("SHOW COLUMNS FROM candidatos LIKE 'formacao'");
            $stmt->execute();
            $formacao_exists = $stmt->rowCount() > 0;
            
            if ($formacao_exists && $data_registro_exists) {
                // Usa o novo schema
                $stmt = $pdo->prepare("
                    INSERT INTO candidatos (nome, email, senha, telefone, data_nascimento, endereco, formacao, 
                                          experiencia, habilidades, curriculo_path, data_registro)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $nome, $email, $senhaHash, $telefone, $data_nascimento, $endereco, 
                    $formacao, $experiencia, $habilidades, $curriculo_path
                ]);
            } else {
                // Usa o schema antigo
                $stmt = $pdo->prepare("
                    INSERT INTO candidatos (nome, email, senha, telefone, data_nascimento, cv_anexo, data_cadastro)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([$nome, $email, $senhaHash, $telefone, $data_nascimento, $curriculo_path]);
            }
            
            $candidatoId = $pdo->lastInsertId();
            
            $pdo->commit();
            
            // Redireciona para página de sucesso ou login
            $_SESSION['mensagem_sucesso'] = "Cadastro realizado com sucesso! Agora você pode fazer login.";
            header('Location: login.php');
            exit;
            
        } catch (PDOException $e) {
            // Em caso de erro, desfaz as alterações
            $pdo->rollBack();
            
            // Remove o arquivo de currículo se foi feito upload
            if ($curriculo_path && file_exists($curriculo_path)) {
                unlink($curriculo_path);
            }
            
            $erros[] = "Erro ao cadastrar: " . $e->getMessage();
        }
    }
    
    // Se houver erros, armazena na sessão para exibir na página de registro
    if (!empty($erros)) {
        $_SESSION['erros_registro_candidato'] = $erros;
        $_SESSION['dados_form_candidato'] = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'data_nascimento' => $data_nascimento,
            'endereco' => $endereco,
            'formacao' => $formacao,
            'experiencia' => $experiencia,
            'habilidades' => $habilidades,
            'curriculo_path_nome' => $curriculo_path_nome
        ];
        header('Location: registro_candidato.php');
        exit;
    }
}

// Se o método não for POST, redireciona para a página de registro
header('Location: registro_candidato.php');
exit; 