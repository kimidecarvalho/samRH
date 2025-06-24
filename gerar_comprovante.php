<?php
// Prevenir qualquer saída antes do PDF
ob_start();

// Desabilitar exibição de erros para evitar saída
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/direitos_ausencias.php';
require_once __DIR__ . '/configuracoes_sam/funcoes_calculo_subsidios.php';

// Recebe o número mecanográfico e mês de referência
$num_mecanografico = isset($_GET['num_mecanografico']) ? $_GET['num_mecanografico'] : '';
$mes_referencia = isset($_GET['mes_referencia']) ? $_GET['mes_referencia'] : date('Y-m');
$empresa_id = isset($_GET['empresa_id']) ? $_GET['empresa_id'] : '';

if (!$num_mecanografico) {
    ob_end_clean();
    die('Funcionário não especificado.');
}

// Buscar dados do funcionário
$sql = "SELECT f.*, c.nome as cargo_nome, e.nome as empresa_nome, e.nipc, e.endereco
        FROM funcionario f
        LEFT JOIN cargos c ON f.cargo = c.id
        LEFT JOIN empresa e ON f.empresa_id = e.id_empresa
        WHERE f.num_mecanografico = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $num_mecanografico);
$stmt->execute();
$result = $stmt->get_result();
$func = $result->fetch_assoc();
if (!$func) {
    ob_end_clean();
    die('Funcionário não encontrado.');
}

// Função para calcular total de dias úteis do mês
function calcularDiasUteis($ano, $mes) {
    $dias_uteis = 0;
    $dias_no_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
    for ($dia = 1; $dia <= $dias_no_mes; $dia++) {
        $data = date("Y-m-d", strtotime("$ano-$mes-$dia"));
        $dia_semana = date('N', strtotime($data));
        if ($dia_semana < 6) { // 1=Segunda, 5=Sexta
            $dias_uteis++;
        }
    }
    return $dias_uteis;
}

// Função para calcular dias úteis do mês até hoje
function calcularDiasUteisAteHoje($ano, $mes) {
    $dias_uteis = 0;
    $hoje = date('Y-m-d');
    $ultimo_dia = min(date('d'), cal_days_in_month(CAL_GREGORIAN, $mes, $ano));
    
    for ($dia = 1; $dia <= $ultimo_dia; $dia++) {
        $data = date("Y-m-d", strtotime("$ano-$mes-$dia"));
        if ($data > $hoje) break;
        
        $dia_semana = date('N', strtotime($data));
        if ($dia_semana < 6) { // 1=Segunda, 5=Sexta
            $dias_uteis++;
        }
    }
    return $dias_uteis;
}

// Função para calcular IRT
function calcularIRT($base) {
    if ($base <= 100000) return 0;
    if ($base <= 150000) return ($base - 100000) * 0.13;
    if ($base <= 200000) return 12500 + ($base - 150000) * 0.16;
    if ($base <= 300000) return 31250 + ($base - 200000) * 0.18;
    if ($base <= 500000) return 49259 + ($base - 300000) * 0.19;
    if ($base <= 1000000) return 87250 + ($base - 500000) * 0.20;
    if ($base <= 1500000) return 187249 + ($base - 1000000) * 0.21;
    if ($base <= 2000000) return 292249 + ($base - 1500000) * 0.22;
    if ($base <= 2500000) return 402249 + ($base - 2000000) * 0.23;
    if ($base <= 5000000) return 517249 + ($base - 2500000) * 0.24;
    if ($base <= 10000000) return 1117249 + ($base - 5000000) * 0.245;
    return 2342248 + ($base - 10000000) * 0.25;
}

// Calcular dados salariais em tempo real
$ano = date('Y', strtotime($mes_referencia.'-01'));
$mes = date('m', strtotime($mes_referencia.'-01'));
$dias_uteis_mes = calcularDiasUteis($ano, $mes);
$dias_uteis_ate_hoje = calcularDiasUteisAteHoje($ano, $mes);

$id_fun = $func['id_fun'];
$empresa_id = $func['empresa_id'];

// Buscar horário do funcionário
$sql_horario = "SELECT hora_entrada, hora_saida FROM horarios_funcionarios WHERE funcionario_id = ?";
$stmt_horario = $conn->prepare($sql_horario);
$stmt_horario->bind_param('i', $id_fun);
$stmt_horario->execute();
$res_horario = $stmt_horario->get_result();
$horario = $res_horario->fetch_assoc();
$hora_entrada = $horario ? $horario['hora_entrada'] : '08:00:00';
$hora_saida = $horario ? $horario['hora_saida'] : '16:00:00';
$jornada_diaria = (strtotime($hora_saida) - strtotime($hora_entrada)) / 3600;
if ($jornada_diaria <= 0) $jornada_diaria = 8;

