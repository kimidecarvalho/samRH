<?php
require_once 'conexao.php';

// Verifica se o campo já existe
$resultado = $conn->query("SHOW COLUMNS FROM candidatos LIKE 'perfil_completo'");

if ($resultado->num_rows == 0) {
    // O campo não existe, então vamos adicioná-lo
    $sql = "ALTER TABLE candidatos ADD COLUMN perfil_completo TINYINT(1) DEFAULT 0";
    
    if ($conn->query($sql) === TRUE) {
        echo "Campo 'perfil_completo' adicionado com sucesso à tabela candidatos";
    } else {
        echo "Erro ao adicionar campo: " . $conn->error;
    }
} else {
    echo "O campo 'perfil_completo' já existe na tabela candidatos";
}

$conn->close();
?> 