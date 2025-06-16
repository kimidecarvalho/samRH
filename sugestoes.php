<?php
include('config.php');

$search = isset($_GET['query']) ? mysqli_real_escape_string($conn, $_GET['query']) : '';

$sql = "SELECT nome FROM funcionario WHERE nome LIKE '%$search%' LIMIT 5"; // Limitar a 5 sugestões
$result = mysqli_query($conn, $sql);

$sugestoes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sugestoes[] = $row['nome'];
}

echo json_encode($sugestoes);
?>