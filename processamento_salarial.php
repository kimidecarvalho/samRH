<?php
// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir direitos_ausencias.php primeiro para garantir que as funções estejam disponíveis
require_once 'direitos_ausencias.php';

include 'protect.php'; // Protege a página para usuários autenticados
include 'config.php'; // Conexão com o banco de dados
include 'configuracoes_sam/funcoes_calculo_subsidios.php';
require_once 'conexao.php';

// Verifica se o usuário está logado e tem um ID válido
if (!isset($_SESSION['id_adm'])) {
    echo "Erro: Usuário não autenticado.";
    exit;
}

// Verifica se o administrador está associado a uma empresa
if (!isset($_SESSION['id_empresa'])) {
    echo "<script>alert('Você precisa criar uma empresa antes de acessar esta página.'); window.location.href='Registro_adm.php';</script>";
    exit;
}

// Buscar funcionários ativos da empresa logada
$empresa_id = $_SESSION['id_empresa'];
$mes_referencia = isset($_GET['mes_referencia']) ? $_GET['mes_referencia'] : date('Y-m');
$departamento_filtro = isset($_GET['departamento']) ? $_GET['departamento'] : 'todos';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : 'Ativo';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Montar query dinâmica de funcionários
$where = ["f.empresa_id = ?"];
$params = [$empresa_id];
$types = 'i';

if ($estado_filtro !== 'todos') {
    $where[] = "f.estado = ?";
    $params[] = $estado_filtro;
    $types .= 's';
}

if ($departamento_filtro !== 'todos') {
    $where[] = "f.departamento = ?";
    $params[] = $departamento_filtro;
    $types .= 's';
}

if ($search !== '') {
    $where[] = "(f.nome LIKE ? OR f.num_mecanografico LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$sql_funcionarios = "SELECT f.*, d.nome as nome_departamento 
                    FROM funcionario f 
                    LEFT JOIN departamentos d ON f.departamento = d.id 
                    WHERE " . implode(' AND ', $where) . " 
                    ORDER BY f.num_mecanografico ASC";
$stmt_funcionarios = $conn->prepare($sql_funcionarios);
$stmt_funcionarios->bind_param($types, ...$params);
$stmt_funcionarios->execute();
$result_funcionarios = $stmt_funcionarios->get_result();
$funcionarios = [];
while ($row = $result_funcionarios->fetch_assoc()) {
    $funcionarios[] = $row;
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
        // Se a data for maior que hoje, para o loop
        if ($data > $hoje) break;
        
        $dia_semana = date('N', strtotime($data));
        if ($dia_semana < 6) { // 1=Segunda, 5=Sexta
            $dias_uteis++;
        }
    }
    return $dias_uteis;
}

$ano = date('Y', strtotime($mes_referencia.'-01'));
$mes = date('m', strtotime($mes_referencia.'-01'));
$dias_uteis_mes = calcularDiasUteis($ano, $mes); // Total de dias úteis do mês
$dias_uteis_ate_hoje = calcularDiasUteisAteHoje($ano, $mes); // Dias úteis até hoje

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

// Função para calcular 13º mês
function calcularDecimoTerceiro($salario_base, $meses_trabalhados) {
    return ($salario_base / 12) * $meses_trabalhados;
}

