<?php
// Funções centralizadas para cálculo de subsídios (horas extras, noturno, etc.)

if (!function_exists('calcularHorasExtrasFuncionario')) {
function calcularHorasExtrasFuncionario($registros_ponto, $jornada_diaria) {
    $horas_extras = 0;
    foreach ($registros_ponto as $p) {
        if (isset($p['hora_entrada'], $p['hora_saida']) && $p['hora_entrada'] && $p['hora_saida']) {
            $h_entrada = strtotime($p['hora_entrada']);
            $h_saida = strtotime($p['hora_saida']);
            $horas_trabalhadas = ($h_saida - $h_entrada) / 3600;
            if ($horas_trabalhadas > $jornada_diaria) {
                $horas_extras += ($horas_trabalhadas - $jornada_diaria);
            }
        }
    }
    return $horas_extras;
}}

if (!function_exists('calcularHorasNoturnasFuncionario')) {
function calcularHorasNoturnasFuncionario($registros_ponto) {
    $total_horas_noturnas = 0;
    
    // Log para debug - comentado para evitar saída antes do PDF
    // error_log("Iniciando cálculo de horas noturnas");
    // error_log("Número de registros de ponto: " . count($registros_ponto));
    
    foreach ($registros_ponto as $registro) {
        // Verificar se tem hora de entrada e saída
        if (empty($registro['hora_entrada']) || empty($registro['hora_saida'])) {
            // error_log("Registro sem hora de entrada ou saída: " . json_encode($registro));
            continue;
        }
        
        try {
            $entrada = new DateTime($registro['hora_entrada']);
            $saida = new DateTime($registro['hora_saida']);
            
            // Se a saída for no dia seguinte, ajustar
            if ($saida < $entrada) {
                $saida->modify('+1 day');
            }
            
            // Definir período noturno (20:00 às 06:00)
            $inicio_noturno = clone $entrada;
            $inicio_noturno->setTime(20, 0, 0);
            
            $fim_noturno = clone $entrada;
            $fim_noturno->setTime(6, 0, 0);
            $fim_noturno->modify('+1 day');
            
            // Se o registro começa antes do período noturno
            if ($entrada < $inicio_noturno) {
                $entrada = clone $inicio_noturno;
            }
            
            // Se o registro termina depois do período noturno
            if ($saida > $fim_noturno) {
                $saida = clone $fim_noturno;
            }
            
            // Calcular horas noturnas apenas se houver sobreposição com o período noturno
            if ($entrada < $fim_noturno && $saida > $inicio_noturno) {
                $intervalo = $entrada->diff($saida);
                $horas = $intervalo->h + ($intervalo->i / 60);
                
                // Só adiciona se realmente houver horas noturnas
                if ($horas > 0) {
                    $total_horas_noturnas += $horas;
                    
                    // Log para debug - comentado para evitar saída antes do PDF
                    // error_log("Registro: " . $registro['hora_entrada'] . " - " . $registro['hora_saida']);
                    // error_log("Horas noturnas calculadas: " . $horas);
                }
            }
        } catch (Exception $e) {
            // error_log("Erro ao processar registro: " . $e->getMessage());
            // error_log("Registro: " . json_encode($registro));
            continue;
        }
    }
    
    // Log para debug - comentado para evitar saída antes do PDF
    // error_log("Total de horas noturnas: " . $total_horas_noturnas);
    
    return $total_horas_noturnas;
}}

if (!function_exists('calcularHorasNoturnas')) {
function calcularHorasNoturnas($hora_entrada, $hora_saida) {
    $entrada = strtotime($hora_entrada);
    $saida = strtotime($hora_saida);
    if ($saida < $entrada) {
        $saida = strtotime('+1 day', $saida);
    }
    $horas_noturnas = 0;
    $inicio_noturno = strtotime('20:00:00');
    $fim_noturno = strtotime('06:00:00');
    $fim_noturno = strtotime('+1 day', $fim_noturno);
    if ($entrada <= $inicio_noturno && $saida >= $fim_noturno) {
        $horas_noturnas = 10; // 20h às 6h = 10 horas
    } else {
        $inicio_periodo = max($entrada, $inicio_noturno);
        $fim_periodo = min($saida, $fim_noturno);
        if ($inicio_periodo < $fim_periodo) {
            $horas_noturnas = ($fim_periodo - $inicio_periodo) / 3600;
        }
    }
    return max(0, $horas_noturnas);
}}

if (!function_exists('calcularValorHoraExtra')) {
function calcularValorHoraExtra($salario_base, $percentual, $jornada_diaria) {
    // Calcular valor da hora normal baseado na jornada real do funcionário
    $dias_uteis = 22; // Média de dias úteis no mês
    $valor_hora_normal = $salario_base / ($jornada_diaria * $dias_uteis);
    return $valor_hora_normal * (1 + $percentual / 100);
}}

if (!function_exists('calcularValorTotalHorasExtras')) {
function calcularValorTotalHorasExtras($valor_hora_extra, $horas_extras) {
    return $valor_hora_extra * $horas_extras;
}}

if (!function_exists('calcularValorNoturno')) {
function calcularValorNoturno($salario_base, $horas_noturnas, $percentual, $jornada_diaria, $funcionario_id) {
    // Log para debug - comentado para evitar saída antes do PDF
    // error_log("Iniciando cálculo do valor noturno");
    // error_log("Salário base: $salario_base");
    // error_log("Horas noturnas: $horas_noturnas");
    // error_log("Percentual: $percentual");
    // error_log("Jornada diária: $jornada_diaria");
    
    // Calcular valor da hora normal
    $valor_hora_normal = $salario_base / ($jornada_diaria * 22); // 22 dias úteis no mês
    
    // Calcular valor da hora noturna
    $valor_hora_noturna = $valor_hora_normal * (1 + ($percentual / 100));
    
    // Calcular valor total
    $valor_total = $valor_hora_noturna * $horas_noturnas;
    
    // Log para debug - comentado para evitar saída antes do PDF
    // error_log("Valor hora normal: $valor_hora_normal");
    // error_log("Valor hora noturna: $valor_hora_noturna");
    // error_log("Valor total: $valor_total");
    
    return $valor_total;
}} 