<?php
session_start();
include('../conexao.php');

// Verifica se o usuário está logado
if (!isset($_SESSION['id_adm'])) {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Configurações do Sistema - Dashboard RH</title>
    <link rel="stylesheet" href="../all.css/registro3.css">
    <link rel="stylesheet" href="../all.css/configuracoes.css">
    <link rel="stylesheet" href="../all.css/conf.sistema.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .profile-card h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .sistema-section {
            margin-bottom: 30px;
            background-color: var(--background-light);
            border-radius: 10px;
            padding: 20px;
        }

        .sistema-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .sistema-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-item {
            display: flex;
            align-items: center;
            width: 100%;
        }

        .detail-item > div:first-child {
            flex-grow: 1;
            margin-right: 20px;
        }

        .detail-item strong {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .detail-item p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.9em;
        }

        .select-input {
            width: 250px;
            padding: 10px;
            border: 1px solid var(--input-border);
            border-radius: 6px;
            background-color: var(--white);
            transition: border-color 0.3s ease;
        }

        .select-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(62, 180, 137, 0.2);
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

        /* Dark Mode Styles */
        body.dark {
            background-color: #121212;
            color: #e0e0e0;
        }

        body.dark .profile-card {
            background-color: #262626;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        body.dark .profile-card h1 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        body.dark .sistema-section {
            background-color: #1a1a1a;
        }

        body.dark .sistema-section h3 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        body.dark .detail-item strong {
            color: #c0c0c0;
        }

        body.dark .detail-item p {
            color: #a0a0a0;
        }

        body.dark .select-input {
            background-color: #333;
            border-color: #444;
            color: #e0e0e0;
        }

        body.dark .select-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(62, 180, 137, 0.3);
        }

        body.dark .btn-primary {
            background-color: var(--primary-color);
            color: #f4f4f4;
        }

        body.dark .btn-primary:hover {
            background-color: #3EB489;
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
                <a href="conf.sistema.php"><li class="active">Configurações do Sistema</li></a>
                <a href="perfil_adm.php"><li>Perfil do Usuário</li></a>
                <a href="seguranca.php"><li>Segurança</li></a>
                <a href="privacidade.php"><li>Privacidade</li></a>
                <a href="rh_config.php"><li>Configurações de RH</li></a>
            </ul>
        </div>

        <div class="main-content">
            <div class="profile-card">
                <h1>Configurações do Sistema</h1>

                <div class="sistema-section">
                    <h3>Tema</h3>
                    <div class="sistema-details">
                        <div class="detail-item">
                            <div>
                                <strong>Modo de Exibição</strong>
                                <p>Escolha entre claro, escuro ou modo do sistema</p>
                            </div>
                            <select class="select-input" id="theme-selector">
                                <option value="light">Claro</option>
                                <option value="dark">Escuro</option>
                                <option value="system">Sistema</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="sistema-section">
                    <h3>Idioma</h3>
                    <div class="sistema-details">
                        <div class="detail-item">
                            <div>
                                <strong>Selecionar Idioma</strong>
                                <p>Escolha o idioma de preferência</p>
                            </div>
                            <select class="select-input" id="idioma-selector">
                                <option value="pt_BR" selected>Português (Pt)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <button class="btn-primary" id="salvar-config">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/theme.js"></script>
</body>
</html>