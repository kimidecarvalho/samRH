<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header("Location: login.php");
    exit();
}

// Recupera os dados do candidato
$candidato_id = $_SESSION['candidato_id'];
$stmt = $conn->prepare("SELECT * FROM candidatos WHERE id = ?");
$stmt->bind_param("i", $candidato_id);
$stmt->execute();
$result = $stmt->get_result();
$candidato = $result->fetch_assoc();

// Processa o upload do currículo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["curriculo"])) {
    $target_dir = "uploads/curriculos/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES["curriculo"]["name"], PATHINFO_EXTENSION));
    $new_filename = "curriculo_" . $candidato_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Verifica o tipo de arquivo
    $allowed_types = ['pdf', 'doc', 'docx'];
    if (in_array($file_extension, $allowed_types)) {
        if (move_uploaded_file($_FILES["curriculo"]["tmp_name"], $target_file)) {
            // Atualiza o caminho do currículo no banco de dados
            $stmt = $conn->prepare("UPDATE candidatos SET curriculo_path = ? WHERE id = ?");
            $stmt->bind_param("si", $target_file, $candidato_id);
            $stmt->execute();
            
            $_SESSION['mensagem_sucesso'] = "Currículo enviado com sucesso!";
            header("Location: curriculums.php");
            exit();
        }
    } else {
        $_SESSION['mensagem_erro'] = "Apenas arquivos PDF, DOC e DOCX são permitidos.";
    }
}

