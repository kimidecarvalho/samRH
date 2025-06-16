<?php
// Configurar sessão para durar mais tempo
ini_set('session.gc_maxlifetime', 3600); // 1 hora
ini_set('session.cookie_lifetime', 3600); // 1 hora
ini_set('session.use_only_cookies', 1);   // Forçar uso de cookies para sessão
ini_set('session.use_strict_mode', 1);    // Modo estrito para maior segurança
ini_set('session.use_trans_sid', 0);      // Não usar SID em URLs
ini_set('session.cookie_httponly', 1);    // Cookie acessível apenas via HTTP
ini_set('session.cookie_samesite', 'Lax'); // Proteção contra CSRF

session_start();
error_log("Debug - Login.php - Sessão iniciada: " . session_id());

include('conexao.php');

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Verifica se já está na etapa de verificação do código
if (isset($_POST['codigo_verificacao'])) {
    $codigo_digitado = trim($_POST['codigo_verificacao']);
    $lembrar_dispositivo = isset($_POST['lembrar_dispositivo']) ? true : false;
    
    // Debug: Verificar valores da sessão
    error_log("Debug - Código digitado: " . $codigo_digitado);
    error_log("Debug - Código na sessão: " . (isset($_SESSION['codigo_verificacao']) ? $_SESSION['codigo_verificacao'] : 'Não definido'));
    error_log("Debug - id_temp: " . (isset($_SESSION['id_temp']) ? $_SESSION['id_temp'] : 'Não definido'));
    error_log("Debug - id_empresa_temp: " . (isset($_SESSION['id_empresa_temp']) ? $_SESSION['id_empresa_temp'] : 'Não definido'));
    error_log("Debug - Session ID: " . session_id());
    
    if (isset($_SESSION['codigo_verificacao']) && $codigo_digitado == $_SESSION['codigo_verificacao']) {
        // Código correto, prossegue com o login
        $_SESSION['id_adm'] = $_SESSION['id_temp'];
        $_SESSION['nome'] = $_SESSION['nome_temp'];
        
        // Verifica se a empresa está definida
        if (isset($_SESSION['id_empresa_temp'])) {
            $_SESSION['id_empresa'] = $_SESSION['id_empresa_temp'];
        } else {
            $_SESSION['id_empresa'] = null; // Define explicitamente como null se não existir
        }
        
        // Debug: Mostrar valores da sessão após atribuição
        error_log("Debug - Sessão após verificação:");
        error_log("id_adm: " . $_SESSION['id_adm']);
        error_log("nome: " . $_SESSION['nome']);
        error_log("id_empresa: " . (isset($_SESSION['id_empresa']) ? $_SESSION['id_empresa'] : 'Não definido'));
        
        // Registrar a sessão no banco de dados
        $session_id = session_id();
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        // Função para obter o IP real
        function getRealIP() {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                return $_SERVER['REMOTE_ADDR'];
            }
        }
        
        $ip_address = getRealIP();
        
        // Primeiro, limpa sessões antigas (mais de 24 horas)
        $sql_cleanup = "DELETE FROM adm_sessions WHERE adm_id = ? AND last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt_cleanup = $mysqli->prepare($sql_cleanup);
        $stmt_cleanup->bind_param("i", $_SESSION['id_adm']);
        $stmt_cleanup->execute();
        
        // Atualiza ou insere a sessão atual
        $query = "INSERT INTO adm_sessions (session_id, adm_id, user_agent, ip_address, last_activity) 
                  VALUES (?, ?, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE last_activity = NOW()";
        
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("siss", $session_id, $_SESSION['id_adm'], $user_agent, $ip_address);
        $stmt->execute();
        $stmt->close();
        
        // Forçar a sessão a ser gravada
        session_write_close();
        
        // Força uma pausa antes do redirecionamento
        usleep(500000); // 0.5 segundos
        
        // Limpa os dados temporários
        $_SESSION['codigo_verificacao'] = null;
        $_SESSION['id_temp'] = null;
        $_SESSION['nome_temp'] = null;
        $_SESSION['id_empresa_temp'] = null;
        
        // Debug: Verificar redirecionamento
        error_log("Debug - Redirecionando para: " . (isset($_SESSION['id_empresa']) && $_SESSION['id_empresa'] ? 'UI.php' : 'Registro_emp.php'));
        
        // Determina a URL de redirecionamento
        $redirect_url = (isset($_SESSION['id_empresa']) && $_SESSION['id_empresa']) ? 'UI.php' : 'Registro_emp.php';
        
        // Se marcou para lembrar o dispositivo, salva na tabela
        if ($lembrar_dispositivo) {
            $sql_salvar = "INSERT INTO dispositivos_confiaveis (adm_id, user_agent, ip_address) 
                          VALUES (?, ?, ?)
                          ON DUPLICATE KEY UPDATE ultimo_acesso = CURRENT_TIMESTAMP";
            $stmt_salvar = $mysqli->prepare($sql_salvar);
            $stmt_salvar->bind_param("iss", $_SESSION['id_adm'], $user_agent, $ip_address);
            $stmt_salvar->execute();
        }
        
        // Redireciona com JavaScript para evitar problemas de header already sent
        echo '<script type="text/javascript">';
        echo 'window.location.href="'.$redirect_url.'";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url='.$redirect_url.'" />';
        echo '</noscript>';
        exit();
    } else {
        $erro_verificacao = "Código de verificação inválido. Tente novamente.";
        error_log("Debug - Código inválido ou sessão perdida. Código digitado: " . $codigo_digitado . ", Código na sessão: " . (isset($_SESSION['codigo_verificacao']) ? $_SESSION['codigo_verificacao'] : 'Não definido'));
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['codigo_verificacao'])) {
    if (empty($_POST['email'])) {
        $erro_login = "Preencha seu email";
    } elseif (empty($_POST['senha'])) {
        $erro_login = "Preencha sua senha";
    } else {
        $email = trim($_POST['email']);
        $senha = trim($_POST['senha']);

        $sql_code = "SELECT id_adm, nome, senha FROM adm WHERE email = ?";
        $stmt = $mysqli->prepare($sql_code);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $usuario = $result->fetch_assoc();

            if (password_verify($senha, $usuario['senha'])) {
                // Verifica se o dispositivo é confiável
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $ip_address = $_SERVER['REMOTE_ADDR'];
                
                $sql_dispositivo = "SELECT id FROM dispositivos_confiaveis 
                                  WHERE adm_id = ? AND user_agent = ? AND ip_address = ?";
                $stmt_dispositivo = $mysqli->prepare($sql_dispositivo);
                $stmt_dispositivo->bind_param("iss", $usuario['id_adm'], $user_agent, $ip_address);
                $stmt_dispositivo->execute();
                $result_dispositivo = $stmt_dispositivo->get_result();
                
                // Verifica se a autenticação de dois fatores está ativada
                $sql_dois_fatores = "SELECT dois_fatores FROM configuracoes_seguranca WHERE adm_id = ?";
                $stmt_dois_fatores = $mysqli->prepare($sql_dois_fatores);
                $stmt_dois_fatores->bind_param("i", $usuario['id_adm']);
                $stmt_dois_fatores->execute();
                $result_dois_fatores = $stmt_dois_fatores->get_result();
                $config_dois_fatores = $result_dois_fatores->fetch_assoc();
                
                $dois_fatores_ativado = $config_dois_fatores ? $config_dois_fatores['dois_fatores'] : false;
                
                if (!$dois_fatores_ativado) {
                    // Se dois fatores estiver desativado, verifica se o dispositivo é confiável
                    if ($result_dispositivo->num_rows > 0) {
                        // Dispositivo confiável, faz login direto
                        $_SESSION['id_adm'] = $usuario['id_adm'];
                        $_SESSION['nome'] = $usuario['nome'];
                        
                        // Verifica se a empresa está definida
                        $sql_empresa = "SELECT id_empresa FROM empresa WHERE adm_id = ?";
                        $stmt_empresa = $mysqli->prepare($sql_empresa);
                        $stmt_empresa->bind_param("i", $usuario['id_adm']);
                        $stmt_empresa->execute();
                        $result_empresa = $stmt_empresa->get_result();

                        if ($result_empresa->num_rows > 0) {
                            $empresa = $result_empresa->fetch_assoc();
                            $_SESSION['id_empresa'] = $empresa['id_empresa'];
                        }
                        
                        // Atualiza o último acesso do dispositivo
                        $sql_update = "UPDATE dispositivos_confiaveis SET ultimo_acesso = CURRENT_TIMESTAMP 
                                     WHERE adm_id = ? AND user_agent = ? AND ip_address = ?";
                        $stmt_update = $mysqli->prepare($sql_update);
                        $stmt_update->bind_param("iss", $usuario['id_adm'], $user_agent, $ip_address);
                        $stmt_update->execute();
                        
                        // Redireciona para a página apropriada
                        $redirect_url = (isset($_SESSION['id_empresa']) && $_SESSION['id_empresa']) ? 'UI.php' : 'Registro_emp.php';
                        header("Location: $redirect_url");
                        exit();
                    }
                }
                
                // Se chegou aqui, ou dois fatores está ativo ou o dispositivo não é confiável
                // Prossegue com verificação de dois fatores
                $codigo_verificacao = rand(100000, 999999);
                $_SESSION['codigo_verificacao'] = $codigo_verificacao;
                $_SESSION['id_temp'] = $usuario['id_adm'];
                $_SESSION['nome_temp'] = $usuario['nome'];
                
                // Verifica se o administrador já cadastrou uma empresa
                $sql_empresa = "SELECT id_empresa FROM empresa WHERE adm_id = ?";
                $stmt_empresa = $mysqli->prepare($sql_empresa);
                $stmt_empresa->bind_param("i", $usuario['id_adm']);
                $stmt_empresa->execute();
                $result_empresa = $stmt_empresa->get_result();

                if ($result_empresa->num_rows > 0) {
                    $empresa = $result_empresa->fetch_assoc();
                    $_SESSION['id_empresa_temp'] = $empresa['id_empresa'];
                }
                
                // Envia o código por email
                $mail = new PHPMailer(true);
                try {
                    // Configurações do servidor SMTP
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'rrh14213@gmail.com'; // Seu email Gmail
                    $mail->Password = 'ajfhzdfpvoafxrap'; // Sua senha do Gmail ou App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    
                    // Remetente e destinatário
                    $mail->setFrom('suporte@samrh.com', 'SAM - Sistema de Administração');
                    $mail->addAddress($email);
                    
                    // Conteúdo do email
                    $mail->isHTML(true);
                    $mail->Subject = 'Seu código de verificação SAM ';
                    $mail->Subject = 'Código de Verificação de E-mail - SAM ';
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;'>
                            <div style='background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                                <h2 style='color: #3EB489; text-align: center; margin-bottom: 20px;'>Verificação de E-mail</h2>
                                <p style='text-align: center; color: #333; line-height: 1.6;'>
                                    Seu código de verificação é:
                                </p>
                                <div style='text-align: center; margin: 20px 0;'>
                                    <span style='display: inline-block; background-color: #3EB489; color: white; padding: 10px 20px; font-size: 24px; border-radius: 6px; letter-spacing: 3px;'>
                                        $codigo_verificacao
                                    </span>
                                </div>
                                <p style='text-align: center; color: #666; font-size: 14px;'>
                                    Este código expirará em 10 minutos. Não compartilhe este código com ninguém.
                                </p>
                                <p style='text-align: center; color: #666; font-size: 12px; margin-top: 20px;'>
                                    Se você não solicitou este código, por favor ignore este email ou entre em contato com o suporte.
                                </p>
                            </div>
                        </div>
                    ";
                    $mail->AltBody = "Seu código de verificação é: $codigo_verificacao\n\nUse este código para completar seu login. O código é válido por 10 minutos.";
                    
                    $mail->send();
                    
                    // Mostra o formulário de verificação
                    $mostrar_verificacao = true;
                } catch (Exception $e) {
                    $erro_login = "Erro ao enviar email. Tente novamente mais tarde. Erro: {$mail->ErrorInfo}";
                }
            } else {
                $erro_login = "Credenciais inválidas. Por favor, tente novamente.";
            }
        } else {
            $erro_login = "Credenciais inválidas. Por favor, tente novamente.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="all.css/login.css">
    <link rel="icon" type="" href="sam1.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <title>SAM - Login</title>
</head>
<style>
            .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            width: 620px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border-radius: 10px;
            padding: 25px;
            z-index: 10;
            left: 50%;
            transform: translateX(-50%);
            top: 50px;
        }
        
        .dropdown:hover .dropdown-content {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }
        
        .dropdown-box {
            width: 48%;
            padding: 0;
            border-radius: 0;
        }
        
        .dropdown-box-rh h3 {
            color: #2baa8f;
        }
        
        .dropdown-box-emprego h3 {
            color: #c9536b;
        }
        
        .dropdown-box h3 {
            display: flex;
            align-items: center;
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .dropdown-box h3 .logo-icon {
            width: 40px;
            height: auto;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dropdown-box h3 .pipe {
            margin: 0 10px;
            color: #888;
        }
        
        .dropdown-box h3 .title-text {
            font-weight: 500;
        }
        
        .dropdown-box p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
            font-size: 14px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .feature-list li {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
        }
        
        .feature-list li::before {
            content: "•";
            font-size: 16px;
            margin-right: 10px;
            display: inline-block;
        }
        
        .dropdown-box-rh .feature-list li::before {
            color: #2baa8f;
        }
        
        .dropdown-box-emprego .feature-list li::before {
            color: #c9536b;
        }
        
        @media (max-width: 992px) {
            .dropdown-content {
                width: 90%;
                max-width: 400px;
                flex-direction: column;
            }
            
            .dropdown-box {
                width: 100%;
                margin-bottom: 20px;
            }
        }

    .verification-card {
        display: <?php echo isset($mostrar_verificacao) ? 'block' : 'none'; ?>;
        max-width: 450px;
        padding: 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-radius: 30px;
    }

    .verification-card .login-title {
        color: #3EB489;
        margin-bottom: 20px;
        text-align: center;
    }

    .verification-card p {
        color: #666;
        text-align: center;
        margin-bottom: 25px;
        line-height: 1.6;
    }

    .verification-card input[name="codigo_verificacao"] {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }

    .verification-card input[name="codigo_verificacao"]:focus {
        outline: none;
        border-color: #3EB489;
    }

    .verification-card .btn-continuar {
        width: 100%;
        padding: 12px;
        background-color: #3EB489;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }

    .verification-card .btn-continuar:hover {
        background-color: #35a376;
    }

    .forgot-password {
        text-align: right;
        margin-top: 0.5rem;
        font-size: 0.85rem;
    }

    .forgot-password a {
        color: #3EB489;
        text-decoration: none;
    }

    .forgot-password a:hover {
        text-decoration: underline;
    }
    
    /* Estilo para o checkbox de lembrar dispositivo */
    .remember-device {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 15px 0;
        gap: 8px;
    }

    .remember-device input[type="checkbox"] {
        margin: 0;
        cursor: pointer;
    }

    .remember-device label {
        cursor: pointer;
        color: #666;
        font-size: 14px;
    }
    
    /* Estilo para o formulário de verificação */
    .verification-card {
        display: <?php echo isset($mostrar_verificacao) ? 'block' : 'none'; ?>;
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
                    <div class="dropdown-box dropdown-box-rh">
                        <h3>
                            <img src="img/sam2logo-32.png" alt="" class="logo-icon" style="width:100px; height:50px;">
                        </h3>
                        <p>Gerencie sua equipe com eficiência. Ferramentas essenciais para administração de funcionários, cargos, salários e tudo que seu RH precisa.</p>
                        <ul class="feature-list">
                            <li>Gestão de Funcionários</li>
                            <li>Gestão de Recrutamento</li>
                            <li>Controle de Salários</li>
                            <li>Administração de Cargos</li>
                            <li>e muito mais.</li>
                        </ul>
                    </div>
                    <div class="dropdown-box dropdown-box-emprego">
                        <h3>
                            <img src="img/emp.png" alt="SAM Emprego" class="logo-icon" style="width:100px; height:50px">
                        </h3>
                        <p>Conecte talentos à sua empresa. Publique vagas diretamente no SAM RH e facilite a contratação dos melhores candidatos.</p>
                        <ul class="feature-list">
                            <li>Publicação de Vagas</li>
                            <li>Triagem de Candidatos</li>
                            <li>Conexão com Empresas</li>
                            <li>Facilidade na Contratação</li>
                            <li>e mais</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="dropdown">
                <a href="#" class="dropbtn">Funcionalidades ▾</a>
                <div class="dropdown-content">
                    <div class="dropdown-box dropdown-box-rh">
                        <h3>
                            <img src="img/sam2logo-32.png" alt="SAM RH" class="logo-icon" style="width:100px; height:50px">
                        </h3>
                        <p>Conheça todas as funcionalidades disponíveis para otimizar os processos do seu RH.</p>
                        <ul class="feature-list">
                            <li>Automação de Processos</li>
                            <li>Controle de Ponto</li>
                            <li>Gestão de Benefícios</li>
                            <li>e muito mais.</li>
                        </ul>
                    </div>
                    <div class="dropdown-box dropdown-box-emprego">
                        <h3>
                            <img src="img/emp.png" alt="SAM Emprego" class="logo-icon"  style="width:100px; height:50px">
                        </h3>
                        <p>Explore os diversos recursos que facilitam a gestão do capital humano da sua empresa.</p>
                        <ul class="feature-list">
                            <li>Integração com Sistemas</li>
                            <li>Customização</li>
                            <li>Compliance</li>
                            <li>e mais</li>
                        </ul>
                    </div>
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
    
    <div class="login-container">
        <!-- Formulário de login (mostrado por padrão) -->
        <div class="login-card" id="loginCard" style="<?php echo isset($mostrar_verificacao) ? 'display: none;' : ''; ?>">
            <h2 class="login-title">Entrar</h2>
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="samrh@exemplo.com" 
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input 
                        type="password" 
                        id="senha" 
                        name="senha" 
                        placeholder="Digite sua senha" 
                        required
                    >
                    <div class="forgot-password">
                        <a href="recuperar_senha.php">Esqueceu a senha?</a>
                    </div>
                </div>
                <button type="submit" class="btn-continuar">Continuar</button>
                <div class="signup-link">
                    Ainda não tenho conta. <a href="Registro_adm.php">cadastrar administrador</a>
                </div>
            </form>
        </div>
        
        <!-- Formulário de verificação (mostrado após enviar email) -->
        <div class="login-card verification-card" id="verificationCard" style="<?php echo isset($mostrar_verificacao) ? '' : 'display: none;' ?>">
            <h2 class="login-title">Verificação de E-mail</h2>
            <p>
                Enviamos um código de verificação para o e-mail <strong><?php echo htmlspecialchars($email); ?></strong>. 
                Insira o código abaixo para confirmar seu endereço de e-mail.
            </p>
            <form method="POST">
                <div class="form-group">
                    <label for="codigo_verificacao">Código de Verificação</label>
                    <input 
                        type="text" 
                        id="codigo_verificacao" 
                        name="codigo_verificacao" 
                        placeholder="Digite o código de 6 dígitos" 
                        required
                        maxlength="6"
                        pattern="\d{6}"
                        title="Por favor, insira exatamente 6 dígitos"
                    >
                </div>
                <div class="remember-device">
                    <input type="checkbox" id="lembrar_dispositivo" name="lembrar_dispositivo">
                    <label for="lembrar_dispositivo">Lembrar este dispositivo</label>
                </div>
                <button type="submit" class="btn-continuar">Verificar E-mail</button>
            </form>
        </div>
    </div>
    
    <!-- Adiciona o SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if (isset($erro_login)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $erro_login; ?>',
            confirmButtonColor: '#3eb489',
            confirmButtonText: 'OK'
        });
    </script>
    <?php endif; ?>
    
    <?php if (isset($erro_verificacao)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Código inválido',
            text: '<?php echo $erro_verificacao; ?>',
            confirmButtonColor: '#3eb489',
            confirmButtonText: 'OK'
        });
    </script>
    <?php endif; ?>
    
    <?php if (isset($mostrar_verificacao)): ?>
    <script>
        document.getElementById('loginCard').style.display = 'none';
        document.getElementById('verificationCard').style.display = 'block';
    </script>
    <?php endif; ?>
</body>
</html>