// Buscar registros de ponto do mês
$sql_ponto = "SELECT * FROM registros_ponto WHERE funcionario_id = ? AND MONTH(data) = ? AND YEAR(data) = ?";
$mes_num = date('m', strtotime($mes_referencia.'-01'));
$ano_num = date('Y', strtotime($mes_referencia.'-01'));
$stmt_ponto = $conn->prepare($sql_ponto);
$stmt_ponto->bind_param('iii', $id_fun, $mes_num, $ano_num);
$stmt_ponto->execute();
$result_ponto = $stmt_ponto->get_result();
$registros_ponto = [];
$dias_com_ponto = [];
$hoje = date('Y-m-d');
while ($p = $result_ponto->fetch_assoc()) {
    $registros_ponto[] = $p;
    if ($p['data'] <= $hoje) {
        $dias_com_ponto[$p['data']] = true;
    }
}
$faltas = $dias_uteis_ate_hoje - count($dias_com_ponto);
if ($faltas < 0) $faltas = 0;

// Buscar ausências justificadas
$sql_ausencias = "SELECT data_inicio, data_fim, dias_uteis, tipo_ausencia, status_justificacao 
                  FROM ausencias 
                  WHERE funcionario_id = ? 
                  AND empresa_id = ? 
                  AND status_justificacao IN ('aprovada', 'pendente')
                  AND (
                      (MONTH(data_inicio) = ? AND YEAR(data_inicio) = ?) OR
                      (MONTH(data_fim) = ? AND YEAR(data_fim) = ?) OR
                      (data_inicio <= ? AND data_fim >= ?)
                  )";
$stmt_ausencias = $conn->prepare($sql_ausencias);
$data_inicio_mes = "$ano-$mes-01";
$data_fim_mes = date('Y-m-t', strtotime($data_inicio_mes));
$stmt_ausencias->bind_param('iiiiiiss', $id_fun, $empresa_id, $mes_num, $ano_num, $mes_num, $ano_num, $data_inicio_mes, $data_fim_mes);
$stmt_ausencias->execute();
$result_ausencias = $stmt_ausencias->get_result();

// Calcular total de subsídios
$sql_subs_temp = "SELECT sp.nome, sp.valor_padrao, sf.valor FROM subsidios_funcionarios sf JOIN subsidios_padrao sp ON sf.subsidio_id = sp.id WHERE sf.funcionario_id = ? AND sf.ativo = 1";
$stmt_subs_temp = $conn->prepare($sql_subs_temp);
$stmt_subs_temp->bind_param('i', $id_fun);
$stmt_subs_temp->execute();
$result_subs_temp = $stmt_subs_temp->get_result();
$total_subs = 0;
while ($s = $result_subs_temp->fetch_assoc()) {
    $valor = floatval($s['valor']);
    if ($valor <= 0) {
        $valor = floatval($s['valor_padrao']);
    }
    $total_subs += $valor;
}

$dias_ausencias_justificadas = 0;
$impacto_salarial_ausencias = [
    'desconto_salario' => 0,
    'desconto_subsidios' => 0
];

while ($ausencia = $result_ausencias->fetch_assoc()) {
    $inicio = new DateTime($ausencia['data_inicio']);
    $fim = new DateTime($ausencia['data_fim']);
    $inicio_mes = new DateTime($data_inicio_mes);
    $fim_mes = new DateTime($data_fim_mes);
    
    $inicio_efetivo = max($inicio, $inicio_mes);
    $fim_efetivo = min($fim, $fim_mes);
    
    if ($inicio_efetivo <= $fim_efetivo) {
        $dias_sobreposicao = 0;
        $intervalo = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio_efetivo, $intervalo, $fim_efetivo->modify('+1 day'));
        
        foreach ($periodo as $data) {
            $dia_semana = $data->format('N');
            if ($dia_semana < 6) {
                $dias_sobreposicao++;
            }
        }
        
        $dias_ausencias_justificadas += $dias_sobreposicao;
        
        $salario_base_func = floatval($func['salario_base']);
        $impacto = calcularImpactoSalarialAusencia(
            $ausencia['tipo_ausencia'], 
            $dias_sobreposicao, 
            $salario_base_func, 
            $total_subs, 
            $empresa_id, 
            $conn,
            $dias_uteis_mes
        );
        
        $impacto_salarial_ausencias['desconto_salario'] += $impacto['desconto_salario'];
        $impacto_salarial_ausencias['desconto_subsidios'] += $impacto['desconto_subsidios'];
    }
}

