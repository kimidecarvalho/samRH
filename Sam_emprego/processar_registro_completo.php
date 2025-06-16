<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado como candidato
if (!isset($_SESSION['candidato_id'])) {
    // Se não estiver logado, redireciona para o login
    header('Location: login.php');
    exit;
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recupera o ID do candidato
    $candidato_id = $_SESSION['candidato_id']; // Usar o ID da sessão para segurança
    
    // Recupera os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? null;
    $endereco = trim($_POST['endereco'] ?? '');
    $formacao = trim($_POST['formacao'] ?? '');
    $experiencia = trim($_POST['experiencia'] ?? '');
    $habilidades = trim($_POST['habilidades'] ?? '');
    $area_atuacao = trim($_POST['area_atuacao'] ?? '');
    $nota_extra = trim($_POST['nota_extra'] ?? '');
    
    // Validações básicas
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório.";
    }
    
    if (empty($data_nascimento)) {
        $erros[] = "A data de nascimento é obrigatória.";
    }
    
    if (empty($endereco)) {
        $erros[] = "O endereço é obrigatório.";
    }
    
    // Processar o upload do currículo, se houver
    $curriculo_path = null;
    
    if (isset($_FILES['curriculo']) && $_FILES['curriculo']['error'] == 0) {
        $curriculo = $_FILES['curriculo'];
        $extensao = pathinfo($curriculo['name'], PATHINFO_EXTENSION);
        $extensoes_permitidas = ['pdf', 'doc', 'docx'];
        
        if (!in_array(strtolower($extensao), $extensoes_permitidas)) {
            $erros[] = "Formato de arquivo não permitido. Use PDF, DOC ou DOCX.";
        } else {
            // Cria diretório para armazenar currículos se não existir
            $diretorio = 'uploads/curriculos/';
            if (!is_dir($diretorio)) {
                mkdir($diretorio, 0755, true);
            }
            
            // Gera um nome único para o arquivo
            $novo_nome = uniqid('cv_') . '.' . $extensao;
            $curriculo_path = $diretorio . $novo_nome;
            
            // Move o arquivo para o diretório
            if (!move_uploaded_file($curriculo['tmp_name'], $curriculo_path)) {
                $erros[] = "Erro ao fazer upload do currículo.";
                $curriculo_path = null;
            }
        }
    }
    
    // Se não houver erros, atualiza o perfil do candidato
    if (empty($erros)) {
        // Prepara a consulta SQL para atualizar os dados do candidato
        $stmt = $conn->prepare("
            UPDATE candidatos 
            SET nome = ?, 
                data_nascimento = ?, 
                endereco = ?, 
                formacao = ?, 
                experiencia = ?, 
                habilidades = ?, 
                curriculo_path = ?,
                perfil_completo = 1
            WHERE id = ?
        ");
        
        $stmt->bind_param("sssssssi", 
            $nome, 
            $data_nascimento, 
            $endereco, 
            $formacao, 
            $experiencia, 
            $habilidades, 
            $curriculo_path,
            $candidato_id
        );
        
        if ($stmt->execute()) {
            // Redireciona para o painel do candidato
            $_SESSION['mensagem_sucesso'] = "Perfil completo com sucesso!";
            header('Location: painel_candidato.php');
            exit;
        } else {
            $erros[] = "Erro ao atualizar o perfil: " . $conn->error;
        }
    }
    
    // Se houver erros, armazena-os na sessão e redireciona de volta para o formulário
    if (!empty($erros)) {
        $_SESSION['erros_registro_completo'] = $erros;
        header('Location: job_register_page.php');
        exit;
    }
} else {
    // Se alguém tentar acessar este arquivo diretamente, redireciona para a página de registro
    header('Location: job_register_page.php');
    exit;
}
?> 