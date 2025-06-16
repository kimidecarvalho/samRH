<?php
session_start();
include('conexao.php');
require_once 'conf.php';
// Verifica se o token foi fornecido na URL
if (empty($_GET['token'])) {
    header("Location: login.php");
    exit();
}

$token = $_GET['token'];

// Verifica se o token existe e é válido
$sql = "SELECT email, data_expiracao FROM redefinicao_senha WHERE token = ? AND data_expiracao > NOW()";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    // Token inválido ou expirado
    $token_invalido = true;
} else {
    $tokenData = $result->fetch_assoc();
    $email = $tokenData['email'];
    
    // Se o formulário foi enviado
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nova_senha = trim($_POST['nova_senha']);
        $confirmar_senha = trim($_POST['confirmar_senha']);
        
        // Validações
        if (strlen($nova_senha) < 8) {
            $erro_senha = "A senha deve ter pelo menos 8 caracteres.";
        } elseif ($nova_senha != $confirmar_senha) {
            $erro_senha = "As senhas não coincidem.";
        } else {
            // Hash da nova senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            // Atualiza a senha no banco de dados
            $sql_update = "UPDATE adm SET senha = ? WHERE email = ?";
            $stmt_update = $mysqli->prepare($sql_update);
            $stmt_update->bind_param("ss", $senha_hash, $email);
            
            if ($stmt_update->execute()) {
                // Remove o token usado
                $sql_delete = "DELETE FROM redefinicao_senha WHERE token = ?";
                $stmt_delete = $mysqli->prepare($sql_delete);
                $stmt_delete->bind_param("s", $token);
                $stmt_delete->execute();
                
                $senha_alterada = true;
            } else {
                $erro_sistema = "Ocorreu um erro ao atualizar sua senha. Por favor, tente novamente.";
            }
        }
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="all.css/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <title>SAM - Redefinir Senha</title>
</head>
<style>
    .reset-container {
        max-width: 500px;
        margin: 100px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 30px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-top:40px;
    }
    .reset-title {
        font-size: 24px;
        color: #3EB489;
        margin-bottom: 20px;
        text-align: center;
    }
    .form-group {
        margin-bottom: 20px;
    }
    label {
        display: block;
        margin-bottom: 8px;
        color: #333;
    }
    input[type="password"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }
    .btn-salvar {
        width: 100%;
        padding: 12px;
        background-color: #3EB489;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 500;
    }
    .btn-salvar:hover {
        background-color: #36a078;
    }
    .password-requirements {
        font-size: 13px;
        color: #666;
        margin-top: 5px;
    }
    .login-link {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
    }
    .login-link a {
        color: #3EB489;
        text-decoration: none;
    }
    .login-link a:hover {
        text-decoration: underline;
    }
    .error-container {
        padding: 20px;
        text-align: center;
        margin-top:80px;
    }
    .error-message {
        color: #721c24;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
</style>
<body>
    <header class="header">
        <a href="login.php">
            <img src="img/sam2logo-32.png" alt="SAM Logo" class="logo">
        </a>
        <div class="nav-container">
            <nav class="nav-menu">
                <div class="dropdown">
                    <a href="#" class="dropbtn">Produtos ▾</a>
                    <div class="dropdown-content">
                        <a href="#">Produto 1</a>
                        <a href="#">Produto 2</a>
                        <a href="#">Produto 3</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="dropbtn">Funcionalidades ▾</a>
                    <div class="dropdown-content">
                        <a href="#">Funcionalidade 1</a>
                        <a href="#">Funcionalidade 2</a>
                        <a href="#">Funcionalidade 3</a>
                    </div>
                </div>
                <a href="#">Preços</a>
            </nav>
        </div>
        <div class="nav-buttons">
            <a href="login.php">
                <button class="btn btn-entrar">Entrar</button>    
            </a>
            <a href="Registro_adm.php">
                <button class="btn btn-criar">Criar empresa</button>   
            </a>
        </div>
    </header>
    
    <?php if (isset($token_invalido) && $token_invalido): ?>
    <div class="reset-container error-container">
        <div class="error-message">
            <p>O link de redefinição de senha é inválido ou expirou.</p>
            <p>Por favor, solicite um novo link de recuperação de senha.</p>
        </div>
        <div class="login-link">
            <a href="recuperar_senha.php">Solicitar nova recuperação</a> ou <a href="login.php">Voltar para o login</a>
        </div>
    </div>
    
    <?php elseif (isset($senha_alterada) && $senha_alterada): ?>
    <div class="reset-container">
        <h2 class="reset-title">Senha Alterada com Sucesso</h2>
        <p style="text-align: center; color: #28a745; margin-bottom: 20px;">Sua senha foi redefinida com sucesso!</p>
        <p style="text-align: center; margin-bottom: 20px;">Agora você já pode acessar o sistema com sua nova senha.</p>
        <div class="login-link" style="margin-top: 30px;">
            <a href="login.php" style="display: block; text-align: center; padding: 12px; background-color: #3EB489; color: white; border-radius: 4px; text-decoration: none;">Fazer Login</a>
        </div>
    </div>
    
    <?php else: ?>
    <div class="reset-container">
        <h2 class="reset-title">Crie uma Nova Senha</h2>
        
        <form method="POST">
            <div class="form-group">
                <label for="nova_senha">Nova Senha</label>
                <input 
                    type="password" 
                    id="nova_senha" 
                    name="nova_senha" 
                    placeholder="Digite sua nova senha" 
                    required
                >
                <div class="password-requirements">A senha deve ter pelo menos 8 caracteres.</div>
            </div>
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha</label>
                <input 
                    type="password" 
                    id="confirmar_senha" 
                    name="confirmar_senha" 
                    placeholder="Confirme sua nova senha" 
                    required
                >
            </div>
            <button type="submit" class="btn-salvar">Salvar Nova Senha</button>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if (isset($erro_senha)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: '<?php echo $erro_senha; ?>',
            confirmButtonColor: '#3eb489',
            confirmButtonText: 'OK'
        });
    </script>
    <?php endif; ?>
    
    <?php if (isset($erro_sistema)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Erro do Sistema',
            text: '<?php echo $erro_sistema; ?>',
            confirmButtonColor: '#3eb489',
            confirmButtonText: 'OK'
        });
    </script>
    <?php endif; ?>
    
</body>
</html>