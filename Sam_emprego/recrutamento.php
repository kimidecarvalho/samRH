<?php
// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


include 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados

// Verifica se o usuário está logado e tem um ID válido
if (!isset($_SESSION['id_adm'])) {
    echo "Erro: Usuário não autenticado.";
    exit;
}

// Verifica se o administrador está associado a uma empresa
if (!isset($_SESSION['id_empresa'])) {
    echo "<script>alert('Você precisa criar uma empresa antes de acessar esta página.'); window.location.href='Registro_adm.php';</script>";
    exit;
}

$empresa_id = $_SESSION['id_empresa'];

// 1. Contar Vagas Ativas
$stmt_vagas_ativas = $conn->prepare("SELECT COUNT(*) AS total_vagas_ativas FROM vagas WHERE empresa_id = ? AND status = 'Aberta'");
$stmt_vagas_ativas->bind_param("i", $empresa_id);
$stmt_vagas_ativas->execute();
$result_vagas_ativas = $stmt_vagas_ativas->get_result();
$vagas_ativas = $result_vagas_ativas->fetch_assoc()['total_vagas_ativas'];
$stmt_vagas_ativas->close();

// 2. Contar Candidatos Únicos para vagas da empresa
$stmt_total_candidatos = $conn->prepare("SELECT COUNT(DISTINCT c.candidato_id) AS total_candidatos 
                                          FROM candidaturas c 
                                          JOIN vagas v ON c.vaga_id = v.id 
                                          WHERE v.empresa_id = ?");
$stmt_total_candidatos->bind_param("i", $empresa_id);
$stmt_total_candidatos->execute();
$result_total_candidatos = $stmt_total_candidatos->get_result();
$total_candidatos = $result_total_candidatos->fetch_assoc()['total_candidatos'];
$stmt_total_candidatos->close();

// 3. Contar Entrevistas Esta Semana
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('sunday this week'));