// Buscar justificações de faltas
$sql_justificacoes = "SELECT data_falta, tipo_justificacao, status 
                      FROM justificacoes_faltas 
                      WHERE funcionario_id = ? 
                      AND empresa_id = ? 
                      AND status IN ('aprovada', 'pendente')
                      AND MONTH(data_falta) = ? 
                      AND YEAR(data_falta) = ?";
$stmt_justificacoes = $conn->prepare($sql_justificacoes);
$stmt_justificacoes->bind_param('iiii', $id_fun, $empresa_id, $mes_num, $ano_num);
$stmt_justificacoes->execute();
$result_justificacoes = $stmt_justificacoes->get_result();

$faltas_justificadas = 0;
while ($justificacao = $result_justificacoes->fetch_assoc()) {
    $data_falta = new DateTime($justificacao['data_falta']);
    $dia_semana = $data_falta->format('N');
    if ($dia_semana < 6) {
        $faltas_justificadas++;
    }
}

$faltas_nao_justificadas = max(0, $faltas - $dias_ausencias_justificadas - $faltas_justificadas);

// Calcular horas extras e noturnas
$horas_extras = calcularHorasExtrasFuncionario($registros_ponto, $jornada_diaria);
$horas_noturnas = calcularHorasNoturnasFuncionario($registros_ponto);

// Buscar percentuais
$sql_he = "SELECT valor_padrao FROM subsidios_padrao WHERE empresa_id = ? AND nome = 'horas_extras'";
$stmt_he = $conn->prepare($sql_he);
$stmt_he->bind_param('i', $empresa_id);
$stmt_he->execute();
$result_he = $stmt_he->get_result();
$he = $result_he->fetch_assoc();
$percentual_he = $he ? floatval($he['valor_padrao']) : 50.00;

$sql_noturno = "SELECT valor_padrao FROM subsidios_padrao WHERE empresa_id = ? AND nome = 'noturno'";
$stmt_noturno = $conn->prepare($sql_noturno);
$stmt_noturno->bind_param('i', $empresa_id);
$stmt_noturno->execute();
$result_noturno = $stmt_noturno->get_result();
$noturno = $result_noturno->fetch_assoc();
$percentual_noturno = $noturno ? floatval($noturno['valor_padrao']) : 35.00;

// Calcular valores
$valor_hora_extra = calcularValorHoraExtra($func['salario_base'], $percentual_he, $jornada_diaria);
$valor_total_phe = calcularValorTotalHorasExtras($valor_hora_extra, $horas_extras);
$valor_subsidio_noturno = calcularValorNoturno($func['salario_base'], $horas_noturnas, $percentual_noturno, $jornada_diaria, $id_fun);

// Calcular salário ilíquido
$salario_iliquido = $func['salario_base'] + $total_subs + $valor_total_phe + $valor_subsidio_noturno;

// Calcular descontos
$iss = $func['salario_base'] * 0.03;
$desconto_faltas = $func['salario_base'] / $dias_uteis_mes * $faltas_nao_justificadas;
$irt = calcularIRT($func['salario_base']);

$desconto_ausencias_salario = $impacto_salarial_ausencias['desconto_salario'];
$desconto_ausencias_subsidios = $impacto_salarial_ausencias['desconto_subsidios'];

$desconto_faltas_total = $desconto_faltas + $desconto_ausencias_salario;
$total_descontos = $iss + $desconto_faltas_total + $irt + $desconto_ausencias_subsidios;

$salario_liquido = $salario_iliquido - $total_descontos;

// Buscar benefícios
$sql_benef = "SELECT sp.nome FROM subsidios_funcionarios sf JOIN subsidios_padrao sp ON sf.subsidio_id = sp.id WHERE sf.funcionario_id = ? AND sf.ativo = 1";
$stmt = $conn->prepare($sql_benef);
$stmt->bind_param('i', $id_fun);
$stmt->execute();
$res_benef = $stmt->get_result();
$beneficios = [];
while ($b = $res_benef->fetch_assoc()) {
    $beneficios[] = $b['nome'];
}

// Montar descontos
$descontos = [];
if ($iss > 0) $descontos[] = ['ISS (3%)', $iss];
if ($desconto_faltas_total > 0) $descontos[] = ['Desconto por Faltas', $desconto_faltas_total];
if ($irt > 0) $descontos[] = ['IRT', $irt];
if ($desconto_ausencias_subsidios > 0) $descontos[] = ['Desconto Subsídios', $desconto_ausencias_subsidios];

