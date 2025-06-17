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
} catch (PDOException $e) {
    $erro = "Erro ao carregar dados: " . $e->getMessage();
}

// Lógica para upload de nova imagem de perfil
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $uploadDir = 'uploads/logos_empresas/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $fileTmp = $_FILES['logo']['tmp_name'];
    $fileName = 'logo_' . $_SESSION['empresa_id'] . '_' . time() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $filePath = $uploadDir . $fileName;
    if (move_uploaded_file($fileTmp, $filePath)) {
        // Atualizar no banco de dados
        $stmt = $pdo->prepare("UPDATE empresas_recrutamento SET logo = ? WHERE id = ?");
        $stmt->execute([$filePath, $_SESSION['empresa_id']]);
        $empresa['logo'] = $filePath;
        $mensagem = 'Imagem de perfil atualizada com sucesso!';
    } else {
        $mensagem = 'Erro ao fazer upload da imagem.';
    }
}

// Definir imagem de perfil (default se não houver)
$logoPath = !empty($empresa['logo']) ? $empresa['logo'] : 'sam2-05.png';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Configurações da Empresa - Dashboard RH</title>

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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo">
                <a href="painel_empresa.php">
                    <img src="../img/sam2logo-32.png" alt="SAM Logo">
                </a>
            </div>
            <select class="nav-select">
                <option>empresa</option>
            </select>
            <ul class="nav-menu">
                <a href="painel_empresa.php"><li>Painel Principal</li></a>
                <a href="configuracoes_empresa.php"><li class="active">Configurações</li></a>
                <a href="vagas_empresa.php"><li>Minhas Vagas</li></a>
                <a href="candidatos.php"><li>Candidatos</li></a>
                <a href="logout.php"><li>Sair</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="profile-card">
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo da Empresa" class="profile-picture" onclick="document.getElementById('logo').click()">
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($empresa['nome'] ?? 'Nome da Empresa'); ?></h1>
                        <p><?php echo htmlspecialchars($empresa['setor'] ?? 'Setor da Empresa'); ?></p>
                        <p><?php echo htmlspecialchars($empresa['cidade'] ?? 'Localização'); ?></p>
                    </div>
                </div>

                <?php if ($mensagem): ?>
                    <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="profile-details">
                        <div class="detail-section">
                            <h3>Logo da Empresa</h3>
                            <div class="detail-item">
                                <label for="logo">Alterar Imagem de Perfil</label>
                                <div class="upload-section">
                                    <label for="logo">
                                        <i class="fas fa-camera"></i> Escolher Nova Imagem
                                        <input type="file" name="logo" id="logo" accept="image/*" onchange="this.form.submit()">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="detail-section">
                            <h3>Informações da Empresa</h3>
                            <div class="detail-item">
                                <label for="nome_empresa">Nome da Empresa</label>
                                <input type="text" id="nome_empresa" name="nome_empresa" value="<?php echo htmlspecialchars($empresa['nome'] ?? ''); ?>">
                            </div>
                            <div class="detail-item">
                                <label for="email_empresa">Email</label>
                                <input type="email" id="email_empresa" name="email_empresa" value="<?php echo htmlspecialchars($empresa['email'] ?? ''); ?>">
                            </div>
                            <div class="detail-item">
                                <label for="telefone_empresa">Telefone</label>
                                <input type="tel" id="telefone_empresa" name="telefone_empresa" value="<?php echo htmlspecialchars($empresa['telefone'] ?? ''); ?>">
                            </div>
                            <div class="detail-item">
                                <label for="setor">Setor de Atuação</label>
                                <input type="text" id="setor" name="setor" value="<?php echo htmlspecialchars($empresa['setor'] ?? ''); ?>">
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
</body>
</html>