$dados_salariais = [];
foreach ($funcionarios as $f) {
    $id_fun = $f['id_fun'];
    // Buscar nome do cargo
    $cargo_nome = $f['cargo'];
    if (is_numeric($cargo_nome)) {
        $sql_cargo = "SELECT nome FROM cargos WHERE id = ? LIMIT 1";
        $stmt_cargo = $conn->prepare($sql_cargo);
        $stmt_cargo->bind_param('i', $cargo_nome);
        $stmt_cargo->execute();
        $res_cargo = $stmt_cargo->get_result();
        if ($row_cargo = $res_cargo->fetch_assoc()) {
            $cargo_nome = $row_cargo['nome'];
        }
    }
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
    if ($jornada_diaria <= 0) $jornada_diaria = 8; // fallback

    // Buscar registros de ponto do mês
    $sql_ponto = "SELECT * FROM registros_ponto WHERE funcionario_id = ? AND MONTH(data) = ? AND YEAR(data) = ?";
    $mes_num = date('m', strtotime($mes_referencia.'-01'));
    $ano_num = date('Y', strtotime($mes_referencia.'-01'));
    $stmt_ponto = $conn->prepare($sql_ponto);
    $stmt_ponto->bind_param('iii', $id_fun, $mes_num, $ano_num);
    $stmt_ponto->execute();
    $result_ponto = $stmt_ponto->get_result();
    $registros_ponto = [];
    $faltas = 0;
    $dias_com_ponto = [];
    $hoje = date('Y-m-d');
    while ($p = $result_ponto->fetch_assoc()) {
        $registros_ponto[] = $p;
        if ($p['data'] <= $hoje) {
            $dias_com_ponto[$p['data']] = true;
        }
    }
    // Faltas = dias úteis até hoje - dias com ponto
    $faltas = $dias_uteis_ate_hoje - count($dias_com_ponto);
    if ($faltas < 0) $faltas = 0;
    
    // Buscar ausências justificadas para o mês
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
    
    // Debug temporário - remover depois
    $total_ausencias_encontradas = $result_ausencias->num_rows;
    
    // Calcular total de subsídios ANTES do loop de ausências
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
    $ausencias_info = [];
    $impacto_salarial_ausencias = [
        'desconto_salario' => 0,
        'desconto_subsidios' => 0,
        'detalhes_por_tipo' => []
    ];
    
    while ($ausencia = $result_ausencias->fetch_assoc()) {
        $inicio = new DateTime($ausencia['data_inicio']);
        $fim = new DateTime($ausencia['data_fim']);
        $inicio_mes = new DateTime($data_inicio_mes);
        $fim_mes = new DateTime($data_fim_mes);
        
        // Calcular sobreposição com o mês de referência
        $inicio_efetivo = max($inicio, $inicio_mes);
        $fim_efetivo = min($fim, $fim_mes);
        
        if ($inicio_efetivo <= $fim_efetivo) {
            $dias_sobreposicao = 0;
            $intervalo = new DateInterval('P1D');
            $periodo = new DatePeriod($inicio_efetivo, $intervalo, $fim_efetivo->modify('+1 day'));
            
            foreach ($periodo as $data) {
                $dia_semana = $data->format('N');
                if ($dia_semana < 6) { // Dias úteis (segunda a sexta)
                    $dias_sobreposicao++;
                }
            }
            
            $dias_ausencias_justificadas += $dias_sobreposicao;
            $ausencias_info[] = [
                'tipo' => $ausencia['tipo_ausencia'],
                'dias' => $dias_sobreposicao,
                'periodo' => $inicio_efetivo->format('d/m/Y') . ' - ' . $fim_efetivo->format('d/m/Y')
            ];
            
            // Calcular impacto salarial específico para este tipo de ausência
            $salario_base_func = floatval($f['salario_base']);
            $impacto = calcularImpactoSalarialAusencia(
                $ausencia['tipo_ausencia'], 
                $dias_sobreposicao, 
                $salario_base_func, 
                $total_subs, 
                $empresa_id, 
                $conn,
                $dias_uteis_mes
            );
            
            // Acumular impactos
            $impacto_salarial_ausencias['desconto_salario'] += $impacto['desconto_salario'];
            $impacto_salarial_ausencias['desconto_subsidios'] += $impacto['desconto_subsidios'];
            $impacto_salarial_ausencias['detalhes_por_tipo'][] = [
                'tipo' => $ausencia['tipo_ausencia'],
                'dias' => $dias_sobreposicao,
                'impacto' => $impacto
            ];
        }
    }
    
    // Buscar justificações de faltas passadas para o mês
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
        if ($dia_semana < 6) { // Apenas dias úteis
            $faltas_justificadas++;
        }
    }
    
    // Ajustar faltas considerando ausências justificadas
    $faltas_nao_justificadas = max(0, $faltas - $dias_ausencias_justificadas - $faltas_justificadas);
    
    // Calcular horas extras e noturnas usando funções centralizadas
    $horas_extras = calcularHorasExtrasFuncionario($registros_ponto, $jornada_diaria);
    $horas_extras_h = floor($horas_extras);
    $horas_extras_m = round(($horas_extras - $horas_extras_h) * 60);
    $horas_extras_fmt = sprintf('%d:%02d', $horas_extras_h, $horas_extras_m);

    $horas_noturnas = calcularHorasNoturnasFuncionario($registros_ponto);
    $horas_noturnas_h = floor($horas_noturnas);
    $horas_noturnas_m = round(($horas_noturnas - $horas_noturnas_h) * 60);
    $horas_noturnas_fmt = sprintf('%d:%02d', $horas_noturnas_h, $horas_noturnas_m);
    $horas_noturnas_decimal = $horas_noturnas; // Manter o valor decimal original para cálculos

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
    $valor_hora_extra = calcularValorHoraExtra($f['salario_base'], $percentual_he, $jornada_diaria);
    $valor_total_phe = calcularValorTotalHorasExtras($valor_hora_extra, $horas_extras);
    $valor_subsidio_noturno = calcularValorNoturno($f['salario_base'], $horas_noturnas_decimal, $percentual_noturno, $jornada_diaria, $id_fun);

    // Calcular salário ilíquido SEM descontos (usando salário base original)
    $salario_iliquido = $f['salario_base'] + $total_subs + $valor_total_phe + $valor_subsidio_noturno;
    
    // Calcular descontos usando o salário base original
    $iss = $f['salario_base'] * 0.03;
    $desconto_faltas = $f['salario_base'] / $dias_uteis_mes * $faltas_nao_justificadas;
    $irt = calcularIRT($f['salario_base']);
    
    // IMPORTANTE: Desconto das ausências justificadas é aplicado DEPOIS do salário ilíquido
    $desconto_ausencias_salario = $impacto_salarial_ausencias['desconto_salario'];
    $desconto_ausencias_subsidios = $impacto_salarial_ausencias['desconto_subsidios'];
    
    // Desconto por faltas TOTAL (faltas não justificadas + ausências justificadas)
    $desconto_faltas_total = $desconto_faltas + $desconto_ausencias_salario;
    
    // Total de descontos (incluindo ausências justificadas)
    $total_descontos = $iss + $desconto_faltas_total + $irt + $desconto_ausencias_subsidios;
    
    // Salário líquido final (salário ilíquido MENOS todos os descontos)
    $salario_liquido = $salario_iliquido - $total_descontos;
    
    // Calcular 13º mês usando o salário base original
    $meses_trabalhados = 12; // Você pode ajustar isso baseado na data de admissão
    $valor_decimo_terceiro = calcularDecimoTerceiro($f['salario_base'], $meses_trabalhados);

    // Buscar subsídios opcionais ativos
    $sql_subs = "SELECT sp.nome, sp.valor_padrao, sf.valor FROM subsidios_funcionarios sf JOIN subsidios_padrao sp ON sf.subsidio_id = sp.id WHERE sf.funcionario_id = ? AND sf.ativo = 1";
    $stmt_subs = $conn->prepare($sql_subs);
    $stmt_subs->bind_param('i', $id_fun);
    $stmt_subs->execute();
    $result_subs = $stmt_subs->get_result();
    $subs_list = [];
    while ($s = $result_subs->fetch_assoc()) {
        $subs_list[] = $s['nome'];
    }
    // Subsídios obrigatórios individuais
    if ($valor_subsidio_noturno > 0.01) $subs_list[] = 'noturno';
    if ($valor_total_phe > 0.01) $subs_list[] = 'horas_extras';
    if ($valor_decimo_terceiro > 0.01) $subs_list[] = 'decimo_terceiro';
    if (isset($subs_valores['risco']) && floatval($subs_valores['risco']) > 0.01) $subs_list[] = 'risco';
    $subs_list = array_unique($subs_list);

    // Criar explicações detalhadas dos cálculos
    $explicacoes_calculo = [
        'salario_base_original' => $f['salario_base'],
        'desconto_ausencias' => $desconto_ausencias_salario,
        'salario_base_ajustado' => $f['salario_base'] - $desconto_ausencias_salario,
        'total_subs_original' => $total_subs,
        'desconto_subsidios_ausencias' => $desconto_ausencias_subsidios,
        'total_subs_ajustado' => $total_subs - $desconto_ausencias_subsidios,
        'horas_extras_valor' => $valor_total_phe,
        'subsidio_noturno_valor' => $valor_subsidio_noturno,
        'salario_iliquido' => $salario_iliquido,
        'iss_base' => $f['salario_base'],
        'iss_percentual' => 0.03,
        'iss_valor' => $iss,
        'faltas_nao_justificadas' => $faltas_nao_justificadas,
        'valor_falta_dia' => $f['salario_base'] / $dias_uteis_mes,
        'desconto_faltas' => $desconto_faltas_total,
        'irt_base' => $f['salario_base'],
        'irt_valor' => $irt,
        'total_descontos' => $total_descontos,
        'salario_liquido' => $salario_liquido,
        'valor_decimo_terceiro' => $valor_decimo_terceiro
    ];

    // Criar detalhes das ausências
    $detalhes_ausencias = [];
    foreach ($impacto_salarial_ausencias['detalhes_por_tipo'] as $detalhe) {
        $detalhes_ausencias[] = [
            'tipo' => $detalhe['tipo'],
            'dias' => $detalhe['dias'],
            'desconto_salario' => $detalhe['impacto']['desconto_salario'],
            'desconto_subsidios' => $detalhe['impacto']['desconto_subsidios'],
            'explicacao' => gerarExplicacaoAusencia($detalhe['tipo'], $detalhe['dias'], $f['salario_base'], $total_subs, $dias_uteis_mes)
        ];
    }

    $dados_salariais[] = [
        'num_mecanografico' => $f['num_mecanografico'],
        'nome' => $f['nome'],
        'foto' => $f['foto'],
        'cargo' => $cargo_nome,
        'salario_base' => $f['salario_base'],
        'salario_base_ajustado' => $f['salario_base'] - $desconto_ausencias_salario,
        'dias_uteis' => $dias_uteis_mes,
        'horas_por_dia' => $jornada_diaria,
        'qhe' => $dias_uteis_mes * $jornada_diaria,
        'faltas' => $faltas_nao_justificadas,
        'faltas_totais' => $faltas,
        'ausencias_justificadas' => $dias_ausencias_justificadas,
        'faltas_justificadas' => $faltas_justificadas,
        'ausencias_info' => $ausencias_info,
        'detalhes_ausencias' => $detalhes_ausencias,
        'horas_extras' => $horas_extras_fmt,
        'horas_noturnas' => $horas_noturnas_fmt,
        'salario_dia' => ($f['salario_base'] - $desconto_ausencias_salario) / $dias_uteis_mes,
        'salario_hora' => ($f['salario_base'] - $desconto_ausencias_salario) / ($jornada_diaria * $dias_uteis_mes),
        'valor_hora_extra' => $valor_hora_extra,
        'subs_list' => $subs_list,
        'total_subs' => $total_subs,
        'total_subs_ajustado' => $total_subs - $desconto_ausencias_subsidios,
        'valor_total_phe' => $valor_total_phe,
        'salario_iliquido' => $salario_iliquido,
        'iss' => $iss,
        'desconto_faltas' => $desconto_faltas_total,
        'irt' => $irt,
        'total_descontos' => $total_descontos,
        'salario_liquido' => $salario_liquido,
        'valor_subsidio_noturno' => $valor_subsidio_noturno,
        'valor_decimo_terceiro' => $valor_decimo_terceiro,
        'total_faltas' => $faltas_nao_justificadas,
        'impacto_salarial_ausencias' => $impacto_salarial_ausencias,
        'explicacoes_calculo' => $explicacoes_calculo
    ];
}

