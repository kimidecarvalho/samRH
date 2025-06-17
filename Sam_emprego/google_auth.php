<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Configuração do cliente Google
$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/../credentials.json');
$client->setRedirectUri('http://localhost:8000/Sam_emprego/callback.php');
$client->addScope('https://www.googleapis.com/auth/calendar.events');
$client->addScope('https://www.googleapis.com/auth/calendar');

// Configuração para ignorar erros de SSL em desenvolvimento
$client->setHttpClient(new GuzzleHttp\Client([
    'verify' => false // Desabilita verificação SSL (apenas para desenvolvimento)
]));

// Gera a URL de autorização
$auth_url = $client->createAuthUrl();

// Redireciona para a página de autorização do Google
header('Location: ' . $auth_url);
exit; 