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

$empresa_id = $_SESSION['id_empresa']; // Recupera o id_empresa da sessão

// Capturar o departamento selecionado no filtro, se houver
$departamento_selecionado = isset($_GET['departamento_filtro']) ? $_GET['departamento_filtro'] : '';

// Consulta para buscar os departamentos ativos para o filtro
$sql_departamentos_filtro = "SELECT id, nome FROM departamentos WHERE empresa_id = ? ORDER BY nome";
$stmt_departamentos_filtro = $conn->prepare($sql_departamentos_filtro);

$departamentos_filtro = [];
if ($stmt_departamentos_filtro) {
    $stmt_departamentos_filtro->bind_param("i", $empresa_id);
    $stmt_departamentos_filtro->execute();
    $result_departamentos_filtro = $stmt_departamentos_filtro->get_result();
    while($row = $result_departamentos_filtro->fetch_assoc()) {
        $departamentos_filtro[] = $row;
    }
    $stmt_departamentos_filtro->close();
} else {
    // Logar ou tratar erro na preparação da consulta de departamentos
    error_log("Erro na preparação da consulta de departamentos para filtro: " . $conn->error);
}

// Debug: Exibir o valor do departamento selecionado no filtro
echo "<!-- Departamento selecionado: " . htmlspecialchars($departamento_selecionado) . " -->";

// Capturar o estado selecionado no filtro
$estado_filtro = isset($_GET['estado_filtro']) ? $_GET['estado_filtro'] : 'ativos';

// Configuração da paginação
$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Filtro de busca
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Consulta para contar o total de registros filtrados
// (Precisa ser atualizada para considerar o filtro de departamento)
$sql_count = "SELECT COUNT(*) AS total FROM funcionario f ";
$count_params = [];
$count_types = '';

// Condições WHERE
$where_clauses = ["f.nome LIKE ?", "f.empresa_id = ?"];
$sql_params = ['%' . $search . '%', $empresa_id];
$sql_types = 'si';

// Adicionar filtro de estado
if ($estado_filtro === 'terminados') {
    $where_clauses[] = "f.estado = 'Terminado'";
    // Adicionar ordenação por data de término
    $order_by = "ORDER BY f.data_termino DESC, f.num_mecanografico ASC";
} else {
    $where_clauses[] = "f.estado IN ('Ativo', 'Inativo')";
    $order_by = "ORDER BY f.num_mecanografico ASC";
}

// Adicionar filtro de departamento se selecionado
if (!empty($departamento_selecionado) && $departamento_selecionado !== 'todos') {
    $where_clauses[] = "f.departamento = ?";
    $sql_params[] = $departamento_selecionado;
    $sql_types .= 'i';

    // A consulta de contagem também precisa do join se o filtro de departamento for aplicado
     $sql_count .= "INNER JOIN departamentos d ON f.departamento = d.id ";
     $count_where_clauses = ["f.nome LIKE ?", "f.empresa_id = ?", "f.departamento = ?"];
     $count_params = ['%' . $search . '%', $empresa_id, $departamento_selecionado];
     $count_types = 'sii';

} else {
    // Consulta de contagem sem filtro de departamento
    $count_where_clauses = ["f.nome LIKE ?", "f.empresa_id = ?"];
    $count_params = ['%' . $search . '%', $empresa_id];
    $count_types = 'si';
}

$sql_count .= " WHERE " . implode(" AND ", $count_where_clauses);

$stmt_count = $conn->prepare($sql_count);

if ($stmt_count) {
    $stmt_count->bind_param($count_types, ...$count_params);
    $stmt_count->execute();
    $result_total = $stmt_count->get_result();
    $total_registros = $result_total->fetch_assoc()['total'];
    $stmt_count->close();
} else {
     // Logar ou tratar erro na preparação da consulta de contagem
    error_log("Erro na preparação da consulta de contagem com filtro: " . $conn->error);
    $total_registros = 0; // Definir como 0 para evitar divisão por zero
}

