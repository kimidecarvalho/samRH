<?php
// Prevenir qualquer saída antes do PDF
ob_start();

require_once('../vendor/autoload.php');
require_once('../config.php');

// Função para obter dados do funcionário
function obterDadosFuncionario($conn, $funcionario_id) {
    $sql_funcionario = "SELECT nome, num_mecanografico FROM funcionario WHERE id_fun = ?";
    $stmt_funcionario = $conn->prepare($sql_funcionario);
    $stmt_funcionario->bind_param("i", $funcionario_id);
    $stmt_funcionario->execute();
    $result_funcionario = $stmt_funcionario->get_result();
    return $result_funcionario->fetch_assoc();
}

// Função para calcular horas trabalhadas
function calcularHorasTrabalhadas($entrada, $saida) {
    if (empty($entrada) || empty($saida)) {
        return '-';
    }
    // Verificar o formato da data/hora
    if (strpos($entrada, ' ') !== false) {
        // Formato datetime completo (YYYY-MM-DD HH:MM:SS)
        $entrada_time = strtotime($entrada);
        $saida_time = strtotime($saida);
    } else {
        // Formato apenas hora (HH:MM:SS)
        $entrada_time = strtotime("1970-01-01 " . $entrada);
        $saida_time = strtotime("1970-01-01 " . $saida);
    }
    // Se a saída for menor que a entrada, provavelmente é do dia seguinte
    if ($saida_time < $entrada_time) {
        $saida_time += 86400; // Adiciona 24 horas
    }
    $diferenca = $saida_time - $entrada_time;
    $horas = floor($diferenca / 3600);
    $minutos = floor(($diferenca % 3600) / 60);
    return sprintf('%02d:%02d', $horas, $minutos);
}

// Função para tratar dados vazios
function formatarDado($dado) {
    return empty($dado) ? 'N/D' : $dado;
}

