<?php
session_start();
if (!isset($_SESSION["empresa_id"])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

// Buscar informações da empresa para o header
try {
    $stmt = $pdo->prepare("SELECT * FROM empresas_recrutamento WHERE id = ?");
    $stmt->execute([$_SESSION['empresa_id']]);
    $empresa = $stmt->fetch();
    
    // Buscar todas as candidaturas para as vagas da empresa
    $stmt = $pdo->prepare("
        SELECT 
            c.*, 
            v.titulo as vaga_titulo,
            cd.nome as candidato_nome,
            cd.email as candidato_email,
            cd.telefone as candidato_telefone,
            cd.curriculo_path as candidato_curriculo,
            cd.formacao as candidato_formacao,
            cd.habilidades as candidato_habilidades,
            cd.experiencia as candidato_experiencia,
            cd.data_nascimento as candidato_data_nascimento,
            cd.endereco as candidato_endereco
        FROM candidaturas c
        JOIN vagas v ON c.vaga_id = v.id
        JOIN candidatos cd ON c.candidato_id = cd.id
        WHERE v.empresa_id = ?
        ORDER BY c.data_candidatura DESC
    ");
    $stmt->execute([$_SESSION['empresa_id']]);
    $candidaturas = $stmt->fetchAll();

    // Atualizar o card de cada candidatura para mostrar mais informações
    foreach ($candidaturas as $key => $candidatura) {
        // Formatar a data de nascimento
        if ($candidatura['candidato_data_nascimento']) {
            $data = new DateTime($candidatura['candidato_data_nascimento']);
            $candidaturas[$key]['candidato_data_nascimento_formatada'] = $data->format('d/m/Y');
        }
    }
} catch (PDOException $e) {
    $erro = "Erro ao carregar dados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="" href="sam2-05.png">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_vagas.css">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_search.css">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_header.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #fafbfc;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif
            color: #2d3748;
            line-height: 1.6;
        }

        .candidaturas-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            margin-bottom: 40px;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        .page-header p {
            color: #718096;
            font-size: 1.125rem;
            font-weight: 400;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(62, 180, 137, 0.1);
            border-color: #3EB489;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3EB489;
            margin-bottom: 4px;
        }

        .stat-label {
            color: #718096;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .candidatura-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .candidatura-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border-color: #3EB489;
        }

        .card-header {
            padding: 24px 32px 20px;
            border-bottom: 1px solid #f7fafc;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .card-title h3 {
            color: #1a202c;
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .card-title .job-icon {
            width: 20px;
            height: 20px;
            color: #3EB489;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .status-pendente { 
            background-color: #fef5e7;
            color: #d69e2e;
            border-color: #f6e05e;
        }
        .status-visualizada { 
            background-color: #ebf8ff;
            color: #3182ce;
            border-color: #90cdf4;
        }
        .status-analise { 
            background-color: #faf5ff;
            color: #805ad5;
            border-color: #c4b5fd;
        }
        .status-entrevista { 
            background-color: #fff5f5;
            color: #e53e3e;
            border-color: #feb2b2;
        }
        .status-aprovado { 
            background-color: #f0fff4;
            color: #38a169;
            border-color: #9ae6b4;
        }
        .status-rejeitado { 
            background-color: #fff5f5;
            color: #e53e3e;
            border-color: #feb2b2;
        }

        .card-content {
            padding: 32px;
        }

        .candidate-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 32px;
            margin-bottom: 32px;
        }

        .info-group {
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 12px;
            padding: 24px;
        }

        .info-group h4 {
            color: #2d3748;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-group .section-icon {
            width: 16px;
            height: 16px;
            color: #3EB489;
        }

        .info-item {
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 12px;
            align-items: start;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-label {
            color: #718096;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .info-value {
            color: #2d3748;
            font-size: 0.875rem;
            word-break: break-word;
        }

        .curriculo-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: #3EB489;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .curriculo-link:hover {
            background-color: #2d8a66;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .settings-icon:hover {
            background-color: var(--primary-ultra-light);
            transform: rotate(15deg);
        }

        .application-meta {
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .meta-icon {
            width: 16px;
            height: 16px;
            color: #3EB489;
        }

        .meta-text {
            color: #718096;
            font-size: 0.875rem;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding-top: 24px;
            border-top: 1px solid #f7fafc;
            justify-content:center;
        }

        .action-btn {
            padding: 10px 20px;
            border: 1px solid transparent;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            min-width: 130px;
            justify-content: center;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-analyze {
            border-color: #d69e2e;
            color: #d69e2e;
        }
        .btn-analyze:hover {
            background-color: #d69e2e;
            color: white;
        }

        .btn-interview {
            border-color: #3182ce;
            color: #3182ce;
        }
        .btn-interview:hover {
            background-color: #3182ce;
            color: white;
        }

        .btn-approve {
            border-color: #3EB489;
            color: #3EB489;
        }
        .btn-approve:hover {
            background-color: #3EB489;
            color: white;
        }

        .btn-reject {
            border-color: #e53e3e;
            color: #e53e3e;
        }
        .btn-reject:hover {
            background-color: #e53e3e;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
        }

        .empty-icon {
            width: 64px;
            height: 64px;
            color: #cbd5e0;
            margin: 0 auto 24px;
        }

        .empty-state h3 {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: #718096;
            font-size: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .candidaturas-container {
                padding: 20px 16px;
            }

            .page-header h1 {
                font-size: 1.875rem;
            }

            .card-header {
                padding: 20px 20px 16px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .card-content {
                padding: 20px;
            }

            .candidate-summary {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .info-item {
                grid-template-columns: 1fr;
                gap: 4px;
            }

            .actions {
                flex-direction: column;
            }

            .action-btn {
                min-width: 100%;
            }

            .application-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto; /* Habilita scroll vertical */
            padding: 20px 0; /* Adiciona padding vertical */
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 20px auto; /* Margem para não tocar os limites da tela */
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: none; /* Remove limite de altura */
            overflow-y: visible; /* Remove overflow do conteúdo */
        }

        @media (max-width: 768px) {
            .modal {
                padding: 10px;
            }
            .modal-content {
                margin: 10px auto;
                padding: 20px;
            }
        }

        .interview-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 500;
            color: #2d3748;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .interview-type-options {
            display: flex;
            gap: 15px;
        }

        .type-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .type-option.selected {
            border-color: #3EB489;
            background-color: #f0fff4;
        }

        .type-option i {
            font-size: 24px;
            margin-bottom: 8px;
            color: #3EB489;
        }

        .schedule-btn {
            background-color: #3EB489;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .schedule-btn:hover {
            background-color: #2d8a66;
        }

        #map {
            height: 300px;
            width: 100%;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .map-container {
            position: relative;
        }
        .coordinates-display {
            margin-top: 12px;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95em;
            color: #2d3748;
        }

        .coordinates-display .label {
            font-weight: 600;
            color: #3EB489;
            margin-bottom: 4px;
        }

        .coordinates-display .value {
            font-family: monospace;
            background: #fff;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            display: inline-block;
            margin-top: 4px;
        }

        .address-input-container {
            margin-top: 16px;
        }

        .address-input-container label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .address-input-container textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95em;
            line-height: 1.5;
            resize: vertical;
            min-height: 80px;
            transition: all 0.2s ease;
        }

        .address-input-container textarea:focus {
            outline: none;
            border-color: #3EB489;
            box-shadow: 0 0 0 3px rgba(62, 180, 137, 0.1);
        }

        .address-input-container textarea::placeholder {
            color: #a0aec0;
        }
    </style>
    <title>Candidaturas - SAM Emprego</title>
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
                    <a href="emp_vagas.php">Minhas vagas</a>
                    <a href="empresas_candidaturas.php" class="active">Candidaturas</a>
                    <a href="painel_candidato.php">Perfil</a>
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

    <div class="candidaturas-container">
        <div class="page-header">
            <h1>Candidaturas Recebidas</h1>
            <p>Gerencie e acompanhe todas as candidaturas para suas vagas</p>
        </div>

        <?php if (isset($candidaturas) && !empty($candidaturas)): ?>
            <?php foreach ($candidaturas as $candidatura): ?>
                <div class="candidatura-card" data-candidatura-id="<?php echo $candidatura['id']; ?>">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-briefcase job-icon"></i>
                            <h3><?php echo htmlspecialchars($candidatura['vaga_titulo']); ?></h3>
                        </div>
                        <span class="status-badge status-<?php echo strtolower(str_replace([' ', 'ç'], ['', 'c'], $candidatura['status'])); ?>">
                            <?php echo htmlspecialchars($candidatura['status']); ?>
                        </span>
                    </div>
                    
                    <div class="card-content">
                        <div class="application-meta">
                            <i class="fas fa-calendar meta-icon"></i>
                            <span class="meta-text">
                                Candidatura enviada em <?php echo date('d/m/Y às H:i', strtotime($candidatura['data_candidatura'])); ?>
                            </span>
                        </div>

                        <div class="candidate-summary">
                            <div class="info-group">
                                <h4>
                                    <i class="fas fa-user section-icon"></i>
                                    Dados Pessoais
                                </h4>
                                <div class="info-item">
                                    <span class="info-label">Nome:</span>
                                    <span class="info-value" data-type="nome"><?php echo htmlspecialchars($candidatura['candidato_nome']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value" data-type="email"><?php echo htmlspecialchars($candidatura['candidato_email']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Telefone:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($candidatura['candidato_telefone'] ?? 'Não informado'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Nascimento:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($candidatura['candidato_data_nascimento_formatada'] ?? 'Não informado'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Endereço:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($candidatura['candidato_endereco'] ?? 'Não informado'); ?></span>
                                </div>
                            </div>

                            <div class="info-group">
                                <h4>
                                    <i class="fas fa-graduation-cap section-icon"></i>
                                    Qualificações
                                </h4>
                                <div class="info-item">
                                    <span class="info-label">Formação:</span>
                                    <span class="info-value"><?php echo nl2br(htmlspecialchars($candidatura['candidato_formacao'] ?? 'Não informado')); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Experiência:</span>
                                    <span class="info-value"><?php echo nl2br(htmlspecialchars($candidatura['candidato_experiencia'] ?? 'Não informado')); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Habilidades:</span>
                                    <span class="info-value"><?php echo nl2br(htmlspecialchars($candidatura['candidato_habilidades'] ?? 'Não informado')); ?></span>
                                </div>
                                <?php if ($candidatura['candidato_curriculo']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Currículo:</span>
                                        <span class="info-value">
                                            <a href="<?php echo htmlspecialchars($candidatura['candidato_curriculo']); ?>" 
                                               target="_blank" class="curriculo-link">
                                                <i class="fas fa-file-pdf"></i>
                                                Visualizar Currículo
                                            </a>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="actions">
                            <button class="action-btn btn-analyze" onclick="updateStatus(<?php echo $candidatura['id']; ?>, 'Em análise')">
                                <i class="fas fa-search"></i> 
                                Em análise
                            </button>
                            <button class="action-btn btn-interview" onclick="updateStatus(<?php echo $candidatura['id']; ?>, 'Entrevista')">
                                <i class="fas fa-calendar-check"></i> 
                                Agendar entrevista
                            </button>
                            <button class="action-btn btn-approve" onclick="updateStatus(<?php echo $candidatura['id']; ?>, 'Aprovado')">
                                <i class="fas fa-check"></i> 
                                Aprovar
                            </button>
                            <button class="action-btn btn-reject" onclick="updateStatus(<?php echo $candidatura['id']; ?>, 'Rejeitado')">
                                <i class="fas fa-times"></i> 
                                Rejeitar
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox empty-icon"></i>
                <h3>Nenhuma candidatura encontrada</h3>
                <p>Você ainda não recebeu candidaturas para suas vagas publicadas. Assim que alguém se candidatar, as informações aparecerão aqui.</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="interviewModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2 style="margin-bottom: 20px;">Agendar Entrevista</h2>
            <form class="interview-form" id="interviewForm" onsubmit="handleInterviewSubmit(event)">
                <input type="hidden" id="candidaturaId" name="candidaturaId">
                <input type="hidden" id="tipo" name="tipo" value="presencial">
                
                <div class="form-group">
                    <label>Tipo de Entrevista</label>
                    <div class="interview-type-options">
                        <div class="type-option selected" data-type="presencial">
                            <i class="fas fa-building"></i>
                            <div>Presencial</div>
                        </div>
                        <div class="type-option" data-type="remota">
                            <i class="fas fa-video"></i>
                            <div>Remota (Online)</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Data</label>
                    <input type="date" id="entrevistaData" name="data" required>
                </div>

                <div class="form-group">
                    <label>Hora</label>
                    <input type="time" id="entrevistaHora" name="hora" required>
                </div>

                <div class="form-group" id="localPresencial" style="display: none;">
                    <label>Local da Entrevista</label>
                    <div class="search-container" style="margin-bottom: 10px;">
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="searchInput" placeholder="Digite o endereço para buscar" style="flex: 1; padding: 8px; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <button type="button" id="searchButton" style="padding: 8px 16px; background: #3EB489; color: white; border: none; border-radius: 8px; cursor: pointer;">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="map-container">
                        <div id="map"></div>
                        <div class="coordinates-display">
                            <div class="label">Localização Selecionada:</div>
                            <div class="value" id="coordinates">Clique no mapa para selecionar a localização</div>
                        </div>
                    </div>
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    <div class="address-input-container">
                        <label for="localInput">Endereço Completo:</label>
                        <textarea name="local" id="localInput" placeholder="O endereço será preenchido automaticamente quando você selecionar uma localização no mapa ou buscar por endereço"></textarea>
                    </div>
                </div>

                <div class="form-group" id="linkRemoto" style="display: none;">
                    <label>Link da Reunião</label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="url" name="link" id="meetLinkInput" placeholder="Ex: https://meet.google.com/..." style="flex:1;">
                        <button type="button" id="generateMeetBtn" style="background:#3EB489;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;">Gerar</button>
                    </div>
                    <div id="meetStatus" style="font-size:0.9em;color:#3EB489;margin-top:4px;"></div>
                </div>

                <button type="submit" class="schedule-btn">Confirmar Agendamento</button>
            </form>
        </div>
    </div>

    <script>
    function updateStatus(candidaturaId, newStatus) {
        if (newStatus === 'Entrevista') {
            showInterviewModal(candidaturaId);
            return;
        }
        if (confirm('Deseja alterar o status desta candidatura para "' + newStatus + '"?')) {
            fetch('update_candidatura_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${candidaturaId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao atualizar status: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                alert('Erro ao processar a requisição: ' + error);
            });
        }
    }

    const modal = document.getElementById('interviewModal');
    const closeBtn = document.querySelector('.modal-close');
    const typeOptions = document.querySelectorAll('.type-option');
    const localPresencial = document.getElementById('localPresencial');
    const linkRemoto = document.getElementById('linkRemoto');
    let currentCandidaturaId = null;
    let map;
    let marker;

    function showInterviewModal(candidaturaId) {
        currentCandidaturaId = candidaturaId;
        document.getElementById('candidaturaId').value = candidaturaId;
        localPresencial.style.display = 'block';
        linkRemoto.style.display = 'none';
        modal.style.display = 'block';
        
        // Initialize map if not already initialized
        if (!map) {
            initMap();
        }
    }

    function initMap() {
        // Initialize the map centered on Brazil
        map = L.map('map').setView([-15.7801, -47.9292], 4);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add click event to map
        map.on('click', function(e) {
            updateLocation(e.latlng.lat, e.latlng.lng);
        });

        // Add search functionality
        const searchButton = document.getElementById('searchButton');
        const searchInput = document.getElementById('searchInput');

        searchButton.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });

        function performSearch() {
            const query = searchInput.value.trim();
            if (!query) return;

            // Show loading state
            searchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            searchButton.disabled = true;

            // Use Nominatim for geocoding
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const result = data[0];
                        const lat = parseFloat(result.lat);
                        const lon = parseFloat(result.lon);
                        
                        // Update map view and location
                        map.setView([lat, lon], 16);
                        updateLocation(lat, lon);
                    } else {
                        alert('Endereço não encontrado. Por favor, tente novamente.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao buscar endereço. Por favor, tente novamente.');
                })
                .finally(() => {
                    // Reset button state
                    searchButton.innerHTML = '<i class="fas fa-search"></i> Buscar';
                    searchButton.disabled = false;
                });
        }
    }

    function updateLocation(lat, lng) {
        // Update hidden inputs
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Update coordinates display with more detailed format
        const latFormatted = lat.toFixed(6);
        const lngFormatted = lng.toFixed(6);
        document.getElementById('coordinates').textContent = `Latitude: ${latFormatted}° | Longitude: ${lngFormatted}°`;
        
        // Update or create marker
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }
        
        // Reverse geocode to get address
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(response => response.json())
            .then(data => {
                if (data.display_name) {
                    document.getElementById('localInput').value = data.display_name;
                    document.getElementById('searchInput').value = data.display_name;
                }
            })
            .catch(error => console.error('Error:', error));
    }

    closeBtn.onclick = function() {
        modal.style.display = 'none';
        resetForm();
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
            resetForm();
        }
    }

    typeOptions.forEach(option => {
        option.addEventListener('click', () => {
            typeOptions.forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
            
            const type = option.dataset.type;
            document.getElementById('tipo').value = type;
            
            if (type === 'presencial') {
                localPresencial.style.display = 'block';
                linkRemoto.style.display = 'none';
            } else {
                localPresencial.style.display = 'none';
                linkRemoto.style.display = 'block';
            }
        });
    });

    function resetForm() {
        document.getElementById('interviewForm').reset();
        typeOptions.forEach(opt => opt.classList.remove('selected'));
        localPresencial.style.display = 'none';
        linkRemoto.style.display = 'none';
        document.getElementById('coordinates').textContent = 'Clique no mapa para selecionar a localização';
        document.getElementById('searchInput').value = '';
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
    }

    const generateMeetBtn = document.getElementById('generateMeetBtn');
    const meetLinkInput = document.getElementById('meetLinkInput');
    const meetStatus = document.getElementById('meetStatus');
    
    if (generateMeetBtn) {
        generateMeetBtn.addEventListener('click', async function() {
            meetStatus.textContent = 'Gerando link do Google Meet...';
            try {
                // Coletar dados do formulário
                const candidaturaId = document.getElementById('candidaturaId').value;
                const candidaturaCard = document.querySelector(`.candidatura-card[data-candidatura-id="${candidaturaId}"]`);
                
                if (!candidaturaCard) {
                    console.error('Card da candidatura não encontrado');
                    meetStatus.textContent = 'Erro: Não foi possível encontrar os dados do candidato.';
                    return;
                }

                const emailElement = candidaturaCard.querySelector('.info-value[data-type="email"]');
                const nomeElement = candidaturaCard.querySelector('.info-value[data-type="nome"]');

                if (!emailElement || !nomeElement) {
                    console.error('Elementos de email ou nome não encontrados');
                    meetStatus.textContent = 'Erro: Dados do candidato incompletos.';
                    return;
                }

                const email = emailElement.textContent.trim();
                const nome = nomeElement.textContent.trim();
                const data = document.getElementById('entrevistaData').value;
                const hora = document.getElementById('entrevistaHora').value;
                const titulo = 'Entrevista de Emprego';
                const duracao = 30;

                if (!email || !data || !hora) {
                    meetStatus.textContent = 'Preencha todos os campos obrigatórios.';
                    return;
                }

                console.log('Enviando dados:', { nome, email, data, hora, titulo, duracao });

                const response = await fetch('generate_meet_link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nome, email, data, hora, titulo, duracao })
                });

                const result = await response.json();
                console.log('Resposta do servidor:', result);

                if (result.success) {
                    meetLinkInput.value = result.meet_link;
                    meetStatus.textContent = 'Link gerado com sucesso!';
                } else if (result.auth_url) {
                    meetStatus.innerHTML = 'É necessário autenticar com o Google. <a href="' + result.auth_url + '" target="_blank">Clique aqui para autenticar</a>.';
                } else {
                    meetStatus.textContent = 'Erro: ' + (result.message || 'Não foi possível gerar o link.');
                }
            } catch (e) {
                console.error('Erro ao gerar link:', e);
                meetStatus.textContent = 'Erro ao conectar ao servidor: ' + e.message;
            }
        });
    }

    async function handleInterviewSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Debug: Log dos dados do formulário
        console.log('Dados do formulário:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        try {
            const response = await fetch('agendar_entrevista.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            console.log('Resposta do servidor:', data);
            
            if (data.success) {
                alert('Entrevista agendada com sucesso!');
                location.reload();
            } else {
                alert('Erro ao agendar entrevista: ' + (data.message || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro completo:', error);
            alert('Erro ao processar a requisição: ' + error);
        }
    }
    </script>
    <script src="../js/dropdown.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>