// Buscar status e valores dos subsídios
$subs_ativos = [];
$subs_valores = [];
$subs_unidades = [];
$sql = "SELECT nome, ativo, valor_padrao, unidade FROM subsidios_padrao WHERE empresa_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $empresa_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subs_ativos[$row['nome']] = (int)$row['ativo'];
    $subs_valores[$row['nome']] = $row['valor_padrao'];
    $subs_unidades[$row['nome']] = $row['unidade'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="all.css/registro3.css">
    <link rel="stylesheet" href="all.css/timer.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processamento Salarial</title>
<style>
    .filters {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
    }
    .filters form {
        display: flex;
        align-items: center;
        gap: 15px;
        width: 100%;
    }
    .filter-select {
        background-color: white;
        border: 1px solid #ddd;
        padding: 8px 15px;
        border-radius: 25px;
        color: #000;
        font-size: 14px;
        width: 180px;
        height: 40px;
    }
    .search-bar {
        flex-grow: 1;
        max-width: 300px;
        background-color: white;
        border: 1px solid #ddd;
        padding: 0 15px;
        border-radius: 25px;
        display: flex;
        align-items: center;
        height: 40px;
        position: relative;
    }
    .search-bar input {
        border: none;
        background: transparent;
        width: 100%;
        outline: none;
        color: #000;
        font-size: 14px;
        height: 100%;
        padding: 0;
        flex-shrink: 1;
    }
    .search-bar button {
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 0;
        margin-left: 10px;
        display: flex;
        align-items: center;
    }
    .search-icon {
        color: #777;
    }
    .table-container {
        width: 100%;
        overflow-x: auto;
        position: relative;
        background-color: white;
        border-radius: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }
    .tabela-funcionarios {
        width: 100%;
        border-collapse: collapse;
        white-space: nowrap;
    }
    .tabela-funcionarios th {
        background-color: rgb(255, 255, 255);
        color: #333;
        font-weight: 500;
        text-align: center;
        padding: 15px;
        border-bottom: 1px solid #eee;
        position: sticky;
        top: 0;
        z-index: 10;
        border-left: none !important;
        transition: none !important;
    }
    .tabela-funcionarios td {
        padding: 10px;
        border-bottom: 1px solid #eee;
        color: #000;
    }
    .tabela-funcionarios tr:last-child td {
        border-bottom: none;
    }
    .tabela-funcionarios tbody tr {
        transition: all 0.2s ease-in-out;
        border-left: 0px solid #64c2a7;
    }
    .tabela-funcionarios tbody tr:hover {
        background-color: #f9f9f9;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        border-left: 5px solid #64c2a7;
        transform: translateX(2px);
    }
    th, td {
        padding: 10px;
        text-align: center;
        font-size: 15px;
        border-bottom: 1px solid #ccc;
        border-right: 1px solid #ccc;
    }
    tr:nth-child(odd):not(:first-child) {
        background-color: #f7f7f7;
    }
    .subs-box {
        display: inline-block;
        background: #e6f7f2;
        color: #3EB489;
        border: 1px solid #3EB489;
        border-radius: 8px;
        padding: 4px 12px;
        margin: 2px 4px 2px 0;
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
    }
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #64c2a7;
        color: white;
        font-weight: 500;
    }
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: relative;
    }
    /* Scrollbar customizada igual funcionarios.php */
    .table-container::-webkit-scrollbar {
        height: 10px;
    }
    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 0 0 8px 8px;
    }
    .table-container::-webkit-scrollbar-thumb {
        background: #64c2a7;
        border-radius: 10px;
    }
    .subs-obrigatorio {
        color: #e74c3c !important;
        border: 1.5px solid #e74c3c !important;
        background: #fff5f5 !important;
    }
    .subs-tooltip {
        position: relative;
        cursor: pointer;
    }
    .subs-tooltip .subs-tooltip-box {
        display: none;
        position: fixed;
        z-index: 9999;
        background: #222;
        color: #fff;
        padding: 7px 14px;
        border-radius: 7px;
        font-size: 14px;
        pointer-events: none;
        white-space: nowrap;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        transition: opacity 0.15s;
        opacity: 0.95;
    }
    
    .ausencias-tooltip {
        cursor: pointer;
        color: #3EB489;
        font-weight: 500;
    }
    
    .ausencias-tooltip:hover {
        color: #2e8c6a;
    }
    
    .tooltip-inner {
        max-width: 300px;
        text-align: left;
    }
    #modalDetalhesCalculo {
        z-index: 1060; /* Valor maior que o da sidebar */
    }
    .modal {
        z-index: 2000 !important;
    }
    .ausencias-tooltip i.fa-info-circle {
        color: #3EB489 !important;
        margin-left: 5px;
    }
    .btn-calculadora {
        border: 1.5px solid #3EB489 !important;
        background: #e6f7f2 !important;
        color: #3EB489 !important;
        padding: 2px 8px;
        border-radius: 6px;
        transition: background 0.2s, color 0.2s;
    }
    .btn-calculadora:hover {
        background: #3EB489 !important;
        color: #fff !important;
        border-color: #2e8c6a !important;
    }
    .tooltip {
        z-index: 3000 !important;
        margin: 12px !important;
        pointer-events: auto;
    }
    .tooltip .tooltip-inner {
        background: #222 !important;
        color: #fff !important;
        border-radius: 7px;
        font-size: 14px;
        opacity: 0.88;
        box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        padding: 10px 16px;
        max-width: 320px;
    }
    .tooltip.bs-tooltip-auto[x-placement^=top] .arrow::before,
    .tooltip.bs-tooltip-top .arrow::before {
        border-top-color: #222 !important;
    }
    .btn-comprovante {
        border: 1.5px solid #3EB489 !important;
        background: #fff !important;
        color: #3EB489 !important;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(62,180,137,0.08);
        transition: background 0.2s, color 0.2s, border 0.2s;
        font-size: 18px;
        padding: 0;
    }
    .btn-comprovante:hover {
        background: #3EB489 !important;
        color: #fff !important;
        border-color: #2e8c6a !important;
    }
    .btn-comprovante i {
        font-size: 18px;
    }
