<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['id_empresa']) && !isset($_SESSION['empresa_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

// Buscar informações da empresa usando o ID da sessão
try {
    $empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : $_SESSION['id_empresa'];
    $stmt = $pdo->prepare("SELECT nome FROM empresas_recrutamento WHERE id = ?");
    $stmt->execute([$empresa_id]);
    $empresa = $stmt->fetch();
    
    if (!$empresa) {
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    $erro = "Erro ao carregar dados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SAM Emprego - Cadastro de Vagas</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    
    :root {
      --primary: #3EB489;
      --primary-dark: #32936F;
      --primary-light: #A5E1CD;
      --primary-ultra-light: #EBF8F4;
      --gray-100: #f5f5f5;
      --gray-200: #e0e0e0;
      --gray-300: #d4d4d4;
      --gray-400: #a3a3a3;
      --gray-500: #737373;
      --gray-600: #525252;
      --gray-700: #404040;
      --gray-800: #262626;
      --gray-900: #171717;
      --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
      --shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
      --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
      --radius-sm: 4px;
      --radius: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --radius-full: 9999px;
    }
    
    body {
      background-color: #f8fafc;
      color: var(--gray-800);
      line-height: 1.5;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      margin-bottom: 30px;
    }
    
    .logo {
      display: flex;
      align-items: center;
    }
    
    .logo img {
      height: 80px;
      margin-right: 10px;
      transition: transform 0.2s ease;
    }
    
    .logo img:hover {
      transform: scale(1.05);
    }

    .user-section {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .user-dropdown {
      display: flex;
      width: 220px;
      height: 40px;
      align-items: center;
      background-color: var(--primary);
      color: white;
      padding: 8px 15px;
      border-radius: var(--radius-full);
      cursor: pointer;
      box-shadow: var(--shadow);
      transition: all 0.2s ease;
    }
    
    .user-dropdown:hover {
      background-color: var(--primary-dark);
      box-shadow: var(--shadow-md);
    }
    
    .user-avatar {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background-color: white;
      margin-right: 10px;
      color: var(--gray-800);
      border: 2px solid white;
      overflow: hidden;
    }
    
    .user-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .settings-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: white;
      border: 2px solid var(--primary);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 18px;
      box-shadow: var(--shadow);
      transition: all 0.2s ease;
    }
    
    .settings-icon:hover {
      background-color: var(--primary-ultra-light);
      transform: rotate(15deg);
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      color: var(--primary);
      text-decoration: none;
      margin: 1rem 0 1.5rem;
      font-weight: 500;
      transition: all 0.2s ease;
      padding: 8px 16px;
      border-radius: var(--radius-full);
    }
    
    .back-link:hover {
      background-color: var(--primary-ultra-light);
      color: var(--primary-dark);
    }
    
    .back-link svg {
      margin-right: 6px;
    }

    .form-container {
      background-color: white;
      border-radius: var(--radius-lg);
      padding: 2.5rem;
      margin-bottom: 2rem;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--gray-200);
    }

    .form-title {
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 2rem;
      color: var(--gray-800);
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--gray-200);
      position: relative;
    }
    
    .form-title::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 80px;
      height: 3px;
      background-color: var(--primary);
      border-radius: var(--radius-full);
    }

    .form-section {
      margin-bottom: 2.5rem;
      padding: 1.5rem;
      background-color: #fafafa;
      border-radius: var(--radius);
      border: 1px solid var(--gray-200);
      transition: box-shadow 0.3s ease;
    }
    
    .form-section:hover {
      box-shadow: var(--shadow);
    }

    .section-title {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      color: var(--gray-700);
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--gray-300);
      display: flex;
      align-items: center;
    }
    
    .section-title::before {
      content: '';
      display: inline-block;
      width: 12px;
      height: 12px;
      background-color: var(--primary);
      margin-right: 8px;
      border-radius: 50%;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-row {
      display: flex;
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }
    
    @media (max-width: 768px) {
      .form-row {
        flex-direction: column;
        gap: 1rem;
      }
    }

    .form-column {
      flex: 1;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--gray-700);
      font-size: 0.95rem;
    }

    input, select, textarea {
      width: 100%;
      padding: 0.875rem 1rem;
      border: 1px solid var(--gray-300);
      border-radius: var(--radius);
      font-size: 0.95rem;
      color: var(--gray-800);
      transition: all 0.2s ease;
      background-color: white;
    }
    
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px var(--primary-light);
    }
    
    input:hover, select:hover, textarea:hover {
      border-color: var(--gray-400);
    }
    
    input::placeholder, textarea::placeholder {
      color: var(--gray-400);
    }

    .input-group {
      display: flex;
      gap: 1rem;
    }

    .input-group input,
    .input-group select {
      flex: 1;
    }

    textarea {
      min-height: 120px;
      resize: vertical;
      line-height: 1.6;
    }

    .checkbox-group {
      margin-top: 0.75rem;
    }

    .checkbox-label {
      display: flex;
      align-items: center;
      margin-bottom: 0.75rem;
      cursor: pointer;
      padding: 6px 10px;
      border-radius: var(--radius);
      transition: background-color 0.2s ease;
    }
    
    .checkbox-label:hover {
      background-color: var(--primary-ultra-light);
    }

    .checkbox-label input {
      width: 18px;
      height: 18px;
      margin-right: 10px;
      accent-color: var(--primary);
    }

    .button-group {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      margin-top: 2.5rem;
    }

    .btn {
      padding: 0.875rem 1.75rem;
      border: none;
      border-radius: var(--radius-full);
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: var(--shadow);
    }

    .btn-primary {
      background-color: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      background-color: var(--primary-dark);
      box-shadow: var(--shadow-md);
      transform: translateY(-1px);
    }
    
    .btn-primary:active {
      transform: translateY(0);
    }

    .btn-secondary {
      background-color: white;
      color: var(--gray-700);
      border: 1px solid var(--gray-300);
    }

    .btn-secondary:hover {
      background-color: var(--gray-100);
      color: var(--gray-800);
    }

    .status-options {
      display: flex;
      gap: 1.5rem;
      margin-top: 0.5rem;
    }

    .status-option {
      display: flex;
      align-items: center;
      padding: 10px 16px;
      background-color: white;
      border: 1px solid var(--gray-300);
      border-radius: var(--radius);
      transition: all 0.2s ease;
      cursor: pointer;
    }
    
    .status-option:hover {
      border-color: var(--primary);
      background-color: var(--primary-ultra-light);
    }
    
    .status-option input {
      width: 18px;
      height: 18px;
      margin-right: 10px;
      accent-color: var(--primary);
    }
    
    .status-option input:checked + label {
      color: var(--primary);
      font-weight: 500;
    }
    
    .status-option:has(input:checked) {
      border-color: var(--primary);
      background-color: var(--primary-ultra-light);
      box-shadow: 0 0 0 1px var(--primary);
    }

    .required-field::after {
      content: "*";
      color: #e74c3c;
      margin-left: 4px;
    }

    .small-text {
      font-size: 0.85rem;
      color: var(--gray-500);
      margin-top: 0.5rem;
      line-height: 1.4;
    }
    
    /* Form validation styling */
    input:invalid, select:invalid, textarea:invalid {
      border-color: #e74c3c;
    }
    
    input:invalid:focus, select:invalid:focus, textarea:invalid:focus {
      box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
    }
    
    /* Toast notification for form submission */
    .toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background-color: var(--primary);
      color: white;
      padding: 16px 24px;
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      display: flex;
      align-items: center;
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.3s ease;
      z-index: 1000;
    }
    
    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }
    
    .toast-icon {
      margin-right: 12px;
    }
    
    /* Progress indicator for form sections */
    .progress-indicator {
      display: flex;
      justify-content: space-between;
      margin-bottom: 2rem;
      position: relative;
      padding: 0 10px;
    }
    
    .progress-indicator::before {
      content: '';
      position: absolute;
      top: 15px;
      left: 0;
      width: 100%;
      height: 2px;
      background-color: var(--gray-200);
      z-index: 1;
    }
    
    .progress-indicator::after {
      content: '';
      position: absolute;
      top: 15px;
      left: 0;
      width: var(--progress-width, 0%);
      height: 2px;
      background-color: var(--primary);
      z-index: 1;
      transition: width 0.3s ease;
    }
    
    .progress-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      z-index: 2;
    }
    
    .step-circle {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background-color: white;
      border: 2px solid var(--gray-300);
      color: var(--gray-500);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 500;
      margin-bottom: 8px;
      transition: all 0.3s ease;
    }
    
    .step-label {
      font-size: 0.85rem;
      color: var(--gray-500);
      text-align: center;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .progress-step.active .step-circle {
      background-color: var(--primary);
      border-color: var(--primary);
      color: white;
    }
    
    .progress-step.active .step-label {
      color: var(--primary);
    }
    
    .progress-step.completed .step-circle {
      background-color: var(--primary);
      border-color: var(--primary);
      color: white;
    }
    
    .progress-step.completed .step-label {
      color: var(--primary);
    }
    
    /* Field tooltip */
    .tooltip-container {
      position: relative;
      display: inline-block;
      margin-left: 6px;
    }
    
    .tooltip-icon {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      background-color: var(--gray-400);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      cursor: help;
    }
    
    .tooltip-text {
      position: absolute;
      bottom: 100%;
      left: 50%;
      transform: translateX(-50%);
      background-color: var(--gray-800);
      color: white;
      padding: 8px 12px;
      border-radius: var(--radius);
      font-size: 0.75rem;
      width: 200px;
      visibility: hidden;
      opacity: 0;
      transition: all 0.2s ease;
      pointer-events: none;
      z-index: 10;
    }
    
    .tooltip-container:hover .tooltip-text {
      visibility: visible;
      opacity: 1;
      bottom: calc(100% + 5px);
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="logo">
        <img src="../fotos/sam30-13.png" alt="SAM Emprego Logo">
      </div>
      <div class="user-section">
        <div class="user-dropdown">
          <div class="user-avatar">
            <img src="../icones/icons-sam-19.svg" alt="User Avatar" width="32">
          </div>
          <span>Josilde da Co...</span>
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: auto;">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
        <div class="settings-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3EB489" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
          </svg>
        </div>
      </div>
    </div>

    <a href="painel_empresa.php" class="back-link">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
      Voltar à gestão de vagas
    </a>
    
    <form action="save_job.php" method="post" class="form-container" id="job-form" onsubmit="return false;">
      <h1 class="form-title">Cadastrar Nova Vaga</h1>
      
      <div class="progress-indicator">
        <div class="progress-step active">
          <div class="step-circle">1</div>
          <div class="step-label">Informações<br>Básicas</div>
        </div>
        <div class="progress-step">
          <div class="step-circle">2</div>
          <div class="step-label">Localização<br>e Salário</div>
        </div>
        <div class="progress-step">
          <div class="step-circle">3</div>
          <div class="step-label">Carga<br>Horária</div>
        </div>
        <div class="progress-step">
          <div class="step-circle">4</div>
          <div class="step-label">Descrição<br>do Trabalho</div>
        </div>
      </div>
      
      <div class="form-section">
        <h2 class="section-title">Informações Básicas</h2>
        
        <div class="form-group">
          <label for="job_title" class="required-field">Título da Vaga</label>
          <input type="text" id="job_title" name="job_title" placeholder="Ex: Assistente de Logística" required>
        </div>
        
        <div class="form-row">
          <div class="form-column">
            <label for="company_name" class="required-field">Nome da Empresa</label>
            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($empresa['nome']); ?>" readonly>
          </div>
          <div class="form-column">
            <label for="job_category" class="required-field">Categoria</label>
            <select id="job_category" name="job_category" required>
              <option value="">Selecione uma categoria</option>
              <option value="Logística e Distribuição">Logística e Distribuição</option>
              <option value="Tecnologia da Informação">Tecnologia da Informação</option>
              <option value="Vendas e Marketing">Vendas e Marketing</option>
              <option value="Administrativo">Administrativo</option>
              <option value="Recursos Humanos">Recursos Humanos</option>
              <option value="Financeiro">Financeiro</option>
              <option value="Outro">Outro</option>
            </select>
          </div>
        </div>
        
        <div class="form-group">
          <label for="job_status" class="required-field">Status da Vaga</label>
          <div class="status-options">
            <div class="status-option">
              <input type="radio" id="status_open" name="job_status" value="Aberta" checked>
              <label for="status_open">Vaga Aberta</label>
            </div>
            <div class="status-option">
              <input type="radio" id="status_closed" name="job_status" value="Fechada">
              <label for="status_closed">Vaga Fechada</label>
            </div>
            <div class="status-option">
              <input type="radio" id="status_paused" name="job_status" value="Pausada">
              <label for="status_paused">Vaga Pausada</label>
            </div>
            <div class="status-option">
              <input type="radio" id="status_draft" name="job_status" value="Rascunho">
              <label for="status_draft">Rascunho</label>
            </div>
            <div class="status-option">
              <input type="radio" id="status_analysis" name="job_status" value="Em análise">
              <label for="status_analysis">Em Análise</label>
            </div>
            <div class="status-option">
              <input type="radio" id="status_finished" name="job_status" value="Finalizada">
              <label for="status_finished">Finalizada</label>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="expiration_date">Data de Expiração</label>
          <input type="date" id="expiration_date" name="data_expiracao" min="<?php echo date('Y-m-d'); ?>">
          <p class="small-text">Data limite para candidaturas. Se não definida, a vaga ficará aberta indefinidamente.</p>
        </div>

        <div class="form-row">
          <div class="form-column">
            <label for="location_type" class="required-field">Tipo de Localização</label>
            <select id="location_type" name="localizacao_tipo" required>
              <option value="">Selecione uma opção</option>
              <option value="nacional">Nacional</option>
              <option value="internacional">Internacional</option>
              <option value="regional">Regional</option>
              <option value="local">Local</option>
            </select>
          </div>
        </div>
      </div>
      
      <div class="form-section">
        <h2 class="section-title">Localização e Salário</h2>
        
        <div class="form-row">
          <div class="form-column">
            <label for="job_location" class="required-field">Localização do Trabalho</label>
            <select id="job_location" name="job_location" required>
              <option value="">Selecione uma opção</option>
              <option value="remoto">Remoto (Online)</option>
              <option value="hibrido">Híbrido</option>
              <option value="presencial">Presencial</option>
            </select>
          </div>
          <div class="form-column">
            <label for="job_type" class="required-field">Tipo de Contrato</label>
            <select id="job_type" name="job_type" required>
              <option value="">Selecione uma opção</option>
              <option value="efetivo">Efetivo</option>
              <option value="meio_periodo">Meio Período</option>
              <option value="temporario">Temporário</option>
              <option value="freelancer">Freelancer</option>
              <option value="estagio">Estágio</option>
            </select>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-column">
            <label for="salary_min" class="required-field">
              Salário Mínimo (AOA)
              <div class="tooltip-container">
                <div class="tooltip-icon">?</div>
                <span class="tooltip-text">Informe o valor mínimo do salário oferecido para esta vaga.</span>
              </div>
            </label>
            <input type="number" id="salary_min" name="salary_min" placeholder="Ex: 115000" required>
          </div>
          <div class="form-column">
            <label for="salary_max" class="required-field">Salário Máximo (AOA)</label>
            <input type="number" id="salary_max" name="salary_max" placeholder="Ex: 180000" required>
          </div>
          <div class="form-column">
            <label for="salary_period" class="required-field">Período</label>
            <select id="salary_period" name="salary_period" required>
              <option value="mensal">Mensal</option>
              <option value="semanal">Semanal</option>
              <option value="hora">Por Hora</option>
            </select>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-column">
            <label for="payment_method" class="required-field">Método de Pagamento</label>
            <select id="payment_method" name="payment_method" required>
              <option value="">Selecione uma opção</option>
              <option value="transferencia">Transferência Bancária</option>
              <option value="cheque">Cheque</option>
              <option value="dinheiro">Dinheiro</option>
              <option value="outro">Outro</option>
            </select>
          </div>
          <div class="form-column">
            <label for="language" class="required-field">Idioma Requerido</label>
            <select id="language" name="language" required>
              <option value="">Selecione uma opção</option>
              <option value="portugues">Português</option>
              <option value="portugues_ingles">Português e Inglês</option>
              <option value="portugues_frances">Português e Francês</option>
              <option value="portugues_espanhol">Português e Espanhol</option>
              <option value="nao_especificado">Não Especificado</option>
            </select>
            <p class="small-text">Idioma(s) necessário(s) para exercer a função</p>
          </div>
        </div>
      </div>
      
      <div class="form-section">
        <h2 class="section-title">Carga Horária</h2>
        
        <div class="form-row">
          <div class="form-column">
            <label for="timezone" class="required-field">Fuso Horário</label>
            <select id="timezone" name="timezone" required>
              <option value="">Selecione uma opção</option>
              <option value="GMT+1">África Ocidental (GMT +1)</option>
              <option value="GMT+0">GMT</option>
              <option value="GMT-3">Brasil (GMT -3)</option>
              <option value="GMT+2">África Central (GMT +2)</option>
              <option value="GMT+3">África Oriental (GMT +3)</option>
            </select>
          </div>
          <div class="form-column">
            <label for="workdays" class="required-field">Dias Úteis Semanais</label>
            <select id="workdays" name="workdays" required>
              <option value="">Selecione uma opção</option>
              <option value="Segunda à Sexta">Segunda à Sexta</option>
              <option value="Segunda à Sábado">Segunda à Sábado</option>
              <option value="Todos os Dias">Todos os Dias</option>
              <option value="Fins de Semana">Fins de Semana</option>
              <option value="Flexível">Flexível</option>
            </select>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-column">
            <label for="weekly_hours_min" class="required-field">Horas Úteis Semanais (Mínimo)</label>
            <input type="number" id="weekly_hours_min" name="weekly_hours_min" placeholder="Ex: 15" required>
          </div>
          <div class="form-column">
            <label for="weekly_hours_max" class="required-field">Horas Úteis Semanais (Máximo)</label>
            <input type="number" id="weekly_hours_max" name="weekly_hours_max" placeholder="Ex: 20" required>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-column">
            <label for="daily_hours_min" class="required-field">Horas Úteis Diárias (Mínimo)</label>
            <input type="number" id="daily_hours_min" name="daily_hours_min" placeholder="Ex: 3" required>
          </div>
          <div class="form-column">
            <label for="daily_hours_max" class="required-field">Horas Úteis Diárias (Máximo)</label>
            <input type="number" id="daily_hours_max" name="daily_hours_max" placeholder="Ex: 4" required>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-column">
            <label for="work_start_time" class="required-field">Horário de Início</label>
            <input type="time" id="work_start_time" name="work_start_time" required>
          </div>
          <div class="form-column">
            <label for="work_end_time" class="required-field">Horário de Término</label>
            <input type="time" id="work_end_time" name="work_end_time" required>
          </div>
        </div>
      </div>
      
      <div class="form-section">
        <h2 class="section-title">Descrição do Trabalho</h2>
        
        <div class="form-group">
          <label for="job_description" class="required-field">Descrição Detalhada</label>
          <textarea id="job_description" name="job_description" placeholder="Descreva as responsabilidades, atividades e objetivos da vaga..." required></textarea>
          <p class="small-text">Seja específico sobre as principais responsabilidades, requisitos e o que faz essa vaga única.</p>
        </div>

        <div class="form-group">
          <label for="job_requirements">Requisitos</label>
          <textarea id="job_requirements" name="requisitos" placeholder="Liste os requisitos necessários para a vaga..."></textarea>
          <p class="small-text">Inclua habilidades técnicas, experiência necessária, certificações, etc.</p>
        </div>
      </div>
      
      <div class="button-group">
        <button type="button" class="btn btn-secondary" id="cancel-button">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="submit-button">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
          Publicar Vaga
        </button>
      </div>
    </form>
    
    <div id="toast" class="toast">
      <div class="toast-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
      </div>
      <span id="toast-message">Vaga publicada com sucesso!</span>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('job-form');
        const progressSteps = document.querySelectorAll('.progress-step');
        const progressBar = document.querySelector('.progress-indicator');
        
        // Function to check if a section is complete
        function isSectionComplete(section) {
            const requiredInputs = section.querySelectorAll('input[required], select[required], textarea[required]');
            let isComplete = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isComplete = false;
                }
            });
            
            return isComplete;
        }
        
        // Function to update progress
        function updateProgress() {
            const sections = document.querySelectorAll('.form-section');
            let completedSections = 0;
            
            sections.forEach((section, index) => {
                if (isSectionComplete(section)) {
                    completedSections++;
                    progressSteps[index].classList.add('completed');
                    if (index < sections.length - 1) {
                        progressSteps[index + 1].classList.add('active');
                    }
                } else {
                    progressSteps[index].classList.remove('completed');
                    if (index < sections.length - 1) {
                        progressSteps[index + 1].classList.remove('active');
                    }
                }
            });
            
            // Update progress bar width
            const progressPercentage = (completedSections / sections.length) * 100;
            progressBar.style.setProperty('--progress-width', `${progressPercentage}%`);
        }
        
        // Add input event listeners to all form inputs
        const formInputs = form.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('input', updateProgress);
            input.addEventListener('change', updateProgress);
        });
        
        // Initial progress check
        updateProgress();
        
        // Form submission code
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = document.getElementById('submit-button');
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;" class="spinner">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v6l4 2"></path>
                </svg>
                Publicando...
            `;

            const formData = new FormData(form);

            fetch('save_job.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const toast = document.getElementById('toast');
                const toastMessage = document.getElementById('toast-message');
                
                if (data.success) {
                    toast.classList.add('show');
                    toastMessage.textContent = data.message;
                    
                    setTimeout(() => {
                        window.location.href = 'painel_empresa.php';
                    }, 2000);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                const toast = document.getElementById('toast');
                const toastMessage = document.getElementById('toast-message');
                toast.style.backgroundColor = '#e74c3c';
                toastMessage.textContent = 'Erro ao cadastrar vaga: ' + error.message;
                toast.classList.add('show');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Publicar Vaga
                `;
                
                setTimeout(() => {
                    const toast = document.getElementById('toast');
                    toast.classList.remove('show');
                    toast.style.backgroundColor = 'var(--primary)';
                }, 3000);
            });
        });
    });
  </script>
</body>
</html>