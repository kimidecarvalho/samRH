<?php
session_start();
require_once 'conexao.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recupera os dados do formulário
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';
    
    // Validações básicas
    if (empty($email) || empty($senha) || empty($tipo_usuario)) {
        $_SESSION['erro_login'] = "Todos os campos são obrigatórios.";
        header('Location: login.php');
        exit;
    }
    
    // Verifica o tipo de usuário e realiza a autenticação
    if ($tipo_usuario == 'candidato') {
        $stmt = $conn->prepare("SELECT id, email, senha, perfil_completo FROM candidatos WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $candidato = $result->fetch_assoc();
            
            if (password_verify($senha, $candidato['senha'])) {
                // Login bem-sucedido, salva informações na sessão
                $_SESSION['candidato_id'] = $candidato['id'];
                $_SESSION['candidato_email'] = $candidato['email'];
                
                // Verifica se o perfil está completo
                if ($candidato['perfil_completo'] == 0) {
                    // Perfil incompleto, redireciona para a página de registro completo
                    header('Location: job_register_page.php?id=' . $candidato['id']);
                } else {
                    // Perfil completo, redireciona para o painel do candidato
                    header('Location: painel_candidato.php');
                }
                exit;
            }
        }
        
        // Login inválido
        $_SESSION['erro_login'] = "Email ou senha incorretos.";
        header('Location: login.php');
        exit;
        
    } elseif ($tipo_usuario == 'empresa') {
        $stmt = $conn->prepare("SELECT id, email, senha FROM empresas_recrutamento WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $empresa = $result->fetch_assoc();
            
            if (password_verify($senha, $empresa['senha'])) {
                // Login bem-sucedido, salva informações na sessão
                $_SESSION['empresa_id'] = $empresa['id'];
                $_SESSION['empresa_email'] = $empresa['email'];
                
                // Redireciona para o painel da empresa
                header('Location: painel_empresa.php');
                exit;
            }
        }
        
        // Login inválido
        $_SESSION['erro_login'] = "Email ou senha incorretos.";
        header('Location: login.php');
        exit;
    }
} else {
    // Se alguém tentar acessar este arquivo diretamente, redireciona para a página de login
    header('Location: login.php');
    exit;
}
?> 