// Período de pagamento
$data_inicio = "$ano-$mes-01";
$data_fim = date('Y-m-t', strtotime($data_inicio));

// Limpar qualquer saída anterior antes de gerar o PDF
ob_end_clean();

// Verificar se há saída no buffer
if (ob_get_length()) {
    ob_end_clean();
}

// Criar PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('SAM RH');
$pdf->SetAuthor('SAM RH');
$pdf->SetTitle('Recibo de Vencimentos');
$pdf->SetMargins(18, 18, 18);
$pdf->AddPage();

// Cores do sistema
$cor_principal = '#3EB489';
$cor_cinza = '#f4f4f4';
$cor_cinza_escuro = '#444';
$cor_tabela = '#eafaf4';
$cor_linha = '#e0e0e0';

// Cabeçalho organizado
$html = '<table style="width:100%; font-size:12px; border-bottom:2px solid '.$cor_principal.'; margin-bottom:8px;">
<tr>
    <td style="width:40%; font-weight:700; color:'.$cor_principal.'; font-size:18px;">'.htmlspecialchars($func['empresa_nome']).'</td>
    <td style="width:30%; text-align:right; font-size:12px; color:'.$cor_cinza_escuro.';">NIF: '.htmlspecialchars($func['nipc']).'</td>
    <td style="width:30%; text-align:right; font-size:12px; color:'.$cor_cinza_escuro.';">'.htmlspecialchars($func['endereco']).'</td>
</tr>
</table>';

$html .= '<h2 style="color:'.$cor_principal.'; text-align:center; margin:10px 0 12px 0; font-size:20px;">Recibo de Vencimentos</h2>';

// Dados do colaborador e período
$html .= '<table cellpadding="3" style="width:100%; font-size:12px; border:1px solid '.$cor_linha.'; border-radius:6px; margin-bottom:6px;">
<tr style="background:'.$cor_cinza.';">
    <td><b>Nome:</b> '.htmlspecialchars($func['nome']).'</td>
    <td><b>Matrícula:</b> '.htmlspecialchars($func['num_mecanografico']).'</td>
    <td><b>Departamento:</b> '.htmlspecialchars($func['departamento'] ?? '-').'</td>
    <td><b>Cargo:</b> '.htmlspecialchars($func['cargo_nome']).'</td>
</tr>
<tr>
    <td><b>Período:</b> '.date('m/Y', strtotime($data_inicio)).'</td>
    <td><b>Dias Úteis:</b> '.$dias_uteis_mes.'</td>
    <td><b>Seguro:</b> '.htmlspecialchars($func['seguro'] ?? '-').'</td>
    <td><b>NIF:</b> '.htmlspecialchars($func['nipc']).'</td>
</tr>
</table>';

// Tabela de faltas/ausências
$html .= '<table cellpadding="3" style="width:100%; font-size:11px; border:1px solid '.$cor_linha.'; border-radius:6px; margin-bottom:8px;">
<tr style="background:'.$cor_cinza.'; color:'.$cor_cinza_escuro.'; font-weight:700;">
    <td style="width:16%;">Faltas</td>
    <td style="width:16%;">Alim.</td>
    <td style="width:16%;">Turno</td>
    <td style="width:16%;">CDH</td>
    <td style="width:16%;">CDD</td>
    <td style="width:16%;">SDH</td>
    <td style="width:16%;">SDD</td>
</tr>
<tr>
    <td>'.($faltas_nao_justificadas ?? 0).'</td>
    <td>-</td>
    <td>-</td>
    <td>-</td>
    <td>-</td>
    <td>-</td>
    <td>-</td>
</tr>
</table>';

// Tabela de remunerações/descontos (substituir por tabela agrupada)
$html .= '<table cellpadding="6" style="width:100%; border-collapse:collapse; font-size:12px; margin-bottom:10px;">';
$html .= '<tr style="background:#f0f0f0; color:#333; font-weight:700;">'
    .'<th style="border:1px solid #ddd; text-align:left;">Proventos</th>'
    .'<th style="border:1px solid #ddd; text-align:right;">Valor (Kz)</th>'
    .'<th style="border:1px solid #ddd; text-align:left;">Descontos</th>'
    .'<th style="border:1px solid #ddd; text-align:right;">Valor (Kz)</th>'
.'</tr>';

