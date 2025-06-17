<?php
require_once '../config.php';
require_once 'SubsidiosController.php';

session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['id_adm'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Receber dados do POST
$data = json_decode(file_get_contents('php://input'), true);

// Criar instância do controlador
$controller = new SubsidiosController($conn);

// Processar a requisição
$controller->toggleSubsidio($data); 