</style>
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
            <a href="processamento_salarial.php"><li class="active">Processamento Salarial</li></a>
            <a href="docs.php"><li>Documentos</li></a>
            <a href="registro_ponto.php"><li>Registro de Ponto</li></a>
            <a href="ausencias.php"><li>Ausências</li></a>
            <a href="recrutamento.php"><li>Recrutamento</li></a>
        </ul>
    </div>
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Processamento Salarial</h1>
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
        <div class="filters">
            <form method="GET" action="#">
                <select class="filter-select" name="mes_referencia">
                    <?php
                    $meses = [
                        '01' => 'Janeiro',
                        '02' => 'Fevereiro',
                        '03' => 'Março',
                        '04' => 'Abril',
                        '05' => 'Maio',
                        '06' => 'Junho',
                        '07' => 'Julho',
                        '08' => 'Agosto',
                        '09' => 'Setembro',
                        '10' => 'Outubro',
                        '11' => 'Novembro',
                        '12' => 'Dezembro'
                    ];
                    
                    $ano_atual = date('Y');
                    
                    // Mostra todos os meses do ano atual
                    foreach ($meses as $mes_num => $mes_nome) {
                        $valor = "$ano_atual-$mes_num";
                        $texto = "$mes_nome/$ano_atual";
                        $selected = ($mes_referencia == $valor) ? 'selected' : '';
                        echo "<option value=\"$valor\" $selected>$texto</option>";
                    }
                    ?>
                </select>
                <select class="filter-select" name="departamento">
                    <option value="todos" <?= $departamento_filtro == 'todos' ? 'selected' : '' ?>>Todos Departamentos</option>
                    <?php
                    $sql_departamentos = "SELECT id, nome FROM departamentos WHERE empresa_id = ? ORDER BY nome";
                    $stmt_departamentos = $conn->prepare($sql_departamentos);
                    $stmt_departamentos->bind_param('i', $empresa_id);
                    $stmt_departamentos->execute();
                    $result_departamentos = $stmt_departamentos->get_result();
                    
                    while ($dept = $result_departamentos->fetch_assoc()) {
                        $selected = ($departamento_filtro == $dept['id']) ? 'selected' : '';
                        echo '<option value="' . $dept['id'] . '" ' . $selected . '>' . htmlspecialchars($dept['nome']) . '</option>';
                    }
                    ?>
                </select>
                <select class="filter-select" name="estado">
                    <option value="Ativo" <?= $estado_filtro == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="Inativo" <?= $estado_filtro == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                    <option value="Terminado" <?= $estado_filtro == 'Terminado' ? 'selected' : '' ?>>Terminado</option>
                </select>
                <div class="search-bar">
                    <input type="text" name="search" id="search-input" placeholder="Pesquisar colaborador..." autocomplete="off" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit"><i class="fas fa-search search-icon"></i></button>
                    <div id="suggestions" class="suggestions-box"></div>
                </div>
            </form>
        </div>
        <div class="table-container">
            <table class="tabela-funcionarios">
                <thead>
                    <tr>
                        <!-- Informações do Colaborador -->
                        <th colspan="4">Informações do Colaborador</th>
                        <!-- Jornada de Trabalho -->
                        <th colspan="3">Jornada de Trabalho</th>
                        <!-- Subsídios Base -->
                        <th colspan="2">Subsídios Base</th>
                        <!-- Horas Extras -->
                        <th colspan="3">Horas Extras</th>
                        <!-- Horas Noturnas -->
                        <th colspan="3">Horas Noturnas</th>
                        <!-- Total Bruto -->
                        <th rowspan="2">Salário Ilíquido</th>
                        <!-- Faltas/Ausências agrupadas -->
                        <th colspan="3" style="border-bottom:2px solid #e0e0e0; text-align:center;">Faltas/Ausências</th>
                        <!-- Descontos agrupados -->
                        <th colspan="4" style="border-bottom:2px solid #e0e0e0; text-align:center;">Descontos</th>
                        <!-- Resultado Final -->
                        <th rowspan="2">Salário Líquido</th>
                        <th>Comprovante</th>
                    </tr>
                    <tr>
                        <!-- Informações do Colaborador -->
                        <th>Nº Mecanográfico</th>
                        <th>Nome</th>
                        <th>Cargo</th>
                        <th>Salário Base</th>
                        <!-- Jornada de Trabalho -->
                        <th>Dias Úteis</th>
                        <th>Horas/Dia</th>
                        <th>QHE</th>
                        <!-- Subsídios Base -->
                        <th>Subsídios</th>
                        <th>Total Subsídios</th>
                        <!-- Horas Extras -->
                        <th>Qtd. Horas Extras</th>
                        <th>Valor/Hora Extra</th>
                        <th>Subsídio HE</th>
                        <!-- Horas Noturnas -->
                        <th>Qtd. Horas Noturnas</th>
                        <th>Valor/Hora Noturna</th>
                        <th>Subsídio Noturno</th>
                        <!-- Faltas/Ausências subgrupos -->
                        <th style="color:#e74c3c;">
                            Não Justificadas
                            <i class="fas fa-info-circle" style="color:#e74c3c;" data-bs-toggle="tooltip" title="Dias em que o colaborador faltou sem justificativa. Gera desconto no salário."></i>
                        </th>
                        <th style="color:#3EB489;">
                            Justificadas
                            <i class="fas fa-info-circle" style="color:#3EB489;" data-bs-toggle="tooltip" title="Dias de ausência com justificativa aprovada."></i>
                        </th>
                        <th style="color:#888;">
                            Total
                            <i class="fas fa-info-circle" style="color:#888;" data-bs-toggle="tooltip" title="Soma de todas as ausências."></i>
                        </th>
                        <!-- Descontos subgrupos -->
                        <th>ISS (3%)</th>
                        <th>
                            <span style="color:#e67e22;">Desconto por Faltas (Kz)</span>
                            <i class="fas fa-info-circle" style="color:#e67e22;" data-bs-toggle="tooltip" title="Valor total descontado do salário por faltas e ausências (justificadas e não justificadas)."></i>
                        </th>
                        <th>IRT</th>
                        <th>Total Descontos</th>
                        <th>Comprovante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados_salariais as $d): ?>
                    <tr>
                        <!-- Informações do Colaborador -->
                        <td><?= htmlspecialchars($d['num_mecanografico']) ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="user-avatar">
                                    <img src="<?= $d['foto'] ? htmlspecialchars($d['foto']) : 'icones/icons-sam-18.svg' ?>" alt="Foto">
                                </div>
                                <span><?= htmlspecialchars($d['nome']) ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($d['cargo']) ?></td>
                        <td><?= number_format($d['salario_base'], 2, ',', '.') ?></td>
                        <!-- Jornada de Trabalho -->
                        <td><?= $d['dias_uteis'] ?></td>
                        <td><?= $d['horas_por_dia'] ?></td>
                        <td><?= $d['qhe'] ?></td>
                        <!-- Subsídios Base -->
                        <td>
                            <?php foreach ($d['subs_list'] as $subs): ?>
                                <?php
                                    $isObrigatorio = in_array($subs, ['noturno', 'horas_extras', 'risco', 'decimo_terceiro']);
                                    $valor = 0;
                                    if ($subs === 'noturno') {
                                        $valor = isset($d['valor_subsidio_noturno']) ? $d['valor_subsidio_noturno'] : 0;
                                    } elseif ($subs === 'horas_extras') {
                                        $valor = isset($d['valor_total_phe']) ? $d['valor_total_phe'] : 0;
                                    } elseif ($subs === 'risco') {
                                        $valor = isset($subs_valores['risco']) ? floatval($subs_valores['risco']) : 0;
                                    } elseif ($subs === 'decimo_terceiro') {
                                        $valor = isset($d['valor_decimo_terceiro']) ? $d['valor_decimo_terceiro'] : 0;
                                    } else {
                                        $valor = isset($subs_valores[$subs]) ? floatval($subs_valores[$subs]) : 0;
                                    }
                                    $valor_formatado = number_format($valor, 2, ',', '.');
                                ?>
                                <span class="subs-box subs-tooltip <?= $isObrigatorio ? 'subs-obrigatorio' : '' ?>"
                                      data-subsidio="<?= str_replace('_', ' ', htmlspecialchars(ucfirst($subs))) ?>"
                                      data-valor="<?= $valor_formatado ?>"
                                      data-tipo="<?= $isObrigatorio ? 'obrigatorio' : 'opcional' ?>">
                                    <?= str_replace('_', ' ', htmlspecialchars(ucfirst($subs))) ?>
                                </span>
                            <?php endforeach; ?>
                        </td>
                        <td><?= number_format($d['total_subs'], 2, ',', '.') ?></td>
                        <!-- Horas Extras -->
                        <td><?= $d['horas_extras'] ?></td>
                        <td><?= number_format($d['valor_hora_extra'], 2, ',', '.') ?></td>
                        <td><?= number_format($d['valor_total_phe'], 2, ',', '.') ?></td>
                        <!-- Horas Noturnas -->
                        <td><?= $d['horas_noturnas'] ?></td>
                        <td><?= number_format($d['salario_hora'] * (1 + ($percentual_noturno / 100)), 2, ',', '.') ?></td>
                        <td><?= number_format($d['valor_subsidio_noturno'], 2, ',', '.') ?></td>
                        <!-- Total Bruto -->
                        <td style="font-weight:bold;">
                            <?php
                            $cor = '';
                            if ($d['salario_iliquido'] <= 0.01) {
                                $cor = 'color:#e74c3c;'; // vermelho
                            } elseif ($d['salario_iliquido'] >= $d['salario_base'] - 0.01) {
                                $cor = 'color:#3EB489;'; // verde
                            } else {
                                $cor = 'color:#e67e22;'; // laranja
                            }
                            $tooltip = '';
                            if (!empty($d['detalhes_ausencias'])) {
                                $tooltip .= 'Ausências no mês:<br>';
                                foreach ($d['detalhes_ausencias'] as $aus) {
                                    $tipo = htmlspecialchars($aus['tipo']);
                                    $dias = intval($aus['dias']);
                                    $desc = '';
                                    if ($aus['desconto_salario'] <= 0.01) {
                                        $desc = '0% desconto';
                                    } elseif ($aus['desconto_salario'] >= $d['salario_base'] - 0.01) {
                                        $desc = '100% desconto';
                                    } else {
                                        $desc = number_format(100 * $aus['desconto_salario'] / $d['salario_base'], 0) . '% desconto';
                                    }
                                    $tooltip .= "+ $tipo: $dias dias — $desc<br>";
                                }
                            } else {
                                $tooltip = 'Sem ausências impactando o salário.';
                            }
                            ?>
                            <span style="<?= $cor ?>">
                                <?= number_format($d['salario_iliquido'], 2, ',', '.') ?> Kz
                                <i class="fas fa-info-circle" 
                                   style="cursor:pointer;<?= $cor ?>margin-left:5px;"
                                   data-bs-toggle="tooltip" 
                                   data-bs-html="true"
                                   title="<?= $tooltip ?>"
                                   onclick="mostrarModalDetalhesAusencias(<?= htmlspecialchars(json_encode($d['detalhes_ausencias'])) ?>, '<?= htmlspecialchars($d['nome']) ?>', <?= $d['salario_base'] ?>)"></i>
                            </span>
                        </td>
                        <!-- Faltas/Ausências -->
                        <td style="color:#e74c3c; font-weight:600;">
                            <?= $d['faltas'] ?>
                        </td>
                        <td style="color:#3EB489; font-weight:600;">
                            <?php if ($d['ausencias_justificadas'] > 0): ?>
                                <span class="ausencias-tooltip" 
                                      data-bs-toggle="tooltip" 
                                      data-bs-html="true"
                                      title="<?php
                                        echo '<strong>Ausências Justificadas:</strong> ' . $d['ausencias_justificadas'] . ' dias<br>';
                                        if (!empty($d['ausencias_info'])) {
                                            echo '<br><strong>Detalhes:</strong><br>';
                                            foreach ($d['ausencias_info'] as $info) {
                                                echo '• ' . htmlspecialchars($info['tipo']) . ': ' . $info['dias'] . ' dias (' . $info['periodo'] . ')<br>';
                                            }
                                        }
                                      ?>">
                                    <?= $d['ausencias_justificadas'] ?>
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            <?php else: ?>
                                <?= $d['ausencias_justificadas'] ?>
                            <?php endif; ?>
                        </td>
                        <td style="color:#888; font-weight:600;">
                            <?= $d['faltas'] + $d['ausencias_justificadas'] ?>
                        </td>
                        <!-- Descontos -->
                        <td><?= number_format($d['iss'], 2, ',', '.') ?> Kz</td>
                        <td><?= number_format($d['desconto_faltas'], 2, ',', '.') ?> Kz</td>
                        <td><?= number_format($d['irt'], 2, ',', '.') ?> Kz</td>
                        <td><?= number_format($d['total_descontos'], 2, ',', '.') ?> Kz</td>
                        <!-- Resultado Final -->
                        <td><?= number_format($d['salario_liquido'], 2, ',', '.') ?> Kz</td>
                        <td style="text-align:center; display:flex; align-items:center; justify-content:center; height:100%;">
                            <button class="btn-comprovante" title="Gerar comprovante de pagamento" onclick="gerarComprovantePagamento('<?= $d['num_mecanografico'] ?>')">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal fade" id="modalFuncionariosSubsidio" tabindex="-1" aria-labelledby="modalFuncionariosSubsidioLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFuncionariosSubsidioLabel">Gerenciar Subsídio para Funcionários</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="lista-funcionarios-subsidio">
                        Carregando funcionários...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Detalhes dos Cálculos -->
    <div class="modal fade" id="modalDetalhesCalculo" tabindex="-1" aria-labelledby="modalDetalhesCalculoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalhesCalculoLabel">
                        <i class="fas fa-calculator"></i> Detalhes dos Cálculos Salariais
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="detalhes-calculo-content">
                        <!-- Conteúdo será preenchido via JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
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
    <script src="js/theme.js"></script>
    <!-- <script src="js/timer.js"></script> -->
    
    <!-- SCRIPT DO BOOTSTRAP DEVE VIR ANTES DO SCRIPT DO MODAL -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // SCRIPT DO MODAL MOVIMOVido PARA CÁ

        // Função para atualizar o formulário automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            // Seleciona todos os selects do formulário
            const selects = document.querySelectorAll('.filter-select');
            
            // Adiciona o evento change para cada select
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    // Encontra o formulário pai e submete
                    this.closest('form').submit();
                });
            });
        });

        // Função para mostrar detalhes dos cálculos
        function mostrarDetalhesCalculo(explicacoes, detalhesAusencias) {
            const modal = document.getElementById('modalDetalhesCalculo');
            const content = document.getElementById('detalhes-calculo-content');
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-money-bill-wave"></i> Cálculo do Salário Base</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Salário Base Original:</strong></td>
                                        <td class="text-end">${formatarMoeda(explicacoes.salario_base_original)}</td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td><strong>Desconto por Ausências:</strong></td>
                                        <td class="text-end text-danger">-${formatarMoeda(explicacoes.desconto_ausencias)}</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>Salário Base Ajustado:</strong></td>
                                        <td class="text-end"><strong>${formatarMoeda(explicacoes.salario_base_ajustado)}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-gift"></i> Cálculo dos Subsídios</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Subsídios Originais:</strong></td>
                                        <td class="text-end">${formatarMoeda(explicacoes.total_subs_original)}</td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td><strong>Desconto por Ausências:</strong></td>
                                        <td class="text-end text-danger">-${formatarMoeda(explicacoes.desconto_subsidios_ausencias)}</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>Subsídios Ajustados:</strong></td>
                                        <td class="text-end"><strong>${formatarMoeda(explicacoes.total_subs_ajustado)}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-plus-circle"></i> Adições ao Salário</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Horas Extras:</strong></td>
                                        <td class="text-end">${formatarMoeda(explicacoes.horas_extras_valor)}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Subsídio Noturno:</strong></td>
                                        <td class="text-end">${formatarMoeda(explicacoes.subsidio_noturno_valor)}</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>Salário Ilíquido:</strong></td>
                                        <td class="text-end"><strong>${formatarMoeda(explicacoes.salario_iliquido)}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="fas fa-minus-circle"></i> Descontos</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>ISS (3%):</strong></td>
                                        <td class="text-end text-danger">-${formatarMoeda(explicacoes.iss_valor)}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Faltas (${explicacoes.faltas_nao_justificadas} dias):</strong></td>
                                        <td class="text-end text-danger">-${formatarMoeda(explicacoes.desconto_faltas)}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>IRT:</strong></td>
                                        <td class="text-end text-danger">-${formatarMoeda(explicacoes.irt_valor)}</td>
                                    </tr>
                                    <tr class="table-danger">
                                        <td><strong>Total Descontos:</strong></td>
                                        <td class="text-end"><strong>-${formatarMoeda(explicacoes.total_descontos)}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-calendar-times"></i> Detalhes das Ausências</h6>
                            </div>
                            <div class="card-body">
            `;

            if (detalhesAusencias && detalhesAusencias.length > 0) {
                detalhesAusencias.forEach((ausencia, index) => {
                    html += `
                        <div class="alert alert-info mb-3">
                            <h6 class="alert-heading">${ausencia.explicacao.titulo}</h6>
                            <p class="mb-2">${ausencia.explicacao.explicacao}</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <small><strong>Cálculo Salário:</strong> ${ausencia.explicacao.calculo_salario}</small>
                                </div>
                                <div class="col-md-6">
                                    <small><strong>Cálculo Subsídios:</strong> ${ausencia.explicacao.calculo_subsidios}</small>
                                </div>
                            </div>
                            <hr class="my-2">
                            <small class="text-muted"><strong>Base Legal:</strong> ${ausencia.explicacao.base_legal}</small>
                        </div>
                    `;
                });
            } else {
                html += '<p class="text-muted">Nenhuma ausência detalhada encontrada.</p>';
            }

            html += `
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-success">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle"></i> Salário Líquido Final: 
                                <strong>${formatarMoeda(explicacoes.salario_liquido)}</strong>
                            </h5>
                        </div>
                    </div>
                </div>
            `;

            content.innerHTML = html;
            
            // Mostrar o modal
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }

        // Função para formatar moeda
        function formatarMoeda(valor) {
            return new Intl.NumberFormat('pt-AO', {
                style: 'currency',
                currency: 'AOA',
                minimumFractionDigits: 2
            }).format(valor);
        }

        // Inicializar tooltips do Bootstrap 5
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        function mostrarModalDetalhesAusencias(detalhes, nome, salarioBase) {
            let html = `<h5>Detalhes das Ausências de <b>${nome}</b></h5>`;
            if (!detalhes || detalhes.length === 0) {
                html += '<p>Sem ausências impactando o salário neste mês.</p>';
            } else {
                html += '<ul>';
                detalhes.forEach(aus => {
                    let desc = '';
                    if (aus.desconto_salario <= 0.01) {
                        desc = '0% desconto';
                    } else if (aus.desconto_salario >= salarioBase - 0.01) {
                        desc = '100% desconto';
                    } else {
                        desc = Math.round(100 * aus.desconto_salario / salarioBase) + '% desconto';
                    }
                    html += `<li><b>${aus.tipo}</b>: ${aus.dias} dias — ${desc}<br><small>${aus.explicacao.explicacao}</small></li>`;
                });
                html += '</ul>';
            }
            document.getElementById('modalDetalhesAusenciasBody').innerHTML = html;
            let modal = new bootstrap.Modal(document.getElementById('modalDetalhesAusencias'));
            modal.show();
        }

        function gerarComprovantePagamento(numMecanografico) {
            const mesReferencia = '<?= $mes_referencia ?>';
            const empresaId = '<?= $empresa_id ?>';
            const url = `gerar_comprovante.php?num_mecanografico=${encodeURIComponent(numMecanografico)}&mes_referencia=${encodeURIComponent(mesReferencia)}&empresa_id=${encodeURIComponent(empresaId)}`;
            window.open(url, '_blank');
        }
    </script>
    <!-- Modal para detalhes das ausências -->
    <div class="modal fade" id="modalDetalhesAusencias" tabindex="-1" aria-labelledby="modalDetalhesAusenciasLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalDetalhesAusenciasLabel">Detalhes das Ausências</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body" id="modalDetalhesAusenciasBody">
            <!-- Conteúdo preenchido via JS -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
          </div>
        </div>
      </div>
    </div>
</body>
</html> 