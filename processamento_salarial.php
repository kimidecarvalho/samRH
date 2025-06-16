<?php
// Inicia a sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'protect.php'; // Protege a página para usuários autenticados
include 'config.php'; // Conexão com o banco de dados

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

// Função para calcular horas noturnas
function calcularHorasNoturnas($hora_entrada, $hora_saida) {
    $entrada = strtotime($hora_entrada);
    $saida = strtotime($hora_saida);
    
    // Se a saída for no dia seguinte
    if ($saida < $entrada) {
        $saida = strtotime('+1 day', $saida);
    }
    
    $horas_noturnas = 0;
    $inicio_noturno = strtotime('20:00:00');
    $fim_noturno = strtotime('06:00:00');
    
    // Ajusta o fim do período noturno para o dia seguinte
    $fim_noturno = strtotime('+1 day', $fim_noturno);
    
    // Calcula as horas noturnas
    if ($entrada <= $inicio_noturno && $saida >= $fim_noturno) {
        // Trabalha todo o período noturno
        $horas_noturnas = 10; // 20h às 6h = 10 horas
    } else {
        // Calcula as horas parciais
        $inicio_periodo = max($entrada, $inicio_noturno);
        $fim_periodo = min($saida, $fim_noturno);
        
        if ($inicio_periodo < $fim_periodo) {
            $horas_noturnas = ($fim_periodo - $inicio_periodo) / 3600;
        }
    }
    
    return $horas_noturnas;
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
    $sql_horario = "SELECT * FROM horarios_funcionarios WHERE funcionario_id = ?";
    $stmt_horario = $conn->prepare($sql_horario);
    $stmt_horario->bind_param('i', $id_fun);
    $stmt_horario->execute();
    $horario = $stmt_horario->get_result()->fetch_assoc();
    $hora_entrada = $horario ? $horario['hora_entrada'] : '08:00:00';
    $hora_saida = $horario ? $horario['hora_saida'] : '16:00:00';
    $horas_por_dia = (strtotime($hora_saida) - strtotime($hora_entrada)) / 3600;
    $qhe = $dias_uteis_mes * $horas_por_dia;

    // Buscar registros de ponto do mês
    $sql_ponto = "SELECT * FROM registros_ponto WHERE funcionario_id = ? AND data LIKE ?";
    $mes_like = "$mes_referencia%";
    $stmt_ponto = $conn->prepare($sql_ponto);
    $stmt_ponto->bind_param('is', $id_fun, $mes_like);
    $stmt_ponto->execute();
    $result_ponto = $stmt_ponto->get_result();
    $faltas = 0;
    $horas_extras = 0;
    $dias_com_ponto = [];
    $hoje = date('Y-m-d');
    while ($p = $result_ponto->fetch_assoc()) {
        // Só considera pontos até hoje
        if ($p['data'] <= $hoje) {
            $dias_com_ponto[$p['data']] = true;
        }
        if ($p['hora_entrada'] && $p['hora_saida']) {
            $h_entrada = strtotime($p['hora_entrada']);
            $h_saida = strtotime($p['hora_saida']);
            $horas_trabalhadas = ($h_saida - $h_entrada) / 3600;
            if ($horas_trabalhadas > $horas_por_dia) {
                $horas_extras += ($horas_trabalhadas - $horas_por_dia);
            }
        }
    }
    // Faltas = dias úteis até hoje - dias com ponto
    $faltas = $dias_uteis_ate_hoje - count($dias_com_ponto);
    if ($faltas < 0) $faltas = 0;

    // Buscar subsídios do funcionário
    $sql_subs = "SELECT sp.nome, sp.valor_padrao, sf.valor FROM subsidios_funcionarios sf JOIN subsidios_padrao sp ON sf.subsidio_id = sp.id WHERE sf.funcionario_id = ? AND sf.ativo = 1";
    $stmt_subs = $conn->prepare($sql_subs);
    $stmt_subs->bind_param('i', $id_fun);
    $stmt_subs->execute();
    $result_subs = $stmt_subs->get_result();
    $subs_list = [];
    $total_subs = 0;
    while ($s = $result_subs->fetch_assoc()) {
        $subs_list[] = $s['nome'];
        $valor = floatval($s['valor']);
        if ($valor <= 0) {
            $valor = floatval($s['valor_padrao']);
        }
        $total_subs += $valor;
    }

    // Garantir que subsídios obrigatórios com valor > 0 apareçam na lista
    $obrigatorios = ['noturno', 'horas_extras', 'risco', 'decimo_terceiro'];
    $valores_obrigatorios = [
        'noturno' => isset($dados_salariais) ? (isset($valor_subsidio_noturno) ? $valor_subsidio_noturno : 0) : 0,
        'horas_extras' => isset($dados_salariais) ? (isset($valor_total_phe) ? $valor_total_phe : 0) : 0,
        'risco' => isset($subs_valores['risco']) ? floatval($subs_valores['risco']) : 0,
        'decimo_terceiro' => isset($valor_decimo_terceiro) ? $valor_decimo_terceiro : 0
    ];
    foreach ($obrigatorios as $ob) {
        if (!in_array($ob, $subs_list) && $valores_obrigatorios[$ob] > 0.01) {
            $subs_list[] = $ob;
        }
        // Se o valor for zero, remove da lista (caso tenha sido adicionado por erro)
        if (($valores_obrigatorios[$ob] <= 0.01) && ($key = array_search($ob, $subs_list)) !== false) {
            unset($subs_list[$key]);
        }
    }

    // Cálculos intermediários
    $salario_base = floatval($f['salario_base']);
    $salario_dia = $dias_uteis_mes > 0 ? $salario_base / $dias_uteis_mes : 0;
    $salario_hora = ($dias_uteis_mes * $horas_por_dia) > 0 ? $salario_base / ($dias_uteis_mes * $horas_por_dia) : 0;
    
    // Buscar percentual do subsídio de horas extras
    $sql_he = "SELECT valor_padrao FROM subsidios_padrao WHERE empresa_id = ? AND nome = 'horas_extras'";
    $stmt_he = $conn->prepare($sql_he);
    $stmt_he->bind_param('i', $empresa_id);
    $stmt_he->execute();
    $result_he = $stmt_he->get_result();
    $he = $result_he->fetch_assoc();
    $percentual_he = $he ? floatval($he['valor_padrao']) : 50.00;
    
    $valor_hora_extra = $salario_hora * (1 + $percentual_he / 100);
    $valor_total_phe = $valor_hora_extra * $horas_extras;
    $salario_iliquido = $salario_base + $total_subs + $valor_total_phe;
    $iss = $salario_base * 0.03;
    $desconto_faltas = $salario_dia * $faltas;
    $irt = calcularIRT($salario_base);
    $total_descontos = $iss + $desconto_faltas + $irt;
    $salario_liquido = $salario_iliquido - $total_descontos;

    // Converter horas_extras para HH:MM
    $horas_extras_h = floor($horas_extras);
    $horas_extras_m = round(($horas_extras - $horas_extras_h) * 60);
    $horas_extras_fmt = sprintf('%d:%02d', $horas_extras_h, $horas_extras_m);

    // Calcular horas noturnas
    $horas_noturnas = calcularHorasNoturnas($hora_entrada, $hora_saida);
    
    // Buscar percentual do subsídio noturno
    $sql_noturno = "SELECT valor_padrao FROM subsidios_padrao WHERE empresa_id = ? AND nome = 'noturno'";
    $stmt_noturno = $conn->prepare($sql_noturno);
    $stmt_noturno->bind_param('i', $empresa_id);
    $stmt_noturno->execute();
    $result_noturno = $stmt_noturno->get_result();
    $noturno = $result_noturno->fetch_assoc();
    $percentual_noturno = $noturno ? floatval($noturno['valor_padrao']) : 35.00;
    
    $valor_subsidio_noturno = $horas_noturnas * $salario_hora * ($percentual_noturno / 100);
    
    // Calcular 13º mês
    $meses_trabalhados = 12; // Você pode ajustar isso baseado na data de admissão
    $valor_decimo_terceiro = calcularDecimoTerceiro($salario_base, $meses_trabalhados);

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

    $dados_salariais[] = [
        'num_mecanografico' => $f['num_mecanografico'],
        'nome' => $f['nome'],
        'foto' => $f['foto'],
        'cargo' => $cargo_nome,
        'salario_base' => $salario_base,
        'dias_uteis' => $dias_uteis_mes,
        'horas_por_dia' => $horas_por_dia,
        'qhe' => $qhe,
        'faltas' => $faltas,
        'horas_extras' => $horas_extras_fmt,
        'salario_dia' => $salario_dia,
        'salario_hora' => $salario_hora,
        'valor_hora_extra' => $valor_hora_extra,
        'subs_list' => $subs_list,
        'total_subs' => $total_subs,
        'valor_total_phe' => $valor_total_phe,
        'salario_iliquido' => $salario_iliquido,
        'iss' => $iss,
        'desconto_faltas' => $desconto_faltas,
        'irt' => $irt,
        'total_descontos' => $total_descontos,
        'salario_liquido' => $salario_liquido,
        'horas_noturnas' => $horas_noturnas,
        'valor_subsidio_noturno' => $valor_subsidio_noturno,
        'valor_decimo_terceiro' => $valor_decimo_terceiro
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
                        <th colspan="4">Informações do Colaborador</th>
                        <th colspan="3">Jornada de Trabalho</th>
                        <th colspan="3">Subsídios</th>
                        <th colspan="2">Horas Extras</th>
                        <th colspan="1">Total Bruto</th>
                        <th colspan="4">Descontos</th>
                        <th colspan="1">Resultado Final</th>
                    </tr>
                    <tr>
                        <!-- Informações do Colaborador -->
                        <th>Nº Matrícula</th>
                        <th>Nome</th>
                        <th>Cargo</th>
                        <th>Salário Base</th>
                        
                        <!-- Jornada de Trabalho -->
                        <th>Dias Úteis</th>
                        <th>Horas/Dia</th>
                        <th>Faltas (dias)</th>
                        
                        <!-- Subsídios -->
                        <th>Lista de Subsídios</th>
                        <th>Total Subsídios</th>
                        <th>Salário + Subsídios</th>
                        
                        <!-- Horas Extras -->
                        <th>Horas Extras</th>
                        <th>Valor Total PHE</th>
                        
                        <!-- Total Bruto -->
                        <th>Salário Ilíquido</th>
                        
                        <!-- Descontos -->
                        <th>ISS (3%)</th>
                        <th>Desconto Faltas</th>
                        <th>IRT</th>
                        <th>Total Descontos</th>
                        
                        <!-- Resultado Final -->
                        <th>Salário Líquido</th>
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
                        <td><?= $d['faltas'] ?></td>
                        
                        <!-- Subsídios -->
                        <td>
                            <?php foreach ($d['subs_list'] as $subs): ?>
                                <?php
                                    $isObrigatorio = in_array($subs, ['noturno', 'horas_extras', 'risco', 'decimo_terceiro']);
                                    $valor = 0;
                                    // Determina o valor do subsídio para o funcionário atual
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
                        <td><?= number_format($d['salario_base'] + $d['total_subs'], 2, ',', '.') ?></td>
                        
                        <!-- Horas Extras -->
                        <td><?= $d['horas_extras'] ?></td>
                        <td><?= number_format($d['valor_total_phe'], 2, ',', '.') ?></td>
                        
                        <!-- Total Bruto -->
                        <td><?= number_format($d['salario_iliquido'], 2, ',', '.') ?></td>
                        
                        <!-- Descontos -->
                        <td><?= number_format($d['iss'], 2, ',', '.') ?></td>
                        <td><?= number_format($d['desconto_faltas'], 2, ',', '.') ?></td>
                        <td><?= number_format($d['irt'], 2, ',', '.') ?></td>
                        <td><?= number_format($d['total_descontos'], 2, ',', '.') ?></td>
                        
                        <!-- Resultado Final -->
                        <td><?= number_format($d['salario_liquido'], 2, ',', '.') ?></td>
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
    <script src="js/timer.js"></script>
    <script>
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
    </script>
    <script>
    // Função para salvar percentual do subsídio
    async function salvarPercentualSubsidio(tipo, valor) {
        try {
            const response = await fetch('atualizar_subsidio_empresa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tipo: tipo,
                    valor_padrao: valor,
                    unidade: 'percentual'
                })
            });
            
            const data = await response.json();
            if (data.success) {
                mostrarMensagem('success', 'Percentual atualizado com sucesso!');
            } else {
                throw new Error(data.error || 'Erro ao atualizar percentual');
            }
        } catch (error) {
            handleError(error, 'Erro ao atualizar percentual do subsídio');
        }
    }

    // Atualizar os event listeners dos sliders
    document.querySelectorAll('.custom-slider').forEach(slider => {
        slider.addEventListener('change', function() {
            const tipo = this.id.replace('slider-', '');
            const valor = this.value;
            salvarPercentualSubsidio(tipo, valor);
        });
    });
    </script>
    <script>
    // Inicializar o modal
    const modalFuncionarios = new bootstrap.Modal(document.getElementById('modalFuncionariosSubsidio'));

    // Função para abrir o modal e buscar funcionários
    function abrirModalFuncionariosSubs(tipo) {
        const lista = document.getElementById('lista-funcionarios-subsidio');
        lista.innerHTML = 'Carregando funcionários...';
        
        // Nome amigável do subsídio
        const nomes = {
            alimentacao: 'Alimentação',
            transporte: 'Transporte',
            comunicacao: 'Comunicação',
            saude: 'Saúde / Seguro',
            ferias: 'Férias',
            decimo_terceiro: '13.º Mês',
            noturno: 'Nocturno / Turno',
            risco: 'Risco / Periculosidade'
        };
        const nomeSubsidio = nomes[tipo] || tipo;
        
        fetch('get_funcionarios_subsidio.php?tipo=' + tipo)
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.error || 'Erro ao buscar funcionários');
                }
                return data;
            })
            .then(data => {
                if (!data.success || !Array.isArray(data.funcionarios)) {
                    throw new Error('Formato de resposta inválido');
                }

                const funcionarios = data.funcionarios;
                if(funcionarios.length === 0) {
                    lista.innerHTML = '<div class="alert alert-info">Nenhum funcionário encontrado.</div>';
                    return;
                }
                
                let html = `<div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;'>
                    <div style='font-weight:600; color:#3EB489; font-size:1.15em;'>Gerenciando Subsídio: ${nomeSubsidio}</div>
                    <div style='display:flex; align-items:center; gap:10px;'>
                        <span style='color:#666; font-size:0.95em;'>Ativar/Desativar Todos</span>
                        <label class='toggle-switch'>
                            <input type='checkbox' id='toggle-todos' onchange='toggleTodosFuncionarios("${tipo}", this)'>
                            <span class='slider'></span>
                        </label>
                    </div>
                </div>`;
                html += `<table class="table table-striped table-hover" style="border-radius:10px;overflow:hidden;min-width:600px;">
                  <thead style="background:#f5f5f5;">
                    <tr>
                      <th style='padding:10px 12px;'>Nome</th>
                      <th style='padding:10px 12px;'>Matrícula</th>
                      <th style='padding:10px 12px;'>Cargo</th>
                      <th style='padding:10px 12px;'>Departamento</th>
                      <th style='padding:10px 12px;text-align:center;'>Subsídio</th>
                    </tr>
                  </thead>
                  <tbody>`;
                
                funcionarios.forEach(f => {
                    const terminado = f.estado && f.estado.toLowerCase() === 'terminado';
                    const ativo = f.subsidios && f.subsidios[tipo] === true;
                    html += `<tr style="background:${terminado ? '#ffebee' : (f.id%2===0?'#fafbfc':'#fff')}; color:${terminado ? '#c62828' : '#222'};"${terminado ? " title='Funcionário Terminado'" : ''}>
                        <td style='padding:8px 12px;'>${f.nome}</td>
                        <td style='padding:8px 12px;'>${f.num_mecanografico}</td>
                        <td style='padding:8px 12px;'>${f.cargo}</td>
                        <td style='padding:8px 12px;'>${f.departamento}</td>
                        <td style='padding:8px 12px;text-align:center;'>
                            <label class='toggle-switch' style='${terminado ? 'pointer-events:none;opacity:0.5;cursor:not-allowed;' : ''}'>
                                <input type='checkbox' onchange='toggleSubsidioFuncionario(${f.id}, "${tipo}", this)' ${ativo ? 'checked' : ''} ${terminado ? 'disabled' : ''}>
                                <span class='slider'></span>
                            </label>
                        </td>
                    </tr>`;
                });
                
                html += '</tbody></table>';
                lista.innerHTML = html;
            })
            .catch(error => {
                console.error('Erro:', error);
                lista.innerHTML = `<div class="alert alert-danger">
                    <strong>Erro ao carregar funcionários:</strong><br>
                    ${error.message}<br>
                    Por favor, tente novamente ou contate o suporte.
                </div>`;
            });
        
        modalFuncionarios.show();
    }
    </script>
    <script>
    // Função para carregar os valores dos subsídios
    async function carregarValoresSubsidios() {
        try {
            const response = await fetch('verificar_subsidios.php');
            const data = await response.json();
            
            if (data.success) {
                console.log('Subsídios carregados:', data.subsidios); // Debug
                
                // Atualizar os sliders com os valores do banco
                data.subsidios.forEach(subsidio => {
                    if (subsidio.nome === 'noturno') {
                        const slider = document.getElementById('slider-nocturno');
                        const valorInfo = document.getElementById('valor-nocturno-info');
                        if (slider && valorInfo) {
                            slider.value = subsidio.valor_padrao;
                            slider.dataset.id = subsidio.id; // Guardar o ID do subsídio
                            valorInfo.textContent = `${subsidio.valor_padrao}%`;
                            console.log('Slider noturno atualizado:', subsidio); // Debug
                        }
                    } else if (subsidio.nome === 'risco') {
                        const slider = document.getElementById('slider-risco');
                        const valorInfo = document.getElementById('valor-risco-info');
                        if (slider && valorInfo) {
                            slider.value = subsidio.valor_padrao;
                            slider.dataset.id = subsidio.id; // Guardar o ID do subsídio
                            valorInfo.textContent = `${subsidio.valor_padrao}%`;
                            console.log('Slider risco atualizado:', subsidio); // Debug
                        }
                    }
                });
            } else {
                throw new Error(data.error || 'Erro ao carregar subsídios');
            }
        } catch (error) {
            handleError(error, 'Erro ao carregar valores dos subsídios');
        }
    }

    // Carregar valores quando a página carregar
    document.addEventListener('DOMContentLoaded', carregarValoresSubsidios);
    </script>
    <script>
    // Evento para abrir modal ao clicar no card do subsídio
    document.querySelectorAll('.subsidio-card').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function(e) {
            // Verifica se o clique foi no toggle switch ou seus elementos
            const toggleSwitch = card.querySelector('.toggle-switch');
            if (toggleSwitch && (e.target === toggleSwitch || toggleSwitch.contains(e.target))) {
                return;
            }
            
            // Verifica se o clique foi no input de valor
            const inputValor = card.querySelector('.input-subsidio-mes');
            if (inputValor && (e.target === inputValor || inputValor.contains(e.target))) {
                return;
            }
            
            // Verifica se o clique foi no slider
            const slider = card.querySelector('.custom-slider');
            if (slider && (e.target === slider || slider.contains(e.target))) {
                return;
            }
            
            // Para subsídios opcionais, verifica se está ativo
            if (card.classList.contains('subsidio-opcional')) {
                const toggle = card.querySelector('.toggle-subsidio');
                if (!toggle.checked) {
                    mostrarMensagem('warning', 'Ative o subsídio primeiro para gerenciar os funcionários');
                    return;
                }
            }
            
            // Determina o tipo do subsídio
            let tipo;
            if (card.classList.contains('subsidio-opcional')) {
                tipo = card.getAttribute('data-subsidio');
            } else {
                // Para subsídios obrigatórios, pega o nome do primeiro span
                const nomeSpan = card.querySelector('span:first-child');
                if (nomeSpan) {
                    tipo = nomeSpan.textContent.trim().toLowerCase()
                        .replace('13.º mês', 'decimo_terceiro')
                        .replace('nocturno / turno', 'noturno')
                        .replace('risco / periculosidade', 'risco')
                        .replace('férias', 'ferias');
                }
            }
            
            if (tipo) {
                abrirModalFuncionariosSubs(tipo);
            }
        });
    });
    </script>
    <script>
    // Evento para switches dos subsídios opcionais
    document.querySelectorAll('.toggle-subsidio').forEach(toggle => {
        toggle.addEventListener('change', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const tipo = this.getAttribute('data-subsidio');
            const ativo = this.checked;
            
            try {
                const response = await fetch('atualizar_subsidio_empresa.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        ativo: ativo
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarMensagem('success', 'Subsídio atualizado com sucesso!');
                } else if (data.requires_confirmation) {
                    if (confirm(data.message)) {
                        const responseConfirm = await fetch('atualizar_subsidio_empresa.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                tipo: tipo,
                                ativo: ativo,
                                confirmed: true
                            })
                        });
                        
                        const dataConfirm = await responseConfirm.json();
                        
                        if (dataConfirm.success) {
                            mostrarMensagem('success', 'Subsídio atualizado com sucesso!');
                        } else {
                            throw new Error(dataConfirm.error || 'Erro ao atualizar subsídio');
                        }
                    } else {
                        this.checked = !this.checked;
                    }
                } else {
                    throw new Error(data.error || 'Erro ao atualizar subsídio');
                }
            } catch (error) {
                handleError(error, 'Erro ao atualizar subsídio');
                this.checked = !this.checked;
            }
        });
    });

    // Função para ativar/desativar subsídio para funcionário
    async function toggleSubsidioFuncionario(id, tipo, btn) {
        btn.disabled = true;
        
        try {
            const response = await fetch('atualizar_subsidio_funcionario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    funcionario_id: id,
                    tipo: tipo,
                    ativo: btn.checked
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                mostrarMensagem('success', 'Subsídio atualizado para o funcionário!');
            } else {
                throw new Error(data.error || 'Erro ao atualizar subsídio do funcionário');
            }
        } catch (error) {
            handleError(error, 'Erro ao atualizar subsídio do funcionário');
            btn.checked = !btn.checked; // Reverter o toggle em caso de erro
        } finally {
            btn.disabled = false;
        }
    }

    // Função para ativar/desativar todos os funcionários
    async function toggleTodosFuncionarios(tipo, btn) {
        const checkboxes = document.querySelectorAll(`#lista-funcionarios-subsidio input[type="checkbox"]:not([id="toggle-todos"])`);
        const ativo = btn.checked;
        
        // Desabilitar todos os checkboxes durante a operação
        checkboxes.forEach(cb => cb.disabled = true);
        btn.disabled = true;
        
        try {
            // Array para armazenar todas as promessas
            const promises = Array.from(checkboxes).map(async (checkbox) => {
                if (!checkbox.closest('tr').title) { // Ignora funcionários terminados
                    const id = checkbox.getAttribute('onchange').match(/\d+/)[0];
                    const response = await fetch('atualizar_subsidio_funcionario.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            funcionario_id: id,
                            tipo: tipo,
                            ativo: ativo
                        })
                    });
                    
                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.error || 'Erro ao atualizar subsídio do funcionário');
                    }
                    
                    checkbox.checked = ativo;
                }
            });
            
            // Aguarda todas as operações terminarem
            await Promise.all(promises);
            mostrarMensagem('success', 'Subsídios atualizados com sucesso!');
        } catch (error) {
            handleError(error, 'Erro ao atualizar subsídios');
            // Reverter o toggle em caso de erro
            btn.checked = !btn.checked;
            checkboxes.forEach(cb => {
                if (!cb.closest('tr').title) {
                    cb.checked = !ativo;
                }
            });
        } finally {
            // Reabilitar todos os checkboxes
            checkboxes.forEach(cb => cb.disabled = false);
            btn.disabled = false;
        }
    }
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tooltip dinâmica para subsídios (estilo rh_config.php)
        let tooltip = null;
        document.querySelectorAll('.subs-tooltip').forEach(function(el) {
            el.addEventListener('mouseenter', function(e) {
                if (!tooltip) {
                    tooltip = document.createElement('div');
                    tooltip.className = 'subs-tooltip-box';
                    document.body.appendChild(tooltip);
                }
                let unidade = el.dataset.tipo === 'opcional' ? 'Kz/mês' : 'Kz';
                tooltip.textContent = `${el.dataset.subsidio}: ${el.dataset.valor} ${unidade}`;
                tooltip.style.display = 'block';
            });
            el.addEventListener('mousemove', function(e) {
                if (tooltip) {
                    tooltip.style.left = (e.clientX + 15) + 'px';
                    tooltip.style.top = (e.clientY + 10) + 'px';
                }
            });
            el.addEventListener('mouseleave', function() {
                if (tooltip) {
                    tooltip.style.display = 'none';
                }
            });
        });
    });
    </script>
</body>
</html> 