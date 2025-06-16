<?php
require_once('../vendor/autoload.php');
require_once('../config.php');

class PersonalDataPDF extends TCPDF {
    private $showTitle = true;
    protected $tipoRelatorio = '';

    public function setTipoRelatorio($tipo) {
        $this->tipoRelatorio = $tipo;
    }

    public function Header() {
        // Adicionar imagem do logo
        $this->Image('../sam-cfundo.jpg', 10, 10, 30, '', 'JPEG');
        
        // Data de geração (topo direito)
        $this->SetFont('helvetica', 'I', 8);
        $this->SetY(10); // Posição Y no topo, alinhada com o topo do logo
        $this->Cell(0, 10, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');

        // Definir a posição vertical para o título (abaixo do logo)
        if ($this->showTitle) {
            $this->SetFont('helvetica', 'B', 15);
            $this->SetY(40); // Posição Y abaixo do logo (ajuste conforme necessário)
            $this->Cell(0, 15, $this->tipoRelatorio . ' - ' . $GLOBALS['empresa_nome'], 0, false, 'C', 0, '', 0, false, 'M', 'M');
            $this->showTitle = false; // Desativa o título para as próximas páginas
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Função para tratar dados vazios
function formatarDado($dado) {
    return empty($dado) ? 'N/D' : $dado;
}

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['id_adm'])) {
    die('Acesso não autorizado');
}

// Verificar se é uma exportação de funcionário específico
$funcionario_id = isset($_GET['funcionario_id']) ? (int)$_GET['funcionario_id'] : null;

// Verificar a ação desejada (visualizar ou baixar)
$action = isset($_GET['action']) ? $_GET['action'] : 'download'; // 'download' é o padrão

if ($funcionario_id) {
    // Exportação de funcionário específico
    $sql = "SELECT f.*, c.nome as cargo_nome, d.nome as departamento_nome 
            FROM funcionario f 
            LEFT JOIN cargos c ON f.cargo = c.id 
            LEFT JOIN departamentos d ON f.departamento = d.id 
            WHERE f.id_fun = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $funcionario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $funcionario = $result->fetch_assoc();

    if (!$funcionario) {
        die('Funcionário não encontrado');
    }

    // Buscar dados da empresa para o funcionário
    $sql_empresa = "SELECT e.* FROM empresa e 
                    INNER JOIN funcionario f ON f.empresa_id = e.id_empresa 
                    WHERE f.id_fun = ?";
    $stmt_empresa = $conn->prepare($sql_empresa);
    $stmt_empresa->bind_param("i", $funcionario_id);
    $stmt_empresa->execute();
    $result_empresa = $stmt_empresa->get_result();
    $empresaData = $result_empresa->fetch_assoc();

    // Definir o nome da empresa globalmente
    $GLOBALS['empresa_nome'] = formatarDado($empresaData['nome']);

    // Criar novo documento PDF
    $pdf = new PersonalDataPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setTipoRelatorio('Relatório de Funcionário');

    // Configurações do documento
    $pdf->SetCreator('SAM - Sistema de Administração de RH');
    $pdf->SetAuthor('SAM');
    $pdf->SetTitle('Relatório de Funcionário - ' . $funcionario['nome']);

    // Configurações de margem
    $pdf->SetMargins(15, 40, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Adicionar página
    $pdf->AddPage();

    // Dados da Empresa
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(62, 180, 137);
    $pdf->Cell(0, 10, 'Dados da Empresa', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Tabela de dados da empresa
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(60, 7, 'Nome da Empresa:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['nome']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'NIPC:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['nipc']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Endereço:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['endereco']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Email Corporativo:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['email_corp']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Telefone:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['telefone']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Setor de Atuação:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['setor_atuacao']), 0, 1, 'L');

    // Dados Pessoais
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(62, 180, 137);
    $pdf->Cell(0, 10, 'Dados Pessoais', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Tabela de dados pessoais
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(60, 7, 'Nome Completo:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['nome']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'BI:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['bi']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Data de Nascimento:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['data_nascimento']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Gênero:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['genero']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Endereço:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['morada']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Telefone:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['telemovel']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Email:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['email']), 0, 1, 'L');

    // Dados Profissionais
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(62, 180, 137);
    $pdf->Cell(0, 10, 'Dados Profissionais', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Tabela de dados profissionais
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(60, 7, 'Matrícula:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['num_mecanografico']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Cargo:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['cargo_nome']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Departamento:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['departamento_nome']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Tipo de Trabalhador:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['tipo_trabalhador']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Data de Admissão:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['data_admissao']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Estado:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['estado']), 0, 1, 'L');

    // Dados Bancários
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(62, 180, 137);
    $pdf->Cell(0, 10, 'Dados Bancários', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Tabela de dados bancários
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(60, 7, 'Banco:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['banco']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Número da Conta:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['num_conta_bancaria']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'IBAN:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['iban']), 0, 1, 'L');

    // Definir o modo de saída do PDF com base na ação
    $output_mode = ($action === 'view') ? 'I' : 'D';

    // Gerar o PDF
    $pdf->Output('RF' . $funcionario['num_mecanografico'] . '-' . $empresaData['nome'] . '-' . date('Y-m-d') . '.pdf', $output_mode);
} else {
    // Buscar dados do administrador
    $id_adm = $_SESSION['id_adm'];
    $sql = "SELECT a.*, d.nome as departamento_nome 
            FROM adm a 
            LEFT JOIN departamentos d ON a.departamento = d.id 
            WHERE a.id_adm = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_adm);
    $stmt->execute();
    $result = $stmt->get_result();
    $adminData = $result->fetch_assoc();

    // Buscar dados da empresa
    $sql_empresa = "SELECT * FROM empresa WHERE adm_id = ?";
    $stmt_empresa = $conn->prepare($sql_empresa);
    $stmt_empresa->bind_param("i", $id_adm);
    $stmt_empresa->execute();
    $result_empresa = $stmt_empresa->get_result();
    $empresaData = $result_empresa->fetch_assoc();

    // Definir o nome da empresa globalmente
    $GLOBALS['empresa_nome'] = formatarDado($empresaData['nome']);

    // Buscar funcionários da empresa com joins para cargos e departamentos
    $sql_funcionarios = "SELECT f.*, c.nome as cargo_nome, d.nome as departamento_nome 
                        FROM funcionario f 
                        LEFT JOIN cargos c ON f.cargo = c.id 
                        LEFT JOIN departamentos d ON f.departamento = d.id 
                        WHERE f.empresa_id = ?";
    $stmt_funcionarios = $conn->prepare($sql_funcionarios);
    $stmt_funcionarios->bind_param("i", $empresaData['id_empresa']);
    $stmt_funcionarios->execute();
    $result_funcionarios = $stmt_funcionarios->get_result();

    $funcionarios = [];
    while ($row = $result_funcionarios->fetch_assoc()) {
        $funcionarios[] = $row;
    }

    // Buscar histórico de atividades
    $sql_historico = "SELECT data_hora as data, acao as tipo, acao as descricao 
                     FROM log_atividades 
                     WHERE adm_id = ? 
                     ORDER BY data_hora DESC 
                     LIMIT 10";
    $stmt_historico = $conn->prepare($sql_historico);
    $stmt_historico->bind_param("i", $id_adm);
    $stmt_historico->execute();
    $result_historico = $stmt_historico->get_result();

    $historico = [];
    while ($row = $result_historico->fetch_assoc()) {
        $historico[] = $row;
    }

    // Criar novo documento PDF
    $pdf = new PersonalDataPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setTipoRelatorio('Relatório Completo');

    // Configurações do documento
    $pdf->SetCreator('SAM - Sistema de Administração de RH');
    $pdf->SetAuthor('SAM');
    $pdf->SetTitle('Relatório Completo - ' . formatarDado($empresaData['nome']));

    // Configurações de margem
    $pdf->SetMargins(15, 40, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Adicionar página
    $pdf->AddPage();

    // Estilo para títulos de seção
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(62, 180, 137); // Cor verde do SAM

    // Dados da Empresa
    $pdf->Ln(15); // Adicionando espaço antes dos dados da empresa
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(62, 180, 137);
    $pdf->Cell(0, 10, 'Dados da Empresa', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Tabela de dados da empresa
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(60, 7, 'Nome da Empresa:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['nome']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'NIPC:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['nipc']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Endereço:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['endereco']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Email Corporativo:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['email_corp']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Telefone:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['telefone']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Setor de Atuação:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($empresaData['setor_atuacao']), 0, 1, 'L');

    // Dados do Administrador
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(62, 180, 137);
    $pdf->Cell(0, 10, 'Dados do Administrador', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Tabela de dados do administrador
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(60, 7, 'Nome Completo:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($adminData['nome']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Email:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($adminData['email']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Telefone:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($adminData['telefone']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Cargo:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($adminData['cargo']), 0, 1, 'L');

    $pdf->Cell(60, 7, 'Nível de Acesso:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($adminData['nivel_acesso']), 0, 1, 'L');

    // Lista de Funcionários
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(62, 180, 137);
    $pdf->Cell(0, 10, 'Funcionários', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Tabela de funcionários
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(30, 7, 'Matrícula', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Nome', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Cargo', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Departamento', 1, 0, 'C', true);
    $pdf->Cell(0, 7, 'Status', 1, 1, 'C', true);

    foreach ($funcionarios as $funcionario) {
        $pdf->Cell(30, 7, formatarDado($funcionario['num_mecanografico']), 1, 0, 'C');
        $pdf->Cell(60, 7, formatarDado($funcionario['nome']), 1, 0, 'C');
        $pdf->Cell(40, 7, formatarDado($funcionario['cargo_nome']), 1, 0, 'C');
        $pdf->Cell(40, 7, formatarDado($funcionario['departamento_nome']), 1, 0, 'C');
        $pdf->Cell(0, 7, formatarDado($funcionario['estado']), 1, 1, 'C');
    }

    // Histórico de Atividades
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(62, 180, 137);
    $pdf->Cell(0, 10, 'Histórico de Atividades', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Tabela de histórico
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(40, 7, 'Data', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Tipo', 1, 0, 'C', true);
    $pdf->Cell(0, 7, 'Descrição', 1, 1, 'C', true);

    if (empty($historico)) {
        $pdf->Cell(0, 7, 'Nenhuma atividade registrada', 1, 1, 'C');
    } else {
        foreach ($historico as $registro) {
            $pdf->Cell(40, 7, date('d/m/Y H:i', strtotime($registro['data'])), 1, 0, 'C');
            $pdf->Cell(60, 7, formatarDado($registro['tipo']), 1, 0, 'C');
            $pdf->Cell(0, 7, formatarDado($registro['descricao']), 1, 1, 'C');
        }
    }

    // Gerar o PDF
    $output_mode = ($action === 'view') ? 'I' : 'D';
    $pdf->Output('RC-' . $empresaData['nome'] . '-' . date('Y-m-d') . '.pdf', $output_mode);
} 