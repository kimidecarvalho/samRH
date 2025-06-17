<?php
// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'protect.php'; // Protege a página para usuários autenticados
include 'config.php'; // Conexão com o banco de dados
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

// Get empresa_id from sam_emprego
$stmt = $conn->prepare("SELECT id FROM sam_emprego.empresas_recrutamento WHERE email = (SELECT email_corp FROM sam.empresa WHERE id_empresa = ?)");
$stmt->bind_param("i", $_SESSION['id_empresa']);
$stmt->execute();
$result = $stmt->get_result();
$empresa_sam_emprego = $result->fetch_assoc();
$empresa_id_emprego = $empresa_sam_emprego['id'];

// 1. Count Active Jobs
$stmt_vagas_ativas = $conn->prepare("
    SELECT COUNT(*) AS total_vagas_ativas 
    FROM sam_emprego.vagas 
    WHERE empresa_id = ? AND status = 'Aberta'
");
$stmt_vagas_ativas->bind_param("i", $empresa_id_emprego);
$stmt_vagas_ativas->execute();
$vagas_ativas = $stmt_vagas_ativas->get_result()->fetch_assoc()['total_vagas_ativas'];

// 2. Count Unique Candidates
$stmt_candidatos = $conn->prepare("
    SELECT COUNT(DISTINCT c.candidato_id) AS total_candidatos 
    FROM sam_emprego.candidaturas c 
    JOIN sam_emprego.vagas v ON c.vaga_id = v.id 
    WHERE v.empresa_id = ?
");
$stmt_candidatos->bind_param("i", $empresa_id_emprego);
$stmt_candidatos->execute();
$total_candidatos = $stmt_candidatos->get_result()->fetch_assoc()['total_candidatos'];

// 3. Count This Week's Interviews
$stmt_entrevistas = $conn->prepare("
    SELECT COUNT(*) AS total_entrevistas 
    FROM sam_emprego.candidaturas c
    JOIN sam_emprego.vagas v ON c.vaga_id = v.id
    WHERE v.empresa_id = ? 
    AND c.status = 'Entrevista' 
    AND c.entrevista_data BETWEEN CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY 
    AND CURDATE() + INTERVAL (6 - WEEKDAY(CURDATE())) DAY
");
$stmt_entrevistas->bind_param("i", $empresa_id_emprego);
$stmt_entrevistas->execute();
$entrevistas_esta_semana = $stmt_entrevistas->get_result()->fetch_assoc()['total_entrevistas'];

// 4. Count This Month's Hires
$stmt_contratacoes = $conn->prepare("
    SELECT COUNT(*) AS total_contratacoes 
    FROM sam_emprego.candidaturas c
    JOIN sam_emprego.vagas v ON c.vaga_id = v.id
    WHERE v.empresa_id = ? 
    AND c.status = 'Aprovado' 
    AND MONTH(c.data_candidatura) = MONTH(CURRENT_DATE())
    AND YEAR(c.data_candidatura) = YEAR(CURRENT_DATE())
");
$stmt_contratacoes->bind_param("i", $empresa_id_emprego);
$stmt_contratacoes->execute();
$contratacoes_este_mes = $stmt_contratacoes->get_result()->fetch_assoc()['total_contratacoes'];

// 5. Get Recent Jobs - Updated query with better sorting and limit
$stmt_vagas_recentes = $conn->prepare("
    SELECT 
        v.*, 
        COUNT(c.id) as total_candidaturas,
        GROUP_CONCAT(DISTINCT LEFT(cand.nome, 1) ORDER BY c.id LIMIT 3) as candidatos_iniciais,
        GROUP_CONCAT(DISTINCT cand.nome ORDER BY c.id LIMIT 3) as candidatos_nomes
    FROM sam_emprego.vagas v
    LEFT JOIN sam_emprego.candidaturas c ON v.id = c.vaga_id
    LEFT JOIN sam_emprego.candidatos cand ON c.candidato_id = cand.id
    WHERE v.empresa_id = ?
    GROUP BY v.id
    ORDER BY v.data_publicacao DESC, v.id DESC
    LIMIT 5
");
$stmt_vagas_recentes->bind_param("i", $empresa_id_emprego);
$stmt_vagas_recentes->execute();
$vagas_recentes = $stmt_vagas_recentes->get_result();

// Store results in array for multiple use
$vagas_array = [];
while ($vaga = $vagas_recentes->fetch_assoc()) {
    $vagas_array[] = $vaga;
}

// 6. Get Recent Candidates
$stmt_candidatos_recentes = $conn->prepare("
    SELECT c.*, v.titulo as vaga_titulo, cand.nome as candidato_nome
    FROM sam_emprego.candidaturas c
    JOIN sam_emprego.vagas v ON c.vaga_id = v.id
    JOIN sam_emprego.candidatos cand ON c.candidato_id = cand.id
    WHERE v.empresa_id = ?
    ORDER BY c.data_candidatura DESC
    LIMIT 5
");
$stmt_candidatos_recentes->bind_param("i", $empresa_id_emprego);
$stmt_candidatos_recentes->execute();
$candidatos_recentes = $stmt_candidatos_recentes->get_result();

// 7. Get Data for Charts
$stmt_candidaturas_por_vaga = $conn->prepare("
    SELECT v.titulo, COUNT(c.id) as total
    FROM sam_emprego.vagas v
    LEFT JOIN sam_emprego.candidaturas c ON v.id = c.vaga_id
    WHERE v.empresa_id = ?
    GROUP BY v.id
    ORDER BY total DESC
    LIMIT 5
");
$stmt_candidaturas_por_vaga->bind_param("i", $empresa_id_emprego);
$stmt_candidaturas_por_vaga->execute();
$dados_grafico_vagas = $stmt_candidaturas_por_vaga->get_result();

$stmt_status_candidatos = $conn->prepare("
    SELECT 
        c.status,
        COUNT(*) as total
    FROM sam_emprego.candidaturas c
    JOIN sam_emprego.vagas v ON c.vaga_id = v.id
    WHERE v.empresa_id = ? 
    AND c.status IN ('Em análise', 'Entrevista', 'Aprovado', 'Rejeitado')
    GROUP BY c.status
");
$stmt_status_candidatos->bind_param("i", $empresa_id_emprego);
$stmt_status_candidatos->execute();
$result_status = $stmt_status_candidatos->get_result();

$status_data = [
    'Em análise' => 0,
    'Entrevista' => 0,
    'Aprovado' => 0,
    'Rejeitado' => 0
];

while ($row = $result_status->fetch_assoc()) {
    $status_data[$row['status']] = (int)$row['total'];
}

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
            <?php foreach($vagas_array as $vaga): ?>
            <div class="job-card">
                <div class="job-card-header">
                    <div>
                        <div class="job-title"><?php echo htmlspecialchars($vaga['titulo']); ?></div>
                        <div class="job-department"><?php echo htmlspecialchars($vaga['departamento']); ?></div>
                    </div>
                    <span class="status status-<?php echo strtolower($vaga['status']); ?>"><?php echo htmlspecialchars($vaga['status']); ?></span>
                </div>
                <div class="job-meta">
                    <div class="job-meta-item">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vaga['localizacao']); ?>
                    </div>
                    <div class="job-meta-item">
                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($vaga['tipo_contrato']); ?>
                    </div>
                </div>
                <div class="applicants-list">
                    <?php 
                    $candidatos_iniciais = explode(',', $vaga['candidatos_iniciais']);
                    foreach ($candidatos_iniciais as $inicial): ?>
                    <div class="applicant-avatar"><?php echo htmlspecialchars($inicial); ?></div>
                    <?php endforeach; ?>
                    <div class="more-applicants"><?php echo $vaga['total_candidaturas']; ?> candidatos</div>
                </div>
                <div class="job-actions">
                    <span class="candidate-count">Criada em <?php echo date('d/m/Y', strtotime($vaga['data_publicacao'])); ?></span>
                    <button class="btn btn-primary btn-small">Ver Detalhes</button>
                </div>
            </div>
            <?php endforeach; ?>
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
                <a href="#" class="view-all">Ver Todas</a>
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
                    <?php foreach($vagas_array as $vaga): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($vaga['titulo']); ?></td>
                        <td><?php echo htmlspecialchars($vaga['departamento']); ?></td>
                        <td><?php echo htmlspecialchars($vaga['localizacao']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($vaga['data_publicacao'])); ?></td>
                        <td><?php echo $vaga['total_candidaturas']; ?></td>
                        <td><span class="status status-<?php echo strtolower($vaga['status']); ?>"><?php echo htmlspecialchars($vaga['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tabela de Candidatos -->
        <div class="candidates-table">
            <h3>
                Candidatos Recentes
                <a href="#" class="view-all">Ver Todos</a>
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Vaga</th>
                        <th>Data da Candidatura</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($candidato = $candidatos_recentes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($candidato['candidato_nome']); ?></td>
                        <td><?php echo htmlspecialchars($candidato['vaga_titulo']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($candidato['data_candidatura'])); ?></td>
                        <td><span class="status status-<?php echo strtolower($candidato['status']); ?>"><?php echo htmlspecialchars($candidato['status']); ?></span></td>
                    </tr>
                    <?php endwhile; ?>
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

        // Configuração dos gráficos
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de candidaturas por vaga
            const ctxJobApplications = document.getElementById('jobApplicationsChart').getContext('2d');
            const jobApplicationsChart = new Chart(ctxJobApplications, {
                type: 'bar',
                data: {
                    labels: [<?php 
                        $labels = [];
                        $data = [];
                        $dados_grafico_vagas->data_seek(0);
                        while($row = $dados_grafico_vagas->fetch_assoc()) {
                            $labels[] = "'" . addslashes($row['titulo']) . "'";
                            $data[] = $row['total'];
                        }
                        echo implode(',', $labels);
                    ?>],
                    datasets: [{
                        label: 'Candidaturas',
                        data: [<?php echo implode(',', $data); ?>],
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
                    labels: ['Em análise', 'Entrevista', 'Contratado', 'Rejeitado'],
                    datasets: [{
                        data: [
                            <?php echo $status_data['Em análise']; ?>,
                            <?php echo $status_data['Entrevista']; ?>,
                            <?php echo $status_data['Aprovado']; ?>,
                            <?php echo $status_data['Rejeitado']; ?>
                        ],
                        backgroundColor: [
                            'rgba(255, 193, 7, 0.7)',  // Em análise - amarelo
                            'rgba(33, 150, 243, 0.7)', // Entrevista - azul
                            'rgba(76, 175, 80, 0.7)',  // Contratado - verde
                            'rgba(244, 67, 54, 0.7)'   // Rejeitado - vermelho
                        ],
                        borderColor: [
                            'rgba(255, 193, 7, 1)',
                            'rgba(33, 150, 243, 1)',
                            'rgba(76, 175, 80, 1)',
                            'rgba(244, 67, 54, 1)'
                        ],
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