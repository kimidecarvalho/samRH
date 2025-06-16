<?php
// Debug para rastrear a execução do arquivo protect.php
error_log("Debug - protect.php - Executando protect.php");
error_log("Debug - protect.php - Session ID: " . session_id());

// Inicia a sessão se ainda não foi iniciada
if(!isset($_SESSION)) {
    session_start();
    error_log("Debug - protect.php - Sessão iniciada no protect.php");
}

// Exibe informações da sessão para debug
error_log("Debug - protect.php - Verificando sessão");
error_log("Debug - protect.php - id_adm: " . (isset($_SESSION['id_adm']) ? $_SESSION['id_adm'] : 'Não definido'));

// Verifica se o usuário está logado
if(!isset($_SESSION['id_adm'])) {
    error_log("Debug - protect.php - Usuário não autenticado, redirecionando para login.php");
    header("Location: login.php");
    exit; // Certifique-se de usar exit após o redirecionamento para parar a execução do script
} else {
    error_log("Debug - protect.php - Usuário autenticado, ID: " . $_SESSION['id_adm']);
}
?>
