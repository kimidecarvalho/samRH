<?php
// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Adicionar logs para depuração
error_log("Debug - Registro_emp.php - Sessão ID: " . session_id());
error_log("Debug - Registro_emp.php - Verificando sessão antes de include protect.php");
if(isset($_SESSION['id_adm'])) {
    error_log("Debug - Registro_emp.php - id_adm: " . $_SESSION['id_adm']);
} else {
    error_log("Debug - Registro_emp.php - id_adm não está definido na sessão");
}
if(isset($_SESSION['nome'])) {
    error_log("Debug - Registro_emp.php - nome: " . $_SESSION['nome']);
} else {
    error_log("Debug - Registro_emp.php - nome não está definido na sessão");
}

include 'protect.php'; // Protege a página para usuários autenticados
include 'config.php'; // Conexão com o banco de dados

// Mais logs após os includes
error_log("Debug - Registro_emp.php - Após include protect.php");
error_log("Debug - Registro_emp.php - id_adm: " . (isset($_SESSION['id_adm']) ? $_SESSION['id_adm'] : 'Não definido'));
error_log("Debug - Registro_emp.php - nome: " . (isset($_SESSION['nome']) ? $_SESSION['nome'] : 'Não definido'));

// Verifica se a conexão com o banco de dados foi bem-sucedida
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verifica se o usuário está logado e tem um ID válido
if (!isset($_SESSION['id_adm'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Erro',
                text: 'Usuário não autenticado.',
                icon: 'error',
                confirmButtonText: 'OK'
            }).then((result) => {
                window.location.href = 'login.php';
            });
        });
    </script>";
    exit;
}

$id = $_SESSION['id_adm']; // Obtendo o ID do usuário logado

// Consulta SQL segura para recuperar os detalhes do usuário logado
$sql = "SELECT id_adm, nome, email FROM adm WHERE id_adm = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close(); // Fechar o statement aqui