$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta principal para recuperar os funcionários filtrados
$sql = "SELECT f.id_fun, f.num_mecanografico, f.nome, f.foto, f.bi, f.emissao_bi, f.validade_bi, f.data_nascimento, 
        f.pais, f.morada, f.genero, f.num_agregados, f.telemovel, f.email, f.estado, f.data_termino,
        c.nome as cargo_nome, d.nome as departamento_nome, 
        f.tipo_trabalhador, f.num_ss, f.data_admissao 
        FROM funcionario f
        LEFT JOIN cargos c ON f.cargo = c.id
        LEFT JOIN departamentos d ON f.departamento = d.id
        WHERE " . implode(" AND ", $where_clauses) . "
        " . $order_by . "
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Adicionar parâmetros da paginação aos parâmetros existentes
$sql_params[] = $registros_por_pagina;
$sql_params[] = $offset;
$sql_types .= 'ii';

$result = false; // Inicializa $result como false

if ($stmt) {
    // Lidar com o bind_param de forma dinâmica
    $bind_params = array();
    $bind_params[] = &$sql_types;
    for ($i = 0; $i < count($sql_params); $i++) {
        $bind_params[] = &$sql_params[$i];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
    } else {
         // Logar erro na execução da consulta principal
        error_log("Erro na execução da consulta principal: " . $stmt->error);
    }
    $stmt->close();
} else {
     // Logar erro na preparação da consulta principal
    error_log("Erro na preparação da consulta principal: " . $conn->error);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    .filters {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
}

.filters form {
    display: flex;
    align-items: center;
    gap: 15px; /* Adiciona espaçamento entre os selects e a search-bar */
    width: 100%; /* Permite que a barra de pesquisa ocupe o espaço restante */
}

.filter-select {
    background-color: white;
    border: 1px solid #ddd;
    padding: 8px 15px;
    border-radius: 25px;
    color: #000;
    font-size: 14px;
    width: 180px;
    height: 40px; /* Altura fixa para alinhamento */
}

.search-bar {
    flex-grow: 1;
    max-width: 300px;
    background-color: white;
    border: 1px solid #ddd;
    padding: 0 15px; /* Adiciona padding interno ao container */
    border-radius: 25px;
    display: flex;
    align-items: center;
    height: 40px; /* Altura fixa para reduzir a "grossura" vertical */
    position: relative; /* Para posicionamento das sugestões */
}

.search-bar form {
    display: flex;
    width: 100%;
    align-items: center;
    padding: 0; /* Remove padding do formulário interno */
}

.search-bar input {
    border: none;
    background: transparent;
    width: 100%;
    outline: none;
    color: #000;
    font-size: 14px;
    height: 100%;
    padding: 0; /* Manter padding 0 no input, o padding externo no .search-bar já o afasta */
    flex-shrink: 1;
}

.search-bar button {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    margin-left: 10px;
    display: flex;
    align-items: center;
}

.search-icon {
    color: #777;
}
/* Container de tabela com rolagem */
.table-container {
    width: 100%;
    overflow-x: auto;
    position: relative;
    background-color: white;
    border-radius: 20px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

/* Barra de rolagem estilizada */
.table-container::-webkit-scrollbar {
    height: 10px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 0 0 8px 8px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #64c2a7;
    border-radius: 10px;
}

/* Tabela de funcionários */
.tabela-funcionarios {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

.tabela-funcionarios th {
    background-color: rgb(255, 255, 255);
    color: #333;
    font-weight: 500;
    text-align: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
    position: sticky;
    top: 0;
    z-index: 10;
    border-left: none !important;
    transition: none !important;
}

.tabela-funcionarios td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    color: #000;
}

.tabela-funcionarios tr:last-child td {
    border-bottom: none;
}

.tabela-funcionarios tbody tr {
    transition: all 0.2s ease-in-out;
    border-left: 0px solid #64c2a7;
}

.tabela-funcionarios tbody tr:hover {
    background-color: #f9f9f9;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    border-left: 5px solid #64c2a7;
    transform: translateX(2px);
}

th, td {
    padding: 10px;
    text-align: center;
    font-size: 15px;
    border-bottom: 1px solid #ccc; 
    border-right: 1px solid #ccc; 
}

tr:nth-child(odd):not(:first-child) {
    background-color: #f7f7f7;
}

/* Dark mode */
body.dark .tabela-funcionarios th {
    background-color: #2C2C2C;
    color: #e0e0e0;
    border-bottom: 1px solid #444;
    border-left: none !important;
    transition: none !important;
}

body.dark .tabela-funcionarios tbody tr {
    transition: all 0.2s ease-in-out;
    border-left: 0px solid #64c2a7;
}

body.dark .tabela-funcionarios tbody tr:hover {
    background-color: rgba(100, 194, 167, 0.1);
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
    border-left: 5px solid #64c2a7;
    transform: translateX(2px);
}

.status-ativo {
    color: #2e7d32;
    display: flex;
    align-items: center;
    gap: 5px;
}

.status-inativo {
    color: #ff8f00;
    display: flex;
    align-items: center;
    gap: 5px;
}

.status-terminado {
    color: #c62828;
}

.status-dot {
    display: inline-block;
    width: 12px;
    height: 12px;
    background-color: #64c2a7;
    border-radius: 50%;
}

.status-dot-yellow {
    background-color: #ffc107;
}

/* User avatar */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #64c2a7;
    color: white;
    font-weight: 500;
}

/* Adicionado: Estilos para a imagem dentro do user-avatar */
.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Garante que a imagem cubra a área do contêiner sem distorção */
}

