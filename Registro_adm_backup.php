<?php
require_once "config.php";
$sweetalert = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST["nome"]);
    $email = trim($_POST["email"]);
    $senha = $_POST["senha"];
    $telefone = trim($_POST["telefone_formatado"]);

    // Validação de campos obrigatórios
    if (empty($nome) || empty($email) || empty($senha) || empty($telefone)) {
        $sweetalert = "Swal.fire('Erro!', 'Todos os campos são obrigatórios!', 'error');";
    } else {
        // Validação de e-mail
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $sweetalert = "Swal.fire('Erro!', 'E-mail inválido!', 'error');";
        } else {
            // Validação de senha
            if (strlen($senha) < 8) {
                $sweetalert = "Swal.fire('Erro!', 'A senha deve ter pelo menos 8 caracteres!', 'error');";
            } else {
                // Verifica se o e-mail já está cadastrado
                $sql_check = "SELECT id_adm FROM adm WHERE email = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("s", $email);
                $stmt_check->execute();
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    $sweetalert = "Swal.fire('Erro!', 'Este e-mail já está cadastrado!', 'error');";
                } else {
                    // Hash da senha
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                    // Insere os dados no banco
                    $sql = "INSERT INTO adm (nome, email, senha, telefone) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $nome, $email, $senha_hash, $telefone);

                    if ($stmt->execute()) {
                        $sweetalert = "Swal.fire({ title: 'Cadastro realizado!', text: 'Você será redirecionado para a página de login.', icon: 'success' }).then(() => { window.location.href = 'login.php'; });";
                    } else {
                        $sweetalert = "Swal.fire('Erro!', 'Erro ao cadastrar. Tente novamente.', 'error');";
                    }
                    $stmt->close();
                }
                $stmt_check->close();
            }
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAM - Registro Adm</title>
    <link rel="stylesheet" href="all.css/login.css">
    <link rel="stylesheet" href="all.css/registro_adm.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
