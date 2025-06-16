<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="all.css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAM - Sistema de RH</title>
    <style>
        .hero {
            display: flex;
            padding: 60px 10%;
            align-items: center;
            justify-content: space-between;
            min-height: calc(100vh - 80px);
            margin-top:-40px;
        }
        
        .hero-content {
            max-width: 500px;
        }
        
        .hero-content h1 {
            font-size: 30px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
            color: #000;
        }
        
        .hero-content p {
            font-size: 18px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 20px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .hero-image {
            position: relative;
            width: 70%;
        }
        
        .dashboard-img {
            max-width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transform: perspective(200px) rotateY(-7deg);
            position: relative;
            z-index: 1;
            border: 2px solid hwb(158 24% 29%);
        }
        
        .circular-frames {
            position: absolute;
            top: -40px;
            right: -40px;
            z-index: 2;
        }
        
        .circular-frame {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 1.5px solid hwb(158 24% 29%);
            background-color: #f0f0f0;
            overflow: hidden;
            position: absolute;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .circular-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .circular-frame-1 {
            width: 100px;
            height: 100px;
            top: 20px;
            right: 250px;
        }
        
        .circular-frame-2 {
            width: 87px;
            height: 87px;
            top: 140px;
            right: 420px;
        }
        
        .circular-frame-3 {
            width: 87px;
            height: 87px;
            top: 200px;
            right: 40px;
        }

        .logo-icon1{
            font-size:20px;
        }
        
        .nav-buttons-content {
            display: flex;
            gap: 1rem;
        }

        .btn-entrar-content {
            background-color: hwb(158 24% 29%);
            color: white;
            font-weight: bold; 
        }

        .btn-criar-content {
            border: 1px solid hwb(158 24% 29%);
            color: hwb(158 24% 29%);
            font-weight: bold;
        }

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
    </style>
</head>
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
    
<section class="hero">
    <div class="hero-content">
        <h1>Simplifique e Otimize o seu RH com um sistema mais humano e eficiente.</h1>
        <p>Automatize processos, gerencie sua equipe e foque no que realmente importa.</p>
        <div class="nav-buttons-content">
        <a href="login.php">
            <button class="btn btn-entrar-content">Entrar</button>
        </a>
        <a href="Registro_adm.php">
            <button class="btn btn-criar-content">Criar empresa</button> 
        </a>
  
    </div>
    </div>
    <div class="hero-image">
        <div class="green-line"></div>
        <img src="img/img1.png" alt="Dashboard do Sistema" class="dashboard-img" />
        <div class="circular-frames">
            <div class="circular-frame circular-frame-1">
                <img src="img/ft.png.jpeg" alt="Funcionário 1">
            </div>
            <div class="circular-frame circular-frame-2">
                <img src="img/placeholder-user3.jpg" alt="Funcionário 2">
            </div>
            <div class="circular-frame circular-frame-3">
                <img src="img/placeholder-user3.jpg" alt="Funcionário 3">
            </div>
        </div>
    </div>
</section>
</body>
</html>