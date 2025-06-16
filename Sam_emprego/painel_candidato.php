<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado como candidato
if (!isset($_SESSION['candidato_id'])) {
    // Redireciona para a página de login
    header("Location: login.php");
    exit();
}

// Recupera mensagem de sucesso, se existir
$mensagem_sucesso = $_SESSION['mensagem_sucesso'] ?? '';
unset($_SESSION['mensagem_sucesso']);

// Recupera os dados do candidato
$candidato_id = $_SESSION['candidato_id'];
$stmt = $conn->prepare("SELECT * FROM candidatos WHERE id = ?");
$stmt->bind_param("i", $candidato_id);
$stmt->execute();
$result = $stmt->get_result();
$candidato = $result->fetch_assoc();

// Impede acesso se o perfil não estiver completo
if (isset($candidato['perfil_completo']) && $candidato['perfil_completo'] == 0) {
    header("Location: job_register_page.php");
    exit();
}

// Recupera as candidaturas do candidato (se a tabela existir)
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
    $query = "SELECT c.*, v.titulo as vaga_titulo, empresa_id
              FROM candidaturas c 
              JOIN vagas v ON c.vaga_id = v.id 
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

foreach ($candidaturas as $candidatura) {
    $status = strtolower($candidatura['status'] ?? '');
    if ($status == 'em análise' || $status == 'entrevista') {
        $emProcesso++;
    } elseif ($status == 'aprovado' || $status == 'aprovada') {
        $aprovadas++;
    }
}

