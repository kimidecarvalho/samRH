<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header("Location: login.php");
    exit();
}

$candidato_id = $_SESSION['candidato_id'];
$mensagem = '';
$tipo_mensagem = '';

// Processa o upload do currículo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['curriculo']) && $_FILES['curriculo']['error'] === UPLOAD_ERR_OK) {
        $arquivo = $_FILES['curriculo'];
        $nome_arquivo = $arquivo['name'];
        $tipo_arquivo = $arquivo['type'];
        $tamanho_arquivo = $arquivo['size'];
        $arquivo_tmp = $arquivo['tmp_name'];

        // Validação do tipo de arquivo
        $tipos_permitidos = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($tipo_arquivo, $tipos_permitidos)) {
            $mensagem = "Erro: Apenas arquivos PDF e Word são permitidos.";
            $tipo_mensagem = "erro";
        }
        // Validação do tamanho (máximo 5MB)
        elseif ($tamanho_arquivo > 5 * 1024 * 1024) {
            $mensagem = "Erro: O arquivo deve ter no máximo 5MB.";
            $tipo_mensagem = "erro";
        } else {
            // Cria o diretório de uploads se não existir
            $diretorio_uploads = "uploads/curriculos/";
            if (!file_exists($diretorio_uploads)) {
                mkdir($diretorio_uploads, 0777, true);
            }

            // Gera um nome único para o arquivo
            $extensao = pathinfo($nome_arquivo, PATHINFO_EXTENSION);
            $novo_nome = uniqid() . '_' . $candidato_id . '.' . $extensao;
            $caminho_completo = $diretorio_uploads . $novo_nome;

            // Move o arquivo para o diretório de uploads
            if (move_uploaded_file($arquivo_tmp, $caminho_completo)) {
                // Atualiza o banco de dados
                $stmt = $conn->prepare("UPDATE candidatos SET curriculo_path = ? WHERE id = ?");
                $stmt->bind_param("si", $caminho_completo, $candidato_id);
                
                if ($stmt->execute()) {
                    $mensagem = "Currículo atualizado com sucesso!";
                    $tipo_mensagem = "sucesso";
                    $_SESSION['mensagem_sucesso'] = $mensagem;
                    header("Location: painel_candidato.php");
                    exit();
                } else {
                    $mensagem = "Erro ao atualizar o banco de dados.";
                    $tipo_mensagem = "erro";
                }
            } else {
                $mensagem = "Erro ao fazer upload do arquivo.";
                $tipo_mensagem = "erro";
            }
        }
    } else {
        $mensagem = "Por favor, selecione um arquivo para upload.";
        $tipo_mensagem = "erro";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="" href="sam2-05.png">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_header.css">
    <link rel="stylesheet" href="../all.css/emprego.css/emp_search.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <title>SAM - Atualizar Currículo</title>
    <style>
        :root {
            --primary-color: #3EB489;
            --primary-light: #4fc89a;
            --primary-dark: #339873;
            --secondary-color: rgb(84, 115, 146);
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

        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .upload-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
            margin-top: 20px;
        }

        .upload-title {
            color: var(--secondary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .file-input-container {
            position: relative;
            border: 2px dashed var(--primary-color);
            border-radius: var(--border-radius);
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-input-container:hover {
            background-color: var(--light-gray);
        }

        .file-input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .upload-text {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .file-info {
            font-size: 0.9rem;
            color: var(--dark-gray);
        }

        .btn {
            padding: 12px 25px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(62, 180, 137, 0.4);
        }

        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .alert-success {
            background-color: #d4edda;
            border-left: 4px solid var(--primary-color);
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .requirements {
            background-color: var(--light-gray);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-top: 20px;
        }

        .requirements h3 {
            color: var(--secondary-color);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .requirements li {
            margin-bottom: 8px;
            padding-left: 25px;
            position: relative;
        }

        .requirements li:before {
            content: '•';
            color: var(--primary-color);
            position: absolute;
            left: 0;
            font-size: 1.2rem;
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
                    <a href="curriculums.php">Meu Currículo</a>
                    <a href="minhas_candidaturas.php">Candidaturas</a>
                    <a href="painel_candidato.php">Perfil</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $tipo_mensagem === 'sucesso' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <div class="upload-section">
            <h1 class="upload-title">Atualizar Currículo</h1>
            
            <form class="upload-form" method="POST" enctype="multipart/form-data">
                <div class="file-input-container" id="dropZone">
                    <input type="file" name="curriculo" class="file-input" id="fileInput" accept=".pdf,.doc,.docx" required>
                    <div class="upload-icon">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <div class="upload-text">
                        Arraste seu currículo aqui ou clique para selecionar
                    </div>
                    <div class="file-info">
                        Formatos aceitos: PDF, DOC, DOCX (máximo 5MB)
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Enviar Currículo
                </button>
            </form>

            <div class="requirements">
                <h3>Requisitos do Currículo:</h3>
                <ul>
                    <li>Formato do arquivo: PDF, DOC ou DOCX</li>
                    <li>Tamanho máximo: 5MB</li>
                    <li>O arquivo deve conter seu currículo atualizado</li>
                    <li>Certifique-se de que o arquivo não está protegido por senha</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');

            // Previne o comportamento padrão de arrastar
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Adiciona efeito visual quando arrastar sobre a área
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                dropZone.classList.add('highlight');
            }

            function unhighlight(e) {
                dropZone.classList.remove('highlight');
            }

            // Atualiza o texto quando um arquivo é selecionado
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const fileName = this.files[0].name;
                    const fileSize = (this.files[0].size / (1024 * 1024)).toFixed(2);
                    document.querySelector('.upload-text').textContent = `Arquivo selecionado: ${fileName}`;
                    document.querySelector('.file-info').textContent = `Tamanho: ${fileSize}MB`;
                }
            });
        });
    </script>
</body>
</html>
