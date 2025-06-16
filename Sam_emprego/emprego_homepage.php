<?php
session_start();
// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Redireciona para a página de login
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="" href="sam2-05.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAM Emprego</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif
        }

        body {
            overflow-x: hidden;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header styles - Mantido intacto conforme solicitado */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 6rem;
            background-color: #f5f5f5;
        }

        .logo {
            height: 80px;
            margin-right: 10rem;
        }

        .nav-menu {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-menu a {
            text-decoration: none;
            font-weight: bold;
            color: #666;
            font-size: 1rem;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            text-decoration: none;
            font-weight: bold;
            color: #666;
            font-size: 1rem;
            cursor: pointer;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 150px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            z-index: 1000;
        }

        .dropdown-content a {
            display: block;
            padding: 10px;
            color: #333;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .dropdown-content a:hover {
            background-color: #f0f0f0;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .btn {
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-entrar {
            border: 1px solid #3EB489;
            color: #3EB489;
            font-weight: bold;
        }

        .btn-criar {
            background-color: #3EB489;
            color: white;
            border: none;
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
            color: #3EB489;
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
            color: #3EB489;
        }

        /* Main content styles - MELHORADO */
        main {
            position: relative;
            overflow: hidden;
        }

        .hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 80px 0;
            position: relative;
            min-height: 600px;
        }

        /* Elemento de fundo estilizado */
        .hero::before {
            content: '';
            position: absolute;
            top: -200px;
            right: -200px;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background-color: rgba(62, 180, 137, 0.05);
            z-index: 0;
        }

        .hero-content {
            width: 50%;
            position: relative;
            z-index: 2;
        }

        .hero-content h2 {
            font-size: 38px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #333;
            line-height: 1.3;
            position: relative;
        }

        .hero-content h2::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 60px;
            height: 4px;
            background-color: #3EB489;
            border-radius: 2px;
        }

        .hero-content h2 span {
            color: #3EB489;
            font-weight: 800;
            position: relative;
        }

        .search-box {
            position: relative;
            margin-top: 40px;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(62, 180, 137, 0.15);
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .search-box:focus-within {
            box-shadow: 0 15px 30px rgba(62, 180, 137, 0.25);
            transform: translateY(-2px);
        }

        .search-input {
            height: 60px;
            width: 100%;
            padding: 0 30px;
            border-radius: 30px;
            border: 1px solid rgba(62, 180, 137, 0.2);
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
            background-color: white;
        }

        .search-input:focus {
            border-color: #3EB489;
        }

        .search-input::placeholder {
            color: #aaa;
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 8px;
            height: 44px;
            background: linear-gradient(135deg, #3EB489, #2baa8f);
            color: white;
            border: none;
            border-radius: 22px;
            padding: 0 25px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(62, 180, 137, 0.3);
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(62, 180, 137, 0.4);
        }

        .tags {
            display: flex;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .tag {
            background-color: rgba(62, 180, 137, 0.1);
            color: #3EB489;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(62, 180, 137, 0.2);
            cursor: pointer;
        }

        .tag:hover {
            background-color: rgba(62, 180, 137, 0.2);
            transform: translateY(-2px);
        }

        .tag-add {
            background-color: transparent;
            border: 1px dashed #3EB489;
            color: #3EB489;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            width: 36px;
            padding: 0;
            border-radius: 50%;
        }

        .tag-add::before {
            content: "+";
            font-size: 18px;
            font-weight: 600;
        }

        .tag-add:hover {
            background-color: rgba(62, 180, 137, 0.05);
        }

        .description {
            margin-top: 30px;
            color: #666;
            font-size: 16px;
            line-height: 1.8;
            max-width: 500px;
            position: relative;
            padding-left: 20px;
            border-left: 3px solid rgba(62, 180, 137, 0.3);
        }

        .hero-image {
            width: 45%;
            position: relative;
            z-index: 1;
        }

        .person-image {
            width: 100%;
            border-radius: 20px;
            box-shadow: 20px 20px 60px rgba(0, 0, 0, 0.1), 
                        -20px -20px 60px rgba(255, 255, 255, 0.8);
            transition: all 0.5s ease;
            position: relative;
            z-index: 3;
        }

        .person-image:hover {
            transform: scale(1.02);
        }

        /* Decoração ao redor da imagem */
        .hero-image::before {
            content: '';
            position: absolute;
            width: 80%;
            height: 80%;
            bottom: -30px;
            right: -30px;
            background-color: rgba(62, 180, 137, 0.1);
            border-radius: 20px;
            z-index: 1;
        }

        .hero-image::after {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            top: -40px;
            left: -40px;
            border: 4px solid rgba(62, 180, 137, 0.2);
            border-radius: 50%;
            z-index: 1;
        }

        /* Responsividade melhorada */
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

            .hero {
                flex-direction: column;
                padding: 40px 0;
                text-align: center;
            }

            .hero-content {
                width: 100%;
                margin-bottom: 60px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .hero-content h2 {
                font-size: 32px;
            }

            .hero-content h2::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .search-box {
                max-width: 100%;
            }

            .description {
                max-width: 100%;
                text-align: center;
                padding-left: 0;
                border-left: none;
            }

            .hero-image {
                width: 80%;
                margin-top: 40px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1.5rem 2rem;
                flex-wrap: wrap;
            }
            
            .logo {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .nav-menu {
                order: 3;
                width: 100%;
                justify-content: center;
                margin-top: 15px;
            }

            .nav-buttons {
                order: 2;
            }

            .hero-content h2 {
                font-size: 28px;
            }

            .hero-image::before,
            .hero-image::after {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .hero-content h2 {
                font-size: 24px;
            }

            .search-input {
                height: 50px;
                font-size: 14px;
            }

            .search-btn {
                height: 36px;
                padding: 0 15px;
                font-size: 13px;
                top: 7px;
            }

            .person-image {
                border-radius: 15px;
            }

            .tag {
                padding: 6px 15px;
                font-size: 12px;
            }
        }
    </style>
</head>
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
                        <div class="dropdown-box dropdown-box-rh">
                            <h3>
                                <img src="sam30-13.png" alt="" class="logo-icon" style="width:100px; height:50px;">
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
                                <img src="../img/emp.png" alt="SAM Emprego" class="logo-icon" style="width:100px; height:50px">
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
                                <img src="../img/sam2logo-32.png" alt="SAM RH" class="logo-icon" style="width:100px; height:50px">
                            </h3>
                            <p>Conheça todas as funcionalidades disponíveis para otimizar os processos do seu RH.</p>
                            <ul class="feature-list">
                                <li>Automação de Processos</li>
                                <li>Relatórios e Dashboards</li>
                                <li>Controle de Ponto</li>
                                <li>Gestão de Benefícios</li>
                                <li>e muito mais.</li>
                            </ul>
                        </div>
                        <div class="dropdown-box dropdown-box-emprego">
                            <h3>
                                <img src="../img/emp.png" alt="SAM Emprego" class="logo-icon"  style="width:100px; height:50px">
                            </h3>
                            <p>Explore os diversos recursos que facilitam a gestão do capital humano da sua empresa.</p>
                            <ul class="feature-list">
                                <li>Integração com Sistemas</li>
                                <li>App Mobile</li>
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
            <button class="btn btn-criar">Criar empresa</button>   
        </div>
    </header>
    <div class="container">
        <main>
            <section class="hero">
                <div class="hero-content">
                    <h2>Encontre Oportunidades e<br>Cresça com o <span>SAM</span> Emprego.</h2>
                    
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Digite cargo, habilidade ou empresa...">
                        <button class="search-btn">Procurar</button>
                    </div>
                    
                    <div class="tags">
                        <div class="tag">Conexão</div>
                        <div class="tag">Inovação</div>
                        <div class="tag">Impacto</div>
                        <div class="tag">Confiança</div>
                        <div class="tag tag-add"></div>
                    </div>
                    
                    <p class="description">
                        Totalmente integrado ao SAM RH, oferecemos um caminho mais simples e eficiente para conectar talentos a empresas, facilitando sua jornada profissional e impulsionando sua carreira.
                    </p>
                </div>
                
                <div class="hero-image">
                    <svg class="person-image" viewBox="0 0 500 600" xmlns="http://www.w3.org/2000/svg">
                        <!-- Fundo -->
                        <rect width="500" height="600" fill="#f8f8f8" rx="20" ry="20" />
                        
                        <!-- Elementos decorativos -->
                        <circle cx="400" cy="100" r="60" fill="rgba(62, 180, 137, 0.1)" />
                        <circle cx="100" cy="500" r="80" fill="rgba(62, 180, 137, 0.05)" />
                        <path d="M450,300 Q500,250 450,200 Q400,150 450,100" stroke="#3EB489" stroke-width="2" fill="none" opacity="0.2" />
                        
                        <!-- Ilustração abstrata de pessoas conectadas -->
                        <!-- Pessoa 1 -->
                        <circle cx="150" cy="200" r="50" fill="#3EB489" opacity="0.8" />
                        <circle cx="150" cy="150" r="25" fill="#3EB489" />
                        <rect x="125" y="230" width="50" height="100" rx="25" fill="#3EB489" opacity="0.8" />
                        
                        <!-- Pessoa 2 -->
                        <circle cx="300" cy="250" r="50" fill="#3EB489" opacity="0.6" />
                        <circle cx="300" cy="200" r="25" fill="#3EB489" opacity="0.8" />
                        <rect x="275" y="280" width="50" height="100" rx="25" fill="#3EB489" opacity="0.6" />
                        
                        <!-- Pessoa 3 -->
                        <circle cx="220" cy="350" r="40" fill="#3EB489" opacity="0.4" />
                        <circle cx="220" cy="310" r="20" fill="#3EB489" opacity="0.6" />
                        <rect x="200" y="370" width="40" height="80" rx="20" fill="#3EB489" opacity="0.4" />
                        
                        <!-- Linhas de conexão -->
                        <line x1="170" y1="220" x2="280" y2="230" stroke="#3EB489" stroke-width="3" stroke-dasharray="5,5" />
                        <line x1="200" y1="350" x2="275" y2="300" stroke="#3EB489" stroke-width="3" stroke-dasharray="5,5" />
                        
                        <!-- Elementos de tecnologia e carreira -->
                        <!-- Laptop -->
                        <rect x="350" y="380" width="100" height="70" rx="5" fill="#555" />
                        <rect x="350" y="380" width="100" height="60" rx="5" fill="#777" />
                        <rect x="360" y="390" width="80" height="40" fill="#fff" />
                        <rect x="330" y="450" width="140" height="10" rx="5" fill="#555" />
                        
                        <!-- Ícones e elementos gráficos -->
                        <circle cx="120" cy="450" r="15" fill="#3EB489" opacity="0.7" />
                        <circle cx="400" cy="150" r="12" fill="#3EB489" opacity="0.7" />
                        <circle cx="350" cy="500" r="10" fill="#3EB489" opacity="0.7" />
                        
                        <!-- Gráfico de crescimento -->
                        <polyline points="50,500 100,480 150,490 200,450 250,430 300,410 350,420 400,380" 
                                 stroke="#3EB489" stroke-width="4" fill="none" />
                        
                        <!-- Logo SAM simplificado -->
                        <text x="230" y="70" font-family="Arial" font-size="28" font-weight="bold" fill="#3EB489">SAM</text>
                        <rect x="210" y="80" width="80" height="4" fill="#3EB489" />
                        
                        <!-- Texto "Emprego" -->
                        <text x="170" y="550" font-family="Arial" font-size="24" font-weight="bold" fill="#3EB489">OPORTUNIDADES</text>
                        
                        <!-- Elementos abstratos adicionais -->
                        <path d="M50,100 Q100,50 150,100 T250,100" stroke="#3EB489" stroke-width="2" fill="none" opacity="0.3" />
                        <path d="M300,550 Q350,500 400,550" stroke="#3EB489" stroke-width="2" fill="none" opacity="0.3" />
                    </svg>
                </div>
            </section>
        </main>
    </div>
</body>
</html>