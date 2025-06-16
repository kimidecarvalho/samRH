<?php
// Iniciar sessão
session_start();

// Exibir o ID da sessão
echo "ID da sessão atual: " . session_id() . "<br>";

// Exibir todas as variáveis de sessão
echo "<h3>Variáveis de sessão:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Se for um POST, definir uma variável de sessão de teste
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['test_value'] = $_POST['test_value'];
    echo "<p style='color:green'>Valor definido: " . $_SESSION['test_value'] . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teste de Sessão</title>
</head>
<body>
    <h2>Teste de Persistência de Sessão</h2>
    
    <form method="post">
        <label for="test_value">Definir valor de teste:</label>
        <input type="text" id="test_value" name="test_value" value="teste_<?php echo rand(1000, 9999); ?>">
        <button type="submit">Salvar na Sessão</button>
    </form>
    
    <hr>
    <h3>Links de teste:</h3>
    <ul>
        <li><a href="login.php">Ir para login.php</a></li>
        <li><a href="UI.php">Ir para UI.php</a></li>
        <li><a href="test_session.php">Recarregar esta página</a></li>
    </ul>
</body>
</html> 