$stmt_entrevistas = $conn->prepare("SELECT COUNT(*) AS total_entrevistas FROM candidaturas c
                                     JOIN vagas v ON c.vaga_id = v.id
                                     WHERE v.empresa_id = ? 
                                     AND c.status = 'Entrevista' 
                                     AND c.entrevista_data BETWEEN ? AND ?");
$stmt_entrevistas->bind_param("iss", $empresa_id, $startOfWeek, $endOfWeek);
$stmt_entrevistas->execute();
$result_entrevistas = $stmt_entrevistas->get_result();
$entrevistas_esta_semana = $result_entrevistas->fetch_assoc()['total_entrevistas'];
$stmt_entrevistas->close();

// 4. Contar Contratações Este Mês
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$stmt_contratacoes = $conn->prepare("SELECT COUNT(*) AS total_contratacoes FROM candidaturas c
                                      JOIN vagas v ON c.vaga_id = v.id
                                      WHERE v.empresa_id = ? 
                                      AND c.status = 'Aprovado' 
                                      AND c.data_candidatura BETWEEN ? AND ?");
$stmt_contratacoes->bind_param("iss", $empresa_id, $startOfMonth, $endOfMonth);
$stmt_contratacoes->execute();
$result_contratacoes = $stmt_contratacoes->get_result();
$contratacoes_este_mes = $result_contratacoes->fetch_assoc()['total_contratacoes'];
$stmt_contratacoes->close();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="all.css/registro3.css">
    <link rel="stylesheet" href="all.css/timer.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAM - Recrutamento</title>
    <style>
        /* Estilos específicos para a página de recrutamento */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #64c2a7;
        }
        
        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #777;
            font-size: 0.9rem;
        }
        
        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            position: relative;
            min-height: 300px;
        }
        
        .chart-card h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.2rem;
        }
        
        .job-listing-table,
        .candidates-table {
            width: 100%;
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .job-listing-table h3,
        .candidates-table h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .view-all {
            font-size: 0.9rem;
            color: #64c2a7;
            text-decoration: none;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background-color: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 500;
            color: #555;
            border-bottom: 1px solid #ddd;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 25px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: #64c2a7;
            color: white;
        }
        
        .btn-secondary {
            background-color: #f5f5f5;
            color: #555;
        }
        
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            text-align: center;
        }
        
        .status-active {
            background-color: rgba(100, 194, 167, 0.2);
            color: #2e7d32;
        }
        
        .status-closed {
            background-color: rgba(239, 83, 80, 0.2);
            color: #c62828;
        }
        
        .status-draft {
            background-color: rgba(158, 158, 158, 0.2);
            color: #424242;
        }
        
        .status-review {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ff8f00;
        }
        
        .status-interview {
            background-color: rgba(33, 150, 243, 0.2);
            color: #1565c0;
        }
        
        .status-hired {
            background-color: rgba(76, 175, 80, 0.2);
            color: #2e7d32;
        }
        
        .status-rejected {
            background-color: rgba(244, 67, 54, 0.2);
            color: #c62828;
        }
        
        .stage-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            background-color: #f5f5f5;
            color: #555;
            margin-right: 5px;
        }
        
        .job-card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .job-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .job-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .job-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .job-department {
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 10px;
        }
        
        .job-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            color: #555;
        }
        
        .job-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .candidate-count {
            font-size: 0.85rem;
            color: #555;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .applicants-list {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .applicant-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: -10px;
            border: 2px solid white;
            font-size: 0.8rem;
            color: #555;
        }
        
        .more-applicants {
            margin-left: 15px;
            font-size: 0.85rem;
            color: #777;
        }
        
        /* Dark mode */
        body.dark {
            background-color: #1A1A1A;
            color: #e0e0e0;
        }
        
        body.dark .stat-card,
        body.dark .chart-card,
        body.dark .job-listing-table,
        body.dark .candidates-table,
        body.dark .job-card {
            background-color: #1E1E1E;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }
        
        body.dark .stat-card .label,
        body.dark .job-department,
        body.dark .job-meta-item,
        body.dark .candidate-count,
        body.dark .more-applicants {
            color: #b0b0b0;
        }
        
        body.dark .chart-card h3,
        body.dark .job-listing-table h3,
        body.dark .candidates-table h3,
        body.dark .job-title {
            color: #e0e0e0;
        }
        
        body.dark table th {
            background-color: #2C2C2C;
            color: #b0b0b0;
            border-bottom: 1px solid #444;
        }
        
        body.dark table td {
            border-bottom: 1px solid #333;
        }
        
        body.dark .btn-secondary,
        body.dark .stage-badge {
            background-color: #2C2C2C;
            color: #e0e0e0;
        }
        
        body.dark .applicant-avatar {
            background-color: #2C2C2C;
            border-color: #1E1E1E;
            color: #b0b0b0;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <a href="UI.php">
                <img src="img/sam2logo-32.png" alt="SAM Logo">
            </a>
        </div>
        <select class="nav-select">
            <option>sam</option>
        </select>
        <ul class="nav-menu">           
            <a href="funcionarios.php"><li>Funcionários</li></a>
            <a href="registro.php"><li>Novo Funcionário</li></a>
            <li>Processamento Salarial</li>
            <a href="docs.php"><li>Documentos</li></a>
            <a href="registro_ponto.php"><li>Registro de Ponto</li></a>
            <a href="ausencias.php"><li>Ausências</li></a>
            <a href="recrutamento.php"><li class="active">Recrutamento</li></a>
        </ul>
    </div>

    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Recrutamento</h1>
            <div class="header-buttons">
                <div class="time" id="current-time"></div>
                <a class="exit-tag" href="logout.php">Sair</a>
                <a href="./configuracoes_sam/perfil_adm.php" class="perfil_img">                
                    <div class="user-profile">
                        <img src="icones/icons-sam-18.svg" alt="User" width="20">
                        <span><?php echo $_SESSION['nome']; ?></span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </div>
                </a>
            </div>
        </header>

        <div class="action-buttons">
            <button class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Vaga
            </button>
            <button class="btn btn-secondary">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <button class="btn btn-secondary">
                <i class="fas fa-file-export"></i> Exportar Relatório
            </button>
        </div>

        <!-- Cards de estatísticas -->
        <div class="dashboard-cards">
            <div class="stat-card">
                <i class="fas fa-briefcase icon"></i>
                <div class="number"><?php echo $vagas_ativas; ?></div>
                <div class="label">Vagas Ativas</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users icon"></i>
                <div class="number"><?php echo $total_candidatos; ?></div>
                <div class="label">Candidatos</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-check icon"></i>
                <div class="number"><?php echo $entrevistas_esta_semana; ?></div>
                <div class="label">Entrevistas Esta Semana</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-plus icon"></i>
                <div class="number"><?php echo $contratacoes_este_mes; ?></div>
                <div class="label">Contratações Este Mês</div>
            </div>
        </div>

        <!-- Vagas em destaque -->
        <div class="job-card-container">
            <?php
            $stmt_vagas_destaque = $conn->prepare("SELECT id, titulo, departamento, localizacao, tipo_contrato, data_publicacao FROM vagas WHERE empresa_id = ? AND status = 'Aberta' ORDER BY data_publicacao DESC LIMIT 3");
            $stmt_vagas_destaque->bind_param("i", $empresa_id);
            $stmt_vagas_destaque->execute();
            $result_vagas_destaque = $stmt_vagas_destaque->get_result();

            if ($result_vagas_destaque->num_rows > 0) {
                while ($vaga = $result_vagas_destaque->fetch_assoc()) {
                    $vaga_id = $vaga['id'];
                    // Count candidates for this specific job
                    $stmt_candidatos_vaga = $conn->prepare("SELECT COUNT(*) AS count_candidatos FROM candidaturas WHERE vaga_id = ?");
                    $stmt_candidatos_vaga->bind_param("i", $vaga_id);
                    $stmt_candidatos_vaga->execute();
                    $result_candidatos_vaga = $stmt_candidatos_vaga->get_result();
                    $count_candidatos = $result_candidatos_vaga->fetch_assoc()['count_candidatos'];
                    $stmt_candidatos_vaga->close();

                    // Get up to 3 applicant initials for display
                    $stmt_applicant_initials = $conn->prepare("SELECT SUBSTRING(c.nome, 1, 1) AS initial FROM candidaturas ca JOIN candidatos c ON ca.candidato_id = c.id WHERE ca.vaga_id = ? LIMIT 3");
                    $stmt_applicant_initials->bind_param("i", $vaga_id);
                    $stmt_applicant_initials->execute();
                    $result_applicant_initials = $stmt_applicant_initials->get_result();
                    $applicant_initials = [];
                    while ($initial = $result_applicant_initials->fetch_assoc()) {
                        $applicant_initials[] = strtoupper($initial['initial']);
                    }
                    $stmt_applicant_initials->close();
            ?>
            <div class="job-card">
                <div class="job-card-header">
                    <div>
                        <div class="job-title"><?php echo htmlspecialchars($vaga['titulo']); ?></div>
                        <div class="job-department"><?php echo htmlspecialchars($vaga['departamento']); ?></div>
                    </div>
                    <span class="status status-active">Ativa</span>
                </div>
                <div class="job-meta">
                    <div class="job-meta-item">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vaga['localizacao']); ?>
                    </div>
                    <div class="job-meta-item">
                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $vaga['tipo_contrato']))); ?>
                    </div>
                </div>
                <div class="applicants-list">
                    <?php foreach ($applicant_initials as $initial): ?>
                        <div class="applicant-avatar"><?php echo $initial; ?></div>
                    <?php endforeach; ?>
                    <?php if ($count_candidatos > 3): ?>
                        <div class="applicant-avatar">+</div>
                    <?php endif; ?>
                    <div class="more-applicants"><?php echo $count_candidatos; ?> candidatos</div>
                </div>
                <div class="job-actions">
                    <span class="candidate-count">Criada em <?php echo date('d/m/Y', strtotime($vaga['data_publicacao'])); ?></span>
                    <a href="job_view_page.php?id=<?php echo $vaga['id']; ?>" class="btn btn-primary btn-small">Ver Detalhes</a>
                </div>
            </div>
            <?php
                }
            } else {
                echo "<p>Nenhuma vaga ativa encontrada para esta empresa.</p>";
            }
            $stmt_vagas_destaque->close();
            ?>
        </div>

        <!-- Gráficos -->
        <div class="chart-container">
            <div class="chart-card">
                <h3>Candidaturas por Vaga</h3>
                <canvas id="jobApplicationsChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Status dos Candidatos</h3>
                <canvas id="candidateStatusChart"></canvas>
            </div>
        </div>

        <!-- Tabela de Vagas -->
        <div class="job-listing-table">
            <h3>
                Vagas Recentes
                <a href="emp_vagas.php" class="view-all">Ver Todas</a>
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Cargo</th>
                        <th>Departamento</th>
                        <th>Localização</th>
                        <th>Data de Publicação</th>
                        <th>Candidatos</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt_recent_vagas = $conn->prepare("SELECT id, titulo, departamento, localizacao, data_publicacao, status FROM vagas WHERE empresa_id = ? ORDER BY data_publicacao DESC LIMIT 5");
                    $stmt_recent_vagas->bind_param("i", $empresa_id);
                    $stmt_recent_vagas->execute();
                    $result_recent_vagas = $stmt_recent_vagas->get_result();

                    if ($result_recent_vagas->num_rows > 0) {
                        while ($vaga = $result_recent_vagas->fetch_assoc()) {
                            $vaga_id = $vaga['id'];
                            $stmt_count_candidatos = $conn->prepare("SELECT COUNT(*) AS count_candidatos FROM candidaturas WHERE vaga_id = ?");
                            $stmt_count_candidatos->bind_param("i", $vaga_id);
                            $stmt_count_candidatos->execute();
                            $result_count_candidatos = $stmt_count_candidatos->get_result();
                            $count_candidatos_table = $result_count_candidatos->fetch_assoc()['count_candidatos'];
                            $stmt_count_candidatos->close();
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($vaga['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($vaga['departamento']); ?></td>
                        <td><?php echo htmlspecialchars($vaga['localizacao']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($vaga['data_publicacao'])); ?></td>
                        <td><?php echo $count_candidatos_table; ?></td>
                        <td><span class="status status-<?php echo strtolower(str_replace(' ', '-', $vaga['status'])); ?>"><?php echo htmlspecialchars($vaga['status']); ?></span></td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='6'>Nenhuma vaga recente encontrada para esta empresa.</td></tr>";
                    }
                    $stmt_recent_vagas->close();
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Tabela de Candidatos -->
        <div class="candidates-table">
            <h3>
                Candidatos Recentes
                <a href="empresas_candidaturas.php" class="view-all">Ver Todos</a>
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Vaga</th>
                        <th>Data da Candidatura</th>
                        <th>Etapa</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt_recent_candidatos = $conn->prepare("SELECT c.nome, v.titulo AS vaga_titulo, ca.data_candidatura, ca.status 
                                                               FROM candidaturas ca
                                                               JOIN candidatos c ON ca.candidato_id = c.id
                                                               JOIN vagas v ON ca.vaga_id = v.id
                                                               WHERE v.empresa_id = ?
                                                               ORDER BY ca.data_candidatura DESC LIMIT 5");
                    $stmt_recent_candidatos->bind_param("i", $empresa_id);
                    $stmt_recent_candidatos->execute();
                    $result_recent_candidatos = $stmt_recent_candidatos->get_result();

                    if ($result_recent_candidatos->num_rows > 0) {
                        while ($candidato = $result_recent_candidatos->fetch_assoc()) {
                            // Map status to a more human-readable "Etapa" or a simplified one for display
                            $etapa = '';
                            $status_class = strtolower(str_replace(' ', '-', $candidato['status']));
                            switch ($candidato['status']) {
                                case 'Pendente': $etapa = 'Pendente'; break;
                                case 'Visualizada': $etapa = 'Visualizada'; break;
                                case 'Em análise': $etapa = 'Avaliação CV'; break;
                                case 'Entrevista': $etapa = 'Entrevista'; break;
                                case 'Aprovado': $etapa = 'Contratado'; break;
                                case 'Rejeitado': $etapa = 'Rejeitado'; break;
                                default: $etapa = 'Desconhecido';
                            }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($candidato['nome']); ?></td>
                        <td><?php echo htmlspecialchars($candidato['vaga_titulo']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($candidato['data_candidatura'])); ?></td>
                        <td><span class="stage-badge"><?php echo htmlspecialchars($etapa); ?></span></td>
                        <td><span class="status status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($candidato['status']); ?></span></td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='5'>Nenhum candidato recente encontrado para esta empresa.</td></tr>";
                    }
                    $stmt_recent_candidatos->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Função para atualizar o relógio
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Configuração dos gráficos (precisará de dados dinâmicos do PHP)
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de candidaturas por vaga
            const ctxJobApplications = document.getElementById('jobApplicationsChart').getContext('2d');
            const jobApplicationsChart = new Chart(ctxJobApplications, {
                type: 'bar',
                data: {
                    labels: [
                        <?php
                        $chart_labels = [];
                        $chart_data = [];
                        $stmt_chart_vagas = $conn->prepare("SELECT titulo, COUNT(ca.id) AS num_candidaturas FROM vagas v LEFT JOIN candidaturas ca ON v.id = ca.vaga_id WHERE v.empresa_id = ? GROUP BY v.id ORDER BY num_candidaturas DESC LIMIT 5");
                        $stmt_chart_vagas->bind_param("i", $empresa_id);
                        $stmt_chart_vagas->execute();
                        $result_chart_vagas = $stmt_chart_vagas->get_result();
                        while ($row = $result_chart_vagas->fetch_assoc()) {
                            $chart_labels[] = "'" . htmlspecialchars($row['titulo']) . "'";
                            $chart_data[] = $row['num_candidaturas'];
                        }
                        $stmt_chart_vagas->close();
                        echo implode(', ', $chart_labels);
                        ?>
                    ],
                    datasets: [{
                        label: 'Candidaturas',
                        data: [<?php echo implode(', ', $chart_data); ?>],
                        backgroundColor: 'rgba(100, 194, 167, 0.7)',
                        borderColor: 'rgba(100, 194, 167, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Gráfico de status dos candidatos
            const ctxCandidateStatus = document.getElementById('candidateStatusChart').getContext('2d');
            const candidateStatusChart = new Chart(ctxCandidateStatus, {
                type: 'doughnut',
                data: {
                    labels: [
                        <?php
                        $status_labels = [];
                        $status_data = [];
                        $status_colors = [
                            'Pendente' => 'rgba(255, 193, 7, 0.7)', // Yellow
                            'Visualizada' => 'rgba(255, 152, 0, 0.7)', // Orange
                            'Em análise' => 'rgba(33, 150, 243, 0.7)', // Blue
                            'Entrevista' => 'rgba(156, 39, 176, 0.7)', // Purple
                            'Aprovado' => 'rgba(76, 175, 80, 0.7)', // Green
                            'Rejeitado' => 'rgba(244, 67, 54, 0.7)' // Red
                        ];
                        $status_border_colors = [
                            'Pendente' => 'rgba(255, 193, 7, 1)',
                            'Visualizada' => 'rgba(255, 152, 0, 1)',
                            'Em análise' => 'rgba(33, 150, 243, 1)',
                            'Entrevista' => 'rgba(156, 39, 176, 1)',
                            'Aprovado' => 'rgba(76, 175, 80, 1)',
                            'Rejeitado' => 'rgba(244, 67, 54, 1)'
                        ];
                        $dynamic_bg_colors = [];
                        $dynamic_border_colors = [];

                        $stmt_chart_status = $conn->prepare("SELECT ca.status, COUNT(ca.id) AS num_status FROM candidaturas ca JOIN vagas v ON ca.vaga_id = v.id WHERE v.empresa_id = ? GROUP BY ca.status");
                        $stmt_chart_status->bind_param("i", $empresa_id);
                        $stmt_chart_status->execute();
                        $result_chart_status = $stmt_chart_status->get_result();
                        while ($row = $result_chart_status->fetch_assoc()) {
                            $status_labels[] = "'" . htmlspecialchars($row['status']) . "'";
                            $status_data[] = $row['num_status'];
                            $dynamic_bg_colors[] = "'" . ($status_colors[$row['status']] ?? 'rgba(0,0,0,0.7)') . "'"; // Fallback color
                            $dynamic_border_colors[] = "'" . ($status_border_colors[$row['status']] ?? 'rgba(0,0,0,1)') . "'"; // Fallback color
                        }
                        $stmt_chart_status->close();
                        echo implode(', ', $status_labels);
                        ?>
                    ],
                    datasets: [{
                        data: [<?php echo implode(', ', $status_data); ?>],
                        backgroundColor: [<?php echo implode(', ', $dynamic_bg_colors); ?>],
                        borderColor: [<?php echo implode(', ', $dynamic_border_colors); ?>],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        });
    </script>
    <script src="./js/theme.js"></script>
</body>
</html> 