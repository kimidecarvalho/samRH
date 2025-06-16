<?php
session_start();
include 'config.php'; // Conexão com o banco de dados

// Verifica se o usuário está logado e tem uma empresa associada
if (!isset($_SESSION['id_empresa'])) {
    echo "<script>alert('Você precisa criar uma empresa antes de acessar esta página.'); window.location.href='Registro_adm.php';</script>";
    exit;
}

$empresa_id = $_SESSION['id_empresa']; // Define $empresa_id a partir da sessão

if (isset($_GET['folder']) && isset($_GET['employeeId'])) {
    $folder = $_GET['folder'];
    $employeeId = $_GET['employeeId'];

    // Prepara a consulta SQL
    $sql = "SELECT titulo, tipo, data, descricao, anexo, num_funcionario FROM documentos WHERE folder = ? AND num_funcionario = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die('Erro ao preparar a consulta: ' . mysqli_error($conn));
    }

    // Vincula os parâmetros e executa a consulta
    mysqli_stmt_bind_param($stmt, 'si', $folder, $employeeId);
    mysqli_stmt_execute($stmt);

    // Obtém o resultado da consulta
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        die('Erro ao executar a consulta: ' . mysqli_error($conn));
    }

    // Processa os resultados
    $documents = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $documents[] = $row;
    }

    // Retorna os documentos em formato JSON
    echo json_encode($documents);
} else {
    echo json_encode([]);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="all.css/registro3.css">
    <link rel="stylesheet" href="all.css/timer.css">
    <link rel="stylesheet" href="all.css/docs.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <title>Sistema de Gestão de Funcionários</title>
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
            <a href="registro.php"><li>Novo Funcionário</li></a>
            <li>Processamento Salarial</li>
            <a href="docs.php"><li class="active">Documentos</li></a>
            <a href="registro_ponto.php"><li>Registro de Ponto</li></a>
            <a href="ausencias.php"><li>Ausências</li></a>
            <a href="recrutamento.php"><li>Recrutamento</li></a>
        </ul>
    </div>

    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Documentos</h1>
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
        <div class="search-container">
            <input type="text" placeholder="">
            <input type="text" placeholder="">
            <input type="text" placeholder="Pesquisar..." class="search-bar">
        </div>

        <div class="container">
            <div class="employee-content">
                <h2>Selecione o funcionário</h2>
                <div class="employee-list-container">
        <ul class="employee-list" id="employeeList">
            <?php
            // Consulta para recuperar os funcionários filtrados por empresa_id
            $sql = "SELECT id_fun, nome FROM funcionario WHERE empresa_id = $empresa_id";
            $result = mysqli_query($conn, $sql);

            while ($row = mysqli_fetch_assoc($result)) {
                echo '<li class="employee-item" data-id="' . $row['id_fun'] . '">' . $row['id_fun'] . ' - ' . $row['nome'] . '</li>';
            }
            ?>
        </ul>
            </div>
        </div>
                <div class="main-content1">
                <div class="top-buttons">
            <button class="back-button" id="backButton" style="display: none;">
                <span class="simbol"> &lt;</span>   
            </button>
            <button class="btn" id="openFolderBtn">Abrir pasta</button>
            <button class="btn" id="uploadBtn">Clique para enviar arquivo</button>
        </div>

        <div id="uploadForm" style="display: none;">
            <form id="fileUploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="document" id="fileInput" required style="display: none;">
                <input type="hidden" name="folder" id="selectedFolderInput">
                <input type="hidden" name="funcionario_id" id="selectedFuncionarioInput">
            </form>
        </div>

            
            <!-- Visualização de pastas -->
            <div class="content" id="folderView">
                <div class="folder" data-folder="documentacao">
                    <div class="folder-icon">
                        <img src="icones/icons-sam-30.svg" alt="">
                    </div>
                    <h3>Documentação do Funcionário</h3>
                    <p>0 elementos</p>
                </div>
                <div class="folder" data-folder="frequencia">
                    <div class="folder-icon">
                        <img src="icones/icons-sam-30.svg" alt="">
                    </div>
                    <h3>Frequência e Pontualidade</h3>
                    <p>0 elementos</p>
                </div>
                <div class="folder" data-folder="solicitacoes">
                    <div class="folder-icon">
                        <img src="icones/icons-sam-30.svg" alt="">
                    </div>
                    <h3>Solicitações e Autorizações</h3>
                    <p>0 elementos</p>
                </div>
                <div class="folder" data-folder="outros">
                    <div class="folder-icon">
                        <img src="icones/icons-sam-30.svg" alt="">
                    </div>
                    <h3>Outros</h3>
                    <p>0 elementos</p>
                </div>
            </div>
            
            <div id="folderContentView" style="display: none;">
                <div class="folder-view-header">
                    <h2 class="folder-title" id="folderTitle">Documentação do Funcionário</h2>
                </div>
                
                <div class="table-container">
                    <table class="documents-table" id="documentsTable">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Anexo</th>
                                <th>Nº Funcionário</th>
                            </tr>
                        </thead>
                        <tbody id="documentsTableBody">
                            <!-- Conteúdo será preenchido dinamicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeItems = document.querySelectorAll('.employee-item');
    const folders = document.querySelectorAll('.folder');
    const openFolderBtn = document.getElementById('openFolderBtn');
    const folderView = document.getElementById('folderView');
    const folderContentView = document.getElementById('folderContentView');
    const documentsTableBody = document.getElementById('documentsTableBody');
    const folderTitle = document.getElementById('folderTitle');
    const backButton = document.getElementById('backButton');
    const uploadBtn = document.getElementById('uploadBtn');
    const fileInput = document.getElementById('fileInput');
    const selectedFolderInput = document.getElementById('selectedFolderInput');
    const selectedFuncionarioInput = document.getElementById('selectedFuncionarioInput');

    // Variável para armazenar a pasta selecionada
    let selectedFolder = null;

    // Função para carregar o número de documentos em cada pasta
    function loadDocumentCounts(employeeId) {
        fetch(`get_document_counts.php?employeeId=${employeeId}`) // Passa o ID do funcionário
            .then(response => response.json())
            .then(data => {
                // Atualiza o número de elementos em cada pasta
                folders.forEach(folder => {
                    const folderName = folder.getAttribute('data-folder');
                    const count = data[folderName] || 0;
                    folder.querySelector('p').textContent = `${count} elementos`;
                });
            })
            .catch(error => console.error('Erro ao carregar contadores:', error));
    }

    // Seleciona um funcionário
    employeeItems.forEach(item => {
        item.addEventListener('click', function() {
            employeeItems.forEach(el => el.classList.remove('active'));
            this.classList.add('active');

            // Obtém o ID do funcionário selecionado
            const employeeId = this.getAttribute('data-id');

            // Carrega os contadores de documentos para o funcionário selecionado
            loadDocumentCounts(employeeId);
        });
    });

    // Seleciona uma pasta
    folders.forEach(folder => {
        folder.addEventListener('click', function() {
            folders.forEach(f => f.classList.remove('selected'));
            this.classList.add('selected');
            selectedFolder = this.getAttribute('data-folder');
        });
    });

    // Abre a pasta selecionada
    openFolderBtn.addEventListener('click', function() {
        const selectedEmployee = document.querySelector('.employee-item.active');
        
        if (!selectedFolder || !selectedEmployee) {
            alert('Por favor, selecione uma pasta e um funcionário primeiro.');
            return;
        }

        const employeeId = selectedEmployee.getAttribute('data-id');
        const folderName = document.querySelector(`.folder[data-folder="${selectedFolder}"] h3`).textContent;

        // Atualiza o título da pasta
        folderTitle.textContent = folderName;

        // Limpa a tabela antes de carregar novos documentos
        documentsTableBody.innerHTML = '';

        // Carrega os documentos do banco de dados
        fetch(`get_documents.php?folder=${selectedFolder}&employeeId=${employeeId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(doc => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${doc.titulo}</td>
                        <td>${doc.tipo}</td>
                        <td>${doc.data}</td>
                        <td class="clickable">${doc.descricao}</td>
                        <td class="clickable">${doc.anexo}</td>
                        <td>${doc.num_funcionario}</td>
                    `;
                    documentsTableBody.appendChild(row);
                });
            })
            .catch(error => console.error('Erro ao carregar documentos:', error));

        // Exibe a visualização da pasta
        folderView.style.display = 'none';
        folderContentView.style.display = 'block';
        backButton.style.display = 'flex';
    });

    // Volta para a visualização das pastas
    backButton.addEventListener('click', function() {
        folderContentView.style.display = 'none';
        folderView.style.display = 'grid';
        backButton.style.display = 'none';
        folders.forEach(f => f.classList.remove('selected'));
        selectedFolder = null;
    });

    // Upload de arquivos
    uploadBtn.addEventListener('click', function() {
        const selectedFolder = document.querySelector('.folder.selected');
        const selectedEmployee = document.querySelector('.employee-item.active');
        
        if (!selectedFolder || !selectedEmployee) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                text: 'Por favor, selecione uma pasta e um funcionário primeiro.',
            });
            return;
        }

        // Define a pasta e o funcionário selecionados nos campos ocultos
        selectedFolderInput.value = selectedFolder.getAttribute('data-folder');
        selectedFuncionarioInput.value = selectedEmployee.getAttribute('data-id');

        // Simula o clique no input de arquivo
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        const selectedFolder = document.querySelector('.folder.selected');
        const selectedEmployee = document.querySelector('.employee-item.active');
        
        if (selectedFolder && selectedEmployee) {
            // Exibe o SweetAlert para confirmar o envio
            Swal.fire({
                title: 'Adicionar Documento',
                text: 'Você selecionou um arquivo. Clique no botão abaixo para enviar.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Enviar Documento',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    // Envia o formulário
                    document.getElementById('fileUploadForm').submit();
                }
            });
        }
    });

    // Interação com elementos clicáveis na tabela
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('clickable')) {
            if (e.target.textContent === 'Clicar para ler descrição') {
                alert('Descrição do documento: Este é um atestado médico para justificar ausência no trabalho.');
            } else if (e.target.textContent.includes('.docx')) {
                alert('Abrindo o arquivo: ' + e.target.textContent);
            }
        }
    });
});
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
    <script src="./js/theme.js"></script>
</body>
</html>