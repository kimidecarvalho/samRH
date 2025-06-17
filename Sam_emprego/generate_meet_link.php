<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/google-config.php';

header('Content-Type: application/json');

// Recebe dados do POST
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

// Campos esperados: nome, email, data, hora, titulo, duracao
$nome = $data['nome'] ?? null;
$email = $data['email'] ?? null;
$data_reuniao = $data['data'] ?? null;
$hora = $data['hora'] ?? null;
$titulo = $data['titulo'] ?? 'Reunião Google Meet';
$duracao = $data['duracao'] ?? 30;

if (!$nome || !$email || !$data_reuniao || !$hora) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

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

// Verifica se tem token na sessão
if (isset($_SESSION['google_access_token'])) {
    $client->setAccessToken($_SESSION['google_access_token']);
    
    // Se o token expirou, tenta atualizar
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $_SESSION['google_access_token'] = $client->getAccessToken();
        } else {
            // Se não tem refresh token, precisa autenticar novamente
            $authUrl = $client->createAuthUrl();
            echo json_encode(['success' => false, 'auth_url' => $authUrl]);
            exit;
        }
    }
} else {
    // Se não tem token, precisa autenticar
    $authUrl = $client->createAuthUrl();
    echo json_encode(['success' => false, 'auth_url' => $authUrl]);
    exit;
}

try {
    // Cria evento
    $service = new Google_Service_Calendar($client);
    $startDateTime = $data_reuniao . 'T' . $hora . ':00';
    $endDateTime = date('Y-m-d\TH:i:s', strtotime("$startDateTime +$duracao minutes"));

    $event = new Google_Service_Calendar_Event([
        'summary' => $titulo,
        'description' => 'Reunião criada automaticamente pelo sistema.',
        'start' => [
            'dateTime' => $startDateTime,
            'timeZone' => 'America/Sao_Paulo',
        ],
        'end' => [
            'dateTime' => $endDateTime,
            'timeZone' => 'America/Sao_Paulo',
        ],
        'attendees' => [
            ['email' => $email, 'displayName' => $nome],
        ],
        'conferenceData' => [
            'createRequest' => [
                'requestId' => uniqid(),
                'conferenceSolutionKey' => [ 'type' => 'hangoutsMeet' ]
            ]
        ],
    ]);

    $createdEvent = $service->events->insert('primary', $event, ['conferenceDataVersion' => 1]);
    $meetLink = $createdEvent->getConferenceData()->getEntryPoints()[0]->getUri();
    echo json_encode(['success' => true, 'meet_link' => $meetLink]);
} catch (Exception $e) {
    error_log("Erro ao criar evento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}