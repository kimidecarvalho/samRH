<?php
session_start();
require_once 'config/database.php'; // Alterado para usar o mesmo arquivo de conexão PDO

// Verifica se o usuário está logado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header("Location: login.php");
    exit();
}

// Buscar informações do candidato
try {
    $stmt = $pdo->prepare("SELECT * FROM candidatos WHERE id = ?");
    $stmt->execute([$_SESSION['candidato_id']]);
    $candidato = $stmt->fetch();
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
    <link rel="stylesheet" href="../all.css/emprego.css/emp_header.css">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_search.css">
    <title>SAM Emprego</title>
    <style>
        :root {
            --primary-color: #3EB489;
            --primary-light: #4fc89a;
            --primary-dark: #339873;
            --secondary-color:rgb(84, 115, 146);
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

        .user-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f5f5f5;
            margin-right: 10px;
            gap: 10px;
            color: #000;
            border: #3EB489 solid 1px;
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

        /* Container principal com largura máxima */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .search-container {
            max-width: 1200px;
            margin: 20px auto;
            background-color: var(white);
            border-radius: 15px;
            overflow: hidden;
            color: white;
            transition: all 0.3s ease;
        }

        .search-container.collapsed {
            max-height: 80px;
        }

        .search-box {
            max-width: 1200px;
            margin: 20px auto;
            display: flex;
            gap: 10px;
            background-color: white;
            border-radius: 15px;
            padding: 15px;
            box-shadow: var(--box-shadow);
        }

        .job-listings {
            max-width: 1200px;
            margin: 20px auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Restante dos estilos CSS para search-box, job-listings, etc. mantidos conforme original */
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
                    <a href="job_search_page.php" class="active">Vagas</a>
                    <a href="curriculums.php">Meu Currículo</a>
                    <a href="minhas_candidaturas.php">Candidaturas</a>
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
                <input type="text" placeholder="Pesquisar vagas...">
            </div>
            <button class="search-button">Procurar</button>
        </div>

        <div class="job-listings">
            <?php
            try {
                // Buscar todas as vagas ativas
                $stmt = $pdo->prepare("
                    SELECT v.*, e.nome as empresa_nome, e.logo as empresa_logo 
                    FROM vagas v 
                    JOIN empresas_recrutamento e ON v.empresa_id = e.id 
                    WHERE v.status = 'Aberta' 
                    ORDER BY v.data_publicacao DESC
                ");
                $stmt->execute();
                $vagas = $stmt->fetchAll();

                if (!empty($vagas)) {
                    foreach ($vagas as $vaga) {
                        // Formatar salário
                        $salario = '';
                        if ($vaga['salario_min'] && $vaga['salario_max']) {
                            $salario = number_format($vaga['salario_min'], 2, ',', '.') . ' - ' . 
                                     number_format($vaga['salario_max'], 2, ',', '.') . ' AOA / Mês';
                        } elseif ($vaga['salario_min']) {
                            $salario = number_format($vaga['salario_min'], 2, ',', '.') . ' AOA / Mês';
                        } elseif ($vaga['salario_max']) {
                            $salario = 'Até ' . number_format($vaga['salario_max'], 2, ',', '.') . ' AOA / Mês';
                        }
            ?>
                <div class="job-card">
                    <div class="job-header"><?php echo htmlspecialchars($vaga['empresa_nome']); ?></div>
                    <div class="job-content">
                        <div class="job-logo">
                            <img src="<?php echo $vaga['empresa_logo'] ? htmlspecialchars($vaga['empresa_logo']) : '../fotos/sam30-13.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($vaga['empresa_nome']); ?> Logo">
                        </div>
                        <div class="job-details">
                            <div class="job-title"><?php echo htmlspecialchars($vaga['titulo']); ?></div>
                            <div class="job-company"><?php echo htmlspecialchars($vaga['empresa_nome']); ?></div>
                            <div class="job-info">
                                <?php if ($vaga['categoria']): ?>
                                <div class="job-category">
                                    <span class="icon icon-category"></span>
                                    <?php echo htmlspecialchars($vaga['categoria']); ?>
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
                                    <?php echo $salario; ?>
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

                                <?php if ($vaga['idioma']): ?>
                                <div class="job-language">
                                    <span class="icon icon-language"></span>
                                    <?php echo htmlspecialchars($vaga['idioma']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="job-actions">
                            <div class="job-status status-open">A contratar</div>
                            <a href="detalhes_vaga.php" class="job-view">Visualizar detalhes</a>
                        </div>
                    </div>
                </div>
            <?php
                    }
                } else {
                    echo '<div class="no-jobs">Nenhuma vaga disponível no momento.</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="error">Erro ao carregar vagas: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
    </script>
    <script src="../js/dropdown.js"></script>
</body>
</html>