// Recupera mensagens de feedback
$mensagem_sucesso = $_SESSION['mensagem_sucesso'] ?? '';
$mensagem_erro = $_SESSION['mensagem_erro'] ?? '';
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAM - Meu Currículo</title>
    <link rel="icon" type="" href="sam2-05.png">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_vagas.css">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_search.css">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_header.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3EB489;
            --primary-light: #4fc89a;
            --primary-dark: #339873;
            --secondary-color: rgb(84, 115, 146);
            --shadow-light: 0 4px 20px rgba(62, 180, 137, 0.1);
            --shadow-medium: 0 8px 30px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 12px 40px rgba(62, 180, 137, 0.15);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .curriculum-section {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-medium);
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(62, 180, 137, 0.1);
        }

        .curriculum-section:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .section-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 24px;
            font-size: 1.3rem;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .section-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .section-header:hover::before {
            left: 100%;
        }

        .section-body {
            padding: 30px;
        }

        .upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 50px 20px;
            text-align: center;
            margin: 25px 0;
            transition: all 0.4s ease;
            position: relative;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        }

        .upload-area:hover {
            border-color: var(--primary-color);
            background: linear-gradient(145deg, #f0fdf4 0%, #ecfdf5 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }

        .upload-area.drag-over {
            border-color: var(--primary-color);
            background: linear-gradient(145deg, #f0fdf4 0%, #ecfdf5 100%);
            animation: pulse 1s infinite;
        }

        .upload-icon {
            font-size: 54px;
            color: var(--primary-color);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .upload-area:hover .upload-icon {
            transform: scale(1.1);
            color: var(--primary-dark);
        }

        .upload-area h3 {
            margin: 0 0 10px 0;
            color: #374151;
            font-weight: 600;
        }

        .upload-area p {
            color: #6b7280;
            margin: 0 0 25px 0;
            font-size: 0.95rem;
        }

        .file-input {
            display: none;
        }

        .upload-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(62, 180, 137, 0.3);
        }

        .upload-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(62, 180, 137, 0.4);
        }

        .upload-btn:active {
            transform: translateY(-1px);
        }

        .current-cv {
            background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 25px;
            margin-top: 25px;
            border: 1px solid rgba(62, 180, 137, 0.1);
            animation: slideIn 0.5s ease-out;
        }

        .cv-details {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .cv-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.4rem;
            box-shadow: 0 4px 15px rgba(62, 180, 137, 0.2);
        }

        .cv-info {
            flex-grow: 1;
        }

        .cv-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 1.1rem;
        }

        .cv-meta {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .action-btn {
            padding: 10px 20px;
            border-radius: 25px;
            border: 2px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(62, 180, 137, 0.3);
        }

        .alert {
            padding: 18px 24px;
            margin-bottom: 25px;
            border-radius: 12px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.5s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-left: 4px solid var(--primary-color);
            color: #047857;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.1);
        }

        .alert-error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 4px solid #ef4444;
            color: #b91c1c;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.1);
        }

        .instructions {
            background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .instructions h3 {
            color: var(--secondary-color);
            margin-top: 0;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .instructions ul {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        .instructions li {
            margin-bottom: 12px;
            color: #475569;
            padding-left: 25px;
            position: relative;
        }

        .instructions li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--primary-color);
            font-weight: bold;
        }

        #selectedFile {
            background: linear-gradient(145deg, #f0fdf4 0%, #dcfce7 100%);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(34, 197, 94, 0.2);
            animation: slideIn 0.4s ease-out;
        }

        #selectedFile p {
            margin: 0 0 15px 0;
            color: #166534;
            font-weight: 500;
        }

        /* Loading animation */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .section-body {
                padding: 20px;
            }
            
            .upload-area {
                padding: 30px 15px;
            }
            
            .cv-details {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
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
                    <a href="job_search_page.php">Vagas</a>
                    <a href="curriculums.php" class="active">Meu Currículo</a>
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
            </div>
            <div class="settings-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3EB489" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </div>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensagem_sucesso); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagem_erro)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensagem_erro); ?>
            </div>
        <?php endif; ?>

        <div class="curriculum-section">
            <div class="section-header">
                <i class="fas fa-file-user" style="margin-right: 10px;"></i>
                Gerenciar Currículo
            </div>
            <div class="section-body">
                <?php if (!empty($candidato['curriculo_path'])): ?>
                    <div class="current-cv">
                        <div class="cv-details">
                            <div class="cv-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="cv-info">
                                <div class="cv-name">Seu currículo atual</div>
                                <div class="cv-meta">
                                    <i class="fas fa-calendar-alt" style="margin-right: 5px;"></i>
                                    Enviado em: <?php echo date('d/m/Y H:i', filemtime($candidato['curriculo_path'])); ?>
                                </div>
                            </div>
                            <div>
                                <a href="<?php echo htmlspecialchars($candidato['curriculo_path']); ?>" target="_blank" class="action-btn">
                                    <i class="fas fa-eye"></i> Visualizar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="curriculums.php" method="post" enctype="multipart/form-data" id="uploadForm">
                    <div class="upload-area" id="dropZone">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <h3>Enviar novo currículo</h3>
                        <p>Arraste e solte seu arquivo aqui ou clique no botão abaixo</p>
                        <input type="file" name="curriculo" id="curriculo" class="file-input" accept=".pdf,.doc,.docx">
                        <button type="button" class="upload-btn" onclick="document.getElementById('curriculo').click()">
                            <i class="fas fa-upload"></i> Selecionar arquivo
                        </button>
                    </div>
                    <div id="selectedFile" style="display: none; margin-top: 20px; text-align: center;">
                        <p><i class="fas fa-file-check" style="margin-right: 8px;"></i>Arquivo selecionado: <strong id="fileName"></strong></p>
                        <button type="submit" class="upload-btn">
                            <i class="fas fa-check"></i> Confirmar envio
                        </button>
                    </div>
                </form>

                <div class="instructions">
                    <h3><i class="fas fa-info-circle" style="margin-right: 10px;"></i>Instruções para envio do currículo</h3>
                    <ul>
                        <li>Aceitos apenas arquivos nos formatos PDF, DOC ou DOCX</li>
                        <li>Tamanho máximo do arquivo: 5MB</li>
                        <li>Certifique-se de que seu currículo está atualizado</li>
                        <li>Inclua suas principais experiências e habilidades</li>
                        <li>Evite usar fontes muito decorativas ou layouts complexos</li>
                        <li>Mantenha um formato profissional e organizado</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Manipulação do input de arquivo
        document.getElementById('curriculo').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.getElementById('fileName').textContent = fileName;
                document.getElementById('selectedFile').style.display = 'block';
                
                // Animação de aparição
                const selectedFile = document.getElementById('selectedFile');
                selectedFile.style.opacity = '0';
                selectedFile.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    selectedFile.style.transition = 'all 0.4s ease';
                    selectedFile.style.opacity = '1';
                    selectedFile.style.transform = 'translateY(0)';
                }, 10);
            }
        });

        // Drag and drop melhorado
        const dropZone = document.getElementById('dropZone');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('drag-over');
        }

        function unhighlight(e) {
            dropZone.classList.remove('drag-over');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            const fileInput = document.getElementById('curriculo');
            fileInput.files = files;
            
            if (fileInput.files[0]) {
                document.getElementById('fileName').textContent = fileInput.files[0].name;
                document.getElementById('selectedFile').style.display = 'block';
                
                // Animação de aparição
                const selectedFile = document.getElementById('selectedFile');
                selectedFile.style.opacity = '0';
                selectedFile.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    selectedFile.style.transition = 'all 0.4s ease';
                    selectedFile.style.opacity = '1';
                    selectedFile.style.transform = 'translateY(0)';
                }, 10);
            }
        }

        // Loading state no formulário
        document.getElementById('uploadForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
        });

        // Smooth scroll para alertas
        if (document.querySelector('.alert')) {
            document.querySelector('.alert').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }
    </script>
</body>
</html>