<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="all.css/login.css">
    <title>SAM - Login</title>
</head>
<body>

    <header class="header">
        <img src="path-to-your-logo.svg" alt="SAM Logo" class="logo">
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
            <button class="btn btn-entrar">Entrar</button>
            <a href="Registro_emp.html">
                <button class="btn btn-criar">Criar empresa</button>   
            </a>
        
        </div>
    </header>
     
    <div class="login-container">
        <div class="login-card">
            <h2 class="login-title">Entrar</h2>
            <form id="loginForm">
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
                <button type="submit" class="btn-continuar">continuar</button>
                <div class="signup-link">
                    Ainda não tenho conta. <a href="Registro_emp.html">criar empresa</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>