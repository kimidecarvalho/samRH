<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Privacidade - Dashboard RH</title>
    <link rel="stylesheet" href="../all.css/registro3.css">
    <link rel="stylesheet" href="../all.css/configuracoes.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #3EB489;
            --background-light: #f4f4f4;
            --text-color: #333;
            --white: #ffffff;
            --border-color: #e0e0e0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-light);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            display: flex;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
            background-color: var(--background-light);
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

        .privacy-section {
            background-color: var(--background-light);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .privacy-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .privacy-details {
            background-color: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 54px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        .toggle-switch input:checked + .slider {
            background-color: var(--primary-color);
        }

        .toggle-switch input:checked + .slider:before {
            transform: translateX(26px);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #32a177;
        }

        /* Dark Mode Styles */
        body.dark {
            background-color: #121212;
            color: #e0e0e0;
        }

        body.dark .dashboard-container {
            background-color: #1e1e1e;
        }

        body.dark .sidebar {
            background-color: #1a1a1a;
            box-shadow: 2px 0 5px rgba(0,0,0,0.3);
        }

        body.dark .sidebar .logo img {
            filter: brightness(0.8) contrast(1.2);
        }

        body.dark .sidebar .nav-select {
            background-color: #262626;
            color: #e0e0e0;
            border-color: #444;
        }

        body.dark .nav-menu li {
            color: #b0b0b0;
        }

        body.dark .nav-menu li:hover {
            background-color: rgba(62, 180, 137, 0.2);
            color: var(--primary-color);
        }

        body.dark .nav-menu li.active {
            background-color: rgba(62, 180, 137, 0.2);
            color: var(--primary-color);
        }

        body.dark .main-content {
            background-color: #2a2a2a;
        }

        body.dark .profile-card {
            background-color: #262626;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        body.dark .profile-card h1 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        body.dark .privacy-section {
            background-color: #1a1a1a;
        }

        body.dark .privacy-section h3 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        body.dark .privacy-details {
            background-color: #1f1f1f;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        body.dark .detail-item {
            border-bottom: 1px solid #333;
        }

        body.dark .toggle-switch .slider {
            background-color: #555;
        }

        body.dark .toggle-switch .slider:before {
            background-color: #aaa;
        }

        body.dark .btn-primary {
            background-color: var(--primary-color);
            color: #f4f4f4;
        }

        body.dark .btn-primary:hover {
            background-color: #3EB489;
        }

        /* Estilos para remover azul do Bootstrap e usar o verde primário */
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-input:focus {
            border-color: #71c7a7; /* Cor de foco um pouco mais clara */
            box-shadow: 0 0 0 0.25rem rgba(62, 180, 137, 0.25);
        }

        .form-control:focus {
            border-color: #71c7a7; /* Cor de foco um pouco mais clara */
            box-shadow: 0 0 0 0.25rem rgba(62, 180, 137, 0.25);
        }

        /* Remover outline e box-shadow azul dos botões em foco/ativo */
        .btn:focus,
        .btn:active {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Opcional: adicionar um box-shadow verde ao focar botões primários */
        .btn-primary:focus {
            box-shadow: 0 0 0 0.25rem rgba(62, 180, 137, 0.5) !important;
        }

        /* Regras mais específicas para remover o azul em diferentes estados */
        .btn-primary:focus,
        .btn-primary:active {
            outline: none !important;
            box-shadow: none !important;
            border-color: var(--primary-color) !important;
        }

        .btn-primary:active {
            background-color: #32a177 !important; /* Um tom um pouco mais escuro do verde ao clicar */
            border-color: #32a177 !important;
        }

        /* Manter o hover verde */
        .btn-primary:hover {
            background-color: #32a177 !important;
            border-color: #32a177 !important;
        }

        /* Ajuste específico para o estado 'active' para dar feedback visual */
        .btn-primary:active,
        .btn-primary.active {
             background-color: #32a177 !important; /* Tom um pouco mais escuro ao clicar */
             border-color: #32a177 !important; /* Tom um pouco mais escuro ao clicar */
        }

        /* Regras adicionais para forçar a cor primária em estados de foco e ativo */
        .btn-primary:focus:not(:active),
        .btn-primary.focus:not(:active) {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.25rem rgba(62, 180, 137, 0.5) !important;
        }

        /* Forçar cor de fundo e borda para primário em :active */
        .btn-primary:active,
        .btn-primary.active {
            background-color: #32a177 !important; 
            border-color: #32a177 !important;
        }

        /* Tentativa final: sobrescrever estilos azuis do Bootstrap com seletores mais específicos */
        /* Foco */
        body .btn-primary:focus,
        body .btn-primary.focus {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.25rem rgba(62, 180, 137, 0.5) !important;
            color: #fff !important; /* Garante texto branco */
        }

        /* Ativo/Clicado */
        body .btn-primary:active,
        body .btn-primary.active {
            background-color: #32a177 !important; /* Tom mais escuro ao clicar */
            border-color: #32a177 !important; /* Tom mais escuro ao clicar */
            box-shadow: none !important; /* Remove qualquer sombra azul */
            color: #fff !important; /* Garante texto branco */
        }

        /* Manter hover verde */
        body .btn-primary:hover {
            background-color: #32a177 !important;
            border-color: #32a177 !important;
            color: #fff !important;
        }

        /* Regra geral para remover outline azul em qualquer botão */
        body .btn:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Estilos para a tabela de funcionários */
        .table-responsive {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background-color: var(--white);
            margin-top: 15px;
            overflow: hidden; /* Garante que o conteúdo respeite o border-radius */
        }

        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-responsive .table thead th { /* Regra mais específica para centralizar */
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 15px;
            border: none;
            font-size: 0.95rem;
            text-align: center; /* Garantir centralização */
        }

        .table thead th:first-child {
            border-top-left-radius: 10px;
        }

        .table thead th:last-child {
            border-top-right-radius: 10px;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
            background-color: var(--white);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover td {
            background-color: rgba(62, 180, 137, 0.05);
        }

        .table .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .table .btn-sm:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Estilo para o campo de busca */
        #buscaFuncionario {
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        #buscaFuncionario:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(62, 180, 137, 0.1);
            outline: none;
        }

        /* Dark mode styles */
        body.dark .table-responsive {
            background-color: #262626;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        body.dark .table thead th {
            background-color: var(--primary-color);
        }

        body.dark .table tbody td {
            border-bottom: 1px solid #333;
            color: #e0e0e0;
            background-color: #262626;
        }

        body.dark .table tbody tr:hover td {
            background-color: rgba(62, 180, 137, 0.1);
        }

        body.dark #buscaFuncionario {
            background-color: #262626;
            border-color: #333;
            color: #e0e0e0;
        }

        body.dark #buscaFuncionario:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(62, 180, 137, 0.2);
        }

        /* Estilo para o label do campo de busca */
        .form-label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        body.dark .form-label {
            color: #e0e0e0;
        }

        /* Adicionar regra para esconder o modal de exportação por padrão */
        #modalExportacao {
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
            <a href="perfil_adm.php"><li>Perfil do Usuário</li></a>
            <a href="seguranca.php"><li>Segurança</li></a>
            <a href="privacidade.php"><li class="active">Privacidade</li></a>
            <a href="rh_config.php"><li>Configurações de RH</li></a>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="profile-card">
            <h1>Configurações de Privacidade</h1>

            <div class="privacy-section">
                <h3>Compartilhamento de Dados</h3>
                <div class="privacy-details">
                    <div class="detail-item">
                        <label>Compartilhar dados com terceiros</label>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="detail-item">
                        <label>Permitir análise de dados para melhorias do sistema</label>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="privacy-section">
                <h3>Preferências de Comunicação</h3>
                <div class="privacy-details">
                    <div class="detail-item">
                        <label>Receber comunicações por email</label>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="detail-item">
                        <label>Receber notificações push</label>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="detail-item">
                        <label>Receber atualizações de marketing</label>
                        <label class="toggle-switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="privacy-section">
                <h3>Consentimento de Dados</h3>
                <div class="privacy-details">
                    <div class="detail-item" style="border-bottom: none;">
                        <div>
                            <strong>Última atualização do consentimento</strong>
                            <p>02/06/2025</p>
                        </div>
                        <button class="btn-primary">Revisar Consentimento</button>
                    </div>
                </div>
            </div>

            <div class="privacy-section">
                <h3>Exportação de Dados Pessoais</h3>
                <div class="privacy-details">
                    <div class="detail-item" style="border-bottom: none;">
                        <div>
                            <strong>Exportar Dados Pessoais</strong>
                            <p>Você pode exportar todos os seus dados pessoais em um formato legível.</p>
                        </div>
                        <button class="btn-primary" onclick="abrirModalExportacao()">Exportar Dados</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Consentimento -->
<div id="consentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Consentimento de Dados Pessoais</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="consent-text">
                <p>Ao utilizar o Sistema de Administração de RH (SAM), você concorda com a coleta e processamento dos seguintes dados pessoais:</p>

                <h3>1. Dados Coletados:</h3>
                <ul>
                    <li>Nome completo</li>
                    <li>Data de nascimento</li>
                    <li>Fotografia</li>
                    <li>Departamento</li>
                    <li>Data de admissão</li>
                    <li>Informações de status profissional</li>
                </ul>

                <h3>2. Finalidade do Uso:</h3>
                <ul>
                    <li>Gerenciamento de recursos humanos</li>
                    <li>Controle de presença e ausência</li>
                    <li>Registro de aniversários</li>
                    <li>Acompanhamento de novos funcionários</li>
                    <li>Gestão de departamentos</li>
                </ul>

                <h3>3. Seus Direitos:</h3>
                <ul>
                    <li>Acesso aos seus dados pessoais</li>
                    <li>Correção de informações incorretas</li>
                    <li>Solicitação de exclusão de dados</li>
                    <li>Revogação do consentimento a qualquer momento</li>
                </ul>

                <div class="consent-checkbox">
                    <input type="checkbox" id="consentCheckbox">
                    <label for="consentCheckbox">Confirmo que li e compreendi todas as informações acima</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" id="declineConsent">Não Concordo</button>
            <button class="btn-primary" id="acceptConsent" disabled>Aceitar</button>
        </div>
    </div>
</div>

<!-- Modal de Exportação -->
<div class="modal fade" id="modalExportacao" tabindex="-1" aria-labelledby="modalExportacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExportacaoLabel">Exportar Dados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h6>Escolha o tipo de exportação:</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tipoExportacao" id="exportacaoGeral" value="geral" checked>
                        <label class="form-check-label" for="exportacaoGeral">
                            Dados Gerais da Empresa
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tipoExportacao" id="exportacaoFuncionario" value="funcionario">
                        <label class="form-check-label" for="exportacaoFuncionario">
                            Dados de Funcionário Específico
                        </label>
                    </div>
                </div>

                <div id="selecaoFuncionario" style="display: none;">
                    <div class="mb-3">
                        <label for="buscaFuncionario" class="form-label">Buscar Funcionário:</label>
                        <input type="text" class="form-control" id="buscaFuncionario" placeholder="Digite o nome do funcionário...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Matrícula</th>
                                    <th>Nome</th>
                                    <th>Cargo</th>
                                    <th>Departamento</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="listaFuncionarios">
                                <!-- Lista de funcionários será carregada aqui -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <!-- Container para os botões de exportação geral/visualização -->
                <div id="botoesGeral" class="d-inline-block">
                    <!-- Botão Visualizar Geral -->
                    <button type="button" class="btn btn-secondary me-2" id="btnVisualizarGeral" onclick="visualizarDadosGeral()">
                        Visualizar
                    </button>
                    <!-- Botão Exportar Geral -->
                <button type="button" class="btn btn-primary" id="btnExportarGeral" onclick="exportarDadosGeral()">
                    Exportar Dados Gerais
                </button>
                </div>
                <!-- Os botões de funcionário específico são gerados via JS na tabela -->
            </div>
        </div>
    </div>
</div>

<style>
    #consentModal.modal {
        display: none; /* O modal de consentimento é controlado por JS customizado */
        position: fixed;
        z-index: 1050; /* Z-index ligeiramente maior que o padrão do Bootstrap */
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: var(--white);
        margin: 5% auto;
        padding: 0;
        width: 70%;
        max-width: 800px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        max-height: 80vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
        position: relative;
    }

    .modal-header h2 {
        color: var(--primary-color);
        margin: 0;
    }

    .close {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .modal-body {
        padding: 20px;
    }

    .consent-text {
        line-height: 1.6;
    }

    .consent-text h3 {
        color: var(--primary-color);
        margin-top: 20px;
    }

    .consent-text ul {
        padding-left: 20px;
    }

    .consent-text li {
        margin-bottom: 8px;
    }

    .consent-checkbox {
        margin: 20px 0;
        padding: 15px;
        background-color: #f8f8f8;
        border-radius: 8px;
    }

    .modal-footer {
        padding: 20px;
        border-top: 1px solid var(--border-color);
        text-align: right;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        margin-right: 10px;
    }

    /* Dark Mode Styles */
    body.dark .modal-content {
        background-color: #262626;
    }

    body.dark .modal-header {
        border-bottom: 1px solid #333;
    }

    body.dark .consent-checkbox {
        background-color: #1a1a1a;
    }

    body.dark .modal-footer {
        border-top: 1px solid #333;
    }

    .export-options {
        display: flex;
        flex-direction: column;
        gap: 15px;
        padding: 20px 0;
    }

    .btn-option {
        display: flex;
        align-items: center;
        padding: 15px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--white);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-option:hover {
        border-color: var(--primary-color);
        background: #f8f8f8;
    }

    .btn-option i {
        font-size: 24px;
        color: var(--primary-color);
        margin-right: 15px;
    }

    .btn-option span {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .btn-option p {
        margin: 0;
        color: #666;
        font-size: 0.9em;
    }

    .search-box {
        margin: 15px 0;
    }

    .search-box input {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
    }

    .funcionarios-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .funcionario-item {
        padding: 10px;
        border: 1px solid var(--border-color);
        margin-bottom: 5px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .funcionario-item:hover {
        background: #f8f8f8;
        border-color: var(--primary-color);
    }

    /* Dark Mode Styles */
    body.dark .btn-option {
        background: #262626;
        border-color: #333;
    }

    body.dark .btn-option:hover {
        background: #2a2a2a;
        border-color: var(--primary-color);
    }

    body.dark .btn-option p {
        color: #aaa;
    }

    body.dark .search-box input {
        background: #262626;
        border-color: #333;
        color: #fff;
    }

    body.dark .funcionario-item {
        background: #262626;
        border-color: #333;
    }

    body.dark .funcionario-item:hover {
        background: #2a2a2a;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Elementos do modal
    const modal = document.getElementById('consentModal');
    const btn = document.querySelector('.btn-primary');
    const span = document.querySelector('.close');
    const checkbox = document.getElementById('consentCheckbox');
    const acceptBtn = document.getElementById('acceptConsent');
    const declineBtn = document.getElementById('declineConsent');

    // Abrir modal quando clicar no botão "Revisar Consentimento"
    btn.addEventListener('click', function() {
        modal.style.display = "block";
    });

    // Fechar modal quando clicar no X
    span.addEventListener('click', function() {
        modal.style.display = "none";
    });

    // Fechar modal quando clicar fora dele
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });

    // Habilitar/desabilitar botão de aceitar baseado no checkbox
    checkbox.addEventListener('change', function() {
        acceptBtn.disabled = !this.checked;
    });

    // Ações dos botões de aceitar/declinar
    acceptBtn.addEventListener('click', function() {
        // Aqui você pode adicionar a lógica para salvar o consentimento
        alert('Consentimento aceito com sucesso!');
        modal.style.display = "none";
    });

    declineBtn.addEventListener('click', function() {
        if(confirm('Tem certeza que deseja recusar o consentimento? Isso pode limitar seu acesso ao sistema.')) {
            // Aqui você pode adicionar a lógica para registrar a recusa
            alert('Consentimento recusado.');
            modal.style.display = "none";
        }
    });

    let funcionarios = [];

    function abrirModalExportacao() {
        const modalExportacao = new bootstrap.Modal(document.getElementById('modalExportacao'));
        modalExportacao.show();
    }

    document.querySelectorAll('input[name="tipoExportacao"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const selecaoFuncionario = document.getElementById('selecaoFuncionario');
            const botoesGeral = document.getElementById('botoesGeral');
            
            if (this.value === 'funcionario') {
                selecaoFuncionario.style.display = 'block';
                botoesGeral.style.display = 'none'; // Oculta botões gerais
                carregarFuncionarios();
            } else {
                selecaoFuncionario.style.display = 'none';
                botoesGeral.style.display = 'inline-block'; // Mostra botões gerais
            }
        });
    });

    function carregarFuncionarios() {
        fetch('get_funcionarios.php')
            .then(response => response.json())
            .then(data => {
                funcionarios = data;
                atualizarListaFuncionarios();
            })
            .catch(error => {
                console.error('Erro ao carregar funcionários:', error);
                alert('Erro ao carregar lista de funcionários');
            });
    }

    function atualizarListaFuncionarios(filtro = '') {
        const tbody = document.getElementById('listaFuncionarios');
        tbody.innerHTML = '';

        const funcionariosFiltrados = funcionarios.filter(f => 
            f.nome.toLowerCase().includes(filtro.toLowerCase()) ||
            f.matricula.toLowerCase().includes(filtro.toLowerCase())
        );

        // Ordenar funcionários por número mecanográfico
        funcionariosFiltrados.sort((a, b) => {
            return a.matricula.localeCompare(b.matricula, undefined, {numeric: true, sensitivity: 'base'});
        });

        funcionariosFiltrados.forEach(funcionario => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${funcionario.matricula}</td>
                <td>${funcionario.nome}</td>
                <td>${funcionario.cargo}</td>
                <td>${funcionario.departamento}</td>
                <td>
                    <button class="btn btn-sm btn-secondary me-2" onclick="visualizarDadosFuncionario(${funcionario.id})" title="Visualizar">
                        <i class="far fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="exportarDadosFuncionario(${funcionario.id})">
                        Exportar
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    document.getElementById('buscaFuncionario').addEventListener('input', function(e) {
        atualizarListaFuncionarios(e.target.value);
    });

    function exportarDadosGeral() {
        // Redireciona para download do relatório geral
        window.location.href = 'generate_pdf.php?report=general';
    }

    function visualizarDadosFuncionario(id) {
        window.open(`generate_pdf.php?funcionario_id=${id}&action=view`, '_blank');
    }

    function exportarDadosFuncionario(id) {
        window.location.href = `generate_pdf.php?funcionario_id=${id}`;
    }

    // Nova função para visualizar o relatório geral
    function visualizarDadosGeral() {
        // Abre o relatório geral em nova aba com action=view e report=general
        window.open('generate_pdf.php?action=view&report=general', '_blank');
    }

    // Inicializar a visibilidade dos botões ao carregar o modal pela primeira vez
    const modalExportacaoElement = document.getElementById('modalExportacao');
    modalExportacaoElement.addEventListener('shown.bs.modal', function () {
        const tipoExportacaoRadios = document.querySelectorAll('input[name="tipoExportacao"]');
        tipoExportacaoRadios.forEach(radio => {
            if (radio.checked) {
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
</script>

<script src="../js/theme.js"></script>
</body>
</html>