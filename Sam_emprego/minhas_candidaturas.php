<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado como candidato
if (!isset($_SESSION['candidato_id'])) {
    // Redireciona para a página de login
    header("Location: login.php");
    exit();
}

// Recupera os dados do candidato
$candidato_id = $_SESSION['candidato_id'];
$stmt = $conn->prepare("SELECT * FROM candidatos WHERE id = ?");
$stmt->bind_param("i", $candidato_id);
$stmt->execute();
$result = $stmt->get_result();
$candidato = $result->fetch_assoc();

// Recupera as candidaturas do candidato
$candidaturas = [];
$temCandidaturas = false;

// Verifica se as tabelas candidaturas e vagas existem
$tabelas_existem = true;
$result = $conn->query("SHOW TABLES LIKE 'candidaturas'");
if ($result->num_rows == 0) {
    $tabelas_existem = false;
}

$result = $conn->query("SHOW TABLES LIKE 'vagas'");
if ($result->num_rows == 0) {
    $tabelas_existem = false;
}

if ($tabelas_existem) {
    $query = "SELECT c.*, v.titulo as vaga_titulo, v.descricao as vaga_descricao, 
                     e.nome as empresa_nome, e.logo as empresa_logo
              FROM candidaturas c 
              JOIN vagas v ON c.vaga_id = v.id 
              JOIN empresas_recrutamento e ON v.empresa_id = e.id
              WHERE c.candidato_id = ? 
              ORDER BY c.data_candidatura DESC";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $candidato_id);
        $stmt->execute();
        $candidaturas_result = $stmt->get_result();
        $temCandidaturas = true;
        
        while ($row = $candidaturas_result->fetch_assoc()) {
            $candidaturas[] = $row;
        }
    }
}

// Calcula estatísticas
$totalCandidaturas = count($candidaturas);
$emProcesso = 0;
$aprovadas = 0;
$rejeitadas = 0;