// Proventos
$proventos = [];
$proventos[] = ['Salário Base', $func['salario_base']];
if ($total_subs > 0) $proventos[] = ['Subsídios', $total_subs];
if ($valor_total_phe > 0) $proventos[] = ['Horas Extras', $valor_total_phe];
if ($valor_subsidio_noturno > 0) $proventos[] = ['Subsídio Noturno', $valor_subsidio_noturno];

// Descontos
$descontos = [];
if ($iss > 0) $descontos[] = ['ISS (3%)', $iss];
if ($desconto_faltas_total > 0) $descontos[] = ['Desconto por Faltas', $desconto_faltas_total];
if ($irt > 0) $descontos[] = ['IRT', $irt];
if ($desconto_ausencias_subsidios > 0) $descontos[] = ['Desconto Subsídios', $desconto_ausencias_subsidios];

$max_linhas = max(count($proventos), count($descontos));
for ($i = 0; $i < $max_linhas; $i++) {
    $html .= '<tr>';
    // Provento
    if (isset($proventos[$i])) {
        $html .= '<td style="border:1px solid #ddd;">'.htmlspecialchars($proventos[$i][0]).'</td>'
                .'<td style="border:1px solid #ddd; text-align:right;">'.number_format($proventos[$i][1], 2, ',', '.').'</td>';
    } else {
        $html .= '<td style="border:1px solid #ddd;"></td><td style="border:1px solid #ddd;"></td>';
    }
    // Desconto
    if (isset($descontos[$i])) {
        $html .= '<td style="border:1px solid #ddd;">'.htmlspecialchars($descontos[$i][0]).'</td>'
                .'<td style="border:1px solid #ddd; text-align:right;">'.number_format($descontos[$i][1], 2, ',', '.').'</td>';
    } else {
        $html .= '<td style="border:1px solid #ddd;"></td><td style="border:1px solid #ddd;"></td>';
    }
    $html .= '</tr>';
}
// Totais
$html .= '<tr style="background:#f0f0f0; font-weight:700;">'
    .'<td style="border:1px solid #ddd; text-align:right;" colspan="2">Total Proventos:</td>'
    .'<td style="border:1px solid #ddd; text-align:right;" colspan="2">'.number_format($salario_iliquido, 2, ',', '.').' Kz</td>'
.'</tr>';
$html .= '<tr style="background:#f0f0f0; font-weight:700;">'
    .'<td style="border:1px solid #ddd; text-align:right;" colspan="2">Total Descontos:</td>'
    .'<td style="border:1px solid #ddd; text-align:right;" colspan="2">'.number_format($total_descontos, 2, ',', '.').' Kz</td>'
.'</tr>';
$html .= '<tr style="background:#e8e8e8; font-weight:700;">'
    .'<td colspan="4" style="border:1px solid #ddd; text-align:center; font-size:15px;">Total Líquido: '.number_format($salario_liquido, 2, ',', '.').' Kz</td>'
.'</tr>';
$html .= '</table>';

// Resumo mensal e formas de pagamento
$html .= '<table style="width:100%; font-size:12px; margin-top:10px;">
<tr>
    <td style="width:60%; vertical-align:top;">
        <b>Resumo Mensal:</b><br>
        <span style="color:'.$cor_cinza_escuro.';">Total Bruto: <b>'.number_format($salario_iliquido, 2, ',', '.').' Kz</b></span><br>
        <span style="color:'.$cor_cinza_escuro.';">Total Descontos: <b>'.number_format($total_descontos, 2, ',', '.').' Kz</b></span><br>
        <span style="color:'.$cor_principal.'; font-size:15px;">Total Líquido: <b>'.number_format($salario_liquido, 2, ',', '.').' Kz</b></span>
    </td>
    <td style="width:40%; text-align:right; vertical-align:top;">
        <b>Formas de Pagamento:</b><br>
        <span style="color:'.$cor_cinza_escuro.';">Transferência Bancária</span>
    </td>
</tr>
</table>';

// Benefícios
if (count($beneficios) > 0) {
    $html .= '<div style="margin-top:10px; color:'.$cor_principal.';"><b>Benefícios:</b> '.implode(', ', array_map('htmlspecialchars', $beneficios)).'</div>';
}

// Rodapé
$html .= '<div style="margin-top:30px; font-size:11px; color:#888;">Declaro que recebi a quantia constante neste recibo.</div>';
$html .= '<div style="margin-top:30px; border-top:1px solid #ccc; width:60%; font-size:11px; color:#888;">Assinatura do Colaborador</div>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('comprovante_pagamento_' . $func['num_mecanografico'] . '.pdf', 'D');
exit; 