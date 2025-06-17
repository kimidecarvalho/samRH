<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Processando autenticação do Google</h1>";

session_start();

// Verifica se o arquivo de configuração existe
if (!file_exists(__DIR__ . '/config/database.php')) {
    die('Erro: Arquivo de configuração não encontrado');
}

// Verifica se o autoload existe
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Erro: Composer autoload não encontrado. Execute "composer install" primeiro');
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Verifica se o arquivo de credenciais existe
if (!file_exists(__DIR__ . '/../credentials.json')) {
    die('Erro: Arquivo credentials.json não encontrado. Baixe-o do Google Cloud Console');
}

$config = require __DIR__ . '/google-config.php';

// Instancia o cliente Google
$client = new Google_Client();
$client->setClientId($config['client_id']);
$client->setClientSecret($config['client_secret']);
$client->setRedirectUri('http://localhost:8000/Sam_emprego/callback.php');
$client->setScopes([
    Google_Service_Calendar::CALENDAR,
    Google_Service_Calendar::CALENDAR_EVENTS,
]);
$client->setAccessType('offline');
$client->setPrompt('consent');

// Configuração para ignorar erros de SSL em desenvolvimento
$client->setHttpClient(new GuzzleHttp\Client([
    'verify' => false // Desabilita verificação SSL (apenas para desenvolvimento)
]));

// Se recebeu o código de autorização
if (isset($_GET['code'])) {
    try {
        // Troca o código por um token de acesso
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        // Armazena apenas o token na sessão, não o objeto cliente inteiro
        $_SESSION['google_access_token'] = $token;
        
        // Redireciona de volta para a página original
        header('Location: painel_empresa.php');
        exit;
    } catch (Exception $e) {
        echo "Erro ao processar autenticação: " . $e->getMessage();
        exit;
    }
} else {
    echo "Código de autorização não recebido";
    exit;
} 