// Verifica se encontrou o usuário
if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc(); // Obtém os dados do usuário
} else {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Erro',
                text: 'Usuário não encontrado.',
                icon: 'error',
                confirmButtonText: 'OK'
            }).then((result) => {
                window.location.href = 'login.php';
            });
        });
    </script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Receber os dados do formulário
    $nome = $_POST["nome"];
    $nipc = $_POST["nipc"];
    $endereco = $_POST["endereco"];
    $telefone = $_POST["telefone"];   
    $email_corp = $_POST["email_corp"];
    $setor_atuacao = $_POST["setor_atuacao"];
    $num_fun = $_POST["num_fun"];

    // Verificar se o e-mail já existe
    $sql_check = "SELECT id_empresa FROM empresa WHERE email_corp = ?";
    $stmt_check = $conn->prepare($sql_check);
    if ($stmt_check) {
        $stmt_check->bind_param("s", $email_corp);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Erro',
                        text: 'Este e-mail já está cadastrado!',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        window.history.back();
                    });
                });
            </script>";
            $stmt_check->close();
        } else {
            $stmt_check->close();
            
            // Inserir os dados no banco
            $sql = "INSERT INTO empresa (nome, nipc, endereco, telefone, email_corp, setor_atuacao, num_fun, adm_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssssssi", $nome, $nipc, $endereco, $telefone, $email_corp, $setor_atuacao, $num_fun, $id);

                if ($stmt->execute()) {
                    // Armazena o ID da empresa recém-criada na sessão
                    $_SESSION['id_empresa'] = $conn->insert_id;
                    
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                title: 'Sucesso',
                                text: 'Cadastro realizado com sucesso!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                window.location.href = 'UI.php';
                            });
                        });
                    </script>";
                } else {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                title: 'Erro',
                                text: 'Erro ao cadastrar: " . $conn->error . "',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                window.history.back();
                            });
                        });
                    </script>";
                }
                $stmt->close();
            } else {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Erro',
                            text: 'Erro na preparação da consulta: " . $conn->error . "',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
                </script>";
            }
        }
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Erro',
                    text: 'Erro na preparação da consulta: " . $conn->error . "',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="all.css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAM - Criar Empresa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="all.css/registro_emp.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header class="header">
        <a href="login.php">
            <img src="img/sam2logo-32.png" alt="SAM Logo" class="logo">
        </a>
        <div class="nav-container">
            <nav class="nav-menu">
                <div class="dropdown">
                    <a href="#" class="dropbtn">Produtos ▾</a>
                    <div class="dropdown-content">
                        <a href="#">Produto 1</a>
                        <a href="#">Produto 2</a>
                        <a href="#">Produto 3</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="dropbtn">Funcionalidades ▾</a>
                    <div class="dropdown-content">
                        <a href="#">Funcionalidade 1</a>
                        <a href="#">Funcionalidade 2</a>
                        <a href="#">Funcionalidade 3</a>
                    </div>
                </div>
                <a href="#">Preços</a>
            </nav>
        </div>
        <div class="nav-buttons">
            <button class="btn btn-entrar">Entrar</button>
            <button class="btn btn-criar">Criar empresa</button>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <h1>Crie o perfil da <span>sua empresa</span></h1>
            <form id="companyForm" action="registro_emp.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome da empresa</label>
                        <input type="text" id="nome" name="nome" placeholder="Nome da empresa" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nipc">NIPC</label>
                        <input type="number" id="nipc" name="nipc" placeholder="NIPC da empresa" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="endereco">Endereço</label>
                        <input type="text" id="endereco" name="endereco" placeholder="Endereço" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Corporativo</label>
                        <input type="email" id="email" name="email_corp" placeholder="Email Corporativo" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefone</label>
                        <input type="number" id="phone" name="telefone" placeholder="Digite aqui" required>
                    </div>

                    <div class="form-group">
                        <label for="setor">Setor de atuação</label>
                        <select id="setor" name="setor_atuacao" required>
                            <option value="" disabled selected>Selecione aqui</option>
                            <option value="industria">Indústria</option>
                            <option value="comercio">Comércio</option>
                            <option value="servicos">Serviços</option>
                            <option value="agronegocio">Agronegócio</option>
                            <option value="construcao_civil">Construção Civil</option>
                            <option value="tecnologia">Tecnologia</option>
                            <option value="financeiro">Financeiro</option>
                            <option value="energia">Energia</option>
                            <option value="telecomunicacoes">Telecomunicações</option>
                            <option value="turismo">Turismo</option>
                            <option value="alimentacao">Alimentação</option>
                            <option value="vestuario">Vestuário</option>
                            <option value="saude">Saúde</option>
                            <option value="educacao">Educação</option>
                            <option value="entretenimento">Entretenimento</option>
                        </select>
                    </div>                            

                    <div class="form-group">
                        <label for="employees">Nº de funcionários</label>
                        <select id="employees" name="num_fun" required>
                            <option value="" disabled selected>Selecione aqui</option>
                            <option value="1-10">1-10</option>
                            <option value="11-50">11-50</option>
                            <option value="51-200">51-200</option>
                            <option value="201+">201+</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="responsavel">Responsável Legal</label>
                        <div class="input-container">
                            <input type="text" id="responsavel" name="responsavel" value="<?php echo isset($usuario) && isset($usuario['nome']) ? htmlspecialchars($usuario['nome']) : ''; ?>" readonly>
                            <i class="fas fa-lock lock-icon"></i>
                        </div>
                    </div>

                    <button type="submit" class="confirm-button">confirmar</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        const type = urlParams.get('type') || 'info';
        
        if (message) {
            Swal.fire({
                title: type.charAt(0).toUpperCase() + type.slice(1),
                text: message,
                icon: type,
                confirmButtonText: 'OK'
            });
        }
    </script>
</body>
</html>