class PontoPDF extends TCPDF {
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
        $this->SetY(10);
        $this->Cell(0, 10, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');

        // Título
        if ($this->showTitle) {
            $this->SetFont('helvetica', 'B', 15);
            $this->SetY(40);
            if ($this->tipoRelatorio == 'Relatório de Ponto da Empresa') {
                $this->Cell(0, 15, $this->tipoRelatorio . ' - ' . ($GLOBALS['empresa_nome'] ?? ''), 0, false, 'C', 0, '', 0, false, 'M', 'M');
            } else {
                $this->Cell(0, 15, $this->tipoRelatorio . ' - ' . ($funcionario['nome'] ?? '') . ' (#' . ($funcionario['num_mecanografico'] ?? '') . ')', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            }
            $this->showTitle = false;
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['id_adm'])) {
    die('Acesso não autorizado');
}

$empresa_id = $_SESSION['id_empresa'];

// Obter parâmetros da URL
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mes';
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'empresa';
$funcionario_id = isset($_GET['funcionario_id']) ? (int)$_GET['funcionario_id'] : null;

// Definir o fuso horário correto
date_default_timezone_set('Europe/Lisbon');

// Definir o período do relatório
$periodo_relatorio = '';
if (isset($_GET['periodo'])) {
    $periodo_relatorio = $_GET['periodo'];
} elseif (isset($_POST['periodo'])) {
    $periodo_relatorio = $_POST['periodo'];
} else {
    $periodo_relatorio = 'semana'; // valor padrão
}

// Definir período
switch($periodo) {
    case 'semana':
        $data_inicio = date('Y-m-d', strtotime('-7 days'));
        $data_fim = date('Y-m-d');
        break;
    case 'trimestre':
        $data_inicio = date('Y-m-d', strtotime('-3 months'));
        $data_fim = date('Y-m-d');
        break;
    case 'mes':
    default:
        $data_inicio = date('Y-m-01');
        $data_fim = date('Y-m-t');
        break;
}

// Buscar dados da empresa
$sql_empresa = "SELECT * FROM empresa WHERE id_empresa = ?";
$stmt_empresa = $conn->prepare($sql_empresa);
$stmt_empresa->bind_param("i", $empresa_id);
$stmt_empresa->execute();
$empresa = $stmt_empresa->get_result()->fetch_assoc();

// Verificar se a empresa existe
if (!$empresa) {
    die("Empresa não encontrada");
}
$GLOBALS['empresa_nome'] = $empresa['nome'];

// Buscar dados do funcionário se for relatório específico
$funcionario = null;
if ($tipo === 'funcionario' && $funcionario_id) {
    $sql_funcionario = "SELECT f.*, d.nome as departamento_nome 
                       FROM funcionario f 
                       LEFT JOIN departamentos d ON f.departamento = d.id 
                       WHERE f.id_fun = ? AND f.empresa_id = ?";
    $stmt_funcionario = $conn->prepare($sql_funcionario);
    $stmt_funcionario->bind_param("ii", $funcionario_id, $empresa_id);
    $stmt_funcionario->execute();
    $funcionario = $stmt_funcionario->get_result()->fetch_assoc();
    
    if (!$funcionario) {
        die("Funcionário não encontrado");
    }
}

// Buscar registros de ponto do funcionário
$sql = "SELECT rp.*, f.nome, f.cargo, f.departamento, f.empresa_id, e.nome AS nome_empresa, fu.num_mecanografico 
        FROM registros_ponto rp 
        JOIN funcionario f ON rp.funcionario_id = f.id_fun 
        JOIN empresa e ON f.empresa_id = e.id_empresa 
        LEFT JOIN funcionario fu ON rp.funcionario_id = fu.id_fun
        WHERE rp.empresa_id = ? 
        AND rp.data BETWEEN ? AND ?";

if ($tipo === 'funcionario' && $funcionario_id) {
    $sql .= " AND rp.funcionario_id = ?";
}

$sql .= " ORDER BY rp.data ASC";

$stmt_registros = $conn->prepare($sql);
if (!$stmt_registros) {
    die("Erro na preparação da consulta: " . $conn->error);
}

if ($tipo === 'funcionario' && $funcionario_id) {
    $stmt_registros->bind_param("issi", $empresa_id, $data_inicio, $data_fim, $funcionario_id);
} else {
    $stmt_registros->bind_param("iss", $empresa_id, $data_inicio, $data_fim);
}

$stmt_registros->execute();
$result = $stmt_registros->get_result();

// Buscar a data do primeiro e último ponto para o período do relatório
$sql_periodo = "SELECT MIN(data) as primeira_data, MAX(data) as ultima_data 
                FROM registros_ponto 
                WHERE empresa_id = ? 
                AND data BETWEEN ? AND ?";
$stmt_periodo = $conn->prepare($sql_periodo);
$stmt_periodo->bind_param("iss", $empresa_id, $data_inicio, $data_fim);
$stmt_periodo->execute();
$periodo = $stmt_periodo->get_result()->fetch_assoc();

$periodo_relatorio = "Período do Relatório: " . date('d/m/Y', strtotime($data_inicio)) . " a " . date('d/m/Y', strtotime($data_fim));

// Criar PDF
$pdf = new PontoPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setTipoRelatorio($tipo === 'funcionario' ? 'Relatório de Ponto do Funcionário' : 'Relatório de Ponto da Empresa');

// Configurar documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($empresa['nome']);
$pdf->SetTitle('Relatório de Ponto - ' . ($tipo === 'funcionario' ? $funcionario['nome'] : $empresa['nome']));

// Configurar margens
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Configurar quebra de página automática
$pdf->SetAutoPageBreak(TRUE, 15);

// Adicionar página
$pdf->AddPage();
$pdf->Ln(50); // Espaço extra após o cabeçalho para evitar sobreposição

// Dados da Empresa (estilo tabela)
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(62, 180, 137);
$pdf->Cell(0, 10, 'Dados da Empresa', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(60, 7, 'Nome da Empresa:', 0, 0, 'L', true);
$pdf->Cell(0, 7, formatarDado($empresa['nome']), 0, 1, 'L');
$pdf->Cell(60, 7, 'NIPC:', 0, 0, 'L', true);
$pdf->Cell(0, 7, formatarDado($empresa['nipc']), 0, 1, 'L');
$pdf->Cell(60, 7, 'Endereço:', 0, 0, 'L', true);
$pdf->Cell(0, 7, formatarDado($empresa['endereco']), 0, 1, 'L');
$pdf->Cell(60, 7, 'Email Corporativo:', 0, 0, 'L', true);
$pdf->Cell(0, 7, formatarDado($empresa['email_corp']), 0, 1, 'L');
$pdf->Cell(60, 7, 'Telefone:', 0, 0, 'L', true);
$pdf->Cell(0, 7, formatarDado($empresa['telefone']), 0, 1, 'L');
$pdf->Cell(60, 7, 'Setor de Atuação:', 0, 0, 'L', true);
$pdf->Cell(0, 7, formatarDado($empresa['setor_atuacao']), 0, 1, 'L');
$pdf->Ln(5);

// Informações do funcionário se for relatório específico
if ($tipo === 'funcionario' && $funcionario) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(62, 180, 137);
    $pdf->Cell(0, 10, 'Dados do Funcionário', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(60, 7, 'Nome:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['nome']), 0, 1, 'L');
    $pdf->Cell(60, 7, 'Cargo:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['cargo']), 0, 1, 'L');
    $pdf->Cell(60, 7, 'Departamento:', 0, 0, 'L', true);
    $pdf->Cell(0, 7, formatarDado($funcionario['departamento_nome']), 0, 1, 'L');
    $pdf->Ln(5);
}

// Período do relatório
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, $periodo_relatorio, 0, 1, 'L');
$pdf->Ln(5);

// Cabeçalho da tabela
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(25, 7, 'Data', 1);
$pdf->Cell(25, 7, 'Entrada', 1);
$pdf->Cell(25, 7, 'Saída', 1);
$pdf->Cell(25, 7, 'Total Horas', 1);
$pdf->Cell(25, 7, 'Status', 1);
$pdf->Cell(60, 7, 'Observação', 1);
$pdf->Ln();

// Dados da tabela
$pdf->SetFont('helvetica', '', 10);
$fill = 0;
$total_horas = 0;
$dias_trabalhados = 0;
$tem_registros = false;
$datas_trabalhadas = [];

while ($row = mysqli_fetch_assoc($result)) {
    $tem_registros = true;
    $data = date('d/m/Y', strtotime($row['data']));
    $entrada = !empty($row['hora_entrada']) ? date('H:i', strtotime($row['hora_entrada'])) : '-';
    $saida = !empty($row['hora_saida']) ? date('H:i', strtotime($row['hora_saida'])) : '-';
    $horas_trabalhadas = calcularHorasTrabalhadas($row['hora_entrada'], $row['hora_saida']);
    
    // Considera como dia trabalhado se status for presente ou atrasado
    if (in_array($row['status'], ['presente', 'atrasado'])) {
        $datas_trabalhadas[$row['data']] = true;
    }
    
    // Soma horas apenas se houver entrada e saída
    if ($horas_trabalhadas !== '-') {
        $total_horas += strtotime($horas_trabalhadas) - strtotime('TODAY');
    }

    $pdf->SetFillColor(224, 235, 255);
    $pdf->Cell(25, 7, $data, 1, 0, 'C', $fill);
    $pdf->Cell(25, 7, $entrada, 1, 0, 'C', $fill);
    $pdf->Cell(25, 7, $saida, 1, 0, 'C', $fill);
    $pdf->Cell(25, 7, $horas_trabalhadas, 1, 0, 'C', $fill);
    $pdf->Cell(25, 7, ucfirst($row['status']), 1, 0, 'C', $fill);
    $pdf->Cell(60, 7, $row['observacao'], 1, 0, 'C', $fill);
    $pdf->Ln();
    $fill = !$fill;
}

$dias_trabalhados = count($datas_trabalhadas);

if (!$tem_registros) {
    $pdf->Cell(185, 7, 'Nenhum registro encontrado para o período.', 1, 1, 'C', 0);
}

// Resumo
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(62, 180, 137);
$pdf->Cell(0, 10, 'Resumo', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

$media_horas = $dias_trabalhados > 0 ? date('H:i', strtotime('TODAY') + ($total_horas / $dias_trabalhados)) : '-';

$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(60, 7, 'Total de dias trabalhados:', 0, 0, 'L', true);
$pdf->Cell(0, 7, $dias_trabalhados, 0, 1, 'L');
$pdf->Cell(60, 7, 'Média de horas por dia:', 0, 0, 'L', true);
$pdf->Cell(0, 7, $media_horas, 0, 1, 'L');

// Limpar qualquer saída anterior
ob_clean();

// Verificar se é para visualizar ou baixar
if (isset($_GET['acao']) && $_GET['acao'] === 'visualizar') {
    $pdf->Output('Relatorio_Ponto.pdf', 'I');
} else {
    $pdf->Output('Relatorio_Ponto.pdf', 'D');
}
?>