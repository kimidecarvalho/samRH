<?php
session_start();
include('../conexao.php');

// Verifica se o usuário está logado
if (!isset($_SESSION['id_adm'])) {
    header('Location: ../login.php');
    exit();
}

// Processa a atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $cargo = $_POST['cargo'];
    $departamento = $_POST['departamento'];
    $matricula = $_POST['matricula'];
    $data_admissao = $_POST['data_admissao'];
    $nivel_acesso = $_POST['nivel_acesso'];
    $adm_id = $_SESSION['id_adm'];
    
    // Verifica se o email já está em uso por outro usuário
    $sql_check = "SELECT id_adm FROM adm WHERE email = ? AND id_adm != ?";
    $stmt_check = $mysqli->prepare($sql_check);
    $stmt_check->bind_param("si", $email, $adm_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Este email já está em uso por outro usuário']);
        exit();
    }
    
    // Atualiza os dados do administrador
    $sql = "UPDATE adm SET nome = ?, email = ?, telefone = ?, cargo = ?, departamento = ?, matricula = ?, data_admissao = ?, nivel_acesso = ? WHERE id_adm = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssssssssi", $nome, $email, $telefone, $cargo, $departamento, $matricula, $data_admissao, $nivel_acesso, $adm_id);
    
    if ($stmt->execute()) {
        // Registra a alteração no log
        $ip = getRealIP();
        $sql_log = "INSERT INTO log_atividades (adm_id, acao, ip_address, data_hora) VALUES (?, 'Atualização de Perfil', ?, NOW())";
        $stmt_log = $mysqli->prepare($sql_log);
        $stmt_log->bind_param("is", $adm_id, $ip);
        $stmt_log->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar perfil']);
    }
    exit();
}

// Busca os dados do administrador
$adm_id = $_SESSION['id_adm'];
$sql = "SELECT nome, email, telefone, cargo, departamento, matricula, data_admissao, nivel_acesso FROM adm WHERE id_adm = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $adm_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Função para obter o IP real
function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Perfil do Usuário - Dashboard RH</title>
    <link rel="stylesheet" href="../all.css/registro3.css">
    <link rel="stylesheet" href="../all.css/configuracoes.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #3EB489;
            --background-light: #f4f4f4;
            --text-color: #333;
            --white: #ffffff;
            --input-border: #e0e0e0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-light);
            color: var(--text-color);
        }

        .profile-card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--background-light);
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 4px solid var(--primary-color);
            background-color: #3EB489;
        }

        .profile-info h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .profile-info p {
            color: #6c757d;
            margin-bottom: 5px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        .btn-primary:hover {
            background-color: #32a177;
        }

        .profile-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .detail-section {
            background-color: var(--background-light);
            border-radius: 10px;
            padding: 20px;
        }

        .detail-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-item label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-color);
        }

        .detail-item input,
        .detail-item select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--input-border);
            border-radius: 6px;
            background-color: var(--white);
            transition: border-color 0.3s ease;
        }

        .detail-item input:focus,
        .detail-item select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(62, 180, 137, 0.2);
        }

        .profile-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        /* Estilos para o modo escuro */
        body.dark {
            background-color: #121212;
            color: #e0e0e0;
        }

        body.dark .profile-card {
            background-color: #262626;
        }

        body.dark .detail-section {
            background-color: #1a1a1a;
        }

        body.dark .detail-item input,
        body.dark .detail-item select {
            background-color: #333;
            border-color: #444;
            color: #e0e0e0;
        }

        body.dark .detail-item label {
            color: #c0c0c0;
        }

        .detail-item select:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
            opacity: 0.8;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        body.dark .detail-item select:disabled {
            background-color: #2a2a2a;
            color: #888;
        }

        /* Remove a seta do select em todos os navegadores */
        .detail-item select:disabled::-ms-expand {
            display: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo">
                <a href="../UI.php">
                    <img src="../img/sam2logo-32.png" alt="SAM Logo">
                </a>
            </div>
            <select class="nav-select">
                <option>sam</option>
            </select>
            <ul class="nav-menu">
                <a href="conf.sistema.php"><li>Configurações do Sistema</li></a>
                <a href="perfil_adm.php"><li class="active">Perfil do Usuário</li></a>
                <a href="seguranca.php"><li>Segurança</li></a>
                <a href="privacidade.php"><li>Privacidade</li></a>
                <a href="rh_config.php"><li>Configurações de RH</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="profile-card">
                <div class="profile-header">
                    <img src="../icones/icons-sam-18.svg" alt="Ícone SAM" class="profile-picture">
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($admin['nome']); ?></h1>
                        <p><?php echo htmlspecialchars($admin['cargo']); ?></p>
                        <p><?php echo htmlspecialchars($admin['departamento']); ?></p>
                    </div>
                </div>

                <form id="perfilForm">
                <div class="profile-details">
                    <div class="detail-section">
                        <h3>Informações Pessoais</h3>
                        <div class="detail-item">
                                <label for="nome">Nome Completo</label>
                                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($admin['nome']); ?>" required>
                        </div>
                        <div class="detail-item">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>
                        <div class="detail-item">
                                <label for="telefone">Telefone</label>
                                <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($admin['telefone']); ?>">
                        </div>
                        <div class="detail-item">
                                <label for="data_admissao">Data de Admissão</label>
                                <input type="date" id="data_admissao" name="data_admissao" value="<?php echo htmlspecialchars($admin['data_admissao']); ?>">
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3>Informações Profissionais</h3>
                        <div class="detail-item">
                                <label for="matricula">Matrícula</label>
                                <input type="text" id="matricula" name="matricula" value="<?php echo htmlspecialchars($admin['matricula']); ?>">
                        </div>
                        <div class="detail-item">
                                <label for="cargo">Cargo</label>
                                <input type="text" id="cargo" name="cargo" value="<?php echo htmlspecialchars($admin['cargo']); ?>">
                        </div>
                        <div class="detail-item">
                                <label for="departamento">Departamento</label>
                                <input type="text" id="departamento" name="departamento" value="<?php echo htmlspecialchars($admin['departamento']); ?>">
                        </div>
                        <div class="detail-item">
                                <label for="nivel_acesso">Nível de Acesso</label>
                                <div style="position: relative;">
                                    <select id="nivel_acesso" name="nivel_acesso" disabled style="padding-right: 40px;">
                                        <option value="Administrador" selected>Administrador</option>
                                    </select>
                                    <i class="fas fa-lock" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #dc3545;"></i>
                                </div>
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                        <button type="submit" class="btn-primary">Salvar Alterações</button>
                </div>
                </form>
            </div>
        </div>
    </div>

        <script src="../js/theme.js"></script>
    <script>
    document.getElementById('perfilForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('atualizar_perfil', '1');
        
        fetch('perfil_adm.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Perfil atualizado com sucesso!');
                // Atualiza o nome e cargo no cabeçalho
                document.querySelector('.profile-info h1').textContent = formData.get('nome');
                document.querySelector('.profile-info p').textContent = formData.get('cargo');
            } else {
                alert(data.error || 'Erro ao atualizar perfil. Tente novamente.');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao atualizar perfil. Tente novamente.');
        });
    });
    </script>
</body>
</html>