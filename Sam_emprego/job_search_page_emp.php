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
        
        // Modificar a query para buscar todas as vagas de todas as empresas
        $stmt = $pdo->prepare("
            SELECT v.*, e.nome as empresa_nome, e.logo as empresa_logo 
            FROM vagas v 
            JOIN empresas_recrutamento e ON v.empresa_id = e.id 
            ORDER BY v.data_publicacao DESC
        ");
        $stmt->execute();
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
    <title>SAM Emprego</title>
</head>
<body>

<header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="../fotos/sam30-13.png" alt="SAM Emprego Logo">
            </div>
            <div class="nav-container">
                <nav class="nav-menu">
                    <a href="job_search_page_emp.php" class="active">Vagas</a>
                    <a href="emp_vagas.php">Minhas vagas</a>
                    <a href="empresas_candidaturas.php">Candidaturas</a>
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
    
    <div class="main-container">
        <div class="search-container collapsed" id="search-container">
            <div class="search-header" id="filter-toggle">
                <div class="closed-content">
                    <div class="filter-title">
                        <strong>Filtros</strong>
                        <span>de procura</span>
                    </div>
                    <div class="divider"></div>
                    <div class="search-description" style="font-size: 1rem;">
                        Procure por empregos que atendam os <strong>seus desejos</strong>.
                    </div>
                    <div class="toggle-icon-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toggle-icon">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
                
                <div class="open-content">
                    <p style="font-size: 1rem;">Procure por empregos que atendam os <strong>seus desejos</strong>.</p>
                    <div class="toggle-icon-container">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toggle-icon">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="filter-options" id="filter-options">
                <div class="filter-row">
                    <div class="filter-label">Tipo de Trabalho:</div>
                    <div class="filter-options-group">
                        <div class="filter-option active">Todos</div>
                        <div class="filter-option">Presencial</div>
                        <div class="filter-option">Remoto</div>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-label">Local de Trabalho:</div>
                    <div class="filter-options-group">
                        <select class="dropdown-select">
                            <option>País</option>
                        </select>
                        <select class="dropdown-select">
                            <option>Cidade*</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-label">Nível de Ensino Mínimo:</div>
                    <div class="filter-options-group">
                        <div class="filter-option">Todos</div>
                        <div class="filter-option">Em andamento</div>
                        <div class="filter-option active">Ensino médio</div>
                        <div class="filter-option">Ensino superior</div>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-label">Área de Trabalho:</div>
                    <div class="filter-options-group">
                        <div class="filter-option">Todos</div>
                        <div class="filter-option">Administrativo</div>
                        <div class="filter-option">Comercial</div>
                        <div class="filter-option">Educação</div>
                        <div class="filter-option">Engenharia</div>
                        <div class="filter-option">Financeira</div>
                        <div class="filter-option">Industrial</div>
                        <div class="filter-option active">Marketing</div>
                        <div class="filter-option">Logística</div>
                        <div class="filter-option">mais</div>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-label">Tipo de Contrato:</div>
                    <div class="filter-options-group">
                        <div class="filter-option active">Todos</div>
                        <div class="filter-option">Efetivo</div>
                        <div class="filter-option">Temporário</div>
                        <div class="filter-option">Prestação de serviços</div>
                    </div>
                </div>
                
                <button class="apply-button">Aplicar</button>
            </div>
        </div>
        
        <div class="search-box">
            <div class="search-input">
                <div class="search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <input type="text" id="searchInput" placeholder="Pesquisar vagas..." onkeyup="searchJobs()">
            </div>
            <button class="search-button" onclick="searchJobs()">Procurar</button>
        </div>

        <div class="job-listings" id="jobListings">
            <?php
            if (isset($vagas) && !empty($vagas)) {
                foreach ($vagas as $vaga) {
                    // Format salary range
                    $salario = '';
                    if ($vaga['salario_min'] && $vaga['salario_max']) {
                        $salario = number_format($vaga['salario_min'], 2, ',', '.') . ' - ' . 
                                 number_format($vaga['salario_max'], 2, ',', '.') . ' AOA / Mês';
                    } elseif ($vaga['salario_min']) {
                        $salario = number_format($vaga['salario_min'], 2, ',', '.') . ' AOA / Mês';
                    } elseif ($vaga['salario_max']) {
                        $salario = number_format($vaga['salario_max'], 2, ',', '.') . ' AOA / Mês';
                    }

                    // Determine status class
                    $statusClass = '';
                    $statusText = '';
                    switch ($vaga['status']) {
                        case 'Aberta':
                            $statusClass = 'status-open';
                            $statusText = 'A contratar';
                            break;
                        case 'Fechada':
                            $statusClass = 'status-closed';
                            $statusText = 'Vaga Fechada';
                            break;
                        case 'Pausada':
                            $statusClass = 'status-suspended';
                            $statusText = 'Vaga Pausada';
                            break;
                    }
            ?>
                <div class="job-card">
                    <div class="job-header"><?php echo htmlspecialchars($vaga['empresa_nome']); ?></div>
                    <div class="job-content">
                        <div class="job-logo">
                            <img src="<?php echo htmlspecialchars($vaga['empresa_logo']); ?>" alt="<?php echo htmlspecialchars($vaga['empresa_nome']); ?> Logo">
                        </div>
                        <div class="job-details">
                            <div class="job-title"><?php echo htmlspecialchars($vaga['titulo']); ?></div>
                            <div class="job-company"><?php echo htmlspecialchars($vaga['empresa_nome']); ?></div>
                            <div class="job-info">
                                <?php if ($vaga['departamento']): ?>
                                <div class="job-category">
                                    <span class="icon icon-category"></span>
                                    <?php echo htmlspecialchars($vaga['departamento']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($vaga['localizacao']): ?>
                                    <div class="job-location">
                                        <span class="icon icon-location"></span>
                                        <?php 
                                        $localizacoes = [
                                            'remoto' => 'Remoto',
                                            'hibrido' => 'Híbrido',
                                            'presencial' => 'Presencial'
                                        ];
                                        echo htmlspecialchars($localizacoes[$vaga['localizacao']] ?? $vaga['localizacao']); 
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($salario): ?>
                                <div class="job-salary">
                                    <span class="icon icon-salary"></span>
                                    <?php echo htmlspecialchars($salario); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($vaga['tipo_contrato']): ?>
                                    <div class="job-type">
                                        <span class="icon icon-time"></span>
                                        <?php 
                                        $tipos_contrato = [
                                            'efetivo' => 'Efetivo',
                                            'meio_periodo' => 'Meio Período',
                                            'temporario' => 'Temporário',
                                            'freelancer' => 'Freelancer',
                                            'estagio' => 'Estágio'
                                        ];
                                        echo htmlspecialchars($tipos_contrato[$vaga['tipo_contrato']] ?? $vaga['tipo_contrato']); 
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="job-actions">
                            <div class="job-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                            <a href="job_view_page.php?id=<?php echo $vaga['id']; ?>" class="job-view">Visualizar detalhes</a>
                        </div>
                    </div>
                </div>
            <?php
                }
            } else {
                echo '<div class="no-jobs">Nenhuma vaga encontrada.</div>';
            }
            ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos do dropdown
            const filterToggle = document.getElementById('filter-toggle');
            const filterOptions = document.getElementById('filter-options');
            const searchContainer = document.getElementById('search-container');
            
            // Estado inicial (fechado)
            let isOpen = false;
            
            // Função para alternar o estado do dropdown
            function toggleDropdown() {
                isOpen = !isOpen;
                
                if (isOpen) {
                    // Mostrar as opções de filtro
                    filterOptions.classList.add('visible');
                    // Remover a classe collapsed para aumentar a altura
                    searchContainer.classList.remove('collapsed');
                } else {
                    // Esconder as opções de filtro
                    filterOptions.classList.remove('visible');
                    // Adicionar a classe collapsed para reduzir a altura
                    searchContainer.classList.add('collapsed');
                }
            }
            
            // Adicionar evento de clique ao cabeçalho do filtro
            filterToggle.addEventListener('click', toggleDropdown);
        });

        // Função de pesquisa
        function searchJobs() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const jobCards = document.querySelectorAll('.job-card');
            let foundJobs = 0;

            jobCards.forEach(card => {
                const title = card.querySelector('.job-title').textContent.toLowerCase();
                const company = card.querySelector('.job-company').textContent.toLowerCase();
                const category = card.querySelector('.job-category')?.textContent.toLowerCase() || '';
                const location = card.querySelector('.job-location')?.textContent.toLowerCase() || '';
                
                if (title.includes(searchTerm) || 
                    company.includes(searchTerm) || 
                    category.includes(searchTerm) || 
                    location.includes(searchTerm) || 
                    searchTerm === '') {
                    card.style.display = 'block';
                    foundJobs++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Mostrar mensagem se nenhuma vaga for encontrada
            const jobListings = document.getElementById('jobListings');
            const existingNoResults = jobListings.querySelector('.no-results');
            
            if (foundJobs === 0) {
                if (!existingNoResults) {
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'no-results';
                    noResultsDiv.innerHTML = 'Nenhuma vaga encontrada para "' + document.getElementById('searchInput').value + '"';
                    noResultsDiv.style.cssText = 'text-align: center; padding: 20px; color: #666; font-style: italic;';
                    jobListings.appendChild(noResultsDiv);
                }
            } else {
                if (existingNoResults) {
                    existingNoResults.remove();
                }
            }
        }

        // Pesquisar ao pressionar Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchJobs();
            }
        });
    </script>
    <script src="../js/dropdown.js"></script>
</body>
</html>