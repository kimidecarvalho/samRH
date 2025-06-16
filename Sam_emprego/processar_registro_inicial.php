<?php
session_start();
require_once 'conexao.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recupera os dados do formulário
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmarSenha'] ?? '';
    
    // Array para armazenar erros
    $erros = [];
    
    // Validação do email
    if (empty($email)) {
        $erros[] = "O email é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "O email informado não é válido.";
    } else {
        // Verifica se o email já está registrado
        $stmt = $conn->prepare("SELECT id FROM candidatos WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $erros[] = "Este email já está registrado.";
        }
    }
    
    // Validação do telefone
    if (empty($telefone)) {
        $erros[] = "O telefone é obrigatório.";
    }
    
    // Validação da senha
    if (empty($senha)) {
        $erros[] = "A senha é obrigatória.";
    } elseif (strlen($senha) < 6) {
        $erros[] = "A senha deve ter pelo menos 6 caracteres.";
    }
    
    // Validação da confirmação de senha
    if ($senha !== $confirmarSenha) {
        $erros[] = "As senhas não coincidem.";
    }
    
    // Se não houver erros, registra o candidato
    if (empty($erros)) {
        // Hash da senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        
        // Insere o candidato no banco de dados
        $stmt = $conn->prepare("INSERT INTO candidatos (email, senha, telefone, perfil_completo) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $email, $senhaHash, $telefone);
        
        if ($stmt->execute()) {
            // Recupera o ID do candidato inserido
            $candidato_id = $conn->insert_id;
            
            // Cria mensagem de sucesso
            $_SESSION['mensagem_sucesso'] = "Cadastro inicial realizado com sucesso! Faça login para completar seu perfil.";
            
            // Redireciona para a página de login
            header('Location: login.php');
            exit;
        } else {
            $erros[] = "Erro ao registrar: " . $conn->error;
        }
    }
    
    // Se houver erros, armazena-os na sessão e redireciona de volta para o formulário
    if (!empty($erros)) {
        $_SESSION['erros_registro_candidato'] = $erros;
        $_SESSION['dados_form_candidato'] = [
            'email' => $email,
            'telefone' => $telefone
        ];
        header('Location: registro_candidato.php');
        exit;
    }
} else {
    // Se alguém tentar acessar este arquivo diretamente, redireciona para a página de registro
    header('Location: registro_candidato.php');
    exit;
}
?> 