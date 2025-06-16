<?php
include 'protect.php';
include 'config.php';

// Obter o id_empresa do administrador
$admin_id = $_SESSION['id_adm'];
$sql_admin = "SELECT e.id_empresa FROM empresa e WHERE e.adm_id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $admin_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$admin = $result_admin->fetch_assoc();
$empresa_id = $admin['id_empresa'];

// Adicionar consultas para buscar cargos, departamentos e bancos
$sql_cargos = "SELECT id, nome, departamento_id, salario_base FROM cargos ORDER BY nome";
$result_cargos = $conn->query($sql_cargos);

$sql_departamentos = "SELECT id, nome FROM departamentos ORDER BY nome";
$result_departamentos = $conn->query($sql_departamentos);

$sql_bancos = "SELECT banco_codigo, banco_nome FROM bancos_ativos WHERE empresa_id = ? AND ativo = 1 ORDER BY banco_nome";
$stmt_bancos = $conn->prepare($sql_bancos);
$stmt_bancos->bind_param("i", $empresa_id);
$stmt_bancos->execute();
$result_bancos = $stmt_bancos->get_result();

// Pegar o ID do funcionário da URL
$id_fun = $_GET['id'];

// Consulta SQL para buscar os dados atuais do funcionário
$sql = "SELECT f.num_mecanografico, f.nome, f.foto, f.bi, f.emissao_bi, f.validade_bi, 
               f.data_nascimento, f.pais, f.morada, f.genero, f.num_agregados, 
               f.contato_emergencia, f.nome_contato_emergencia, f.telemovel, f.email, f.estado, 
               f.cargo, f.departamento, f.tipo_trabalhador, 
               f.num_conta_bancaria, f.banco, f.iban, 
               f.salario_base, f.num_ss, f.data_admissao,
               b.banco_nome
        FROM funcionario f
        LEFT JOIN bancos_ativos b ON f.banco = b.banco_codigo
        WHERE f.id_fun = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_fun);
$stmt->execute();
$result = $stmt->get_result();

// Verificar se encontrou o funcionário
if ($result->num_rows > 0) {
    $dados = $result->fetch_assoc();
} else {
    echo "Funcionário não encontrado!";
    exit();
}

// Processar o formulário de edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coletar dados do formulário
    $nome = $_POST['nome'];
    $bi = $_POST['bi'];
    $emissao_bi = $_POST['emissao_bi'];
    $validade_bi = $_POST['validade_bi'];
    $data_nascimento = $_POST['data_nascimento'];
    $pais = $_POST['pais'];
    $morada = $_POST['morada'];
    $genero = $_POST['genero'];
    $num_agregados = $_POST['num_agregados'];
    $contato_emergencia = $_POST['contato_emergencia'];
    $nome_contato_emergencia = $_POST['nome_contato_emergencia'];
    $telemovel = $_POST['telemovel'];
    $email = $_POST['email'];
    $estado = trim($_POST['estado']); // Remover espaços em branco
    error_log("Estado recebido do formulário: '" . $estado . "'");
    
    // Garantir que o estado seja sempre 'Ativo' ou 'Inativo'
    if (empty($estado) || ($estado !== 'Ativo' && $estado !== 'Inativo')) {
        $estado = 'Ativo'; // Valor padrão se não for nenhum dos dois
        error_log("Estado ajustado para: " . $estado);
    }
    $cargo = $_POST['cargo'];
    $departamento = $_POST['departamento'];
    $tipo_trabalhador = $_POST['tipo_trabalhador'];
    $num_conta_bancaria = $_POST['num_conta_bancaria'];
    $banco = $_POST['banco'];
    $iban = $_POST['iban'];
    $salario_base = $_POST['salario_base'];
    $num_ss = $_POST['num_ss'];
    $data_admissao = $_POST['data_admissao'];

    // Debug para verificar os valores
    error_log("Valor do banco selecionado: " . $banco);

    // Atualizar os dados no banco de dados
    $sql_update = "UPDATE funcionario SET 
                   nome = ?, bi = ?, emissao_bi = ?, validade_bi = ?, 
                   data_nascimento = ?, pais = ?, morada = ?, genero = ?, 
                   num_agregados = ?, contato_emergencia = ?, nome_contato_emergencia = ?, 
                   telemovel = ?, email = ?, estado = ?, cargo = ?, departamento = ?, 
                   tipo_trabalhador = ?, num_conta_bancaria = ?, banco = ?, iban = ?, 
                   salario_base = ?, num_ss = ?, data_admissao = ? 
                   WHERE id_fun = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssssssssisssssssssssdsi", 
        $nome, $bi, $emissao_bi, $validade_bi, $data_nascimento, $pais, $morada, $genero, 
        $num_agregados, $contato_emergencia, $nome_contato_emergencia, $telemovel, $email, 
        $estado, $cargo, $departamento, $tipo_trabalhador, $num_conta_bancaria, $banco, 
        $iban, $salario_base, $num_ss, $data_admissao, $id_fun);

    if ($stmt_update->execute()) {
        echo "Dados atualizados com sucesso!";
        // Redirecionar de volta para a página de detalhes
        header("Location: detalhes_funcionario.php?id=$id_fun");
        exit();
    } else {
        echo "Erro ao atualizar os dados: " . $stmt_update->error;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Detalhes do Funcionário</title>
    <link rel="stylesheet" href="all.css/registro3.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>

        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}


