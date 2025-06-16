<?php
session_start();
include('conexao.php');
require_once 'conf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Verifica se o email existe na tabela adm
    $sql = "SELECT id_adm, nome FROM adm WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        // Email encontrado, continua com a recuperação de senha
        $usuario = $result->fetch_assoc();
        
        // Gera um token único
        $token = bin2hex(random_bytes(50));
        
        // Define a data de expiração (24 horas a partir de agora)
        $expiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Primeiro exclui tokens antigos deste email
        $sql_delete = "DELETE FROM redefinicao_senha WHERE email = ?";
        $stmt_delete = $mysqli->prepare($sql_delete);
        $stmt_delete->bind_param("s", $email);
        $stmt_delete->execute();
        
        // Salva o novo token no banco de dados
        $sql_token = "INSERT INTO redefinicao_senha (email, token, data_expiracao) VALUES (?, ?, ?)";
        $stmt_token = $mysqli->prepare($sql_token);
        $stmt_token->bind_param("sss", $email, $token, $expiracao);
        
        if (!$stmt_token->execute()) {
            // Se houve um erro ao salvar o token
            $erro_email = "Erro ao processar a solicitação: " . $mysqli->error;
        } else {
            // Link para redefinição de senha
            $reset_link = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/redefinir_senha.php?token=".$token;
            
            // Configurar e enviar o email
            require 'vendor/autoload.php';
            
            $mail = new PHPMailer(true);
            try {
                // Configurações do servidor
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; 
                $mail->SMTPAuth = true;
                $mail->Username = 'rrh14213@gmail.com'; 
                $mail->Password = 'ajfhzdfpvoafxrap'; 
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                
                // Destinatários - envia para o email que foi inserido
                $mail->setFrom('suporte@samrh.com', 'SAM - Sistema de Administração');
                $mail->addAddress($email, $usuario['nome']);
                
                // Conteúdo
                $mail->isHTML(true);
                $mail->Subject = 'Recuperação de Senha - SAM';
                $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: 'Poppins', sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; border-radius: 10px; }
                        .header { background-color: #3EB489; color: white; padding: 10px; text-align: center; }
                        .content { padding: 20px; background-color: #f7f7f7; }
                        .button { display: inline-block; padding: 10px 20px; background-color: #3EB489; color: white; text-decoration: none; border-radius: 5px; }
                        .logo { display: block; margin: 0 auto; padding-bottom: 10px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <img src=\"img/sam2logo-32.png\" alt=\"SAM Logo\" class=\"logo\">                        
                            <h2>SAM - Recuperação de Senha</h2>
                        </div>
                        <div class='content'>
                            <p>Olá, {$usuario['nome']}!</p>
                            <p>Recebemos uma solicitação para redefinir sua senha.</p>
                            <p>Clique no botão abaixo para criar uma nova senha:</p>
                            <p style='text-align: center;'>
                                <a class='button' href='{$reset_link}'>Redefinir Senha</a>
                            </p>
                            <p>Ou copie e cole o link a seguir no seu navegador:</p>
                            <p>{$reset_link}</p>
                            <p>Este link expirará em 24 horas.</p>
                            <p>Se você não solicitou a redefinição de senha, ignore este email.</p>
                            <p>Atenciosamente,<br>Equipe SAM</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
                $mail->AltBody = "Olá, {$usuario['nome']}! Recebemos uma solicitação para redefinir sua senha. Acesse o link para criar uma nova senha: {$reset_link}. Se você não solicitou isso, ignore este email.";
                
                $mail->send();
                $mensagem_sucesso = "As instruções para redefinir sua senha foram enviadas para o seu email.";
            } catch (Exception $e) {
                $erro_email = "Ocorreu um erro ao enviar o email: " . $mail->ErrorInfo;
            }
        }
    } else {
        $erro_email = "O email informado não está cadastrado no sistema.";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="all.css/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <title>SAM - Recuperar Senha</title>
</head>
<style>
    .recovery-container {
        max-width: 500px;
        margin: 100px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 30px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-top:40px;
    }
    .recovery-title {
        font-size: 24px;
        color: #3EB489;
        margin-bottom: 20px;
        text-align: center;
    }
    .recovery-description {
        color: #666;
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
    input[type="email"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }
    .btn-enviar {
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
    .btn-enviar:hover {
        background-color: #36a078;
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
    
    <div class="recovery-container">
        <h2 class="recovery-title">Recuperar Senha</h2>
        <p class="recovery-description">Informe seu email para receber instruções de recuperação de senha.</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Digite seu email cadastrado" 
                    required
                >
            </div>
            <button type="submit" class="btn-enviar">Enviar</button>
            <div class="login-link">
                Lembrou sua senha? <a href="login.php">Voltar para o login</a>
            </div>
        </form>
    </div>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php
    // Exibir mensagem de sucesso ou erro com SweetAlert
    if (isset($mensagem_sucesso)): 
    ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Enviado!',
            text: '<?php echo $mensagem_sucesso; ?>',
            confirmButtonColor: '#3eb489',
            confirmButtonText: 'OK'
        });
    </script>
    <?php endif; ?>
    
    <?php
    if (isset($erro_email)): 
    ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $erro_email; ?>',
            confirmButtonColor: '#3eb489',
            confirmButtonText: 'OK'
        });
    </script>
    <?php endif; ?>
    
</body>
</html>