<?php
session_start();

// Recupera os erros e dados do formulário da sessão, se existirem
$erros = $_SESSION['erros_registro'] ?? [];
$dadosForm = $_SESSION['dados_form'] ?? [];

// Limpa as variáveis de sessão
unset($_SESSION['erros_registro']);
unset($_SESSION['dados_form']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="" href="sam2-05.png">
    <link rel="stylesheet" href="../all.css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>SAM - Cadastro de Empresa</title>
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
        max-width: 650px;
        padding: 2.5rem;
        transition: transform 0.3s ease;
        margin-bottom: 2rem;
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

    .form-group input,
    .form-group select,
    .form-group textarea {
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
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(62, 180, 137, 0.2);
    }

    .form-group input::placeholder,
    .form-group select::placeholder,
    .form-group textarea::placeholder {
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

    /* Estilos específicos para select e textarea */
    select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23555' d='M6 8.825L1.175 4 2.05 3.125 6 7.075 9.95 3.125 10.825 4 6 8.825z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        padding-right: 2.5rem;
        cursor: pointer;
    }

    textarea {
        min-height: 120px;
        resize: vertical;
        line-height: 1.5;
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

    /* Estilos para link de login */
    .signup-link {
        text-align: center;
        margin: 1.2rem 0;
        font-size: 0.9rem;
        color: #555;
    }

    .signup-link a {
        color: var(--primary-green);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .signup-link a:hover {
        color: var(--hover-green);
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
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(62, 180, 137, 0.2);
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

        .phone-group {
            flex-direction: column;
            gap: 8px;
        }

        .phone-group select {
            width: 100%;
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
            <a href="registro_candidato.php">
                <button class="btn btn-criar">Cadastrar Currículo</button>
            </a>
        </div>
    </header>
    
    <div class="login-container">
        <div class="login-card" id="registroEmpresaCard">
            <h2 class="login-title">Cadastro de Empresa</h2>
            
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($erros as $erro): ?>
                            <li><?php echo htmlspecialchars($erro); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="processar_registro_empresa.php" id="registroEmpresaForm">
                <div class="form-group">
                    <label for="nome">Nome da Empresa</label>
                    <input 
                        type="text" 
                        id="nome" 
                        name="nome" 
                        placeholder="Nome da sua empresa" 
                        value="<?php echo htmlspecialchars($dadosForm['nome'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea 
                        id="descricao" 
                        name="descricao" 
                        placeholder="Descreva sua empresa brevemente"
                    ><?php echo htmlspecialchars($dadosForm['descricao'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Corporativo</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="email@suaempresa.com" 
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
                </div>
                
                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <input 
                        type="text" 
                        id="endereco" 
                        name="endereco" 
                        placeholder="Endereço completo da empresa" 
                        value="<?php echo htmlspecialchars($dadosForm['endereco'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="setor">Setor de Atuação</label>
                        <input 
                            type="text" 
                            id="setor" 
                            name="setor" 
                            placeholder="Ex: Tecnologia, Saúde, Educação" 
                            value="<?php echo htmlspecialchars($dadosForm['setor'] ?? ''); ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="tamanho">Tamanho da Empresa</label>
                        <select id="tamanho" name="tamanho">
                            <option value="">Selecione o tamanho</option>
                            <option value="1-10" <?php echo (isset($dadosForm['tamanho']) && $dadosForm['tamanho'] === '1-10') ? 'selected' : ''; ?>>1-10 funcionários</option>
                            <option value="11-50" <?php echo (isset($dadosForm['tamanho']) && $dadosForm['tamanho'] === '11-50') ? 'selected' : ''; ?>>11-50 funcionários</option>
                            <option value="51-200" <?php echo (isset($dadosForm['tamanho']) && $dadosForm['tamanho'] === '51-200') ? 'selected' : ''; ?>>51-200 funcionários</option>
                            <option value="201-500" <?php echo (isset($dadosForm['tamanho']) && $dadosForm['tamanho'] === '201-500') ? 'selected' : ''; ?>>201-500 funcionários</option>
                            <option value="501+" <?php echo (isset($dadosForm['tamanho']) && $dadosForm['tamanho'] === '501+') ? 'selected' : ''; ?>>Mais de 500 funcionários</option>
                        </select>
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
                
                <div class="form-group">
                    <label for="website">Site da Empresa</label>
                    <input 
                        type="url" 
                        id="website" 
                        name="website" 
                        placeholder="https://www.suaempresa.com" 
                        value="<?php echo htmlspecialchars($dadosForm['website'] ?? ''); ?>"
                    >
                </div>
                
                <button type="submit" class="btn-continuar">Cadastrar Empresa</button>
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
    const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
    return re.test(senha);
}

function validarURL(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

function validarNome(nome) {
    return nome.length >= 3;
}

function validarDescricao(descricao) {
    return descricao.length >= 10;
}

function validarEndereco(endereco) {
    return endereco.length >= 5;
}

function validarSetor(setor) {
    return setor.length >= 3;
}

document.getElementById('registroEmpresaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const nome = document.getElementById('nome').value;
    const descricao = document.getElementById('descricao').value;
    const email = document.getElementById('email').value;
    const telefone = document.getElementById('telefone').value;
    const endereco = document.getElementById('endereco').value;
    const setor = document.getElementById('setor').value;
    const tamanho = document.getElementById('tamanho').value;
    const senha = document.getElementById('senha').value;
    const confirmarSenha = document.getElementById('confirmarSenha').value;
    const website = document.getElementById('website').value;
    
    let erros = [];
    
    // Validação do nome da empresa
    if (!validarNome(nome)) {
        erros.push('Nome da empresa deve ter no mínimo 3 caracteres');
        document.getElementById('nome').classList.add('input-erro');
    }
    
    // Validação da descrição
    if (!validarDescricao(descricao)) {
        erros.push('A descrição deve ter no mínimo 10 caracteres');
        document.getElementById('descricao').classList.add('input-erro');
    }
    
    // Validação do email
    if (!validarEmail(email)) {
        erros.push('Email corporativo inválido');
        document.getElementById('email').classList.add('input-erro');
    }
    
    // Validação do telefone
    if (!validarTelefone(telefone)) {
        erros.push('Telefone deve conter 9 dígitos');
        document.getElementById('telefone').classList.add('input-erro');
    }
    
    // Validação do endereço
    if (!validarEndereco(endereco)) {
        erros.push('Endereço deve ter no mínimo 5 caracteres');
        document.getElementById('endereco').classList.add('input-erro');
    }
    
    // Validação do setor
    if (!validarSetor(setor)) {
        erros.push('Setor deve ter no mínimo 3 caracteres');
        document.getElementById('setor').classList.add('input-erro');
    }
    
    // Validação do tamanho da empresa
    if (!tamanho) {
        erros.push('Selecione o tamanho da empresa');
        document.getElementById('tamanho').classList.add('input-erro');
    }
    
    // Validação da senha
    if (!validarSenha(senha)) {
        erros.push('A senha deve ter no mínimo 8 caracteres, uma letra maiúscula, uma minúscula e um número');
        document.getElementById('senha').classList.add('input-erro');
    }
    
    // Validação de confirmação de senha
    if (senha !== confirmarSenha) {
        erros.push('As senhas não coincidem');
        document.getElementById('confirmarSenha').classList.add('input-erro');
    }
    
    // Validação do website (se fornecido)
    if (website && !validarURL(website)) {
        erros.push('URL do website inválida');
        document.getElementById('website').classList.add('input-erro');
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

// Feedback visual em tempo real
document.querySelectorAll('input, textarea, select').forEach(input => {
    input.addEventListener('input', function() {
        if (this.classList.contains('input-erro')) {
            this.classList.remove('input-erro');
        }
    });
});
</script>
</html>