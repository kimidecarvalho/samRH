<?php
// Verifica se a sessão já foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Limpar os dados do formulário apenas quando for uma requisição GET (F5) e não houver erro
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_SESSION['error'])) {
    unset($_SESSION['form_data']);
}

include('protect.php');
include('config.php'); // Conexão com o banco
include_once('includes/sync_app.php'); // Incluir arquivo de sincronização

if (!isset($conn)) {
    die("Erro: Conexão com o banco de dados não estabelecida.");
}

// Obter o id_empresa do administrador
$admin_id = $_SESSION['id_adm'];
$sql_admin = "SELECT e.id_empresa FROM empresa e WHERE e.adm_id = ?";
$stmt_admin = $conn->prepare($sql_admin);

if (!$stmt_admin) {
    die("Erro na preparação da consulta admin: " . $conn->error);
}

$stmt_admin->bind_param("i", $admin_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$admin = $result_admin->fetch_assoc();

if ($admin) {
    $empresa_id = $admin['id_empresa'];
} else {
    die("Erro: Nenhuma empresa cadastrada para este administrador.");
}

$stmt_admin->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturar os dados do formulário
    $nome = $_POST['nome'] ?? '';
    $bi = $_POST['bi'] ?? '';
    $emissao_bi = $_POST['emissao_bi'] ?? '';
    $validade_bi = $_POST['validade_bi'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $pais = $_POST['pais'] ?? '';
    $morada = $_POST['morada'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $num_agregados = $_POST['num_agregados'] ?? 0;
    $contato_emergencia = $_POST['contato_emergencia'] ?? '';
    $nome_contato_emergencia = $_POST['nome_contato_emergencia'] ?? '';
    $telemovel = $_POST['telemovel'] ?? '';
    $email = $_POST['email'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $departamento = $_POST['departamento'] ?? '';
    $tipo_trabalhador = $_POST['tipo_trabalhador'] ?? '';
    $num_conta_bancaria = $_POST['num_conta_bancaria'] ?? '';
    $banco = $_POST['banco'] ?? '';
    $iban = $_POST['iban'] ?? '';
    $salario_base = $_POST['salario_base'] ?? 0.00;
    $num_ss = $_POST['num_ss'] ?? '';

    // Salvar os dados do formulário na sessão antes de qualquer validação
    $_SESSION['form_data'] = $_POST;

    // Verificar se "Outro" foi selecionado e usar o valor do campo adicional
    /* Removido:
    if ($banco === 'OUTRO') {
        $banco = $_POST['outro_banco'] ?? '';
    }
    */

    // Pegando o ID do admin logado
    if (!isset($_SESSION['id_adm'])) {
        $_SESSION['error'] = "Erro: Sessão expirada ou admin não autenticado.";
        header("Location: registro.php");
        exit;
    }
    $id_adm = $_SESSION['id_adm'];

    // Buscar o id_empresa da empresa do admin logado
    $sql_empresa = "SELECT id_empresa FROM empresa WHERE adm_id = ?";
    $stmt_empresa = $conn->prepare($sql_empresa);
    
    if (!$stmt_empresa) {
        $_SESSION['error'] = "Erro na preparação da consulta da empresa: " . $conn->error;
        header("Location: registro.php");
        exit;
    }

    $stmt_empresa->bind_param("i", $id_adm);
    $stmt_empresa->execute();
    $result_empresa = $stmt_empresa->get_result();

    if ($result_empresa->num_rows > 0) {
        $empresa = $result_empresa->fetch_assoc();
        $empresa_id = $empresa['id_empresa']; 
    } else {
        $_SESSION['error'] = "Erro: Nenhuma empresa cadastrada para este administrador.";
        header("Location: registro.php");
        exit;
    }

    // Diretório para armazenar imagens
    $uploadDir = "fotos/";
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $_SESSION['error'] = "Erro ao criar diretório para fotos.";
            header("Location: registro.php");
            exit;
        }
    }

    $fotoFinal = NULL; // Por padrão, deixa o campo NULL no banco

    // Processar upload da foto se o usuário enviou
    if (!empty($_FILES["foto"]["name"])) {
        $ext = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
        $fotoNome = uniqid("func_") . "." . $ext;
        $fotoCaminho = $uploadDir . $fotoNome;

        if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $fotoCaminho)) {
            $_SESSION['error'] = "Erro ao fazer upload da foto.";
            header("Location: registro.php");
            exit;
        }
        $fotoFinal = $fotoCaminho; // Guarda o caminho da foto no banco
    }

    // Iniciar transação para garantir integridade dos dados
    $conn->begin_transaction();

    try {
        // Preparar a query SQL para inserir funcionário
        $sql = "INSERT INTO funcionario 
            (nome, foto, bi, emissao_bi, validade_bi, data_nascimento, pais, morada, genero, num_agregados, 
            contato_emergencia, nome_contato_emergencia, telemovel, email, estado, cargo, departamento, 
            tipo_trabalhador, num_conta_bancaria, banco, iban, salario_base, num_ss, empresa_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Não foi possível iniciar o cadastro. Por favor, tente novamente.");
        }

        $stmt->bind_param("sssssssssissssssssssdsii", 
            $nome, $fotoFinal, $bi, $emissao_bi, $validade_bi, $data_nascimento, 
            $pais, $morada, $genero, $num_agregados, $contato_emergencia, 
            $nome_contato_emergencia, $telemovel, $email, $estado, $cargo, 
            $departamento, $tipo_trabalhador, $num_conta_bancaria, $banco, 
            $iban, $salario_base, $num_ss, $empresa_id
        );

        if (!$stmt->execute()) {
            // Verificar se é um erro de duplicação
            if ($stmt->errno == 1062) {
                // Extrair o campo duplicado e o valor da mensagem de erro
                $error_message = $stmt->error;
                $duplicate_value = '';
                
                // Extrair o valor duplicado da mensagem de erro
                if (preg_match("/'([^']+)' for key/", $error_message, $matches)) {
                    $duplicate_value = $matches[1];
                }

                // Criar mensagem amigável baseada no campo duplicado
                if (strpos($error_message, 'num_conta_bancaria') !== false) {
                    $error_message = "Ops! Parece que a conta bancária $duplicate_value já está registrada em nosso sistema.";
                } elseif (strpos($error_message, 'email') !== false) {
                    $error_message = "Ops! O e-mail $duplicate_value já está cadastrado em nosso sistema.";
                } elseif (strpos($error_message, 'bi') !== false) {
                    $error_message = "Ops! O número de BI $duplicate_value já está registrado em nosso sistema.";
                } elseif (strpos($error_message, 'num_ss') !== false) {
                    $error_message = "Ops! O número de Segurança Social $duplicate_value já está registrado em nosso sistema.";
                } else {
                    $error_message = "Ops! Parece que este registro já existe em nosso sistema.";
                }
                
                // Salvar a mensagem de erro na sessão
                $_SESSION['error'] = $error_message;
                header("Location: registro.php");
                exit;
            } else {
                // Mensagens personalizadas para outros tipos de erro
                switch ($stmt->errno) {
                    case 1048: // Campo não pode ser nulo
                        $error_message = "Ops! Parece que alguns campos obrigatórios não foram preenchidos.";
                        break;
                    case 1406: // Dados muito longos
                        $error_message = "Ops! Alguns dados excedem o tamanho permitido.";
                        break;
                    case 1452: // Erro de chave estrangeira
                        $error_message = "Ops! Alguns dados selecionados não são válidos.";
                        break;
                    case 1366: // Erro de tipo de dado
                        $error_message = "Ops! Alguns dados foram preenchidos em formato incorreto.";
                        break;
                    default:
                        $error_message = "Ops! Algo deu errado ao tentar cadastrar o funcionário. Por favor, tente novamente.";
                }
                throw new Exception($error_message);
            }
        }

        // Obter o ID do funcionário recém-cadastrado
        $funcionario_id = $stmt->insert_id;
        $stmt->close();

        // Inserir horário padrão na tabela horarios_funcionarios
        $sql_horario = "INSERT INTO horarios_funcionarios (funcionario_id, hora_entrada, hora_saida) VALUES (?, '08:00', '16:00')";
        $stmt_horario = $conn->prepare($sql_horario);
        $stmt_horario->bind_param("i", $funcionario_id);
        $stmt_horario->execute();
        $stmt_horario->close();

        // Sincronizar com o aplicativo
        sincronizarFuncionarioSiteParaApp($funcionario_id, $empresa_id);

        // Commit da transação se tudo ocorrer bem
        $conn->commit();

        // Limpar os dados do formulário da sessão após sucesso
        unset($_SESSION['form_data']);

        // Redirecionar com mensagem de sucesso
        $_SESSION['mensagem'] = "Funcionário cadastrado com sucesso!";
        header("Location: funcionarios.php");
        exit;

    } catch (Exception $e) {
        // Rollback em caso de erro
        $conn->rollback();
        
        // Excluir a foto se foi enviada mas ocorreu erro depois
        if ($fotoFinal && file_exists($fotoFinal)) {
            unlink($fotoFinal);
        }
        
        // Verificar se é um erro de duplicação
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $error_message = $e->getMessage();
            $duplicate_value = '';
            
            // Extrair o valor duplicado da mensagem de erro
            if (preg_match("/'([^']+)' for key/", $error_message, $matches)) {
                $duplicate_value = $matches[1];
            }

            // Criar mensagem amigável baseada no campo duplicado
            if (strpos($error_message, 'num_conta_bancaria') !== false) {
                $_SESSION['error'] = "Ops! Parece que a conta bancária $duplicate_value já está registrada em nosso sistema.";
            } elseif (strpos($error_message, 'email') !== false) {
                $_SESSION['error'] = "Ops! O e-mail $duplicate_value já está cadastrado em nosso sistema.";
            } elseif (strpos($error_message, 'bi') !== false) {
                $_SESSION['error'] = "Ops! O número de BI $duplicate_value já está registrado em nosso sistema.";
            } elseif (strpos($error_message, 'num_ss') !== false) {
                $_SESSION['error'] = "Ops! O número de Segurança Social $duplicate_value já está registrado em nosso sistema.";
            } else {
                $_SESSION['error'] = "Ops! Parece que este registro já existe em nosso sistema.";
            }
        } else {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header("Location: registro.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="all.css/registro3.css">
    <link rel="stylesheet" href="./all.css/timer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAM - Novo Funcionário</title>
    <style>
        .nav-menu a {
            text-decoration: none;
        }
        .exit-tag {
        text-decoration: none;
        }

        .profile-circle {
            border-radius: 50%; /* Garante que a borda seja totalmente circular */
            overflow: hidden; /* Corta qualquer parte da imagem que sair do círculo */
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #ccc; /* Cor de fundo caso não tenha imagem */
            cursor: pointer;
            margin-left: 10px;
            margin-top: 20px;
        }

        .profile-circle img {
            width: 100%; /* Faz a imagem ocupar toda a largura */
            height: 100%; /* Faz a imagem ocupar toda a altura */
            object-fit: cover; /* Garante que a imagem cubra todo o círculo */
        }

        

        .btn-confirm {
        background-color: #3EB489;
        color: white;   
        padding: 8px 25px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        float: right;
        margin-top: -49px;
        font-size: 14px;
        }
        /* Estilos para campos somente leitura */
        input[readonly] {
            background-color: #f0f0f0;
            cursor: not-allowed;
            color: #555;
            border: 1px solid #ddd;
        }

        .readonly-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        /* Estilo para o ícone de cadeado */
        .lock-icon {
            position: absolute;
            right: 10px;
            color: #666;
            font-size: 14px;
            display: none; /* Começa oculto por padrão */
        }

        .readonly-container select:disabled ~ .lock-icon {
            display: block; /* Mostra apenas quando o select está desabilitado */
        }

        /* Estilos específicos para o campo Estado */
        input[placeholder="Ativo*"][readonly] {
            font-weight: 500;
        }

        /* Estilos específicos para o campo Salário base */
        input[placeholder="Ad. automaticamente*"][readonly] {
            font-weight: 500;
            color: #ff0000d5 !important; /* Garantir que a fonte seja vermelha */
        }

/* Media Queries for Responsive Design */

@media (max-width: 1200px) {
    /* Adjust form grid for medium sized screens */
    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    /* Slightly reduce sidebar width */
    .sidebar {
        width: 220px;
    }
    
    .main-content {
        margin-left: 220px;
    }
}

@media (max-width: 992px) {
    /* Reduce sidebar width */
    .sidebar {
        width: 200px;
    }
    
    .main-content {
        margin-left: 200px;
        padding: 15px 25px;
    }
    
    /* Adjust form wrapper */
    .form-wrapper {
        flex-direction: column;
        height: auto;
    }
    
    /* Adjust sections for vertical stacking */
    .form-section {
        padding: 30px;
    }
    
    /* Adjust border radius for vertical stacking */
    .personal-info {
        border-radius: 25px 25px 0 0;
    }
    
    .professional-info {
        border-radius: 0 0 25px 25px;
    }
    
    /* Reposition profile circle for vertical layout */
    .profile-circle {
        top: 0;
        transform: translate(-50%, -50%);
        margin-top: 86.5%;
    }
    
    /* Adjust button position */
    .btn-confirm {
        margin-top: 20px;
        float: right;
    }
}

@media (max-width: 768px) {
    /* Reduce sidebar width further */
    .sidebar {
        width: 180px;
    }
    
    .main-content {
        margin-left: 180px;
        padding: 15px 20px;
    }
    
    .profile-circle{
        margin-top: 161vh;
    }
    /* Single column for form grid on tablets */
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    /* Adjust header for smaller screens */
    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .header-buttons {
        width: 100%;
        justify-content: space-between;
    }
    
    /* Adjust search container */
    .search-container {
        flex-wrap: wrap;
    }
    
    .search-container input {
        width: 100%;
    }
    
    .search-container .search-bar {
        width: 100%;
    }
}

@media (max-width: 576px) {
    /* Sidebar adjustments for mobile */
    .sidebar {
        width: 160px;
    }
    
    .main-content {
        margin-left: 160px;
        padding: 10px 15px;
    }
    
    /* Reduce padding for form sections */
    .form-section {
        padding: 25px 15px;
    }
    
    /* Adjust section titles */
    .section-title {
        font-size: 18px;
        margin-bottom: 20px;
    }
    
    /* Simplify user profile display */
    .user-profile span {
        display: none;
    }
    
    /* Make document note more compact */
    .document-note {
        height: auto;
        padding: 20px;
    }
    
    /* Adjust button size */
    .btn-confirm {
        width: 100%;
        margin-top: 15px;
    }
}

@media (max-width: 480px) {
    /* Full width layout for very small screens */
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding: 15px;
    }
    
    .main-content {
        margin-left: 0;
        padding: 15px;
    }
    
    .nav-menu {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 5px;
    }
    
    .nav-menu li {
        padding: 8px 12px;
        font-size: 12px;
    }
    
    /* Adjust form sections for full width */
    .form-section {
        border-radius: 25px;
        margin-bottom: 20px;
    }
    
    .personal-info, .professional-info {
        border-radius: 25px;
    }
    
    /* Center profile circle between sections */
    .profile-circle {
        position: relative;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        margin: -32.5px 0;
    }
    
    /* Simplify header elements */
    .time, .exit-tag, .user-profile {
        padding: 6px 10px;
        font-size: 12px;
    }
}


/* Dark Mode Styles */
body.dark {
        background-color: #1A1A1A;
        color: #e0e0e0;
    }

    body.dark .sidebar {
        background-color: #1E1E1E;
        border-right: 1px solid #333;
    }

    body.dark .header-buttons {
        background-color: #2C2C2C;
    }

    body.dark .user-profile {
        background-color: #2C2C2C; 
        color: #ffffff;
    }

    body.dark #current-time {
        background-color: #2C2C2C;
        color: #ffffff;
    }

    body.dark .logo img {
        filter: brightness(0.8) contrast(1.2);
    }

    body.dark .nav-menu {
        background-color: #1E1E1E;
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

    body.dark .time, 
    body.dark .exit-tag, 
    body.dark .user-profile {
        color: #e0e0e0;
    }

    body.dark .search-container input {
        background-color: #2C2C2C;
        color: #e0e0e0;
        border: 1px solid #444;
    }

    body.dark .form-section {
        background-color: #1E1E1E;
        border: 1px solid #333;
    }

    body.dark .personal-info{
        background-color: #3EB489;
        
    }

    body.dark .personal-info .form-group label{
        color: white;

    }

    body.dark .document-note {
    display: flex;
    align-items: center;
    justify-content: center; 
    text-align: center;
    padding: 30px;
    border: 2px dashed #70c7b0;
    border-radius: 25px;
    color: white; 
    margin-top: 10px;
    height: 200px;
    background-color: #1E1E1E;
    }

    body.dark .document-note a {
        color: #70c7b0;
        text-decoration: none;
        font-weight: bold;
    }

    body.dark .personal-info .form-group input,
    body.dark .personal-info .form-group select {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #333;
    }

    body.dark .section-title {
        color: #e0e0e0;
    }

    body.dark .form-group label {
        color: #b0b0b0;
    }

    body.dark .form-group input,
    body.dark .form-group select {
        background-color: #2C2C2C;
        color: #e0e0e0;
        border: 1px solid #444;
    }

    body.dark .form-group input::placeholder {
        color: #888;
    }

    body.dark .profile-circle {
        background-color: #2C2C2C;
        border: 2px solid #444;
    }

    body.dark .btn-confirm {
        background-color: #64c2a7;
        color: #121212;
    }

    body.dark input[readonly] {
        background-color: #2C2C2C;
        color: #888;
        border: 1px solid #444;
    }

    /* Scrollbar Styles */
    body.dark ::-webkit-scrollbar-track {
        background: #2C2C2C;
    }

    body.dark ::-webkit-scrollbar-thumb {
        background: #64c2a7;
    }

    /* Estilo para esconder a seta do select quando desabilitado */
    select:disabled {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: none !important;
    }

    /* Estilo para o container do select com cadeado */
    .readonly-container {
        position: relative;
        display: flex; /* Garante que select e ícone fiquem na mesma linha */
        align-items: center;
        width: 100%;
    }

    /* Estilo base para o select */
    .readonly-container select {
        padding-right: 30px; /* Espaço para o ícone/seta */
        width: 100%;
        /* Remove a seta padrão do navegador e outros estilos nativos */
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        /* Posição e tamanho para a seta personalizada */
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 15px;
        /* Cor de fundo padrão */
        background-color: #fff; /* Ou outra cor padrão do tema light */
        color: #333; /* Cor do texto padrão */
        border: 1px solid #ccc; /* Borda padrão */
    }

    /* Adiciona a seta personalizada E controla a cor de fundo/texto/borda quando habilitado */
    .readonly-container select:not(:disabled) {
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/polyline%3e%3c/svg%3e") !important; /* Adicionado !important */
        /* Você pode adicionar estilos de cor de fundo, borda, texto aqui se precisar diferenciar habilitado/desabilitado além do disabled */
        background-color: #fff; /* Exemplo */
        color: #333; /* Exemplo */
        border-color: #ccc; /* Exemplo */
    }

    /* Estilo para select desabilitado */
    .readonly-container select:disabled {
        background-image: none !important; /* Garante que a seta personalizada não apareça */
        background-color: #f0f0f0; /* Cor para indicar que está desabilitado */
        cursor: not-allowed;
        color: #555; /* Cor do texto desabilitado */
        border-color: #ddd; /* Borda desabilitada */
    }

    /* Estilo para o ícone do cadeado */
    .readonly-container .lock-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none; /* Não interfere com cliques no select */
        color: #666;
        font-size: 14px;
        /* Controla a visibilidade com opacidade para transição suave */
        opacity: 0; /* Oculto por padrão */
        transition: opacity 0.2s ease;
    }

    /* Mostra o cadeado apenas quando o select está desabilitado */
    .readonly-container select:disabled ~ .lock-icon {
        opacity: 1; /* Visível quando desabilitado */
    }

    /* Dark Mode Styles */
    body.dark .readonly-container select:not(:disabled) {
        background-color: #2C2C2C;
        color: #e0e0e0;
        border: 1px solid #444;
        /* Ajuste a cor da seta SVG para o modo escuro se necessário */
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23e0e0e0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/polyline%3e%3c/svg%3e") !important; /* Adicionado !important */ /* Cor #e0e0e0 para a seta no dark mode */
    }

    body.dark .readonly-container select:disabled {
        background-color: #2C2C2C;
        color: #888;
        border: 1px solid #444;
    }

    body.dark .readonly-container .lock-icon {
         color: #b0b0b0; /* Cor do cadeado no dark mode */
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
            <a href="registro.php"><li class="active">Novo Funcionário</li></a>
            <a href="processamento_salarial.php"><li>Processamento Salarial</li></a>
            <a href="docs.php"><li>Documentos</li></a>
            <a href="registro_ponto.php"><li>Registro de Ponto</li></a>
            <a href="ausencias.php"><li>Ausências</li></a>
            <a href="recrutamento.php"><li>Recrutamento</li></a>
        </ul>
    </div>

   
    <div class="main-content">
    <header class="header">
            <h1 class="page-title">Registro Funcionário</h1>
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

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']); // Limpa a mensagem após exibir
                ?>
            </div>
        <?php endif; ?>

        <div class="search-container">
            <input type="text" placeholder="">
            <input type="text" placeholder="">
            <input type="text" placeholder="Pesquisar..." class="search-bar">
        </div>

    <form action="registro.php" method="POST" enctype="multipart/form-data">
        <div class="form-wrapper">
            <div class="form-section personal-info">
                <h2 class="section-title">
                    <img src="path-to-personal-icon.svg" alt="">
                    Identificação e Relações Pessoais
                </h2>
                <div class="form-grid">

                    <div class="form-group">
                        <label>Nome do funcionário</label>
                        <input type="text" id="nome" name="nome" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['nome']) ? htmlspecialchars($_SESSION['form_data']['nome']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Nº do BI</label>
                        <input type="text" id="bi" name="bi" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['bi']) ? htmlspecialchars($_SESSION['form_data']['bi']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Emissão do BI</label>
                        <input type="date" id="emissao_bi" name="emissao_bi" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['emissao_bi']) ? htmlspecialchars($_SESSION['form_data']['emissao_bi']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Validade do BI</label>
                        <input type="date" id="validade_bi" name="validade_bi" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['validade_bi']) ? htmlspecialchars($_SESSION['form_data']['validade_bi']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Data de Nascimento</label style="white-space: nowrap;">
                        <input type="date" id="data_nascimento" name="data_nascimento" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['data_nascimento']) ? htmlspecialchars($_SESSION['form_data']['data_nascimento']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>País</label>
                        <select name="pais" id="pais" required>
                            <option value="">Selecione um país</option>
                            <?php
                            $paises = [
                                'angola' => 'Angola',
                                'argentina' => 'Argentina',
                                'brasil' => 'Brasil',
                                'canada' => 'Canadá',
                                'chile' => 'Chile',
                                'china' => 'China',
                                'colombia' => 'Colômbia',
                                'espanha' => 'Espanha',
                                'estados_unidos' => 'Estados Unidos',
                                'franca' => 'França',
                                'alemanha' => 'Alemanha',
                                'italia' => 'Itália',
                                'japao' => 'Japão',
                                'mexico' => 'México',
                                'moçambique' => 'Moçambique',
                                'portugal' => 'Portugal',
                                'reino_unido' => 'Reino Unido',
                                'russia' => 'Rússia',
                                'africa_do_sul' => 'África do Sul',
                                'australia' => 'Austrália',
                                'coreia_do_sul' => 'Coreia do Sul',
                                'india' => 'Índia',
                                'indonesia' => 'Indonésia',
                                'nigeria' => 'Nigéria',
                                'venezuela' => 'Venezuela'
                            ];
                            foreach ($paises as $codigo => $nome) {
                                $selected = (isset($_SESSION['form_data']['pais']) && $_SESSION['form_data']['pais'] === $codigo) ? 'selected' : '';
                                echo "<option value='$codigo' $selected>$nome</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Morada</label>
                        <input type="text" id="morada" name="morada" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['morada']) ? htmlspecialchars($_SESSION['form_data']['morada']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Gênero</label>
                        <select name="genero" id="genero" class="date" required>
                            <option value="masculino" <?php echo (isset($_SESSION['form_data']['genero']) && $_SESSION['form_data']['genero'] === 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="feminino" <?php echo (isset($_SESSION['form_data']['genero']) && $_SESSION['form_data']['genero'] === 'feminino') ? 'selected' : ''; ?>>Feminino</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nº de agregados</label>
                        <input type="number" name="num_agregados" id="num_agregados" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['num_agregados']) ? htmlspecialchars($_SESSION['form_data']['num_agregados']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contato de emergência</label>
                        <input type="tel" id="contato_emergencia" name="contato_emergencia" placeholder="Digite o número de telefone" value="<?php echo isset($_SESSION['form_data']['contato_emergencia']) ? htmlspecialchars($_SESSION['form_data']['contato_emergencia']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nome do contato emergência</label>
                        <input type="text" name="nome_contato_emergencia" id="nome_contato_emergencia" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['nome_contato_emergencia']) ? htmlspecialchars($_SESSION['form_data']['nome_contato_emergencia']) : ''; ?>" required>
                    </div>

                </div>
            </div>

            <div class="profile-circle" onclick="document.getElementById('foto').click();">
                <img id="preview" src="icones/icons-sam-18.svg" alt="Inserir">
            </div>

            <!-- Input escondido para upload da foto -->
            <input type="file" name="foto" id="foto" accept="image/*" style="display: none;" onchange="previewImage(event)">

            <div class="form-section professional-info">
                <h2 class="section-title">
                    Dados Profissionais e Financeiros
                </h2>
                <div class="form-grid">

                <div class="form-group">
                    <label>Telemóvel</label>
                    <input type="tel" id="telemovel" name="telemovel" placeholder="Digite o número de telefone" value="<?php echo isset($_SESSION['form_data']['telemovel']) ? htmlspecialchars($_SESSION['form_data']['telemovel']) : ''; ?>" required>
                </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" name="email" id="email" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <input type="text" name="estado" id="estado" placeholder="Ativo*" readonly value="<?php echo isset($_SESSION['form_data']['estado']) ? htmlspecialchars($_SESSION['form_data']['estado']) : 'Ativo'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="departamento">Departamento</label>
                        <select id="departamento" name="departamento" required>
                            <option value="">Selecione o Departamento</option>
                            <?php
                            // Buscar departamentos
                            $sql_departamentos = "SELECT id, nome FROM departamentos WHERE empresa_id = ? ORDER BY nome";
                            $stmt_departamentos = $conn->prepare($sql_departamentos);
                            
                            if ($stmt_departamentos) {
                                $stmt_departamentos->bind_param("i", $empresa_id);
                                $stmt_departamentos->execute();
                                $result_departamentos = $stmt_departamentos->get_result();
                                
                                while($depto = $result_departamentos->fetch_assoc()) {
                                    $selected = (isset($_SESSION['form_data']['departamento']) && $_SESSION['form_data']['departamento'] == $depto['id']) ? 'selected' : '';
                                    echo "<option value='".$depto['id']."' $selected>".$depto['nome']."</option>";
                                }
                                
                                $stmt_departamentos->close();
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cargo">Cargo</label>
                        <div class="readonly-container" title="Selecione o Departamento primeiro para prosseguir">
                            <select id="cargo" name="cargo" required <?php echo (!isset($_SESSION['form_data']['departamento']) || empty($_SESSION['form_data']['departamento'])) ? 'disabled' : ''; ?>>
                                <option value="">Selecione o Cargo</option>
                                <?php
                                if (isset($_SESSION['form_data']['departamento']) && !empty($_SESSION['form_data']['departamento'])) {
                                    // Buscar cargos do departamento selecionado
                                    $sql_cargos = "SELECT id, nome, salario_base FROM cargos WHERE departamento_id = ? ORDER BY nome";
                                    $stmt_cargos = $conn->prepare($sql_cargos);
                                    
                                    if ($stmt_cargos) {
                                        $stmt_cargos->bind_param("i", $_SESSION['form_data']['departamento']);
                                        $stmt_cargos->execute();
                                        $result_cargos = $stmt_cargos->get_result();
                                        
                                        while($cargo = $result_cargos->fetch_assoc()) {
                                            $selected = (isset($_SESSION['form_data']['cargo']) && $_SESSION['form_data']['cargo'] == $cargo['id']) ? 'selected' : '';
                                            echo "<option value='".$cargo['id']."' data-salario='".$cargo['salario_base']."' $selected>".$cargo['nome']."</option>";
                                        }
                                        
                                        $stmt_cargos->close();
                                    }
                                }
                                ?>
                            </select>
                            <i class="fas fa-lock lock-icon" style="display: <?php echo (!isset($_SESSION['form_data']['departamento']) || empty($_SESSION['form_data']['departamento'])) ? 'block' : 'none'; ?>"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="salario_base">Salário Base</label>
                        <div class="readonly-container" title="Salário Calculado automaticamente">
                            <input type="number" id="salario_base" name="salario_base" step="0.01" readonly value="<?php echo isset($_SESSION['form_data']['salario_base']) ? htmlspecialchars($_SESSION['form_data']['salario_base']) : ''; ?>">
                            <i class="fas fa-lock lock-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo</label>
                        <select size="1" name="tipo_trabalhador" id="tipo_trabalhador" required>
                            <option value="">Selecione um tipo de trabalhador</option>
                            <?php
                            $tipos = [
                                'efetivo' => 'Trabalhador Efetivo',
                                'temporario' => 'Trabalhador Temporário',
                                'estagiario' => 'Trabalhador Estagiário',
                                'autonomo' => 'Trabalhador Autônomo',
                                'freelancer' => 'Trabalhador Freelancer',
                                'terceirizado' => 'Trabalhador Terceirizado',
                                'intermitente' => 'Trabalhador Intermitente',
                                'voluntario' => 'Trabalhador Voluntário'
                            ];
                            foreach ($tipos as $codigo => $nome) {
                                $selected = (isset($_SESSION['form_data']['tipo_trabalhador']) && $_SESSION['form_data']['tipo_trabalhador'] === $codigo) ? 'selected' : '';
                                echo "<option value='$codigo' $selected>$nome</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Nº conta banco</label>
                        <input type="number" name="num_conta_bancaria" id="num_conta_bancaria" title="Número da conta bancária" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['num_conta_bancaria']) ? htmlspecialchars($_SESSION['form_data']['num_conta_bancaria']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Banco</label>
                        <select size="1" name="banco" id="banco" required>
                            <option value="">Selecione um banco</option>
                            <?php
                            // Buscar bancos ativos
                            $sql_bancos = "SELECT banco_nome, banco_codigo FROM bancos_ativos WHERE empresa_id = ? AND ativo = 1 ORDER BY banco_nome";
                            $stmt_bancos = $conn->prepare($sql_bancos);
                            
                            if (!$stmt_bancos) {
                                die("Erro na preparação da consulta: " . $conn->error);
                            }
                            
                            $stmt_bancos->bind_param("i", $empresa_id);
                            
                            if (!$stmt_bancos->execute()) {
                                die("Erro ao executar a consulta: " . $stmt_bancos->error);
                            }
                            
                            $result_bancos = $stmt_bancos->get_result();
                            
                            if ($result_bancos->num_rows > 0) {
                                while($banco = $result_bancos->fetch_assoc()) {
                                    $selected = (isset($_SESSION['form_data']['banco']) && $_SESSION['form_data']['banco'] === $banco['banco_codigo']) ? 'selected' : '';
                                    echo "<option value='".$banco['banco_codigo']."' $selected>".$banco['banco_nome']."</option>";
                                }
                            }
                            
                            $stmt_bancos->close();
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="IBAN">IBAN</label>
                        <input type="text" id="iban" name="iban" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['iban']) ? htmlspecialchars($_SESSION['form_data']['iban']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nº SS</label>
                        <input type="text" name="num_ss" id="num_ss" placeholder="Digite aqui" value="<?php echo isset($_SESSION['form_data']['num_ss']) ? htmlspecialchars($_SESSION['form_data']['num_ss']) : ''; ?>" required>
                    </div>

                </div>
                <button type="submit" class="btn-confirm">confirmar</button>
            </div>
        </div>
    </form>

        <div class="document-note">
            Clique no retângulo para abrir o&nbsp;<a href="#"> Gestor de documentos</a>&nbsp; do funcionário
        </div>
    </div>

    <script src="UI.js"></script>
    <script>
// Definir os salários base para cada cargo (em KZs)
const salariosPorCargo = {
    "Administrador": "220.000,00",
    "Analista Financeiro": "180.000,00",
    "Assistente Administrativo": "90.000,00",
    "Assistente de Recursos Humanos": "100.000,00",
    "Atendente Comercial": "85.000,00",
    "Auditor": "200.000,00",
    "Contabilista": "170.000,00",
    "Coordenador de Projetos": "210.000,00",
    "Diretor Comercial": "300.000,00",
    "Diretor de Recursos Humanos": "300.000,00",
    "Engenheiro Civil": "250.000,00",
    "Engenheiro Informático": "240.000,00",
    "Especialista em Marketing": "150.000,00",
    "Gerente de Contas": "180.000,00",
    "Gestor de Projetos": "200.000,00",
    "Jurista": "230.000,00",
    "Operador de Caixa": "80.000,00",
    "Operador de Máquinas": "90.000,00",
    "Programador": "200.000,00",
    "Rececionista": "85.000,00",
    "Secretário Executivo": "120.000,00",
    "Supervisor de Vendas": "160.000,00",
    "Técnico de Manutenção": "110.000,00",
    "Técnico de Suporte": "120.000,00",
    "Vendedor": "95.000,00"
};

// Função para adicionar ícone de bloqueio a um campo
function adicionarIconeBloqueio(input, isSalario = false) {
    // Criar o contêiner para o campo e o ícone
    const container = document.createElement('div');
    container.className = 'readonly-container';
    container.style.position = 'relative';
    container.style.display = 'flex';
    container.style.alignItems = 'center';
    container.style.width = '100%';
    
    // Obter o elemento pai do input
    const parentElement = input.parentElement;
    
    // Substituir o input original pelo contêiner
    parentElement.appendChild(container);
    
    // Adicionar estilo ao input
    input.style.paddingRight = '30px'; // Espaço para o ícone
    input.style.backgroundColor = '#f0f0f0'; // Cor de fundo mais clara para indicar que está desativado
    input.style.width = '100%';
    input.readOnly = true; // Tornar o campo somente leitura
    
    // Aplicar cor vermelha se for campo de salário
    if (isSalario) {
        input.style.color = '#FF0000';
    }
    
    // Mover o input para o contêiner
    container.appendChild(input);
    
    // Criar o ícone de bloqueio com Font Awesome
    const lockIcon = document.createElement('i');
    lockIcon.className = 'fas fa-lock lock-icon';
    lockIcon.style.position = 'absolute';
    lockIcon.style.right = '10px';
    lockIcon.style.pointerEvents = 'none'; // Evita que o ícone receba eventos de mouse
    lockIcon.style.color = '#666'; // Cor cinza para o ícone
    
    // Adicionar o ícone ao contêiner
    container.appendChild(lockIcon);
}

// Buscar os elementos DOM após o carregamento da página
document.addEventListener('DOMContentLoaded', function() {
    // Buscar os elementos relevantes
    const selectCargo = document.querySelector('.form-group select[size="1"]:not([name=""])'); // Seletor mais específico para o campo de cargo
    const inputSalario = document.querySelector('input[placeholder="Ad. automaticamente*"]');
    const inputEstado = document.querySelector('input[placeholder="Ativo*"]');
    
    // Verificar se os elementos necessários existem
    if (inputEstado) {
        // Definir o valor padrão para o campo Estado
        inputEstado.value = "Ativo";
        
        // Modificar o elemento de entrada Estado para incluir o ícone de bloqueio
        adicionarIconeBloqueio(inputEstado, false);
    }
    
    if (selectCargo && inputSalario) {
        // Modificar o elemento de entrada Salário para incluir o ícone de bloqueio
        adicionarIconeBloqueio(inputSalario, true); // true indica que é um campo de salário (para aplicar cor vermelha)
        
        // Função para atualizar o salário base quando o cargo for selecionado
        function atualizarSalarioBase() {
            const cargoSelecionado = selectCargo.value;
            
            if (cargoSelecionado && salariosPorCargo[cargoSelecionado]) {
                inputSalario.value = salariosPorCargo[cargoSelecionado] + " KZs";
            } else {
                inputSalario.value = "";
            }
        }
        
        // Adicionar listener para mudanças no select de cargo
        selectCargo.addEventListener('change', atualizarSalarioBase);
        
        // Executar uma vez para inicializar o valor do salário se o cargo já estiver selecionado
        if (selectCargo.value) {
            atualizarSalarioBase();
        }
    } else {
        console.error("Elementos não encontrados: selectCargo ou inputSalario");
        if (!selectCargo) console.error("selectCargo não encontrado");
        if (!inputSalario) console.error("inputSalario não encontrado");
    }
});


document.addEventListener('DOMContentLoaded', function() {
        // Inicializar o intl-tel-input
        const input = document.querySelector("#telemovel");
        window.intlTelInput(input, {
            initialCountry: "ao", // Código do país inicial (Angola)
            separateDialCode: true, // Mostrar o código do país separadamente
            showFlags: false, // Oculta as bandeiras
            preferredCountries: ["ao", "pt", "br", "us"], // Países preferenciais
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js" // Utilitários necessários
        });
    });

        document.addEventListener('DOMContentLoaded', function() {
            const inputEmergencia = document.querySelector("#contato_emergencia");
            window.intlTelInput(inputEmergencia, {
                initialCountry: "ao", // Código do país inicial (Angola)
                separateDialCode: true, // Mostrar o código do país separadamente
                preferredCountries: ["ao", "pt", "br", "us"], // Países preferenciais
                showFlags: false, // Oculta as bandeiras
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js" // Utilitários necessários
            });
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataNascimento = document.getElementById('data_nascimento');
    const emissaoBi = document.getElementById('emissao_bi');
    const validadeBi = document.getElementById('validade_bi');
    const errorMessage = document.getElementById('error-message'); // Elemento para exibir mensagens de erro

    if (dataNascimento && emissaoBi && validadeBi) {
        // Get current date
        const hoje = new Date();
        const anoAtual = hoje.getFullYear();
        const mesAtual = String(hoje.getMonth() + 1).padStart(2, '0');
        const diaAtual = String(hoje.getDate()).padStart(2, '0');
        const dataAtual = `${anoAtual}-${mesAtual}-${diaAtual}`;
        
        // Set min/max constraints for data_nascimento (birth date)
        const minAnoNascimento = anoAtual - 120;
        dataNascimento.min = `${minAnoNascimento}-01-01`;
        dataNascimento.max = dataAtual;
        
        // BI emission date (can't be in the future)
        emissaoBi.max = dataAtual;
        
        // BI validity date (must be future date)
        validadeBi.min = dataAtual;
        
        // Custom validation for data_nascimento
        dataNascimento.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const selectedYear = selectedDate.getFullYear();
            
            if (selectedYear < minAnoNascimento) {
                errorMessage.innerText = 'Data de nascimento inválida.';
                this.value = '';
            } else if (selectedDate > hoje) {
                errorMessage.innerText = 'Data de nascimento inválida!';
                this.value = '';
            } else {
                errorMessage.innerText = ''; // Limpa a mensagem de erro
            }
        });
        
        // Custom validation for emission date
        emissaoBi.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            
            if (selectedDate > hoje) {
                errorMessage.innerText = 'A data de emissão inválida!';
                this.value = '';
            } else {
                errorMessage.innerText = ''; // Limpa a mensagem de erro
            }
        });
        
        // Custom validation for validity date
        validadeBi.addEventListener('change', function() {
            // Só valida se o campo estiver completamente preenchido (10 caracteres, formato YYYY-MM-DD)
            if (this.value.length === 10) {
                const emissaoDate = new Date(emissaoBi.value);
                const selectedDate = new Date(this.value);
                
                if (emissaoBi.value && selectedDate <= emissaoDate) {
                    errorMessage.innerText = 'A data de validade deve ser posterior à data de emissão!';
                    this.value = '';
                } else {
                    errorMessage.innerText = ''; // Limpa a mensagem de erro
                }
            }
        });
    }
});
</script>
    <script src="./js/theme.js"></script>

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
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const departamentoSelect = document.getElementById('departamento');
    const cargoSelect = document.getElementById('cargo');
    const salarioBaseInput = document.getElementById('salario_base');
    const lockIcon = document.querySelector('.lock-icon');

    // Função para atualizar o estado do select e ícones
    function atualizarEstadoSelect() {
        const temDepartamento = departamentoSelect.value !== '';
        
        // Atualizar estado do select
        cargoSelect.disabled = !temDepartamento;
        
        // Forçar atualização do estilo
        cargoSelect.style.display = 'none';
        cargoSelect.offsetHeight; // Força reflow
        cargoSelect.style.display = '';
        
        // Atualizar ícone do cadeado
        lockIcon.style.opacity = temDepartamento ? '0' : '1';
    }

    // Função para carregar cargos
    function carregarCargos(departamentoId) {
        if (!departamentoId) return;
        
        fetch(`get_cargos.php?departamento_id=${departamentoId}`)
            .then(response => response.json())
            .then(cargos => {
                cargoSelect.innerHTML = '<option value="">Selecione o Cargo</option>';
                cargos.forEach(cargo => {
                    const option = document.createElement('option');
                    option.value = cargo.id;
                    option.textContent = cargo.nome;
                    option.dataset.salario = cargo.salario_base;
                    if (cargo.id == '<?php echo isset($_SESSION['form_data']['cargo']) ? $_SESSION['form_data']['cargo'] : ''; ?>') {
                        option.selected = true;
                        salarioBaseInput.value = cargo.salario_base;
                    }
                    cargoSelect.appendChild(option);
                });
                atualizarEstadoSelect();
            })
            .catch(error => {
                console.error('Erro ao carregar cargos:', error);
            });
    }

    // Evento de mudança do departamento
    departamentoSelect.addEventListener('change', function() {
        const departamentoId = this.value;
        
        if (departamentoId) {
            carregarCargos(departamentoId);
        } else {
            cargoSelect.innerHTML = '<option value="">Selecione o Cargo</option>';
            salarioBaseInput.value = '';
            atualizarEstadoSelect();
        }
    });

    // Evento de mudança do cargo
    cargoSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.dataset.salario) {
            salarioBaseInput.value = selectedOption.dataset.salario;
        } else {
            salarioBaseInput.value = '';
        }
    });

    // Estado inicial
    if (departamentoSelect.value) {
        carregarCargos(departamentoSelect.value);
    } else {
        atualizarEstadoSelect();
    }
});
</script>
</body>
</html>