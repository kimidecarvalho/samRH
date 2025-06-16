<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        :root {
            --primary-color: #3EB489;
            --primary-light: #4fc89a;
            --primary-dark: #339873;
            --secondary-color:rgb(84, 115, 146);
            --light-gray: #f5f7fa;
            --medium-gray: #e9ecef;
            --dark-gray: #6c757d;
            --box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
            --border-radius: 12px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            height: 80px;
        }

        .nav-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
        }

        .nav-menu {
            display: flex;
            gap: 20px;
        }

        .nav-menu a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 50px;
            transition: var(--transition);
        }

        .nav-menu a:hover {
            background-color: var(--light-gray);
            color: var(--primary-color);
        }

        .nav-menu a.active {
            color: var(--primary-color);
            position: relative;
        }

        .nav-menu a.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 10px;
        }

        .nav-buttons button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-buttons button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }


    </style>
</head>
<body>
<header class="header">
        <a href="emprego_homepage.php">
            <img src="../fotos/sam30-13.png" alt="SAM Logo" class="logo">
        </a>
        <div class="nav-container">
            <nav class="nav-menu">
                <a href="n.sei.php" class="active">Vagas</a>
                <a href="curriculums.php">Meu Currículo</a>
                <a href="minhas_candidaturas.php">Candidaturas</a>
                <a href="painel_candidato.php">Perfil</a>
            </nav>
        </div>
        <div class="nav-buttons">
            <a href="logout.php">
                <button class="btn btn-entrar">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </a>
        </div>
    </header>
</body>
</html>