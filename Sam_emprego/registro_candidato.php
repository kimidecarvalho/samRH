<?php
session_start();

// Recupera os erros e dados do formulário da sessão, se existirem
$erros = $_SESSION['erros_registro_candidato'] ?? [];
$dadosForm = $_SESSION['dados_form_candidato'] ?? [];

// Limpa as variáveis de sessão
unset($_SESSION['erros_registro_candidato']);
unset($_SESSION['dados_form_candidato']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="" href="sam2-05.png">
    <link rel="stylesheet" href="../all.css/login.css">
    <title>SAM - Cadastro de Candidato</title>
</head>
<style>
    /* Cores principais */
    :root {
        --primary-green: #3EB489;
        --primary-blue: #3EB489;
        --hover-green: #36a078;
        --hover-blue: #3EB489;
        --text-dark: #333;
        --bg-light: #f8f9fa;
        --border-color: #e0e0e0;
        --shadow-color: rgba(0, 0, 0, 0.1);
    }

    .logo {
        height: 80px;
    }

    /* Estilização do container de cadastro */
    .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 120px);
        padding: 2rem 1rem;
    }

    /* Card de cadastro aprimorado */
    .login-card {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px var(--shadow-color);
        width: 100%;
        max-width: 550px;
        padding: 2.5rem;
        transition: transform 0.3s ease;
    }

    .login-card:hover {
        transform: translateY(-5px);
    }

    .login-title {
        color: var(#3EB489);
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

    .form-group input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    }

    .form-group input::placeholder {
        color: #aaa;
    }

    /* Layout para form rows (campos lado a lado) */
    .form-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .form-row .form-group {
        flex: 1;
        margin-bottom: 0;
    }

    /* Botão de continuar */
    .btn-continuar {
        width: 100%;
        padding: 0.85rem;
        background-color: var(--primary-blue);
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
        background-color: var(--hover-blue);
        transform: translateY(-2px);
    }

    .btn-continuar:active {
        transform: translateY(0);
    }

    /* Estilos para link de login */
    .signup-link {
        text-align: center;
        margin: 1.2rem 0;
        font-size: 0.9rem;
        color: #555;
    }

    .signup-link a {
        color: var(--primary-blue);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .signup-link a:hover {
        color: var(--hover-blue);
        text-decoration: underline;
    }

    /* Estilos para alerts */
    .alert {
        padding: 1rem 1.2rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-size: 0.9rem;
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

    .alert ul {
        margin: 0.5rem 0;
        padding-left: 1.2rem;
    }

    .alert li {
        margin-bottom: 0.3rem;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .login-card {
            padding: 2rem;
        }
        
        .form-row {
            flex-direction: column;
            gap: 0;
        }
        
        .form-row .form-group {
            margin-bottom: 1.5rem;
        }
    }

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
        <a href="login.php">
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
            <a href="registro_empresa.php">
                <button class="btn btn-criar">Cadastrar Empresa</button>
            </a>
        </div>
    </header>
    
    <div class="login-container">
        <div class="login-card" id="registroCandidatoCard">
            <h2 class="login-title">Cadastro de Candidato</h2>
            
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($erros as $erro): ?>
                            <li><?php echo htmlspecialchars($erro); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="processar_registro_inicial.php" id="registroCandidatoForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="seu.email@exemplo.com" 
                        value="<?php echo htmlspecialchars($dadosForm['email'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input 
                        type="tel" 
                        id="telefone" 
                        name="telefone" 
                        placeholder="(00) 00000-0000" 
                        value="<?php echo htmlspecialchars($dadosForm['telefone'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            placeholder="Crie uma senha" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmarSenha">Confirmar Senha</label>
                        <input 
                            type="password" 
                            id="confirmarSenha" 
                            name="confirmarSenha" 
                            placeholder="Confirme sua senha" 
                            required
                        >
                    </div>
                </div>
                
                <button type="submit" class="btn-continuar">Continuar</button>
                <div class="signup-link">
                    Já possui uma conta? <a href="login.php">Entrar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>