<?php
// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'protect.php'; // Protege a página para usuários autenticados
include 'config.php'; // Conexão com o banco de dados

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
                <div class="number">8</div>
                <div class="label">Vagas Ativas</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users icon"></i>
                <div class="number">87</div>
                <div class="label">Candidatos</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-check icon"></i>
                <div class="number">12</div>
                <div class="label">Entrevistas Esta Semana</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-plus icon"></i>
                <div class="number">5</div>
                <div class="label">Contratações Este Mês</div>
            </div>
        </div>

        <!-- Vagas em destaque -->
        <div class="job-card-container">
            <div class="job-card">
                <div class="job-card-header">
                    <div>
                        <div class="job-title">Desenvolvedor Front-end</div>
                        <div class="job-department">Tecnologia da Informação</div>
                    </div>
                    <span class="status status-active">Ativa</span>
                </div>
                <div class="job-meta">
                    <div class="job-meta-item">
                        <i class="fas fa-map-marker-alt"></i> Lisboa
                    </div>
                    <div class="job-meta-item">
                        <i class="fas fa-clock"></i> Tempo Integral
                    </div>
                </div>
                <div class="applicants-list">
                    <div class="applicant-avatar">JS</div>
                    <div class="applicant-avatar">MP</div>
                    <div class="applicant-avatar">CS</div>
                    <div class="applicant-avatar">+</div>
                    <div class="more-applicants">24 candidatos</div>
                </div>
                <div class="job-actions">
                    <span class="candidate-count">Criada em 15/05/2023</span>
                    <button class="btn btn-primary btn-small">Ver Detalhes</button>
                </div>
            </div>
            
            <div class="job-card">
                <div class="job-card-header">
                    <div>
                        <div class="job-title">Analista de Marketing Digital</div>
                        <div class="job-department">Marketing</div>
                    </div>
                    <span class="status status-active">Ativa</span>
                </div>
                <div class="job-meta">
                    <div class="job-meta-item">
                        <i class="fas fa-map-marker-alt"></i> Porto
                    </div>
                    <div class="job-meta-item">
                        <i class="fas fa-clock"></i> Tempo Integral
                    </div>
                </div>
                <div class="applicants-list">
                    <div class="applicant-avatar">RL</div>
                    <div class="applicant-avatar">AM</div>
                    <div class="applicant-avatar">+</div>
                    <div class="more-applicants">18 candidatos</div>
                </div>
                <div class="job-actions">
                    <span class="candidate-count">Criada em 20/05/2023</span>
                    <button class="btn btn-primary btn-small">Ver Detalhes</button>
                </div>
            </div>
            
            <div class="job-card">
                <div class="job-card-header">
                    <div>
                        <div class="job-title">Gerente de Vendas</div>
                        <div class="job-department">Vendas</div>
                    </div>
                    <span class="status status-active">Ativa</span>
                </div>
                <div class="job-meta">
                    <div class="job-meta-item">
                        <i class="fas fa-map-marker-alt"></i> Lisboa
                    </div>
                    <div class="job-meta-item">
                        <i class="fas fa-clock"></i> Tempo Integral
                    </div>
                </div>
                <div class="applicants-list">
                    <div class="applicant-avatar">FS</div>
                    <div class="applicant-avatar">JL</div>
                    <div class="applicant-avatar">+</div>
                    <div class="more-applicants">12 candidatos</div>
                </div>
                <div class="job-actions">
                    <span class="candidate-count">Criada em 01/06/2023</span>
                    <button class="btn btn-primary btn-small">Ver Detalhes</button>
                </div>
            </div>
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
                    <tr>
                        <td>Desenvolvedor Front-end</td>
                        <td>Tecnologia da Informação</td>
                        <td>Lisboa</td>
                        <td>15/05/2023</td>
                        <td>24</td>
                        <td><span class="status status-active">Ativa</span></td>
                    </tr>
                    <tr>
                        <td>Analista de Marketing Digital</td>
                        <td>Marketing</td>
                        <td>Porto</td>
                        <td>20/05/2023</td>
                        <td>18</td>
                        <td><span class="status status-active">Ativa</span></td>
                    </tr>
                    <tr>
                        <td>Gerente de Vendas</td>
                        <td>Vendas</td>
                        <td>Lisboa</td>
                        <td>01/06/2023</td>
                        <td>12</td>
                        <td><span class="status status-active">Ativa</span></td>
                    </tr>
                    <tr>
                        <td>Especialista em Recursos Humanos</td>
                        <td>Recursos Humanos</td>
                        <td>Porto</td>
                        <td>03/06/2023</td>
                        <td>9</td>
                        <td><span class="status status-active">Ativa</span></td>
                    </tr>
                    <tr>
                        <td>Contador Sênior</td>
                        <td>Financeiro</td>
                        <td>Lisboa</td>
                        <td>10/05/2023</td>
                        <td>15</td>
                        <td><span class="status status-closed">Encerrada</span></td>
                    </tr>
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
                        <th>Etapa</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>João Silva</td>
                        <td>Desenvolvedor Front-end</td>
                        <td>10/06/2023</td>
                        <td><span class="stage-badge">Entrevista Técnica</span></td>
                        <td><span class="status status-interview">Entrevista</span></td>
                    </tr>
                    <tr>
                        <td>Maria Pereira</td>
                        <td>Analista de Marketing Digital</td>
                        <td>12/06/2023</td>
                        <td><span class="stage-badge">Avaliação CV</span></td>
                        <td><span class="status status-review">Em Análise</span></td>
                    </tr>
                    <tr>
                        <td>Carlos Santos</td>
                        <td>Desenvolvedor Front-end</td>
                        <td>08/06/2023</td>
                        <td><span class="stage-badge">Entrevista RH</span></td>
                        <td><span class="status status-interview">Entrevista</span></td>
                    </tr>
                    <tr>
                        <td>Ana Oliveira</td>
                        <td>Contador Sênior</td>
                        <td>15/05/2023</td>
                        <td><span class="stage-badge">Proposta</span></td>
                        <td><span class="status status-hired">Contratado</span></td>
                    </tr>
                    <tr>
                        <td>Ricardo Lima</td>
                        <td>Gerente de Vendas</td>
                        <td>05/06/2023</td>
                        <td><span class="stage-badge">Teste Prático</span></td>
                        <td><span class="status status-rejected">Rejeitado</span></td>
                    </tr>
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
                    labels: ['Dev Front-end', 'Analista Marketing', 'Gerente Vendas', 'Esp. RH', 'Contador'],
                    datasets: [{
                        label: 'Candidaturas',
                        data: [24, 18, 12, 9, 15],
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
                    labels: ['Em Análise', 'Entrevista', 'Teste', 'Proposta', 'Contratado', 'Rejeitado'],
                    datasets: [{
                        data: [35, 28, 15, 5, 12, 30],
                        backgroundColor: [
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(33, 150, 243, 0.7)',
                            'rgba(156, 39, 176, 0.7)',
                            'rgba(255, 152, 0, 0.7)',
                            'rgba(76, 175, 80, 0.7)',
                            'rgba(244, 67, 54, 0.7)'
                        ],
                        borderColor: [
                            'rgba(255, 193, 7, 1)',
                            'rgba(33, 150, 243, 1)',
                            'rgba(156, 39, 176, 1)',
                            'rgba(255, 152, 0, 1)',
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