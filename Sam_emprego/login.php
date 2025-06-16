<?php
session_start();

// Verificar se já existe uma mensagem de sucesso
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? '';
unset($_SESSION['mensagem_sucesso']);

// Verificar se há mensagem de erro do login
$erroLogin = $_SESSION['erro_login'] ?? '';
unset($_SESSION['erro_login']);

// Redirecionar se já estiver logado
if (isset($_SESSION['empresa_id'])) {
    header('Location: painel_empresa.php');
    exit;
} else if (isset($_SESSION['candidato_id'])) {
    header('Location: painel_candidato.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="" href="sam2-05.png">
    <link rel="stylesheet" href="../all.css/login.css">
    <title>SAM - Login</title>
</head>
<style>
    /* Cores principais */
    :root {
        --primary-green: #3EB489;
        --primary-blue: #007bff;
        --hover-green: #36a078;
        --hover-blue: #0069d9;
        --text-dark: #333;
        --bg-light: #f8f9fa;
        --border-color: #e0e0e0;
        --shadow-color: rgba(0, 0, 0, 0.1);
    }

    .logo {
        height: 80px;
    }

    /* Estilização do container de login */
    .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 120px);
        padding: 2rem 1rem;
    }

    /* Card de login aprimorado */
    .login-card {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px var(--shadow-color);
        width: 100%;
        max-width: 420px;
        padding: 2.5rem;
        transition: transform 0.3s ease;
    }

    .login-card:hover {
        transform: translateY(-5px);
    }

    .login-title {
        color: var(--text-dark);
        text-align: center;
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 1.8rem;
    }

    /* Melhorias nos form groups */
    .form-group {
        margin-bottom: 1.5rem;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        font-weight: 500;
        color: #555;
    }

    .form-group input, 
    .form-group select {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    .form-group input:focus, 
    .form-group select:focus {
        outline: none;
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(62, 180, 137, 0.2);
    }

    .form-group input::placeholder {
        color: #aaa;
    }

    /* Estilos para esqueceu a senha */
    .forgot-password {
        text-align: right;
        margin-top: 0.5rem;
        font-size: 0.85rem;
    }

    .forgot-password a {
        color: var(--primary-green);
        text-decoration: none;
        transition: color 0.2s;
    }

    .forgot-password a:hover {
        color: var(--hover-green);
        text-decoration: underline;
    }

    /* Botão de continuar */
    .btn-continuar {
        width: 100%;
        padding: 0.85rem;
        background-color: var(--primary-green);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }

    .btn-continuar:hover {
        background-color: var(--hover-green);
        transform: translateY(-2px);
    }

    .btn-continuar:active {
        transform: translateY(0);
    }

    /* Estilos para link de cadastro */
    .signup-link {
        text-align: center;
        margin: 1.2rem 0;
        font-size: 0.9rem;
        color: #555;
    }

    /* Melhorias nas opções de registro */
    .register-options {
        display: flex;
        gap: 12px;
        margin-top: 0.8rem;
    }

    .register-options a {
        flex: 1;
        text-decoration: none;
    }

    .register-options button {
        width: 100%;
        padding: 0.75rem;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-empresa {
        background-color: var(--primary-green);
        color: white;
    }

    .btn-candidato {
        background-color: var(--primary-blue);
        color: white;
    }

    .btn-empresa:hover {
        background-color: var(--hover-green);
        transform: translateY(-2px);
    }

    .btn-candidato:hover {
        background-color: var(--hover-blue);
        transform: translateY(-2px);
    }

    .btn-empresa:active,
    .btn-candidato:active {
        transform: translateY(0);
    }

    /* Estilos para alerts */
    .alert {
        padding: 0.8rem 1rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-size: 0.9rem;
        position: relative;
    }

    .alert-danger {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    /* Responsividade */
    @media (max-width: 576px) {
        .login-card {
            padding: 1.5rem;
        }
        
        .login-title {
            font-size: 1.5rem;
        }
    }
</style>
<body>
    <header class="header">
        <a href="emprego_homepage.php">
            <img src="../fotos/sam30-13.png" alt="SAM Logo" class="logo">
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
            <div class="dropdown">
                <button class="btn btn-criar">Cadastrar-se ▾</button>
                <div class="dropdown-content">
                    <a href="registro_empresa.php">Empresa</a>
                    <a href="registro_candidato.php">Candidato</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="login-container">
        <!-- Formulário de login (mostrado por padrão) -->
        <div class="login-card" id="loginCard">
            <h2 class="login-title">Entrar</h2>
            
            <?php if (!empty($mensagemSucesso)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($mensagemSucesso); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($erroLogin)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erroLogin); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="processar_login.php" id="loginForm">
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
                
                <div class="form-group">
                    <label for="tipo_usuario">Entrar como:</label>
                    <select name="tipo_usuario" id="tipo_usuario" class="form-control" required>
                        <option value="empresa">Empresa</option>
                        <option value="candidato">Candidato</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-continuar">Continuar</button>
                <div class="signup-link">
                    Ainda não tenho conta.
                </div>
                
                <div class="register-options">
                    <a href="registro_empresa.php">
                        <button type="button" class="btn-empresa">Empresa</button>
                    </a>
                    <a href="registro_candidato.php">
                        <button type="button" class="btn-candidato">Candidato</button>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>