// Calcula o progresso do perfil
$progress = 20; // Base progress (apenas por ter criado a conta)
if (!empty($candidato['telefone'])) $progress += 20;
if (!empty($candidato['data_nascimento'])) $progress += 20;
if (!empty($candidato['curriculo_path'] ?? $candidato['cv_anexo'] ?? '')) $progress += 40;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="" href="sam2-05.png">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_header.css">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_search.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <title>SAM - Painel do Candidato</title>
    <style>
        :root {
            --primary-color: #3EB489;
            --primary-light: #4fc89a;
            --primary-dark: #339873;
            --secondary-color: rgb(84, 115, 146);
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
            position: sticky;
            top: 0;
            z-index: 100;
            width: 100%;
            box-shadow: none;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            height: 80px;
        }

        .logo img {
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

        /* Estilos para a seção de usuário */
        .user-section {
            display: flex;
            align-items: center;
        }



        .settings-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #f5f5f5;
            border: 2px solid #3EB489;
            color: #3EB489;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Welcome section */
        .welcome-section {
            background: linear-gradient(135deg, white 0%, #f8f9fa 100%);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
            border-left: 5px solid var(--primary-color);
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(62, 180, 137, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .welcome-message {
            font-size: 1.8rem;
            color: var(--secondary-color);
            margin-bottom: 10px;
            position: relative;
            font-weight: 600;
        }

        .welcome-message span {
            color: var(--primary-color);
        }

        .welcome-subtitle {
            color: var(--dark-gray);
            font-size: 1.1rem;
            max-width: 80%;
        }

        /* Alerts */
        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-left: 4px solid var(--primary-color);
            color: #155724;
        }

        /* Grid layout */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        /* Cards */
        .panel-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            overflow: hidden;
            transition: var(--transition);
            height: fit-content;
        }

        .panel-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .panel-header {
            background-color: var(--primary-color);
            color: white;
            padding: 16px 20px;
            font-size: 1.2rem;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-body {
            padding: 25px;
        }

        /* Stats section */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
       
        .stat-card {
            background: linear-gradient(45deg, var(--primary-color), var(--primary-light));
            color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(62, 180, 137, 0.2);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(62, 180, 137, 0.3);
        }
        
        .stat-card h3 {
            font-size: 0.9rem;
            margin: 0 0 5px 0;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }

        /* Profile items */
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .profile-item {
            margin-bottom: 15px;
            padding: 15px;
            background-color: var(--light-gray);
            border-radius: 8px;
            transition: var(--transition);
        }

        .profile-item:hover {
            background-color: #e6f7f2;
        }

        .profile-label {
            display: block;
            margin-bottom: 8px;
            color: var(--secondary-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .profile-value {
            display: block;
            font-size: 1.1rem;
            color: #333;
            word-break: break-word;
        }

        /* Profile progress */
        .profile-progress {
            margin-top: 25px;
            padding: 20px;
            background-color: var(--light-gray);
            border-radius: 10px;
        }
        
        .progress-title {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-bar-container {
            height: 10px;
            background-color: var(--medium-gray);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 5px;
            transition: width 0.5s ease;
        }

        /* CV section */
        .cv-container {
            display: flex;
            align-items: center;
            background-color: var(--light-gray);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .cv-container:hover {
            background-color: #e6f7f2;
            transform: translateY(-3px);
        }

        .cv-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 1.2rem;
        }

        .cv-info {
            flex-grow: 1;
        }

        .cv-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--secondary-color);
        }

        .cv-meta {
            font-size: 0.9rem;
            color: #777;
        }

        /* Table styles */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, 
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: var(--light-gray);
            font-weight: 600;
            color: var(--secondary-color);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tr:hover {
            background-color: #f5f8fa;
        }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background-color: #d4f5e2;
            color: var(--primary-dark);
        }

        .badge-warning {
            background-color: #fef2d9;
            color: #f39c12;
        }

        .badge-danger {
            background-color: #fcded9;
            color: #e74c3c;
        }

        /* Candidatura item */
        .candidatura-item {
            border: none;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }
        
        .candidatura-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        
        .candidatura-item h3 {
            margin-top: 0;
            margin-bottom: 12px;
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #666;
            background-color: var(--light-gray);
            border-radius: 10px;
            transition: var(--transition);
        }

        .empty-state:hover {
            background-color: #e6f7f2;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            display: block;
        }

        .empty-state p {
            margin-top: 10px;
            margin-bottom: 20px;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(62, 180, 137, 0.4);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Dropdown styles */
        .dropdown-item {
            // ...existing code...
        }

        /* Add this new style for Font Awesome icons */
        .fas, .far, .fa, .svg-inline--fa {
            color: var(--primary-color) !important;
            fill: var(--primary-color) !important;
        }
        
        /* E mantenha as exceções */
        .stat-card i,
        .stat-card .svg-inline--fa,
        .cv-icon i,
        .cv-icon .svg-inline--fa,
        .btn i,
        .btn .svg-inline--fa,
        .settings-icon i,
        .settings-icon .svg-inline--fa,
        .dropdown-arrow {  /* Added this exception */
            color: inherit !important;
            fill: inherit !important;
        }

        /* Add specific style for dropdown arrow */
        .dropdown-arrow {
            color: white !important;
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-subtitle {
                max-width: 100%;
            }
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
                    <a href="minhas_candidaturas.php">Candidaturas</a>
                    <a href="painel_candidato.php"class="active">Perfil</a>
                </nav>
            </div>
            <div class="user-section">
                <div class="user-dropdown" id="userDropdownToggle">
                    <div class="user-avatar">
                        <img src="../icones/icons-sam-19.svg" alt="" width="40">
                    </div>
                    <span><?php echo htmlspecialchars($candidato['nome'] ?? 'Candidato'); ?></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                    
                    <!-- Dropdown Menu -->
                    <div class="dropdown-menu" id="userDropdownMenu">
                        <a href="painel_candidato.php" class="dropdown-item">
                            <i class="fas fa-user"></i class="dropdown-item-1">
                            Meu Perfil
                        </a>
                        <a href="editar_perfil.php" class="dropdown-item">
                            <i class="fas fa-cog"></i class="dropdown-item-1">
                            Configurações
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i class="dropdown-item-1">
                            Logout
                        </a>
                    </div>
                </div>
                
                <div class="settings-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3EB489" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensagem_sucesso); ?>
            </div>
        <?php endif; ?>
        
        <div class="welcome-section fade-in">
            <div class="welcome-message">
                Bem-vindo, <span><?php echo htmlspecialchars($candidato['nome'] ?? 'Candidato'); ?></span>!
            </div>
            <p class="welcome-subtitle">Gerencie seu perfil profissional e acompanhe todas as suas candidaturas de forma eficiente e organizada.</p>
        </div>
        
        <div class="content-grid">
            <div class="panel-section fade-in">
                <div class="panel-header">
                    <div>Dados Pessoais</div>
                    <a href="editar_perfil.php" class="btn btn-outline" style="background-color: white; color: var(--primary-color); padding: 6px 15px; font-size: 0.9rem;">
                        <i class="fas fa-edit"></i> Editar Perfil
                    </a>
                </div>
                <div class="panel-body">
                    <!-- Estatísticas do perfil -->
                    <div class="stats-container">
                        <div class="stat-card">
                            <h3>Total de Candidaturas</h3>
                            <div class="number"><?php echo $totalCandidaturas; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <h3>Em Processo</h3>
                            <div class="number"><?php echo $emProcesso; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <h3>Aprovadas</h3>
                            <div class="number"><?php echo $aprovadas; ?></div>
                        </div>
                    </div>
                
                    <div class="profile-grid">
                        <div class="profile-item">
                            <span class="profile-label">Nome Completo</span>
                            <div class="profile-value"><?php echo htmlspecialchars($candidato['nome'] ?? 'Não informado'); ?></div>
                        </div>
                        
                        <div class="profile-item">
                            <span class="profile-label">E-mail</span>
                            <div class="profile-value"><?php echo htmlspecialchars($candidato['email'] ?? 'Não informado'); ?></div>
                        </div>
                        
                        <div class="profile-item">
                            <span class="profile-label">Telefone</span>
                            <div class="profile-value"><?php echo htmlspecialchars($candidato['telefone'] ?? 'Não informado'); ?></div>
                        </div>
                        
                        <div class="profile-item">
                            <span class="profile-label">Data de Nascimento</span>
                            <div class="profile-value">
                                <?php 
                                if (!empty($candidato['data_nascimento'])) {
                                    $data = new DateTime($candidato['data_nascimento']);
                                    echo $data->format('d/m/Y');
                                } else {
                                    echo 'Não informado';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="profile-item">
                            <span class="profile-label">Endereço</span>
                            <div class="profile-value"><?php echo htmlspecialchars($candidato['endereco'] ?? 'Não informado'); ?></div>
                        </div>
                        
                        <div class="profile-item">
                            <span class="profile-label">Status da Conta</span>
                            <div class="profile-value">
                                <?php 
                                $status = $candidato['status'] ?? 'Pendente';
                                $badge_class = '';
                                
                                switch ($status) {
                                    case 'Ativo':
                                        $badge_class = 'badge-success';
                                        break;
                                    case 'Inativo':
                                        $badge_class = 'badge-danger';
                                        break;
                                    case 'Pendente':
                                        $badge_class = 'badge-warning';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="profile-progress">
                        <div class="progress-title">
                            <span>Perfil completo</span>
                            <span><strong><?php echo $progress; ?>%</strong></span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="panel-section fade-in">
                <div class="panel-header">
                    <div>Informações Profissionais</div>
                </div>
                <div class="panel-body">
                    <div class="profile-item">
                        <span class="profile-label">Formação Acadêmica</span>
                        <div class="profile-value"><?php echo nl2br(htmlspecialchars($candidato['formacao'] ?? 'Não informado')); ?></div>
                    </div>
                    
                    <div class="profile-item">
                        <span class="profile-label">Experiência Profissional</span>
                        <div class="profile-value"><?php echo nl2br(htmlspecialchars($candidato['experiencia'] ?? 'Não informado')); ?></div>
                    </div>
                    
                    <div class="profile-item">
                        <span class="profile-label">Habilidades</span>
                        <div class="profile-value"><?php echo nl2br(htmlspecialchars($candidato['habilidades'] ?? 'Não informado')); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="panel-section fade-in">
                <div class="panel-header">
                    <div>Currículo</div>
                </div>
                <div class="panel-body">
                    <?php 
                    $cv_path = $candidato['curriculo_path'] ?? $candidato['cv_anexo'] ?? null;
                    
                    if (!empty($cv_path)): 
                    ?>
                        <div class="cv-container">
                            <div class="cv-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="cv-info">
                                <div class="cv-name">Meu Currículo</div>
                                <div class="cv-meta">
                                    <?php 
                                    $pathinfo = pathinfo($cv_path);
                                    echo htmlspecialchars($pathinfo['basename']);
                                    ?>
                                </div>
                            </div>
                            <div>
                                <a href="<?php echo htmlspecialchars($cv_path); ?>" target="_blank" class="btn btn-outline">
                                    <i class="fas fa-eye"></i> Visualizar
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-upload"></i>
                            <p>Você ainda não enviou o seu currículo.</p>
                            <a href="atualizar_curriculo.php" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Enviar Currículo
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($tabelas_existem && $temCandidaturas): ?>
            <div class="panel-section fade-in">
                <div class="panel-header">
                    <div>Candidaturas Recentes</div>
                    <a href="listar_vagas.php" class="btn btn-outline" style="background-color: white; color: var(--primary-color); padding: 6px 15px; font-size: 0.9rem;">
                        <i class="fas fa-search"></i> Explorar Vagas
                    </a>
                </div>
                <div class="panel-body">
                    <?php if (count($candidaturas) > 0): ?>
                        <div style="max-height: 500px; overflow-y: auto; padding-right: 10px;">
                            <?php foreach ($candidaturas as $candidatura): ?>
                                <div class="candidatura-item">
                                    <h3><?php echo htmlspecialchars($candidatura['vaga_titulo']); ?></h3>
                                    
                                    <p>
                                        <strong>Empresa:</strong> <?php echo htmlspecialchars($candidatura['empresa']); ?>
                                    </p>
                                    
                                    <p>
                                        <strong>Data da candidatura:</strong>
                                        <?php 
                                        $data = new DateTime($candidatura['data_candidatura']);
                                        echo $data->format('d/m/Y H:i'); 
                                        ?>
                                    </p>
                                    
                                    <p>
                                        <strong>Status:</strong>
                                        <?php 
                                        $status = $candidatura['status'] ?? 'Em análise';
                                        $badge_class = '';
                                        
                                        switch ($status) {
                                            case 'Aprovado':
                                            case 'Aprovada':
                                                $badge_class = 'badge-success';
                                                break;
                                            case 'Rejeitado':
                                            case 'Rejeitada':
                                                $badge_class = 'badge-danger';
                                                break;
                                            default:
                                                $badge_class = 'badge-warning';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </p>
                                    <a href="ver_vaga.php?id=<?php echo $candidatura['vaga_id']; ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-eye"></i> Ver Vaga
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>Você ainda não se candidatou para nenhuma vaga.</p>
                            <a href="listar_vagas.php" class="btn btn-primary">
                                <i class="fas fa-briefcase"></i> Explorar Vagas
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Efeito de fade-in para os elementos
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
    <script src="../js/dropdown.js"></script>
</body>
</html>