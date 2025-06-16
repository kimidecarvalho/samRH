<?php
    session_start();
    if (!isset($_SESSION["empresa_id"])) {
        header("Location: login.php");
        exit;
    }

    require_once 'config/database.php';

    // Buscar informações da empresa
    try {
        $stmt = $pdo->prepare("SELECT * FROM empresas_recrutamento WHERE id = ?");
        $stmt->execute([$_SESSION['empresa_id']]);
        $empresa = $stmt->fetch();
        
        // Buscar vagas da empresa
        $stmt = $pdo->prepare("SELECT * FROM vagas WHERE empresa_id = ? ORDER BY data_publicacao DESC");
        $stmt->execute([$_SESSION['empresa_id']]);
        $vagas = $stmt->fetchAll();
        
        // Contar candidaturas totais (exemplo)
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM candidaturas WHERE empresa_id = ?");
        $stmt->execute([$_SESSION['empresa_id']]);
        $totalCandidaturas = $stmt->fetch()['total'] ?? 0;
        
        // Contar candidaturas novas dos últimos 7 dias (exemplo)
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM candidaturas WHERE empresa_id = ? AND data_candidatura >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute([$_SESSION['empresa_id']]);
        $novasCandidaturas = $stmt->fetch()['total'] ?? 0;
    } catch (PDOException $e) {
        $erro = "Erro ao carregar dados: " . $e->getMessage();
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel da Empresa</title>
    <link rel="stylesheet" href="../all.css/emprego.css/emp_search.css">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #3EB489;
            --primary-light: #e6f7f1;
            --secondary-color: #4361ee;
            --text-color: #333;
            --light-text: #666;
            --lighter-text: #888;
            --danger-color: #ff4d6d;
            --warning-color: #ffbe0b;
            --success-color: #06d6a0;
            --gray-bg: #f5f7fa;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
            --primary-light: #4fc89a;
            --primary-dark: #339873;
            --secondary-color:rgb(84, 115, 146);
            --light-gray: #f5f7fa;
            --medium-gray: #e9ecef;
            --dark-gray: #6c757d;
            --box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            --border-radius: 12px;
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


        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        /* Seção de boas-vindas melhorada */
        .welcome-section {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            border-left: 4px solid var(--primary-color);
        }
        
        .welcome-section::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background-color: var(--primary-light);
            border-radius: 0 0 0 100%;
            z-index: 0;
            opacity: 0.6;
        }
        
        .welcome-section h1 {
            color: var(--primary-dark);
            margin-bottom: 10px;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
        }
        
        .welcome-section p {
            color: var(--light-text);
            font-size: 1rem;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        /* Área de estatísticas */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            border-bottom: 3px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card:nth-child(1) {
            border-bottom-color: var(--primary-color);
        }
        
        .stat-card:nth-child(2) {
            border-bottom-color: var(--secondary-color);
        }
        
        .stat-card:nth-child(3) {
            border-bottom-color: var(--warning-color);
        }
        
        .stat-card h3 {
            font-size: 0.9rem;
            color: var(--lighter-text);
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }
        
        .stat-trend {
            margin-top: 10px;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }
        
        .stat-trend.up {
            color: var(--success-color);
        }
        
        .stat-trend.down {
            color: var(--danger-color);
        }
        
        .stat-trend i {
            margin-right: 5px;
        }
        
        /* Área de vagas melhorada */
        .jobs-section {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .section-header h2 {
            color: var(--text-color);
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }
        
        .action-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .action-btn i {
            margin-right: 8px;
        }
        
        /* Lista de vagas melhorada */
        .jobs-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .vaga-item {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            transition: var(--transition);
            position: relative;
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }
        
        .vaga-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: var(--primary-light);
        }
        
        .vaga-status {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .status-ativa {
            background-color: #e6f7f1;
            color: var(--success-color);
        }
        
        .status-encerrada {
            background-color: #feecef;
            color: var(--danger-color);
        }
        
        .status-pausada {
            background-color: #fff4e0;
            color: var(--warning-color);
        }
        
        .vaga-item h3 {
            margin-top: 5px;
            margin-bottom: 15px;
            color: var(--text-color);
            font-size: 1.1rem;
            line-height: 1.4;
            font-weight: 600;
            padding-right: 60px;
        }
        
        .vaga-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .vaga-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: var(--light-text);
        }
        
        .vaga-meta-item i {
            margin-right: 5px;
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .vaga-actions {
            margin-top: auto;
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            transition: var(--transition);
            flex: 1;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .btn-view {
            background-color: var(--secondary-color);
        }
        
        .btn-view:hover {
            background-color: #3251d4;
        }
        
        .btn-edit {
            background-color: #6c757d;
        }
        
        .btn-edit:hover {
            background-color: #5a6268;
        }
        
        /* Mensagem quando não há vagas */
        .no-vagas {
            text-align: center;
            padding: 30px;
            background-color: var(--primary-light);
            border-radius: 10px;
            color: var(--text-color);
        }
        
        .no-vagas i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            opacity: 0.7;
        }
        
        .no-vagas h3 {
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .no-vagas p {
            margin: 5px 0;
            color: var(--light-text);
        }
        
        .no-vagas .action-btn {
            margin-top: 20px;
            display: inline-flex;
        }
        
        /* Área de atividades recentes */
        .recent-activity {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--card-shadow);
        }
        
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .icon-candidate {
            background-color: #e6f7f1;
            color: var(--primary-color);
        }
        
        .icon-job {
            background-color: #e0f2fe;
            color: var(--secondary-color);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-content p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--light-text);
        }
        
        .activity-content .highlight {
            color: var(--text-color);
            font-weight: 500;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: var(--lighter-text);
            margin-top: 5px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            color: var(--lighter-text);
            font-size: 0.85rem;
        }
        
        .footer p {
            margin: 0;
        }
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .jobs-list {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .welcome-section {
                padding: 20px;
            }
            
            .welcome-section h1 {
                font-size: 1.5rem;
            }

            .nav-menu {
                gap: 10px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-menu a {
                padding: 6px 10px;
                font-size: 0.9rem;
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
                    <a href="job_search_page_emp.php">Vagas</a>
                    <a href="curriculums.php">Minhas vagas</a>
                    <a href="minhas_candidaturas.php">Candidaturas</a>
                    <a href="painel_candidato.php" class="active">Perfil</a>
                </nav>
            </div>
            <div class="user-section">
                <div class="user-dropdown" id="userDropdownToggle">
                    <div class="user-avatar">
                        <img src="../icones/icons-sam-19.svg" alt="" width="40">
                    </div>
                        <span><?php echo htmlspecialchars($empresa['nome'] ?? $_SESSION['empresa_nome']); ?></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                    
                    <!-- Dropdown Menu -->
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
        <!-- Seção de boas-vindas melhorada -->
        <div class="welcome-section">
            <h1>Bem-vindo(a) ao seu painel, <?php echo htmlspecialchars($empresa['nome'] ?? $_SESSION['empresa_nome']); ?>!</h1>
            <p>Gerencie suas vagas de emprego e acompanhe as candidaturas em um só lugar.</p>
        </div>
        
        <!-- Nova seção de estatísticas -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total de Vagas</h3>
                <p class="stat-number"><?php echo count($vagas); ?></p>
                <span class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 12% este mês
                </span>
            </div>
            
            <div class="stat-card">
                <h3>Candidaturas Recebidas</h3>
                <p class="stat-number"><?php echo $totalCandidaturas ?? 0; ?></p>
                <span class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> 8% esta semana
                </span>
            </div>
            
            <div class="stat-card">
                <h3>Novas Candidaturas</h3>
                <p class="stat-number"><?php echo $novasCandidaturas ?? 0; ?></p>
                <span class="stat-trend">
                    <i class="fas fa-clock"></i> Últimos 7 dias
                </span>
            </div>
        </div>
        
        <!-- Seção de vagas melhorada -->
        <div class="jobs-section">
            <div class="section-header">
                <h2>Suas Vagas</h2>
                <a href="registro_vagas.php" class="action-btn">
                    <i class="fas fa-plus"></i> Nova Vaga
                </a>
            </div>
            
            <?php if (isset($vagas) && count($vagas) > 0): ?>
                <div class="jobs-list">
                    <?php foreach($vagas as $vaga): ?>
                        <div class="vaga-item">
                            <span class="vaga-status status-<?php echo strtolower($vaga['status']) === 'ativa' ? 'ativa' : (strtolower($vaga['status']) === 'encerrada' ? 'encerrada' : 'pausada'); ?>">
                                <?php echo htmlspecialchars($vaga['status']); ?>
                            </span>
                            
                            <h3><?php echo htmlspecialchars($vaga['titulo']); ?></h3>
                            
                            <div class="vaga-meta">
                                <div class="vaga-meta-item">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('d/m/Y', strtotime($vaga['data_publicacao'])); ?>
                                </div>
                                
                                <div class="vaga-meta-item">
                                    <i class="fas fa-user-friends"></i>
                                    <?php echo rand(5, 30); ?> candidatos
                                </div>
                            </div>
                            
                            <div class="vaga-actions">
                                <a href="visualizar_vaga.php?id=<?php echo $vaga['id']; ?>" class="btn-small btn-view">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <a href="editar_vaga.php?id=<?php echo $vaga['id']; ?>" class="btn-small btn-edit">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-vagas">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Nenhuma vaga publicada ainda</h3>
                    <p>Você ainda não possui vagas cadastradas.</p>
                    <p>Comece agora mesmo a encontrar os melhores profissionais para sua empresa!</p>
                    <a href="cadastrar_vaga.php" class="action-btn">
                        <i class="fas fa-plus"></i> Publicar Primeira Vaga
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Nova seção de atividades recentes -->
        <div class="recent-activity">
            <div class="section-header">
                <h2>Atividades Recentes</h2>
            </div>
            
            <ul class="activity-list">
                <?php if (isset($vagas) && count($vagas) > 0): ?>
                    <li class="activity-item">
                        <div class="activity-icon icon-candidate">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="activity-content">
                            <p><span class="highlight">João Silva</span> candidatou-se à vaga de <?php echo htmlspecialchars($vagas[0]['titulo']); ?></p>
                            <div class="activity-time">há 2 horas</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon icon-job">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="activity-content">
                            <p>Você publicou uma nova vaga: <span class="highlight"><?php echo htmlspecialchars($vagas[0]['titulo']); ?></span></p>
                            <div class="activity-time">há 1 dia</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon icon-candidate">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="activity-content">
                            <p><span class="highlight">Maria Oliveira</span> candidatou-se à vaga de <?php echo htmlspecialchars($vagas[0]['titulo']); ?></p>
                            <div class="activity-time">há 2 dias</div>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="activity-item">
                        <div class="activity-icon icon-job">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="activity-content">
                            <p>Não há atividades recentes para exibir.</p>
                            <div class="activity-time">Publique sua primeira vaga para começar!</div>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Rodapé simples -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> SAM - Sistema de Anúncios de Emprego</p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown do usuário (mantido do código original)
            const userDropdown = document.getElementById('user-dropdown');
            const userMenu = document.getElementById('user-menu');
            
            if (userDropdown && userMenu) {
                userDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('visible');
                });
                
                // Fechar dropdown ao clicar fora
                document.addEventListener('click', function(e) {
                    if (userMenu.classList.contains('visible') && 
                        !userDropdown.contains(e.target) && 
                        !userMenu.contains(e.target)) {
                        userMenu.classList.remove('visible');
                    }
                });
            }
        });
    </script>
    <script src="../js/dropdown.js"></script>
</body>
</html>