foreach ($candidaturas as $candidatura) {
    $status = strtolower($candidatura['status'] ?? '');
    if ($status == 'em análise' || $status == 'entrevista') {
        $emProcesso++;
    } elseif ($status == 'aprovado' || $status == 'aprovada') {
        $aprovadas++;
    } elseif ($status == 'rejeitado' || $status == 'rejeitada') {
        $rejeitadas++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="" href="sam2-05.png">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_vagas.css">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_search.css">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <title>SAM - Minhas Candidaturas</title>
    <style>
        :root {
            --primary-color: #3EB489;
            --primary-light: #4fc89a;
            --primary-dark: #339873;
            --primary-gradient: linear-gradient(135deg, #3EB489 0%, #4fc89a 100%);
            --secondary-color: rgb(84, 115, 146);
            --secondary-light: rgba(84, 115, 146, 0.1);
            --light-gray: #f8fafc;
            --medium-gray: #e2e8f0;
            --dark-gray: #64748b;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --white: #ffffff;
            --success: #10b981;
            --warning: #f97316;
            --warning-light: #fed7aa;
            --danger: #ef4444;
            --info: #3b82f6;
            --info-light: #dbeafe;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            --box-shadow-hover: 0 4px 20px rgba(0, 0, 0, 0.1);
            --box-shadow-card: 0 1px 8px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 12px;
            --border-radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem 1rem;
        }

        /* Hero Section - Mais compacto */
        .hero-section {
            background: var(--primary-gradient);
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
            box-shadow: var(--box-shadow);
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            text-align: center;
            color: var(--white);
        }

        .hero-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .hero-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        /* Stats Section - Componentes menores */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
       
        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            text-align: center;
            box-shadow: var(--box-shadow-card);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--medium-gray);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-gradient);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--box-shadow-hover);
            border-color: var(--primary-light);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            background: var(--secondary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            color: var(--secondary-color);
            font-size: 1.2rem;
        }
        
        .stat-card h3 {
            font-size: 0.8rem;
            margin: 0 0 0.25rem 0;
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            line-height: 1;
        }

        /* Candidaturas Section */
        .candidaturas-section {
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .filter-tabs {
            display: flex;
            gap: 0.25rem;
            background: var(--white);
            padding: 0.2rem;
            border-radius: 30px;
            box-shadow: var(--box-shadow-card);
        }

        .filter-tab {
            padding: 0.4rem 1rem;
            border-radius: 30px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.85rem;
        }

        .filter-tab.active {
            background: var(--primary-gradient);
            color: var(--white);
            box-shadow: 0 2px 6px rgba(62, 180, 137, 0.3);
        }

        /* Candidatura Card - Mais compacto */
        .candidatura-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: var(--box-shadow-card);
            transition: var(--transition);
            border: 1px solid var(--medium-gray);
            position: relative;
            overflow: hidden;
        }

        .candidatura-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary-gradient);
        }

        .candidatura-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-hover);
            border-color: var(--primary-light);
        }

        .candidatura-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 1rem;
            position: relative;
        }

        .empresa-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
            padding-right: 100px; /* Espaço para o status */
        }

        /* Modificando o posicionamento do status */
        .candidatura-status {
            position: absolute;
            right: 45px; /* Espaço para o X */
            top: 0;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: var(--box-shadow-card);
            border: 2px solid transparent;
            transition: var(--transition);
            white-space: nowrap;
        }

        /* Ajustando responsividade do status */
        @media (max-width: 768px) {
            .candidatura-header {
                flex-direction: column;
                gap: 0.75rem;
            }

            .empresa-info {
                padding-right: 0;
            }

            .candidatura-status {
                position: relative;
                right: auto;
                top: auto;
                align-self: flex-start;
                margin-top: 0.5rem;
            }
        }

        .empresa-logo {
            width: 50px;
            height: 50px;
            border-radius: var(--border-radius);
            object-fit: cover;
            background: var(--light-gray);
            border: 2px solid var (--medium-gray);
            transition: var(--transition);
        }

        .empresa-logo:hover {
            transform: scale(1.05);
        }

        .empresa-details h3 {
            margin: 0 0 0.25rem 0;
            color: var(--text-primary);
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.3;
        }

        .empresa-details p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .empresa-details p::before {
            content: '';
            width: 4px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 50%;
        }

        /* Status badges - Cores melhoradas */
        .candidatura-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: var(--box-shadow-card);
            border: 2px solid transparent;
            transition: var(--transition);
            max-width: calc(100% - 50px); /* Limita a largura do status */
            word-wrap: break-word;
        }

        .status-em-analise {
            background: var(--warning-light);
            color: #c2410c;
            border-color: var(--warning);
        }

        .status-aprovado {
            background: #d1fae5;
            color: #065f46;
            border-color: var(--success);
        }

        .status-rejeitado {
            background: #fee2e2;
            color: #991b1b;
            border-color: var(--danger);
        }

        .status-entrevista {
            background: var(--info-light);
            color: #1e40af;
            border-color: var(--info);
        }

        .candidatura-body {
            margin-bottom: 1rem;
        }

        .candidatura-body h4 {
            margin: 0 0 0.5rem 0;
            color: var (--text-primary);
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .candidatura-body h4::before {
            content: '';
            width: 3px;
            height: 16px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .candidatura-body p {
            margin: 0;
            color: var(--text-secondary);
            line-height: 1.6;
            max-height: 80px;
            overflow: hidden;
            position: relative;
            font-size: 0.9rem;
        }

        .candidatura-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--medium-gray);
            gap: 1rem;
        }

        .candidatura-date {
            color: var(--text-secondary);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .candidatura-date i {
            color: var(--primary-color);
        }

        /* Buttons - Mais compactos */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.8rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: var(--white);
            box-shadow: 0 2px 8px rgba(62, 180, 137, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(62, 180, 137, 0.4);
        }

        .btn-outline {
            background: var(--white);
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            box-shadow: var(--box-shadow-card);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(62, 180, 137, 0.3);
        }

        /* Empty State - Mais compacto */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-card);
            border: 1px solid var(--medium-gray);
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: var(--secondary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state i {
            font-size: 2rem;
            color: var(--secondary-color);
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 1rem;
            max-width: 350px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 1.7rem;
            }

            .container {
                padding: 1rem 0.75rem;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .candidatura-header {
                flex-direction: column;
                gap: 0.75rem;
            }

            .candidatura-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .filter-tabs {
                width: 100%;
                justify-content: center;
            }

            .stat-card {
                padding: 1rem;
            }

            .candidatura-card {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .hero-section {
                padding: 1.5rem 0;
            }

            .candidatura-card {
                padding: 1rem;
            }

            .stat-card .number {
                font-size: 2rem;
            }

            .hero-title {
                font-size: 1.5rem;
            }
        }

        /* Loading States */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Micro interactions */
        .candidatura-card:hover .empresa-logo {
            transform: scale(1.05) rotate(1deg);
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            background: var(--primary-color);
            color: var(--white);
        }

        /* Accessibility improvements */
        .btn:focus,
        .filter-tab:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Melhorias gerais de espaçamento */
        .section-title {
            margin-bottom: 0;
        }

        .hero-content {
            padding: 0 1rem;
        }

        /* Otimizações para telas menores */
        @media (max-width: 640px) {
            .filter-tab {
                padding: 0.35rem 0.75rem;
                font-size: 0.8rem;
            }
            
            .empresa-logo {
                width: 45px;
                height: 45px;
            }
            
            .empresa-details h3 {
                font-size: 1rem;
            }
            
            .candidatura-status {
                padding: 0.4rem 0.8rem;
                font-size: 0.7rem;
            }
        }

        .cancel-button {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #e53e3e;
            background: rgba(255, 255, 255, 0.95);
            color: #e53e3e;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 15;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(229, 62, 62, 0.15);
            backdrop-filter: blur(10px);
            opacity: 0;
            transform: scale(0.8);
        }


        .cancel-button:hover {
            background: #e53e3e;
            color: white;
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 4px 16px rgba(229, 62, 62, 0.3);
            border-color: #c53030;
        }

        .cancel-button:active {
            transform: scale(0.95) rotate(90deg);
        }

        .cancel-button i {
            transition: all 0.2s ease;
        }

        .cancel-button:hover i {
            transform: rotate(-90deg);
        }

        .candidatura-card:hover .cancel-button {
            opacity: 1;
            transform: scale(1);
        }
        /* Modal de confirmação */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 400px;
            text-align: center;
            animation: slideIn 0.3s ease;
        }

        .modal-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            color: #e53e3e;
        }

        .modal h2 {
            color: #2d3748;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .modal p {
            color: #718096;
            margin-bottom: 2rem;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .modal-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cancel {
            background: white;
            border: 2px solid #e2e8f0;
            color: #718096;
        }

        .btn-cancel:hover {
            background: #f7fafc;
        }

        .btn-confirm {
            background: #e53e3e;
            border: none;
            color: white;
        }

        .btn-confirm:hover {
            background: #c53030;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translate(-50%, -60%); opacity: 0; }
            to { transform: translate(-50%, -50%); opacity: 1; }
        }

        @media (max-width: 768px) {
            .cancel-button {
                top: 12px;
                right: 12px;
                width: 32px;
                height: 32px;
                font-size: 12px;
                opacity: 1; /* Sempre visível em mobile */
                transform: scale(1);
            }
        }

        /* Efeito de pulse sutil quando aparece */
        @keyframes pulseIn {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .candidatura-card:hover .cancel-button {
            animation: pulseIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="../fotos/sam30-13.png" alt="SAM Emprego Logo">
            </div>
            <div class="nav-container">
                <nav class="nav-menu">
                    <a href="job_search_page.php">Vagas</a>
                    <a href="curriculums.php">Meu Currículo</a>
                    <a href="minhas_candidaturas.php" class="active">Candidaturas</a>
                    <a href="painel_candidato.php">Perfil</a>
                </nav>
            </div>
            <div class="user-section">
                <div class="user-dropdown" id="userDropdownToggle">
                    <div class="user-avatar">
                        <img src="../icones/icons-sam-19.svg" alt="" width="40">
                    </div>
                    <span><?php echo htmlspecialchars($candidato['nome'] ?? 'Candidato'); ?></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                    
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <a href="painel_candidato.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            Meu Perfil
                        </a>
                        <a href="editar_perfil.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            Configurações
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
            <div class="settings-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3EB489" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </div>
        </div>
    </header>

    <div class="container">
        <!-- Stats Cards -->
        <div class="stats-container fade-in">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3>Total de Candidaturas</h3>
                <div class="number"><?php echo $totalCandidaturas; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Em Processo</h3>
                <div class="number"><?php echo $emProcesso; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Aprovadas</h3>
                <div class="number"><?php echo $aprovadas; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h3>Rejeitadas</h3>
                <div class="number"><?php echo $rejeitadas; ?></div>
            </div>
        </div>

        <!-- Candidaturas Section -->
        <section class="candidaturas-section">
            <div class="section-header fade-in">
                <h2 class="section-title">Suas Candidaturas</h2>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all">Todas</button>
                    <button class="filter-tab" data-filter="processo">Em Análise</button>
                    <button class="filter-tab" data-filter="aprovado">Aprovadas</button>
                    <button class="filter-tab" data-filter="rejeitado">Rejeitadas</button>
                </div>
            </div>

            <?php if ($tabelas_existem && $temCandidaturas): ?>
                <?php foreach ($candidaturas as $index => $candidatura): ?>
                    <div class="candidatura-card fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s" data-status="<?php echo strtolower($candidatura['status'] ?? 'em-analise'); ?>" data-candidatura-id="<?php echo $candidatura['id']; ?>">
                        <div class="cancel-button" onclick="showCancelModal(<?php echo $candidatura['id']; ?>)">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="candidatura-header">
                            <div class="empresa-info">
                                <img src="<?php echo htmlspecialchars($candidatura['empresa_logo'] ?? '../icones/default-company.png'); ?>" 
                                     alt="Logo da empresa" 
                                     class="empresa-logo">
                                <div class="empresa-details">
                                    <h3><?php echo htmlspecialchars($candidatura['vaga_titulo']); ?></h3>
                                    <p><?php echo htmlspecialchars($candidatura['empresa_nome']); ?></p>
                                </div>
                            </div>
                            <?php 
                            $status = strtolower($candidatura['status'] ?? 'em análise');
                            $status_class = '';
                            
                            switch ($status) {
                                case 'aprovado':
                                case 'aprovada':
                                    $status_class = 'status-aprovado';
                                    break;
                                case 'rejeitado':
                                case 'rejeitada':
                                    $status_class = 'status-rejeitado';
                                    break;
                                case 'entrevista':
                                    $status_class = 'status-entrevista';
                                    break;
                                default:
                                    $status_class = 'status-em-analise';
                            }
                            ?>
                            <span class="candidatura-status <?php echo $status_class; ?>">
                                <?php echo ucfirst(htmlspecialchars($candidatura['status'] ?? 'Em análise')); ?>
                            </span>
                        </div>

                        <div class="candidatura-body">
                            <h4><i class="fas fa-file-alt"></i> Descrição da Vaga</h4>
                            <p><?php echo nl2br(htmlspecialchars(substr($candidatura['vaga_descricao'], 0, 200))); ?><?php echo strlen($candidatura['vaga_descricao']) > 200 ? '...' : ''; ?></p>
                        </div>

                        <div class="candidatura-footer">
                            <div class="candidatura-date">
                                <i class="far fa-calendar-alt"></i>
                                Candidatura realizada em: 
                                <?php 
                                $data = new DateTime($candidatura['data_candidatura']);
                                echo $data->format('d/m/Y H:i'); 
                                ?>
                            </div>
                            <a href="job_view_page.php?id=<?php echo $candidatura['vaga_id']; ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i> Ver Detalhes
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state fade-in">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Nenhuma candidatura encontrada</h3>
                    <p>Você ainda não se candidatou para nenhuma vaga. Explore as oportunidades disponíveis e comece sua jornada profissional conosco!</p>
                    <a href="job_search_page.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Explorar Vagas
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-exclamation-circle fa-4x"></i>
            </div>
            <h2>Cancelar Candidatura</h2>
            <p>Tem certeza que deseja cancelar sua candidatura? Esta ação não poderá ser desfeita.</p>
            <div class="modal-buttons">
                <button class="modal-btn btn-cancel" onclick="closeModal()">Não, manter</button>
                <button class="modal-btn btn-confirm" onclick="confirmCancelamento()">Sim, cancelar</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="../js/dropdown.js"></script>
    <script>
        // Enhanced animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Fade-in animation with stagger effect
            const fadeElements = document.querySelectorAll('.fade-in');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, 100 * index);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            fadeElements.forEach(el => {
                observer.observe(el);
            });

// Filter functionality
const filterTabs = document.querySelectorAll('.filter-tab');
            const candidaturaCards = document.querySelectorAll('.candidatura-card');

            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const filter = this.dataset.filter;

                    // Update active tab
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    // Filter cards with animation
                    candidaturaCards.forEach((card, index) => {
                        const cardStatus = card.dataset.status;
                        let shouldShow = false;

                        switch (filter) {
                            case 'all':
                                shouldShow = true;
                                break;
                            case 'processo':
                                shouldShow = cardStatus === 'em análise' || cardStatus === 'entrevista';
                                break;
                            case 'aprovado':
                                shouldShow = cardStatus === 'aprovado' || cardStatus === 'aprovada';
                                break;
                            case 'rejeitado':
                                shouldShow = cardStatus === 'rejeitado' || cardStatus === 'rejeitada';
                                break;
                        }

                        if (shouldShow) {
                            card.style.display = 'block';
                            setTimeout(() => {
                                card.style.opacity = '1';
                                card.style.transform = 'translateY(0)';
                            }, index * 50);
                        } else {
                            card.style.opacity = '0';
                            card.style.transform = 'translateY(-20px)';
                            setTimeout(() => {
                                card.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });

            // Smooth hover effects for cards
            candidaturaCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Animate stats numbers on page load
            const statNumbers = document.querySelectorAll('.stat-card .number');
            statNumbers.forEach(number => {
                const finalValue = parseInt(number.textContent);
                let currentValue = 0;
                const increment = finalValue / 50;
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        number.textContent = finalValue;
                        clearInterval(timer);
                    } else {
                        number.textContent = Math.floor(currentValue);
                    }
                }, 30);
            });

            // Add click animation to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');

                    this.appendChild(ripple);

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Status badge pulse animation for "em processo"
            const emProcessoCards = document.querySelectorAll('[data-status="em análise"], [data-status="entrevista"]');
            emProcessoCards.forEach(card => {
                const status = card.querySelector('.candidatura-status');
                if (status) {
                    status.classList.add('pulse');
                }
            });

            // Lazy loading for company logos
            const logos = document.querySelectorAll('.empresa-logo');
            const logoObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            logoObserver.unobserve(img);
                        }
                    }
                });
            });

            logos.forEach(logo => {
                logoObserver.observe(logo);
            });

            // Add search functionality (if needed in future)
            function addSearchFunctionality() {
                const searchInput = document.createElement('input');
                searchInput.type = 'text';
                searchInput.placeholder = 'Buscar candidaturas...';
                searchInput.className = 'search-input';
                
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    candidaturaCards.forEach(card => {
                        const title = card.querySelector('.empresa-details h3').textContent.toLowerCase();
                        const company = card.querySelector('.empresa-details p').textContent.toLowerCase();
                        
                        if (title.includes(searchTerm) || company.includes(searchTerm)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // Performance optimization: Debounce scroll events
            let ticking = false;
            function updateScrollPosition() {
                // Add scroll-based animations here if needed
                ticking = false;
            }

            window.addEventListener('scroll', function() {
                if (!ticking) {
                    requestAnimationFrame(updateScrollPosition);
                    ticking = true;
                }
            });

            // Add keyboard navigation support
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    document.body.classList.add('keyboard-navigation');
                }
            });

            document.addEventListener('mousedown', function() {
                document.body.classList.remove('keyboard-navigation');
            });

            // Error handling for images
            logos.forEach(logo => {
                logo.addEventListener('error', function() {
                    this.src = '../icones/default-company.png';
                });
            });

            // Add tooltips for status badges
            const statusBadges = document.querySelectorAll('.candidatura-status');
            statusBadges.forEach(badge => {
                const status = badge.textContent.toLowerCase().trim();
                let tooltip = '';
                
                switch (status) {
                    case 'em análise':
                        tooltip = 'Sua candidatura está sendo analisada pela empresa';
                        break;
                    case 'entrevista':
                        tooltip = 'Você foi selecionado para a próxima etapa';
                        break;
                    case 'aprovado':
                    case 'aprovada':
                        tooltip = 'Parabéns! Sua candidatura foi aprovada';
                        break;
                    case 'rejeitado':
                    case 'rejeitada':
                        tooltip = 'Infelizmente sua candidatura não foi selecionada';
                        break;
                }
                
                if (tooltip) {
                    badge.title = tooltip;
                }
            });

            // Auto-refresh functionality (optional)
            function autoRefresh() {
                // Check for updates every 5 minutes
                setInterval(() => {
                    // You can implement an AJAX call here to check for status updates
                    console.log('Checking for candidatura updates...');
                }, 300000); // 5 minutes
            }

            // Initialize auto-refresh if needed
            // autoRefresh();

            // Print functionality
            window.printCandidaturas = function() {
                window.print();
            };

            console.log('Minhas Candidaturas page loaded successfully');
        });

        // CSS for ripple effect
        const rippleCSS = `
            .ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
                pointer-events: none;
            }

            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }

            .keyboard-navigation *:focus {
                outline: 2px solid var(--primary-color) !important;
                outline-offset: 2px !important;
            }

            .search-input {
                padding: 0.75rem 1rem;
                border: 2px solid var(--medium-gray);
                border-radius: 50px;
                font-size: 1rem;
                transition: var(--transition);
                width: 300px;
                background: var(--white);
            }

            .search-input:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(62, 180, 137, 0.1);
            }

            @media (max-width: 768px) {
                .search-input {
                    width: 100%;
                }
            }
        `;

        // Inject CSS
        const style = document.createElement('style');
        style.textContent = rippleCSS;
        document.head.appendChild(style);

        let candidaturaIdToCancel = null;

        function showCancelModal(candidaturaId) {
            candidaturaIdToCancel = candidaturaId;
            document.getElementById('confirmModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
            candidaturaIdToCancel = null;
        }

        function confirmCancelamento() {
            if (candidaturaIdToCancel) {
                fetch('cancelar_candidatura.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `candidatura_id=${candidaturaIdToCancel}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const card = document.querySelector(`[data-candidatura-id="${candidaturaIdToCancel}"]`);
                        card.style.animation = 'fadeOut 0.3s ease forwards';
                        setTimeout(() => {
                            card.remove();
                            if (document.querySelectorAll('.candidatura-card').length === 0) {
                                location.reload(); // Recarrega se não houver mais candidaturas
                            }
                        }, 300);
                    } else {
                        alert('Erro ao cancelar candidatura: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    alert('Erro ao processar a requisição: ' + error);
                })
                .finally(() => {
                    closeModal();
                });
            }
        }

        // Fechar modal quando clicar fora
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                closeModal();
            }
        }
    </script>
</body>
</html>