body {
    background-color: #f5f5f5;
}

.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background-color: white;
    padding: 20px;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.logo {
    margin-bottom: 20px;
    padding: 0 10px;
}

.logo img {
    height: 40px;
}

.nav-select {
    width: 100%;
    padding: 8px 12px;
    margin-bottom: 30px;
    border: 1px solid #ddd;
    border-radius: 25px;
    color: #666;
    background: white;
}

.nav-menu {
    list-style: none;
    padding: 0 10px;
}

.nav-menu a {
    text-decoration: none;
}

.nav-menu li {
    padding: 12px 15px;
    margin: 5px 0;
    color: #666;
    cursor: pointer;
    border-radius: 5px;
    display: flex;
    align-items: center;
    font-size: 14px;
}

.nav-menu li::before {
    content: "•";
    color: #70c7b0;
    margin-right: 10px;
    font-size: 20px;
}

.nav-menu li:hover,
.nav-menu li.active {
    background-color: rgba(112, 199, 176, 0.1);
    color: #70c7b0;
}

.main-content {
    margin-left: 250px;
    padding: 20px 40px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.header-buttons,
.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.page-title {
    color: #000000b7;
    font-size: 28px;
    font-weight: 600;
}

.time,
.user-profile {
    background-color: white;
    color: #000;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
}

.exit-tag {
    background-color: #FF6B6B;
    border: none;
    color: white;
    cursor: pointer;
    text-decoration: none;
}

.user-profile {
    display: flex;
    gap: 10px;
    cursor: pointer;
    padding: 6px 15px;
}

.user-profile img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #3EB489;
}

.dropdown-arrow {
    font-size: 12px;
    margin-left: 5px;
}

.search-container {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    justify-content: flex-start;
}

.search-container .search-bar {
    background-color: #ffffff;
    width: 20%;
}

.search-container input {
    width: 140px;
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 25px;
    font-size: 14px;
    background: #f5f5f5;
    transition: all 0.3s ease-in-out;
    height: 32px;
}

.search-bar {
    background-color: #3EB489;
}

.search-container input:focus {
    border-color: #70c7b0;
    box-shadow: 0 0 5px rgba(112, 199, 176, 0.5);
    outline: none;
}

.form-wrapper {
    position: relative;
    display: flex;
    margin-bottom: 0px;
    height: 60%;
}

.profile-circle {
    width: 65px;
    height: 65px;
    background-color: white;
    border: 3px solid #70c7b0;
    border-radius: 50%;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -365%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}

