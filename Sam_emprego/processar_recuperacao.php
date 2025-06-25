<?php
session_start();
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';

    if (empty($email) || empty($tipo_usuario)) {
        $_SESSION['erro_recuperacao'] = "Por favor, preencha todos os campos.";
        header('Location: recuperar_senha.php');
        exit;
    }

    $tabela = '';
    if ($tipo_usuario === 'candidato') {
        $tabela = 'candidatos';
    } elseif ($tipo_usuario === 'empresa') {
        $tabela = 'empresas_recrutamento';
    } else {
        $_SESSION['erro_recuperacao'] = "Tipo de usuário inválido.";
        header('Location: recuperar_senha.php');
        exit;
    }

    // Verificar se o email existe na tabela correta
    $sql = "SELECT id FROM $tabela WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Usuário encontrado, armazenar dados na sessão para o próximo passo
        $_SESSION['recuperacao'] = [
            'email' => $email,
            'tipo_usuario' => $tipo_usuario
        ];
        header('Location: redefinir_senha.php');
        exit;
    } else {
        // Usuário não encontrado
        $_SESSION['erro_recuperacao'] = "Nenhuma conta encontrada com este email para o tipo de usuário selecionado.";
        header('Location: recuperar_senha.php');
        exit;
    }
} else {
    header('Location: recuperar_senha.php');
    exit;
}
?> 