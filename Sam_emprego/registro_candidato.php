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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="" href="sam2-05.png">
    <link rel="stylesheet" href="../all.css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

    /* Estilo para o grupo do telefone */
    .phone-group {
        display: flex;
        gap: 10px;
        align-items: stretch;
    }

    .phone-group select {
        width: 140px;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
        cursor: pointer;
        height: 100%;
    }

    .phone-group .phone-input {
        flex: 1;
        min-width: 0; /* Previne overflow em containers flex */
    }

    .phone-group .phone-input input {
        width: 100%;
        margin: 0;
        height: 100%;
    }

    .phone-group select:focus,
    .phone-group .phone-input input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    }

    @media (max-width: 576px) {
        .phone-group {
            flex-direction: column;
            gap: 8px;
        }

        .phone-group select {
            width: 100%;
        }
    }

    /* Estilo para o botão de mostrar senha */
    .password-toggle {
        position: absolute;
        right: 10px;
        top: calc(50% + 12px); /* Ajustado para considerar a altura do label */
        transform: translateY(-50%);
        border: none;
        background: none;
        cursor: pointer;
        color: #666;
        padding: 5px;
        height: 24px; /* Altura fixa para centralização */
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .password-toggle:hover {
        color: var(--primary-blue);
    }

    .form-group {
        position: relative;
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

    .input-erro {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25) !important;
    }

    .form-group .feedback-text {
        color: #dc3545;
        font-size: 0.8rem;
        margin-top: 0.25rem;
        display: none;
    }

    .form-group.error .feedback-text {
        display: block;
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
                    <div class="phone-group">
                        <select name="codigo_pais" id="codigo_pais">
                            <option value="+244">+244 (Angola)</option>
                            <option value="+258">+258 (Moçambique)</option>
                            <option value="+239">+239 (São Tomé e Príncipe)</option>
                            <option value="+245">+245 (Guiné-Bissau)</option>
                            <option value="+238">+238 (Cabo Verde)</option>
                            <option value="+351">+351 (Portugal)</option>
                            <option value="+55">+55 (Brasil)</option>
                        </select>
                        <div class="phone-input">
                            <input 
                                type="tel" 
                                id="telefone" 
                                name="telefone" 
                                placeholder="900000000" 
                                value="<?php echo htmlspecialchars($dadosForm['telefone'] ?? ''); ?>"
                                required
                            >
                        </div>
                    </div>
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
                        <button type="button" class="password-toggle" onclick="togglePassword('senha')">
                            <i class="fa-regular fa-eye"></i>
                        </button>
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
                        <button type="button" class="password-toggle" onclick="togglePassword('confirmarSenha')">
                            <i class="fa-regular fa-eye"></i>
                        </button>
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
<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = event.currentTarget.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}

function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validarTelefone(telefone) {
    const re = /^\d{9}$/;
    return re.test(telefone);
}

function validarSenha(senha) {
    // Mínimo 8 caracteres, pelo menos uma letra maiúscula, uma minúscula e um número
    const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
    return re.test(senha);
}

document.getElementById('registroCandidatoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const telefone = document.getElementById('telefone').value;
    const senha = document.getElementById('senha').value;
    const confirmarSenha = document.getElementById('confirmarSenha').value;
    
    let erros = [];
    
    if (!validarEmail(email)) {
        erros.push('Email inválido');
        document.getElementById('email').classList.add('input-erro');
    } else {
        document.getElementById('email').classList.remove('input-erro');
    }
    
    if (!validarTelefone(telefone)) {
        erros.push('Telefone deve conter 9 dígitos');
        document.getElementById('telefone').classList.add('input-erro');
    } else {
        document.getElementById('telefone').classList.remove('input-erro');
    }
    
    if (!validarSenha(senha)) {
        erros.push('A senha deve ter no mínimo 8 caracteres, uma letra maiúscula, uma minúscula e um número');
        document.getElementById('senha').classList.add('input-erro');
    } else {
        document.getElementById('senha').classList.remove('input-erro');
    }
    
    if (senha !== confirmarSenha) {
        erros.push('As senhas não coincidem');
        document.getElementById('confirmarSenha').classList.add('input-erro');
    } else {
        document.getElementById('confirmarSenha').classList.remove('input-erro');
    }
    
    if (erros.length > 0) {
        const alertaErro = document.createElement('div');
        alertaErro.className = 'alert alert-danger';
        alertaErro.innerHTML = '<ul>' + erros.map(erro => `<li>${erro}</li>`).join('') + '</ul>';
        
        // Remove alertas anteriores
        const alertaAnterior = document.querySelector('.alert-danger');
        if (alertaAnterior) {
            alertaAnterior.remove();
        }
        
        // Insere o novo alerta no início do formulário
        this.insertBefore(alertaErro, this.firstChild);
    } else {
        this.submit();
    }
});

// Adiciona feedback visual em tempo real
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('input', function() {
        const parent = this.parentElement;
        if (this.classList.contains('input-erro')) {
            this.classList.remove('input-erro');
        }
    });
});
</script>
</html>