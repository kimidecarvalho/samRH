<?php
// Configurações da conexão com o banco de dados
$host = 'localhost';
$dbname = 'sam_emprego';
$username = 'root';
$password = '';

try {
    // Cria uma nova conexão PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configura o PDO para lançar exceções em caso de erros
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configura o fetch mode para retornar arrays associativos
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Em caso de erro na conexão
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}