<?php
$host = "localhost"; // Nome do Servidor
$user = "root"; // Usuário do banco de dados
$password = ""; // Senha do banco 
$database = "sam"; // Nome do banco de dados

$conn = new mysqli($host, $user, $password, $database);

// Verifica conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
