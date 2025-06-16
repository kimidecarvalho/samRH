<?php
session_start();
require_once '../config';

header('Content-Type: application/json');

if (!isset($_SESSION['id_adm'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT session_id, user_agent, ip_address, last_activity 
              FROM adm_sessions 
              WHERE adm_id = :adm_id AND last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
              ORDER BY last_activity DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':adm_id', $_SESSION['id_adm']);
    $stmt->execute();
    
    $sessions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sessions[] = [
            'id' => $row['session_id'],
            'device' => parseUserAgent($row['user_agent']),
            'ip' => $row['ip_address'],
            'last_active' => $row['last_activity']
        ];
    }
    
    echo json_encode(['sessions' => $sessions]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro no servidor: ' . $e->getMessage()]);
}

function parseUserAgent($userAgent) {
    if (strpos($userAgent, 'Mobile') !== false) {
        if (strpos($userAgent, 'Android') !== false) {
            return 'Dispositivo Android';
        } elseif (strpos($userAgent, 'iPhone') !== false) {
            return 'Dispositivo iOS';
        }
        return 'Dispositivo Mobile';
    } elseif (strpos($userAgent, 'Windows') !== false) {
        return 'Computador - Windows';
    } elseif (strpos($userAgent, 'Macintosh') !== false) {
        return 'Computador - Mac';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        return 'Computador - Linux';
    }
    return 'Navegador Web';
}
?>