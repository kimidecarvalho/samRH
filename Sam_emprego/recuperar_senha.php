<?php
session_start();

// Verificar se há mensagem de erro
$erro = $_SESSION['erro_recuperacao'] ?? '';
unset($_SESSION['erro_recuperacao']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="" href="sam2-05.png">
    <link rel="stylesheet" href="../all.css/login.css">
    <title>SAM - Recuperar Senha</title>
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

    /* Estilização do container */
    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 120px);
        padding: 2rem 1rem;
    }

    /* Card */
    .card {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px var(--shadow-color);
        width: 100%;
        max-width: 420px;
        padding: 2.5rem;
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .card-title {
        color: var(--text-dark);
        text-align: center;
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 1.8rem;
    }

    /* Form groups */
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
    }

    .btn-continuar:hover {
        background-color: var(--hover-green);
        transform: translateY(-2px);
    }

    /* Alerts */
    .alert {
        padding: 0.8rem 1rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-size: 0.9rem;
    }

    .alert-danger {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    
    .back-link {
        display: block;
        text-align: center;
        margin-top: 1.5rem;
        color: var(--primary-green);
        text-decoration: none;
        font-weight: 500;
    }

    .back-link:hover {
        text-decoration: underline;
    }
</style>
<body>
    <header class="header">
        <a href="emprego_homepage.php">
            <img src="../fotos/sam30-13.png" alt="SAM Logo" class="logo">
        </a>
    </header>
    
    <div class="container">
        <div class="card">
            <h2 class="card-title">Recuperar Senha</h2>
            
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="processar_recuperacao.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Digite seu email de cadastro" required>
                </div>
                
                <div class="form-group">
                    <label for="tipo_usuario">Você é:</label>
                    <select name="tipo_usuario" id="tipo_usuario" required>
                        <option value="empresa">Empresa</option>
                        <option value="candidato">Candidato</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-continuar">Continuar</button>
            </form>
            <a href="login.php" class="back-link">Voltar para o Login</a>
        </div>
    </div>
</body>
</html> 