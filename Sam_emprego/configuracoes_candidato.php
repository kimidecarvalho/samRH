<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['candidato_id'])) {
    header('Location: login.php');
    exit;
}

$candidato_id = $_SESSION['candidato_id'];
$mensagem = '';
$mensagem_erro = '';

// Buscar informações do candidato
$stmt = $conn->prepare("SELECT * FROM candidatos WHERE id = ?");
$stmt->bind_param("i", $candidato_id);
$stmt->execute();
$result = $stmt->get_result();
$candidato = $result->fetch_assoc();

// Buscar mensagens do candidato
$mensagens = [];
$stmt = $conn->prepare("
    SELECT m.*, c.vaga_id, v.titulo as vaga_titulo, e.nome as empresa_nome
    FROM mensagens m
    JOIN candidaturas c ON m.candidatura_id = c.id
    JOIN vagas v ON c.vaga_id = v.id
    JOIN empresas_recrutamento e ON v.empresa_id = e.id
    WHERE c.candidato_id = ?
    ORDER BY m.data_envio DESC
    LIMIT 5
");
$stmt->execute([$candidato_id]);
$mensagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Upload de currículo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['curriculo'])) {
    $target_dir = "uploads/curriculos/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $file_extension = strtolower(pathinfo($_FILES["curriculo"]["name"], PATHINFO_EXTENSION));
    $new_filename = "curriculo_" . $candidato_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    $allowed_types = ['pdf', 'doc', 'docx'];
    if (in_array($file_extension, $allowed_types)) {
        if (move_uploaded_file($_FILES["curriculo"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("UPDATE candidatos SET curriculo_path = ? WHERE id = ?");
            $stmt->bind_param("si", $target_file, $candidato_id);
            $stmt->execute();
            $candidato['curriculo_path'] = $target_file;
            $mensagem = "Currículo atualizado com sucesso!";
        } else {
            $mensagem_erro = "Erro ao fazer upload do currículo.";
        }
    } else {
        $mensagem_erro = "Apenas arquivos PDF, DOC e DOCX são permitidos.";
    }
}

// Atualização de dados do candidato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_dados'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $endereco = trim($_POST['endereco'] ?? '');
    $formacao = trim($_POST['formacao'] ?? '');
    $experiencia = trim($_POST['experiencia'] ?? '');
    $habilidades = trim($_POST['habilidades'] ?? '');

    $stmt = $conn->prepare("UPDATE candidatos SET nome=?, email=?, telefone=?, data_nascimento=?, endereco=?, formacao=?, experiencia=?, habilidades=? WHERE id=?");
    $stmt->bind_param("ssssssssi", $nome, $email, $telefone, $data_nascimento, $endereco, $formacao, $experiencia, $habilidades, $candidato_id);
    if ($stmt->execute()) {
        $mensagem = "Dados atualizados com sucesso!";
        // Atualiza array local para refletir mudanças
        $candidato = array_merge($candidato, [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'data_nascimento' => $data_nascimento,
            'endereco' => $endereco,
            'formacao' => $formacao,
            'experiencia' => $experiencia,
            'habilidades' => $habilidades
        ]);
    } else {
        $mensagem_erro = "Erro ao atualizar dados: " . $conn->error;
    }
}

// Currículo
$cv_path = $candidato['curriculo_path'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações do Candidato - SAM</title>
    <link rel="stylesheet" href="../all.css/registro3.css">
    <link rel="stylesheet" href="../all.css/configuracoes.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;     
            background-color: var(--background-light);
            color: var(--text-color);
        }

        .profile-card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .logo {
            height: 80px;
        }

        .logo img {
            height: 60px;
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
            background-color:rgb(255, 255, 255);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .profile-picture:hover {
            transform: scale(1.05);
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

        .mensagem {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .upload-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .upload-section label {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .upload-section label:hover {
            background-color: #32a177;
        }

        .upload-section input[type="file"] {
            display: none;
        }

        /* Estilos da sidebar */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Estilos para a seção de mensagens na sidebar */
        .sidebar-messages {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .sidebar-messages h4 {
            color: #3EB489;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .message-item {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 12px;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-empresa {
            font-weight: 600;
            color: #3EB489;
        }

        .message-vaga {
            color: #6c757d;
            font-size: 11px;
        }

        .message-date {
            color: #adb5bd;
            font-size: 10px;
        }

        .no-messages {
            color: #6c757d;
            font-size: 12px;
            text-align: center;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo">
                <a href="painel_candidato.php">
                    <img src="../fotos/sam30-13.png" alt="SAM Logo">
                </a>
            </div>
            <select class="nav-select">
                <option>Candidato</option>
            </select>
            <ul class="nav-menu">
                <a href="painel_candidato.php"><li>Painel Principal</li></a>
                <a href="configuracoes_candidato.php"><li class="active">Configurações</li></a>
                <a href="minhas_candidaturas.php"><li>Minhas Candidaturas</li></a>
                <a href="curriculums.php"><li>Meu Currículo</li></a>
                <a href="job_search_page.php"><li>Buscar Vagas</li></a>
                <a href="logout.php"><li>Sair</li></a>
            </ul>

            <!-- Seção de Mensagens na Sidebar -->
            <div class="sidebar-messages">
                <h4><i class="fas fa-envelope"></i> Mensagens Recentes</h4>
                <?php if (!empty($mensagens)): ?>
                    <?php foreach ($mensagens as $msg): ?>
                        <div class="message-item">
                            <div class="message-empresa"><?php echo htmlspecialchars($msg['empresa_nome']); ?></div>
                            <div class="message-vaga"><?php echo htmlspecialchars($msg['vaga_titulo']); ?></div>
                            <div class="message-date"><?php echo date('d/m/Y H:i', strtotime($msg['data_envio'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-messages">Nenhuma mensagem</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="main-content">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-picture" style="background:#f5f5f5;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-user" style="font-size:60px;color:#3EB489;"></i>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($candidato['nome'] ?? 'Nome do Candidato'); ?></h1>
                        <p><?php echo htmlspecialchars($candidato['email'] ?? 'Email'); ?></p>
                        <p><?php echo htmlspecialchars($candidato['telefone'] ?? 'Telefone'); ?></p>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
                <?php endif; ?>
                <?php if ($mensagem_erro): ?>
                    <div class="mensagem-erro"><?php echo htmlspecialchars($mensagem_erro); ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="profile-details">
                        <div class="detail-section">
                            <h3>Currículo</h3>
                            <div class="detail-item">
                                <label for="curriculo">Alterar Currículo (PDF, DOC, DOCX)</label>
                                <div class="upload-section">
                                    <label for="curriculo">
                                        <i class="fas fa-upload"></i> Escolher Novo Currículo
                                        <input type="file" name="curriculo" id="curriculo" accept=".pdf,.doc,.docx" onchange="this.form.submit()">
                                    </label>
                                    <?php if ($cv_path): ?>
                                        <div style="margin-top:10px;">
                                            <a href="<?php echo htmlspecialchars($cv_path); ?>" target="_blank" style="color:#3EB489;text-decoration:underline;">Ver Currículo Atual</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="detail-section">
                            <h3>Informações do Candidato</h3>
                            <div class="detail-item">
                                <label for="nome">Nome Completo</label>
                                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($candidato['nome'] ?? ''); ?>">
                            </div>
                            <div class="detail-item">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($candidato['email'] ?? ''); ?>">
                            </div>
                            <div class="detail-item">
                                <label for="telefone">Telefone</label>
                                <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($candidato['telefone'] ?? ''); ?>">
                            </div>
                            <div class="detail-item">
                                <label for="data_nascimento">Data de Nascimento</label>
                                <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo htmlspecialchars($candidato['data_nascimento'] ?? ''); ?>">
                            </div>
                            <div class="detail-item">
                                <label for="endereco">Endereço</label>
                                <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($candidato['endereco'] ?? ''); ?>">
                            </div>
                            <div class="detail-item">
                                <label for="formacao">Formação Acadêmica</label>
                                <input type="text" id="formacao" name="formacao" value="<?php echo htmlspecialchars($candidato['formacao'] ?? ''); ?>">
                            </div>
                            <div class="detail-item">
                                <label for="experiencia">Experiência Profissional</label>
                                <input type="text" id="experiencia" name="experiencia" value="<?php echo htmlspecialchars($candidato['experiencia'] ?? ''); ?>">
                            </div>
                            <div class="detail-item">
                                <label for="habilidades">Habilidades</label>
                                <input type="text" id="habilidades" name="habilidades" value="<?php echo htmlspecialchars($candidato['habilidades'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="profile-actions">
                        <button type="submit" name="atualizar_dados" class="btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 