</head>
<style>
    .alert-container {
        display: flex; 
        align-items: center; 
        margin-top: 1px; 
    }

    .exclamation {
        width: 17px;
        margin-right: 5px; 
    }

    .alert {
        color: #666;
        font-size: 11px;
        margin: 0; 
        margin-top: 1.5px;
    }

    .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            width: 620px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border-radius: 10px;
            padding: 25px;
            z-index: 10;
            left: 50%;
            transform: translateX(-50%);
            top: 50px;
        }
        
        .dropdown:hover .dropdown-content {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }
        
        .dropdown-box {
            width: 48%;
            padding: 0;
            border-radius: 0;
        }
        
        .dropdown-box-rh h3 {
            color: #2baa8f;
        }
        
        .dropdown-box-emprego h3 {
            color: #c9536b;
        }
        
        .dropdown-box h3 {
            display: flex;
            align-items: center;
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .dropdown-box h3 .logo-icon {
            width: 40px;
            height: auto;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dropdown-box h3 .pipe {
            margin: 0 10px;
            color: #888;
        }
        
        .dropdown-box h3 .title-text {
            font-weight: 500;
        }
        
        .dropdown-box p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
            font-size: 14px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .feature-list li {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
        }
        
        .feature-list li::before {
            content: "•";
            font-size: 16px;
            margin-right: 10px;
            display: inline-block;
        }
        
        .dropdown-box-rh .feature-list li::before {
            color: #2baa8f;
        }
        
        .dropdown-box-emprego .feature-list li::before {
            color: #c9536b;
        }
        
        @media (max-width: 992px) {
            .dropdown-content {
                width: 90%;
                max-width: 400px;
                flex-direction: column;
            }
            
            .dropdown-box {
                width: 100%;
                margin-bottom: 20px;
            }
        }
</style>
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
                    <div class="dropdown-box dropdown-box-rh">
                        <h3>
                            <img src="img/sam2logo-32.png" alt="" class="logo-icon" style="width:100px; height:50px;">
                        </h3>
                        <p>Gerencie sua equipe com eficiência. Ferramentas essenciais para administração de funcionários, cargos, salários e tudo que seu RH precisa.</p>
                        <ul class="feature-list">
                            <li>Gestão de Funcionários</li>
                            <li>Gestão de Recrutamento</li>
                            <li>Controle de Salários</li>
                            <li>Administração de Cargos</li>
                            <li>e muito mais.</li>
                        </ul>
                    </div>
                    <div class="dropdown-box dropdown-box-emprego">
                        <h3>
                            <img src="img/emp.png" alt="SAM Emprego" class="logo-icon" style="width:100px; height:50px">
                        </h3>
                        <p>Conecte talentos à sua empresa. Publique vagas diretamente no SAM RH e facilite a contratação dos melhores candidatos.</p>
                        <ul class="feature-list">
                            <li>Publicação de Vagas</li>
                            <li>Triagem de Candidatos</li>
                            <li>Conexão com Empresas</li>
                            <li>Facilidade na Contratação</li>
                            <li>e mais</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="dropdown">
                <a href="#" class="dropbtn">Funcionalidades ▾</a>
                <div class="dropdown-content">
                    <div class="dropdown-box dropdown-box-rh">
                        <h3>
                            <img src="img/sam2logo-32.png" alt="SAM RH" class="logo-icon" style="width:100px; height:50px">
                        </h3>
                        <p>Conheça todas as funcionalidades disponíveis para otimizar os processos do seu RH.</p>
                        <ul class="feature-list">
                            <li>Automação de Processos</li>
                            <li>Controle de Ponto</li>
                            <li>Gestão de Benefícios</li>
                            <li>e muito mais.</li>
                        </ul>
                    </div>
                    <div class="dropdown-box dropdown-box-emprego">
                        <h3>
                            <img src="img/emp.png" alt="SAM Emprego" class="logo-icon"  style="width:100px; height:50px">
                        </h3>
                        <p>Explore os diversos recursos que facilitam a gestão do capital humano da sua empresa.</p>
                        <ul class="feature-list">
                            <li>Integração com Sistemas</li>
                            <li>Customização</li>
                            <li>Compliance</li>
                            <li>e mais</li>
                        </ul>
                    </div>
                </div>
            </div>
            <a href="#">Preços</a>
        </nav>
        </div>
        <div class="nav-buttons">
            <a href="login.php">
                <button class="btn btn-entrar">Entrar</button>
            </a>
            <a href="Registro_adm.php">
                <button class="btn btn-criar">Criar empresa</button>
            </a>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <h1>Crie o seu perfil de <span>Administrador</span></h1>
            <form id="companyForm" action="registro_adm.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" placeholder="Nome do administrador" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Digite aqui" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" placeholder="Digite aqui" required>
                        <div class="alert-container"> <!-- Novo contêiner para alinhar ícone e texto -->
                        <img src="icones/ponto-de-exclamacao.png" alt="" class="exclamation">
                        <small class="alert">A senha deve ter pelo menos 8 caracteres.</small>
                    </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" placeholder="Digite aqui" required>
                        <input type="hidden" id="formatted_telefone" name="telefone_formatado"> <!-- Campo oculto -->
                    </div>
                </div>
                    
                <button type="submit" class="confirm-button">Confirmar</button>
            </form>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script>
        const input = document.querySelector("#telefone");
        const hiddenInput = document.querySelector("#formatted_telefone");

        const iti = window.intlTelInput(input, {
            initialCountry: "ao", 
            preferredCountries: ["ao", "pt", "br", "us"], 
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
        });

        document.querySelector("#companyForm").addEventListener("submit", function(event) {
            event.preventDefault(); // Evita envio antes da modificação

            // Validação de campos obrigatórios
            const nome = document.querySelector("#nome").value.trim();
            const email = document.querySelector("#email").value.trim();
            const senha = document.querySelector("#senha").value.trim();
            const telefone = iti.getNumber();

            if (!nome || !email || !senha || !telefone) {
                Swal.fire('Erro!', 'Todos os campos são obrigatórios!', 'error');
                return;
            }

            // Validação de e-mail
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.fire('Erro!', 'E-mail inválido!', 'error');
                return;
            }

            // Validação de senha
            if (senha.length < 8) {
                Swal.fire('Erro!', 'A senha deve ter pelo menos 8 caracteres!', 'error');
                return;
            }

            // Validação de telefone
            if (!iti.isValidNumber()) {
                Swal.fire('Erro!', 'Número de telefone inválido!', 'error');
                return;
            }

            hiddenInput.value = telefone; // Define o telefone formatado no campo oculto
            this.submit(); // Agora submete o formulário corretamente
        });

        <?php echo $sweetalert; ?>
    </script>
</body>
</html>