.profile-circle img {
    width: 30px;
    height: 30px;
}

.form-section {
    flex: 1;
    padding: 45px;
}

.personal-info {
    background-color: #3EB489;
    color: white;
    border-top-left-radius: 25px;
    border-bottom-left-radius: 25px;
}

.professional-info {
    background-color: white;
    border-top-right-radius: 25px;
    border-bottom-right-radius: 25px;
}

.section-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
    color: inherit;
}

.professional-info .section-title,
.secao.o .section-title {
    border-bottom-color: #eee;
    color: #333;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.form-group,
.secao p {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.form-group label,
.secao p strong {
    display: block;
    margin-bottom: 5px;
    font-size: 13px;
    color: inherit;
    font-weight: 600;
}

.form-group input:not([type="submit"]),
.form-group select,
.secao input:not([name="num_mecanografico"], [type="submit"]),
.secao select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 13px;
    color: #333;
    background-color: white;
    box-sizing: border-box;
}

.secao input[name="num_mecanografico"] {
    background-color: #f0f0f0;
    color: #555;
    cursor: not-allowed;
    border: 1px solid #ccc;
    padding: 8px 12px;
    border-radius: 5px;
    font-size: 13px;
    width: 100%;
    box-sizing: border-box;
}

.btn-confirm {
    background-color: #3EB489;
    color: white;
    padding: 8px 25px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    float: right;
    margin-top: -10%;
    font-size: 14px;
}

.document-note {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 30px;
    border: 2px dashed #70c7b0;
    border-radius: 25px;
    color: #000000;
    margin-top: 10px;
    height: 200px;
    background-color: #fff;
}

.document-note a {
    color: #70c7b0;
    text-decoration: none;
    font-weight: bold;
}

.container {
    margin: 20px 0;
    padding: 0 15px;
    position: relative;
    height: auto;
}

.foto-perfil {
    position: absolute;
    top: 50px;
    right: 50px;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid white;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    background: linear-gradient(to right, #5cbea5, #77d6c1);
    z-index: 10;
}

.foto-perfil img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.secao {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    width: 100%;
    margin-bottom: 20px;
    height: auto;
    max-height: none;
    overflow: visible;
    color: #333;
}

.teste {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.teste div {
    margin: 0;
    padding: 0;
}

.teste div p {
    margin: 0;
    font-size: 14px;
}

.secao input,
.secao select {
}

.juntos {
    display: flex;
    gap: 20px;
    margin-top: 0px;
    width: 100%;
}

.m {
    width: 100%;
    margin-bottom: 20px;
    background: linear-gradient(to right, rgb(255, 255, 255) 80%, #82dab7);
    padding: 20px;
    height: auto;
    margin-top: 10px;
    color: #333;
}

.n {
    width: 50%;
    box-sizing: border-box;
}

.o {
    width: 50%;
    box-sizing: border-box;
}

.secao h2 {
    color: #333;
    font-size: 18px;
    margin-bottom: 15px;
    font-weight: 600;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.secao p {
    margin: 8px 0;
    font-size: 14px;
    color: #333;
}

.secao strong {
    font-weight: 600;
    color: #555;
}

.doc-icons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 20px;
    margin-top: 30px;
    border: 1px dashed #ccc;
    border-radius: 12px;
    padding: 20px;
}

.doc-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.icon-circle {
    width: 60px;
    height: 60px;
    background-color: rgba(92, 190, 165, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}

.icon-circle i {
    color: #5cbea5;
    font-size: 24px;
}

.doc-icon p {
    font-size: 13px;
    font-weight: 500;
    color: #555;
    margin-bottom: 5px;
}

.doc-icon small {
    font-size: 11px;
    color: #999;
}

.edit-button {
    display: block;
    margin: 20px 0 20px auto;
    background-color: #3EB489;
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 500;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.edit-button1 {
    text-decoration: none;
}

/* Estilo para inputs e selects dentro da seção de Informações Pessoais */
.secao.m input:not([type="submit"]),
.secao.m select {
    max-width: 350px; /* Define uma largura máxima para os campos */
    width: 100%; /* Mantém a largura base de 100% dentro do container flex/grid */
    box-sizing: border-box; /* Garante que o padding e borda sejam incluídos na largura */
}

/* Estilo específico para o input num_mecanografico (readonly) */
.secao input[name="num_mecanografico"] {
    background-color: #f0f0f0;
    color: #555;
    cursor: not-allowed;
    border: 1px solid #ccc;
    padding: 8px 12px;
    border-radius: 5px;
    font-size: 13px;
    width: 100%;
    box-sizing: border-box;
}

/* Estilo para o container do select */
.readonly-container {
    position: relative;
    display: inline-block;
    width: 100%;
}

/* Estilo para o select */
.readonly-container select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 13px;
    color: #333;
    background-color: white;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 30px; /* Espaço para o ícone */
}

/* Estilo para o ícone de cadeado */
.lock-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
    pointer-events: none;
    font-size: 14px;
}

/* Estilo para o select quando desabilitado */
.readonly-container select:disabled {
    background-color: #f0f0f0;
    cursor: not-allowed;
    color: #555;
}

/* Estilo para a seta do select quando habilitado */
.readonly-container select:not(:disabled) {
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 16px;
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
            <h1 class="page-title">Editar Funcionário | #<?php echo $dados['num_mecanografico']; ?></h1>
            <div class="header-buttons">
                <div class="time" id="current-time"></div>
                <a class="exit-tag" href="logout.php">Sair</a>
                <div class="user-profile">
                    <img src="Apresentação1 (1).png" alt="User" width="20">
                    <span><?php echo $_SESSION['nome']; ?></span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </div>
            </div>
        </header>

        <form method="POST" action="editar_funcionario.php?id=<?php echo $id_fun; ?>" onsubmit="return validateForm()">
        <button type="submit" class="edit-button">
                    <i class="fas fa-save"></i> Guardar
        </button>
            <div class="container">
                <!-- Foto -->
                <div class="foto-perfil">
                    <img src="<?php echo !empty($dados['foto']) ? $dados['foto'] : 'icones/icons-sam-18.svg'; ?>" alt="Foto de <?php echo $dados['nome']; ?>">
                </div>

                <!-- Informações Pessoais -->
                <div class="secao m">
                    <h2>Informações Pessoais</h2>
                    <div class="teste">
                        <div style="margin-left:0px;">
                            <p><strong>Nome:</strong> <input type="text" name="nome" value="<?php echo $dados['nome']; ?>"></p>
                            <p><strong>Nº BI:</strong> <input type="text" name="bi" value="<?php echo $dados['bi']; ?>"></p>
                            <p><strong>Emissão:</strong> <input type="date" name="emissao_bi" value="<?php echo $dados['emissao_bi']; ?>"></p>
                            <p><strong>Validade:</strong> <input type="date" name="validade_bi" value="<?php echo $dados['validade_bi']; ?>"></p>
                            <br>
                            <p style="margin-top:-10px; margin-bottom:10px;"><strong>Telefone:</strong> <input type="text" name="telemovel" value="<?php echo $dados['telemovel']; ?>"></p>
                        </div>
                        <div style="margin-left:-100px;">
                            <p><strong>Nascimento:</strong> <input type="date" name="data_nascimento" value="<?php echo $dados['data_nascimento']; ?>"></p>
                            <p><strong>Nacionalidade:</strong> <input type="text" name="pais" value="<?php echo $dados['pais']; ?>"></p>
                            <p><strong>Morada:</strong> <input type="text" name="morada" value="<?php echo $dados['morada']; ?>"></p>
                            <p><strong>Gênero:</strong> <input type="text" name="genero" value="<?php echo $dados['genero']; ?>"></p>
                            <br>
                            <p style="margin-top:-10px; margin-bottom:10px;"><strong>Email:</strong> <input type="email" name="email" value="<?php echo $dados['email']; ?>"></p>
                        </div>
                        <div style="margin-left:-130px;">
                            <p><strong>Nº de Agregados:</strong> <input type="number" name="num_agregados" value="<?php echo $dados['num_agregados']; ?>"></p>
                            <p><strong>Salário Base:</strong> <input type="number" step="0.01" name="salario_base" value="<?php echo $dados['salario_base']; ?>"></p>
                            <p><strong>Nº da SS:</strong> <input type="text" name="num_ss" value="<?php echo $dados['num_ss']; ?>"></p>
                        </div>
                    </div>
                </div>

                <div class="juntos">
                    <!-- Informações Profissionais -->  
                    <div class="secao n">
                        <h2>Informações Profissionais</h2>
                        <p><strong>Nº Mecanográfico:</strong> <input type="text" name="num_mecanografico" value="<?php echo $dados['num_mecanografico']; ?>"readonly></p>
                        <p><strong>Departamento:</strong>
                            <select name="departamento" id="departamento" required>
                                <option value="">Selecione um departamento</option>
                                <?php
                                if ($result_departamentos->num_rows > 0) {
                                    while($row = $result_departamentos->fetch_assoc()) {
                                        $selected = ($dados['departamento'] == $row['id']) ? 'selected' : '';
                                        echo "<option value='" . $row['id'] . "' " . $selected . ">" . $row['nome'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </p>

                        <p><strong>Cargo:</strong>
                            <div class="readonly-container" title="Selecione o Departamento primeiro para prosseguir">
                                <select name="cargo" id="cargo" required disabled>
                                    <option value="">Selecione um cargo</option>
                                    <?php
                                    if ($result_cargos->num_rows > 0) {
                                        while($row = $result_cargos->fetch_assoc()) {
                                            $selected = ($dados['cargo'] == $row['id']) ? 'selected' : '';
                                            echo "<option value='" . $row['id'] . "' " . $selected . " 
                                                data-departamento='" . $row['departamento_id'] . "'
                                                data-salario='" . $row['salario_base'] . "'>" . 
                                                $row['nome'] . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <i class="fas fa-lock lock-icon"></i>
                            </div>
                        </p>
                        <p><strong>Tipo:</strong>
                            <select name="tipo_trabalhador" id="tipo_trabalhador" required>
                                <option value="">Selecione um tipo de trabalhador</option>
                                <option value="Efetivo" <?php echo ($dados['tipo_trabalhador'] == 'Efetivo') ? 'selected' : ''; ?>>Trabalhador Efetivo</option>
                                <option value="Temporário" <?php echo ($dados['tipo_trabalhador'] == 'Temporário') ? 'selected' : ''; ?>>Trabalhador Temporário</option>
                                <option value="Estagiário" <?php echo ($dados['tipo_trabalhador'] == 'Estagiário') ? 'selected' : ''; ?>>Trabalhador Estagiário</option>
                                <option value="Autônomo" <?php echo ($dados['tipo_trabalhador'] == 'Autônomo') ? 'selected' : ''; ?>>Trabalhador Autônomo</option>
                                <option value="Freelancer" <?php echo ($dados['tipo_trabalhador'] == 'Freelancer') ? 'selected' : ''; ?>>Trabalhador Freelancer</option>
                                <option value="Terceirizado" <?php echo ($dados['tipo_trabalhador'] == 'Terceirizado') ? 'selected' : ''; ?>>Trabalhador Terceirizado</option>
                                <option value="Intermitente" <?php echo ($dados['tipo_trabalhador'] == 'Intermitente') ? 'selected' : ''; ?>>Trabalhador Intermitente</option>
                                <option value="Voluntário" <?php echo ($dados['tipo_trabalhador'] == 'Voluntário') ? 'selected' : ''; ?>>Trabalhador Voluntário</option>
                            </select>
                        </p>
                        <p><strong>Estado:</strong> 
                        <select name="estado" required onchange="console.log('Estado selecionado:', this.value)">
                            <option value="">Selecione um estado</option>
                            <option value="Ativo" <?php echo ($dados['estado'] == 'Ativo' || empty($dados['estado'])) ? 'selected' : ''; ?>>Ativo</option>
                            <option value="Inativo" <?php echo ($dados['estado'] == 'Inativo') ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </p>
                        <p><strong>Data de Admissão:</strong> <input type="date" name="data_admissao" value="<?php echo $dados['data_admissao']; ?>"></p>
                    </div>

                    <!-- Informações Bancárias -->
                    <div class="secao o">
                        <h2>Informações Bancárias</h2>
                        <p><strong>Nº Conta:</strong> <input type="text" name="num_conta_bancaria" value="<?php echo $dados['num_conta_bancaria']; ?>"></p>
                        <p><strong>Banco:</strong>
                            <select name="banco" id="banco" required>
                                <option value="">Selecione um banco</option>
                                <?php
                                if ($result_bancos->num_rows > 0) {
                                    while($row = $result_bancos->fetch_assoc()) {
                                        $selected = ($dados['banco'] == $row['banco_codigo']) ? 'selected' : '';
                                        echo "<option value='" . $row['banco_codigo'] . "' " . $selected . ">" . $row['banco_nome'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </p>
                        <p><strong>IBAN:</strong> <input type="text" name="iban" value="<?php echo $dados['iban']; ?>"></p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
        }

        // Initial update
        updateTime();
        
        // Update every second
        setInterval(updateTime, 1000);
    </script>
    <script>
                // Function to add lock icon to readonly fields
        function addLockIcon() {
        // Find the num_mecanografico input
        const numMecInput = document.querySelector('input[name="num_mecanografico"]');
        
        if (numMecInput) {
            // Create wrapper div to hold both input and icon
            const wrapper = document.createElement('div');
            wrapper.style.position = 'relative';
            wrapper.style.display = 'inline-block';
            wrapper.style.width = 'calc(55% - 130px)'; // Leave space for label
            
            // Get the parent paragraph that contains the label and input
            const parentP = numMecInput.parentElement;
            
            // Insert wrapper after the label but before the input
            numMecInput.parentNode.insertBefore(wrapper, numMecInput);
            
            // Move input inside wrapper
            wrapper.appendChild(numMecInput);
            
            // Style the input
            numMecInput.style.width = '100%';
            numMecInput.style.backgroundColor = '#f0f0f0';
            numMecInput.style.cursor = 'not-allowed';
            numMecInput.style.paddingRight = '30px';
            numMecInput.readOnly = true;
            
            // Create lock icon
            const lockIcon = document.createElement('i');
            lockIcon.className = 'fas fa-lock';
            lockIcon.style.position = 'absolute';
            lockIcon.style.right = '10px';
            lockIcon.style.top = '50%';
            lockIcon.style.transform = 'translateY(-50%)';
            lockIcon.style.color = '#888';
            lockIcon.style.pointerEvents = 'none';
            
            // Add icon to wrapper
            wrapper.appendChild(lockIcon);
        }
        
        // Increase height of bottom containers
        const professionalSection = document.querySelector('.secao.n');
        const bankingSection = document.querySelector('.secao.o');
        
        if (professionalSection) {
            professionalSection.style.minHeight = '320px';
        }
        
        if (bankingSection) {
            bankingSection.style.minHeight = '320px';
        }
        }

        // Execute when document is fully loaded
        document.addEventListener('DOMContentLoaded', addLockIcon);
    </script>
<script>
    function validateForm() {
        const dataNascimento = document.querySelector('input[name="data_nascimento"]').value;
        const emissaoBi = document.querySelector('input[name="emissao_bi"]').value;
        const validadeBi = document.querySelector('input[name="validade_bi"]').value;
        const hoje = new Date();
        
        // Verificar data de nascimento
        if (dataNascimento) {
            const nascimento = new Date(dataNascimento);
            const anoNascimento = nascimento.getFullYear();
            const anoAtual = hoje.getFullYear();

            // Verificar se a data de nascimento é realista
            if (nascimento > hoje) {
                alert('Data de nascimento não pode ser no futuro!');
                return false; // Impede o envio do formulário
            }
            if (anoNascimento < (anoAtual - 120)) {
                alert('Data de nascimento muito antiga!');
                return false; // Impede o envio do formulário
            }
        }

        // Verificar data de emissão do BI
        if (emissaoBi) {
            const emissao = new Date(emissaoBi);
            const anoEmissao = emissao.getFullYear();

            // Verificar se a data de emissão é no futuro
            if (emissao > hoje) {
                alert('Data de emissão não pode ser no futuro!');
                return false; // Impede o envio do formulário
            }

            // Verificar se a data de emissão é muito antiga
            if (anoEmissao < 1900) {
                alert('Data de emissão do BI não pode ser anterior a 1900!');
                return false; // Impede o envio do formulário
            }
        }

        // Verificar data de validade do BI
        if (validadeBi) {
            const validade = new Date(validadeBi);
            if (validade < new Date(emissaoBi)) {
                alert('A data de validade deve ser posterior à data de emissão!');
                return false; // Impede o envio do formulário
            }
            if (validade < hoje) {
                alert('A data de validade não pode ser no passado!');
                return false; // Impede o envio do formulário
            }
        }

        // Se todas as validações passarem
        return true; // Permite o envio do formulário
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const departamentoSelect = document.getElementById('departamento');
        const cargoSelect = document.getElementById('cargo');
        const salarioBaseInput = document.querySelector('input[name="salario_base"]');
        const lockIcon = document.querySelector('.readonly-container .lock-icon');

        // Armazenar todos os cargos para filtragem posterior
        const todosCargos = Array.from(cargoSelect.options).map(option => ({
            id: option.value,
            nome: option.text,
            departamento_id: option.getAttribute('data-departamento'),
            salario_base: option.getAttribute('data-salario')
        }));

        // Função para atualizar os cargos baseado no departamento
        function atualizarCargos(departamentoId) {
            // Guardar o cargo selecionado
            const cargoSelecionado = cargoSelect.value;
            
            // Limpar o select de cargos
            cargoSelect.innerHTML = '<option value="">Selecione um cargo</option>';
            
            if (departamentoId) {
                // Filtrar cargos pelo departamento selecionado
                const cargosFiltrados = todosCargos.filter(cargo => 
                    cargo.departamento_id === departamentoId
                );

                // Adicionar cargos filtrados ao select
                cargosFiltrados.forEach(cargo => {
                    const option = document.createElement('option');
                    option.value = cargo.id;
                    option.textContent = cargo.nome;
                    option.setAttribute('data-salario', cargo.salario_base);
                    // Se este for o cargo que estava selecionado, marcar como selected
                    if (cargo.id === cargoSelecionado) {
                        option.selected = true;
                    }
                    cargoSelect.appendChild(option);
                });

                // Habilitar o select de cargos e esconder o ícone de cadeado
                cargoSelect.disabled = false;
                lockIcon.style.display = 'none';
            } else {
                // Desabilitar o select de cargos e mostrar o ícone de cadeado
                cargoSelect.disabled = true;
                lockIcon.style.display = 'block';
            }
        }

        // Atualizar cargos quando o departamento mudar
        departamentoSelect.addEventListener('change', function() {
            atualizarCargos(this.value);
        });

        cargoSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.getAttribute('data-salario')) {
                salarioBaseInput.value = selectedOption.getAttribute('data-salario');
            } else {
                salarioBaseInput.value = '';
            }
        });

        // Verificar estado inicial do departamento e atualizar cargos
        if (departamentoSelect.value) {
            atualizarCargos(departamentoSelect.value);
        } else {
            cargoSelect.disabled = true;
            lockIcon.style.display = 'block';
        }
    });
</script>
</body>
</html>