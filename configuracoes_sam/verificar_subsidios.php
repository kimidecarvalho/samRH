<?php

function padronizar_nome_subsidio($nome) {
    return strtolower(str_replace(['-', ' '], '_', trim($nome)));
}
 
$subsidios = [];
while ($row = $result->fetch_assoc()) {
    $nome_padronizado = padronizar_nome_subsidio($row['nome']);
    $subsidios[$nome_padronizado] = $row;
}