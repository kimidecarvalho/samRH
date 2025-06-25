<?php
session_start();
require 'conexao.php';

// Se não houver dados de recuperação na sessão, redirecionar
if (!isset($_SESSION['recuperacao'])) {
    header('Location: recuperar_senha.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (empty($nova_senha) || empty($confirmar_senha)) {
        $_SESSION['erro_redefinicao'] = "Por favor, preencha todos os campos.";
        header('Location: redefinir_senha.php');
        exit;
    }

    if ($nova_senha !== $confirmar_senha) {
        $_SESSION['erro_redefinicao'] = "As senhas não coincidem.";
        header('Location: redefinir_senha.php');
        exit;
    }

    // Hash da nova senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    // Obter dados da sessão
    $email = $_SESSION['recuperacao']['email'];
    $tipo_usuario = $_SESSION['recuperacao']['tipo_usuario'];
    
    $tabela = '';
    if ($tipo_usuario === 'candidato') {
        $tabela = 'candidatos';
    } elseif ($tipo_usuario === 'empresa') {
        $tabela = 'empresas_recrutamento';
    }

    // Atualizar a senha no banco de dados
    $sql = "UPDATE $tabela SET senha = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $senha_hash, $email);

    if ($stmt->execute()) {
        // Limpar a sessão de recuperação e definir mensagem de sucesso
        unset($_SESSION['recuperacao']);
        $_SESSION['mensagem_sucesso'] = "Sua senha foi redefinida com sucesso!";
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['erro_redefinicao'] = "Ocorreu um erro ao redefinir sua senha. Tente novamente.";
        header('Location: redefinir_senha.php');
        exit;
    }
} else {
    header('Location: redefinir_senha.php');
    exit;
}
?> 