/* Paginação */
.pagination {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    align-items: center;
    gap: 5px;
}

.pagination-item {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    color: #000;
}

.pagination-item.active {
    background-color: #64c2a7;
    color: white;
}

.suggestions-box {
    width: 100%;
    border: 1px solid #ccc;
    max-height: 200px;
    overflow-y: auto;
    position: absolute;
    top: 100%;
    left: 0;
    background-color: white;
    z-index: 1000;
    display: none;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-top: 5px;
}

.suggestion-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background-color: #f5f5f5;
}

/*Darkmode*/ 
body.dark {
    background-color: #1A1A1A;
    color: #e0e0e0;
}

body.dark .sidebar {
    background-color: #1E1E1E;
    border-right: 1px solid #333;
}

body.dark .nav-menu a {
    color: #b0b0b0;
}

body.dark .nav-menu a:hover,
body.dark .nav-menu a.active {
    color: #64c2a7;
    background-color: rgba(100, 194, 167, 0.1);
}

body.dark .nav-select {
    background-color: #2C2C2C;
    color: #e0e0e0;
    border-color: #444;
}

body.dark .main-content {
    background-color: #1A1A1A;
}



body.dark .page-title {
    color: #e0e0e0;
}

body.dark .filters {
    background-color: transparent;
}

body.dark .filter-select {
    background-color: #2C2C2C;
    color: #e0e0e0;
    border: 1px solid #444;
}

body.dark .search-bar {
    background-color: #2C2C2C;
    border: 1px solid #444;
}

body.dark .search-bar input {
    color: #e0e0e0;
}


body.dark .search-icon {
    color: #999;
}

body.dark .table-container {
    background-color: #1E1E1E;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}

body.dark .tabela-funcionarios {
    background-color: #1E1E1E;
}

body.dark .tabela-funcionarios th {
    background-color: #2C2C2C;
    color: #e0e0e0;
    border-bottom: 1px solid #444;
}

body.dark .tabela-funcionarios td {
    color: #d0d0d0;
    border-bottom: 1px solid #333;
    border-right: 1px solid #333;
}

body.dark .tabela-funcionarios tr:nth-child(odd) {
    background-color: #222;
}

body.dark .tabela-funcionarios tr:hover {
    background-color: rgba(100, 194, 167, 0.1);
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
}

body.dark .status-ativo {
    color: #81c784;
}

body.dark .status-inativo {
    color: #ffb74d;
}

body.dark .status-terminado {
    color: #e57373;
}

body.dark .user-avatar {
    background-color: rgba(100, 194, 167, 0.2);
}

body.dark .pagination-item {
    color: #e0e0e0;
}

body.dark .pagination-item.active {
    background-color: #64c2a7;
    color: #121212;
}

body.dark .suggestions-box {
    background-color: #2C2C2C;
    border-color: #444;
}

body.dark .suggestion-item {
    border-bottom: 1px solid #333;
    color: #e0e0e0;
}

body.dark .suggestion-item:hover {
    background-color: rgba(100, 194, 167, 0.1);
}

body.dark ::-webkit-scrollbar-track {
    background: #2C2C2C;
}

body.dark ::-webkit-scrollbar-thumb {
    background: #64c2a7;
}

/* Estilos para a div que envolve a foto e o nome */
.tabela-funcionarios .user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Estilos para a div que envolve os botões de ação */
.acoes {
    display: flex;
    gap: 10px;
}

/* Estilos para os botões de editar e apagar */
.btn-edit,
.btn-delete {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1;
    font-size: 16px;
}

.btn-edit {
    color: #3EB489; /* Cor verde */
}

.btn-delete {
    color: #f44336; /* Cor vermelha */
}

.btn-edit:hover,
.btn-delete:hover {
    transform: scale(1.1); /* Efeito de zoom ao passar o mouse */
}

.btn-edit i,
.btn-delete i {
    font-size: 18px; /* Tamanho dos ícones */
}

/* Estilos para o tooltip do cargo bloqueado */
.readonly-container[title] {
    position: relative;
    cursor: not-allowed;
}

.readonly-container[title]:hover::after {
    content: attr(title);
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(0, 0, 0, 0.8);
    color: #fff;
    padding: 5px 10px;
    border-radius: 5px;
    z-index: 1001;
    white-space: nowrap;
}

/* Adicionar estilos para a tabela de terminados */
<?php if ($estado_filtro === 'terminados'): ?>
<style>
    .table-container {
        background-color: white;
        border: 1px solid #ffcdd2;
    }
    
    .tabela-funcionarios th {
        background-color: #ffebee;
        color: #c62828;
        border-bottom: 1px solid #ffcdd2;
        border-left: none !important;
        transition: none !important;
    }
    
    .tabela-funcionarios td {
        border-bottom: 1px solid #ffcdd2;
        border-right: 1px solid #ffcdd2;
    }
    
    .tabela-funcionarios tbody tr {
        background-color: white;
        transition: all 0.2s ease-in-out;
        border-left: 0px solid #c62828;
    }
    
    .tabela-funcionarios tbody tr:nth-child(odd) {
        background-color: #f7f7f7;
    }
    
    .tabela-funcionarios tbody tr:hover {
        background-color: #ffebee;
        box-shadow: 0px 4px 10px rgba(198, 40, 40, 0.1);
        border-left: 5px solid #c62828;
        transform: translateX(2px);
    }
    
    .status-terminado {
        color: #c62828;
        font-weight: 500;
    }

    /* Avatar predefinido */
    .user-avatar {
        background-color: #64c2a7;
    }

    /* Paginação */
    .pagination-item.active {
        background-color: #64c2a7;
        color: white;
    }
    
    /* Dark mode para terminados */
    body.dark .table-container {
        background-color: #1E1E1E;
        border-color: #4a2c2c;
    }
    
    body.dark .tabela-funcionarios th {
        background-color: #3d2222;
        color: #ff8a8a;
        border-bottom-color: #4a2c2c;
        border-left: none !important;
        transition: none !important;
    }
    
    body.dark .tabela-funcionarios td {
        border-color: #4a2c2c;
    }
    
    body.dark .tabela-funcionarios tbody tr {
        background-color: #1E1E1E;
        transition: all 0.2s ease-in-out;
        border-left: 0px solid #c62828;
    }
    
    body.dark .tabela-funcionarios tbody tr:nth-child(odd) {
        background-color: #222;
    }
    
    body.dark .tabela-funcionarios tbody tr:hover {
        background-color: #3d2222;
        box-shadow: 0px 4px 10px rgba(198, 40, 40, 0.2);
        border-left: 5px solid #c62828;
        transform: translateX(2px);
    }

    /* Dark mode avatar predefinido */
    body.dark .user-avatar {
        background-color: #64c2a7;
    }

    /* Dark mode paginação */
    body.dark .pagination-item.active {
        background-color: #64c2a7;
        color: white;
    }
</style>
<?php endif; ?>
</style>
    <title>SAM - Funcionários</title>
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
            <a href="funcionarios.php"><li class="active">Funcionários</li></a>
            <a href="registro.php"><li>Novo Funcionário</li></a>
            <a href="processamento_salarial.php"><li>Processamento Salarial</li></a>
            <a href="docs.php"><li>Documentos</li></a>
            <a href="registro_ponto.php"><li>Registro de Ponto</li></a>
            <a href="ausencias.php"><li>Ausências</li></a>
            <a href="recrutamento.php"><li>Recrutamento</li></a>
        </ul>
    </div>

    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Funcionários</h1>
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

        <div class="filters">
            <form method="GET" action="funcionarios.php">
                <select class="filter-select" name="departamento_filtro" onchange="this.form.submit()">
                    <option value="todos" <?php echo $departamento_selecionado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <?php foreach ($departamentos_filtro as $depto): ?>
                        <option value="<?php echo $depto['id']; ?>" <?php echo $departamento_selecionado == $depto['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($depto['nome']); ?></option>
                    <?php endforeach; ?>
            </select>

                <select class="filter-select" name="estado_filtro" onchange="this.form.submit()">
                    <option value="ativos" <?php echo (!isset($_GET['estado_filtro']) || $_GET['estado_filtro'] === 'ativos') ? 'selected' : ''; ?>>Ativos e Inativos</option>
                    <option value="terminados" <?php echo (isset($_GET['estado_filtro']) && $_GET['estado_filtro'] === 'terminados') ? 'selected' : ''; ?>>Terminados</option>
                </select>

                <select class="filter-select" name="tipo_trabalhador_filtro" onchange="this.form.submit()">
                     <option value="todos" <?php echo (isset($_GET['tipo_trabalhador_filtro']) && $_GET['tipo_trabalhador_filtro'] === 'todos') ? 'selected' : ''; ?>>Todos</option>
                     <option value="efetivo" <?php echo (isset($_GET['tipo_trabalhador_filtro']) && $_GET['tipo_trabalhador_filtro'] === 'efetivo') ? 'selected' : ''; ?>>Trabalhador Efetivo</option>
                     <option value="temporario" <?php echo (isset($_GET['tipo_trabalhador_filtro']) && $_GET['tipo_trabalhador_filtro'] === 'temporario') ? 'selected' : ''; ?>>Trabalhador Temporário</option>
                     <option value="estagiario" <?php echo (isset($_GET['tipo_trabalhador_filtro']) && $_GET['tipo_trabalhador_filtro'] === 'estagiario') ? 'selected' : ''; ?>>Trabalhador Estagiário</option>
                     <option value="autonomo" <?php echo (isset($_GET['tipo_trabalhador_filtro']) && $_GET['tipo_trabalhador_filtro'] === 'autonomo') ? 'selected' : ''; ?>>Trabalhador Autônomo</option>
                     <option value="freelancer" <?php echo (isset($_GET['tipo_trabalhador_filtro']) && $_GET['tipo_trabalhador_filtro'] === 'freelancer') ? 'selected' : ''; ?>>Trabalhador Freelancer</option>
                     <option value="terceirizado" <?php echo (isset($_GET['tipo_trabalhador_filtro']) && $_GET['tipo_trabalhador_filtro'] === 'terceirizado') ? 'selected' : ''; ?>>Trabalhador Terceirizado</option>
                     <option value="intermitente" <?php echo (isset($_GET['tipo_trabalhador_filtro']) && $_GET['tipo_trabalhador_filtro'] === 'intermitente') ? 'selected' : ''; ?>>Trabalhador Intermitente</option>
                     <option value="voluntario" <?php echo (isset($_GET['tipo_trabalhador_filtro']) && $_GET['tipo_trabalhador_filtro'] === 'voluntario') ? 'selected' : ''; ?>>Trabalhador Voluntário</option>
            </select>

            <div class="search-bar">
                    <input type="text" name="search" id="search-input" placeholder="Pesquisar..." autocomplete="off" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit"><i class="fas fa-search search-icon"></i></button>
    <div id="suggestions" class="suggestions-box"></div>
</div>
            </form>
        </div>
        
        <div class="table-container">
            <table class="tabela-funcionarios">
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>nº</th>
                        <th>email</th>
                        <th>data</th>
                        <th>Estado</th>
                        <th>BI</th>
                        <th>Emissão BI</th>
                        <th>Validade BI</th>
                        <th>País</th>
                        <th>Morada</th>
                        <th>Gênero</th>
                        <th>Nº Agregados</th>
                        <th>Telemóvel</th>
                        <th>Cargo</th>
                        <th>Departamento</th>
                        <th>Tipo Trabalhador</th>
                        <th>Nº Segurança Social</th>
                        <th>Data Admissão</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { 
                        // Formatar as datas
                        $data_nascimento = !empty($row['data_nascimento']) ? date('d/m/Y', strtotime($row['data_nascimento'])) : '';
                        $emissao_bi = !empty($row['emissao_bi']) ? date('d/m/Y', strtotime($row['emissao_bi'])) : '';
                        $validade_bi = !empty($row['validade_bi']) ? date('d/m/Y', strtotime($row['validade_bi'])) : '';
                        $data_admissao = !empty($row['data_admissao']) ? date('d/m/Y', strtotime($row['data_admissao'])) : '';
                        
                        // Determinar a classe CSS para o estado
                        $estado_class = '';
                        $estado_texto = $row['estado'];
                        
                        if ($estado_texto == 'Ativo') {
                            $estado_class = 'status-ativo';
                        } else if ($estado_texto == 'Inativo') {
                            $estado_class = 'status-inativo';
                        } else if (strpos($estado_texto, 'Terminado') !== false) {
                            $estado_class = 'status-terminado';
                            // Calcular dias desde o término
                            $data_termino = new DateTime($row['data_termino']);
                            $hoje = new DateTime();
                            $dias_passados = $hoje->diff($data_termino)->days;
                            $dias_restantes = 30 - $dias_passados;
                            $estado_texto = "Terminado (Faltam {$dias_restantes}d)";
                        }
                    ?>
                    <tr data-id="<?php echo $row['id_fun']; ?>">
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="user-avatar">
                                    <?php 
                                        $foto = !empty($row['foto']) && file_exists($row['foto']) ? $row['foto'] : 'icones/icons-sam-18.svg';
                                    ?>
                                    <img src="<?php echo $foto; ?>" alt="Foto">
                                </div>
                                <span><?php echo htmlspecialchars($row['nome']); ?></span>
                            </div>
                        </td>
                        <td><?php echo str_pad($row['num_mecanografico'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo $data_nascimento; ?></td>
                        <td>
                            <span class="<?php echo $estado_class; ?>" <?php if (strpos($estado_texto, 'Terminado') !== false): ?>title="Daqui a <?php echo $dias_restantes; ?> dias, os dados do funcionário serão permanentemente apagados."<?php endif; ?>>
                                <?php echo htmlspecialchars($estado_texto); ?>
                                <?php if (strpos($estado_texto, 'Terminado') === false): ?>
                                    <?php if ($estado_texto == 'Ativo'): ?>
                                        <span class="status-dot"></span>
                                    <?php elseif ($estado_texto == 'Inativo'): ?>
                                        <span class="status-dot status-dot-yellow"></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['bi']); ?></td>
                        <td><?php echo $emissao_bi; ?></td>
                        <td><?php echo $validade_bi; ?></td>
                        <td><?php echo htmlspecialchars($row['pais']); ?></td>
                        <td><?php echo htmlspecialchars($row['morada']); ?></td>
                        <td><?php echo htmlspecialchars($row['genero']); ?></td>
                        <td><?php echo htmlspecialchars($row['num_agregados']); ?></td>
                        <td><?php echo htmlspecialchars($row['telemovel']); ?></td>
                        <td><?php echo htmlspecialchars($row['cargo_nome']); ?></td>
                        <td><?php echo htmlspecialchars($row['departamento_nome']); ?></td>
                        <td><?php echo htmlspecialchars($row['tipo_trabalhador']); ?></td>
                        <td><?php echo htmlspecialchars($row['num_ss']); ?></td>
                        <td><?php echo $data_admissao; ?></td>
                        <td>
                             <div class='acoes'>
                                <?php if (strpos($estado_texto, 'Terminado') !== false): ?>
                                    <form action='restaurar_funcionario.php' method='POST' style='display:inline;'>
                                        <input type='hidden' name='id_fun' value='<?php echo $row['id_fun']; ?>'>
                                        <button type='submit' class='btn-edit' title='Restaurar funcionário'>
                                            <i class='fas fa-undo'></i>
                                        </button>
                                    </form>
                                    <form action='eliminar_funcionario.php' method='POST' style='display:inline;'>
                                        <input type='hidden' name='id_fun' value='<?php echo $row['id_fun']; ?>'>
                                        <button type='submit' class='btn-delete' onclick='return confirm("Tem certeza que deseja eliminar permanentemente este funcionário? Esta ação não pode ser desfeita.")' title='Eliminar permanentemente'>
                                            <i class='fas fa-trash'></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href='editar_funcionario.php?id=<?php echo $row['id_fun']; ?>' class='btn-edit'>
                                        <i class='fas fa-edit'></i>
                                    </a>
                                    <form action='marcar_terminado.php' method='POST' style='display:inline;'>
                                        <input type='hidden' name='id_fun' value='<?php echo $row['id_fun']; ?>'>
                                        <button type='submit' class='btn-delete' onclick='return confirm("Tem certeza que deseja marcar este funcionário como terminado? Ele ficará disponível para exclusão permanente após 30 dias.")'>
                                            <i class='fas fa-times'></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="scroll-indicator"></div>
            
        </div>
        
        <div class="pagination">
    <?php if ($pagina_atual > 1): ?>
        <a href="?pagina=<?php echo $pagina_atual - 1; ?>" class="pagination-item">
            <i class="fas fa-chevron-left"></i>
        </a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <a href="?pagina=<?php echo $i; ?>" class="pagination-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($pagina_atual < $total_paginas): ?>
        <a href="?pagina=<?php echo $pagina_atual + 1; ?>" class="pagination-item">
            <i class="fas fa-chevron-right"></i>
        </a>
    <?php endif; ?>
</div>

    </div>
    <script>
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
        }
        updateTime();

        setInterval(updateTime, 1000);

        // Script para redirecionar ao clicar na linha da tabela
        document.addEventListener('DOMContentLoaded', function() {
            const tabelaFuncionarios = document.querySelector('.tabela-funcionarios tbody');
            if (tabelaFuncionarios) {
                tabelaFuncionarios.addEventListener('click', function(event) {
                    // Encontra a linha (tr) clicada, subindo a partir do elemento clicado
                    const row = event.target.closest('tr');
                    
                    // Verifica se uma linha foi encontrada e se não foi o clique nos botões de ação
                    if (row && !event.target.closest('div.acoes') && !event.target.closest('a.btn-edit') && !event.target.closest('button.btn-delete')) {
                        const funcionarioId = row.getAttribute('data-id');
                        if (funcionarioId) {
                            window.location.href = `detalhes_funcionario.php?id=${funcionarioId}`;
                        }
                    }
                });
            }
        });
    </script>
    <script src="sugestoes.js"></script>
    <script src="./js/theme.js"></script>
</body>
</html>