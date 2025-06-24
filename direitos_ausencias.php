<?php
/**
 * Sistema de Direitos de Ausências - Legislação Angolana
 * Implementa as regras específicas para cada tipo de ausência
 */

/**
 * Calcula o impacto salarial de uma ausência no processamento salarial
 * Implementa as regras específicas da legislação angolana para cada tipo de ausência
 */
function calcularImpactoSalarialAusencia($tipo_ausencia, $dias_uteis, $salario_base, $total_subs, $empresa_id, $conn, $dias_uteis_mes = 22) {
    // Buscar política da empresa para este tipo de ausência
    $sql = "SELECT * FROM politicas_ausencia WHERE empresa_id = ? AND tipo_ausencia = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $empresa_id, $tipo_ausencia);
    $stmt->execute();
    $result = $stmt->get_result();
    $politica = $result->fetch_assoc();
    
    if (!$politica) {
        // Política padrão se não existir - tratar como ausência não justificada
        return [
            'salario_final' => 0,
            'subsidios_finais' => 0,
            'desconto_salario' => $salario_base,
            'desconto_subsidios' => $total_subs
        ];
    }
    
    // Calcular o valor diário dos subsídios
    $dias_uteis_calculo = ($dias_uteis_mes > 0) ? $dias_uteis_mes : 22; // Evita divisão por zero
    $salario_dia = $salario_base / $dias_uteis_calculo;
    $subsidios_dia = $total_subs / $dias_uteis_calculo;
    
    // Calcular impacto baseado no tipo de ausência conforme legislação angolana
    switch ($tipo_ausencia) {
        case 'Férias':
            // Férias: 100% do salário base, subsídios conforme contrato
            $desconto_salario = 0; // Não desconta salário
            $desconto_subsidios = 0; // Não desconta subsídios
            break;
            
        case 'Doença':
            // Doença: 50% do salário nos primeiros 2 meses, depois responsabilidade INSS
            // Calcular desconto de 50% do salário
            $desconto_salario = ($salario_dia * $dias_uteis) * 0.5; // 50% desconto
            $desconto_subsidios = $subsidios_dia * $dias_uteis; // 100% desconto dos subsídios
            break;
            
        case 'Pessoal':
            // Licença pessoal: SEM remuneração - desconta salário e subsídios
            $desconto_salario = $salario_dia * $dias_uteis;
            $desconto_subsidios = $subsidios_dia * $dias_uteis;
            break;
            
        case 'Formação':
            // Formação: 100% remuneração se promovida pela empresa
            $desconto_salario = 0; // Não desconta salário
            $desconto_subsidios = 0; // Não desconta subsídios
            break;
            
        case 'Outro':
            // Outras licenças: SEM remuneração por regra
            $desconto_salario = $salario_dia * $dias_uteis;
            $desconto_subsidios = $subsidios_dia * $dias_uteis;
            break;
            
        default:
            // Tipo não reconhecido - tratar como ausência não justificada
            $desconto_salario = $salario_dia * $dias_uteis;
            $desconto_subsidios = $subsidios_dia * $dias_uteis;
    }
    
    // IMPORTANTE: A política da empresa pode sobrescrever a regra padrão
    // Se a política indicar um percentual específico, usar ele
    if ($politica['salario_base_percentual'] < 100) {
        $desconto_salario = ($salario_dia * $dias_uteis) * (1 - ($politica['salario_base_percentual'] / 100));
    } elseif ($politica['salario_base_percentual'] == 100) {
        // Se a política é 100%, garantir que não há desconto
        $desconto_salario = 0;
    }

    // Descontos de subsídios baseados na política da empresa
    if ($politica['subsidio_alimentacao'] == 0) {
        $desconto_subsidios = $subsidios_dia * $dias_uteis; // Desconta subsídios se política indicar
    } elseif ($politica['subsidio_alimentacao'] == 1) {
        $desconto_subsidios = 0; // Não desconta subsídios se política indicar
    }
    
    return [
        'salario_final' => $salario_base - $desconto_salario,
        'subsidios_finais' => $total_subs - $desconto_subsidios,
        'desconto_salario' => $desconto_salario,
        'desconto_subsidios' => $desconto_subsidios,
        'politica_aplicada' => $politica,
        'tipo_ausencia' => $tipo_ausencia,
        'dias_uteis' => $dias_uteis,
        'salario_dia' => $salario_dia,
        'subsidios_dia' => $subsidios_dia
    ];
}

/**
 * Calcula os direitos de uma ausência específica
 */
function calcularDireitosAusencia($tipo_ausencia, $dias_uteis, $salario_base, $subsidiados = [], $mes_doenca = 1, $justificada = true, $promovida_empresa = true) {
    $resultado = [
        'direito' => '',
        'remuneracao' => 0,
        'subsidiados' => [],
        'observacoes' => [],
        'base_legal' => []
    ];
    
    switch ($tipo_ausencia) {
        case 'Férias':
            $resultado = calcularDireitosFerias($dias_uteis, $salario_base, $subsidiados);
            break;
            
        case 'Doença':
            $resultado = calcularDireitosDoenca($dias_uteis, $salario_base, $subsidiados, $mes_doenca, $justificada);
            break;
            
        case 'Pessoal':
            $resultado = calcularDireitosPessoal($dias_uteis, $salario_base, $subsidiados);
            break;
            
        case 'Formação':
            $resultado = calcularDireitosFormacao($dias_uteis, $salario_base, $subsidiados, $promovida_empresa);
            break;
            
        case 'Outro':
            $resultado = calcularDireitosOutro($dias_uteis, $salario_base, $subsidiados);
            break;
            
        default:
            $resultado['direito'] = 'Tipo de ausência não reconhecido';
            $resultado['observacoes'][] = 'Consulte o departamento de RH para esclarecimentos';
    }
    
    return $resultado;
}

/**
 * Calcula direitos para FÉRIAS
 */
function calcularDireitosFerias($dias_uteis, $salario_base, $subsidiados) {
    $resultado = [
        'direito' => 'Férias Anuais - Direito Adquirido',
        'remuneracao' => $salario_base, // 100% do salário base
        'subsidiados' => [],
        'observacoes' => [],
        'base_legal' => [
            'Lei Geral do Trabalho - Artigo 123',
            'Direito adquirido após 12 meses de trabalho'
        ]
    ];
    
    // Verificar se excede o limite de 22 dias úteis por ano
    if ($dias_uteis > 22) {
        $resultado['observacoes'][] = 'Atenção: Excede o limite legal de 22 dias úteis por ano';
        $resultado['base_legal'][] = 'Limite máximo: 22 dias úteis anuais';
    }
    
    // Subsídios dependem do contrato ou prática da empresa
    if (in_array('alimentação', $subsidiados)) {
        $resultado['observacoes'][] = 'Subsídio de alimentação: Conforme regulamento interno';
    }
    if (in_array('transporte', $subsidiados)) {
        $resultado['observacoes'][] = 'Subsídio de transporte: Conforme regulamento interno';
    }
    
    $resultado['observacoes'][] = 'Aprovação automática - direito adquirido';
    $resultado['observacoes'][] = 'Não requer documentação específica';
    
    return $resultado;
}

/**
 * Calcula direitos para DOENÇA
 */
function calcularDireitosDoenca($dias_uteis, $salario_base, $subsidiados, $mes_doenca, $justificada) {
    $resultado = [
        'direito' => 'Baixa Médica',
        'remuneracao' => 0,
        'subsidiados' => [],
        'observacoes' => [],
        'base_legal' => [
            'Lei Geral do Trabalho - Artigo 145',
            '50% do salário nos primeiros 2 meses'
        ]
    ];
    
    if (!$justificada) {
        $resultado['direito'] = 'Baixa Médica - Sem Justificação';
        $resultado['observacoes'][] = 'ATENÇÃO: Ausência sem justificação médica';
        $resultado['observacoes'][] = 'Pode resultar em desconto salarial total';
        return $resultado;
    }
    
    // Verificar limite de 2 meses (60 dias úteis)
    if ($mes_doenca > 2) {
        $resultado['observacoes'][] = 'ATENÇÃO: Excede o limite de 2 meses com pagamento da empresa';
        $resultado['observacoes'][] = 'Após 2 meses, responsabilidade da Segurança Social (INSS)';
        $resultado['base_legal'][] = 'Após 2 meses, INSS assume responsabilidade';
    }
    
    // Salário base pago 50% nos primeiros 2 meses
    $resultado['remuneracao'] = $salario_base * 0.5; // 50% do salário
    
    // Subsídios não pagos durante doença
    $resultado['observacoes'][] = 'Subsídios não pagos durante período de doença';
    $resultado['observacoes'][] = 'Atestado médico obrigatório';
    $resultado['observacoes'][] = 'Aprovação manual necessária';
    $resultado['observacoes'][] = 'Limite: 2 meses consecutivos com pagamento da empresa';
    
    return $resultado;
}

/**
 * Calcula direitos para LICENÇA PESSOAL
 */
function calcularDireitosPessoal($dias_uteis, $salario_base, $subsidiados) {
    $resultado = [
        'direito' => 'Licença Pessoal - Sem Remuneração',
        'remuneracao' => 0, // Não há pagamento
        'subsidiados' => [], // Todos suspensos
        'observacoes' => [
            'Licença sem remuneração',
            'Todos os subsídios suspensos',
            'Aprovação manual necessária',
            'Documentação pode ser solicitada pela empresa'
        ],
        'base_legal' => [
            'Lei Geral do Trabalho - Artigo 150',
            'Licença por solicitação do trabalhador'
        ]
    ];
    
    $resultado['observacoes'][] = 'Sem limite definido em lei - depende de acordo com a empresa';
    
    return $resultado;
}

/**
 * Calcula direitos para FORMAÇÃO
 */
function calcularDireitosFormacao($dias_uteis, $salario_base, $subsidiados, $promovida_empresa) {
    $resultado = [
        'direito' => 'Formação Profissional',
        'remuneracao' => 0,
        'subsidiados' => [],
        'observacoes' => [],
        'base_legal' => [
            'Lei Geral do Trabalho - Artigo 155',
            'Formação promovida pela empresa'
        ]
    ];
    
    if ($promovida_empresa) {
        $resultado['direito'] = 'Formação Promovida pela Empresa';
        $resultado['remuneracao'] = $salario_base; // Salário integral
        $resultado['observacoes'][] = 'Remuneração integral mantida';
        $resultado['observacoes'][] = 'Todos os subsídios mantidos';
        $resultado['observacoes'][] = 'Comprovativo de participação obrigatório';
        $resultado['observacoes'][] = 'Aprovação manual pela empresa';
        
        // Adicionar subsídios que o funcionário tem
        if (in_array('alimentação', $subsidiados)) {
            $resultado['subsidiados'][] = 'alimentação';
        }
        if (in_array('transporte', $subsidiados)) {
            $resultado['subsidiados'][] = 'transporte';
        }
    } else {
        $resultado['direito'] = 'Formação Solicitada pelo Trabalhador';
        $resultado['observacoes'][] = 'Licença sem remuneração';
        $resultado['observacoes'][] = 'Máximo 60 dias conforme lei';
        $resultado['observacoes'][] = 'Todos os subsídios suspensos';
        $resultado['base_legal'][] = 'Licença sem remuneração até 60 dias';
    }
    
    return $resultado;
}

/**
 * Calcula direitos para OUTRAS LICENÇAS
 */
function calcularDireitosOutro($dias_uteis, $salario_base, $subsidiados) {
    $resultado = [
        'direito' => 'Outras Licenças',
        'remuneracao' => 0, // Por regra, não há pagamento
        'subsidiados' => [], // Todos suspensos
        'observacoes' => [
            'Por regra, sem remuneração',
            'Todos os subsídios suspensos',
            'Aprovação manual necessária',
            'Justificativa obrigatória',
            'Pode haver acordo interno para pagamento parcial'
        ],
        'base_legal' => [
            'Lei Geral do Trabalho - Disposições gerais',
            'Acordos internos da empresa'
        ]
    ];
    
    $resultado['observacoes'][] = 'Sem limite fixado em lei';
    $resultado['observacoes'][] = 'Depende de acordo interno ou política da empresa';
    
    return $resultado;
}

/**
 * Verifica se uma ausência está dentro dos limites legais
 */
function verificarLimitesAusencia($tipo_ausencia, $dias_uteis, $empresa_id, $conn, $funcionario_id = null) {
    // Buscar política da empresa
    $sql = "SELECT dias_maximos_ano FROM politicas_ausencia WHERE empresa_id = ? AND tipo_ausencia = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $empresa_id, $tipo_ausencia);
    $stmt->execute();
    $result = $stmt->get_result();
    $politica = $result->fetch_assoc();
    
    if (!$politica || $politica['dias_maximos_ano'] == 0) {
        return [
            'dentro_limite' => true,
            'limite_legal' => 0,
            'dias_utilizados' => 0,
            'dias_restantes' => 0
        ];
    }
    
    $limite_legal = $politica['dias_maximos_ano'];
    
    // Calcular dias já utilizados no ano atual
    $ano_atual = date('Y');
    $sql_utilizados = "SELECT SUM(dias_uteis) as total FROM ausencias 
                      WHERE funcionario_id = ? AND empresa_id = ? AND tipo_ausencia = ? 
                      AND YEAR(data_inicio) = ? AND status_justificacao IN ('aprovada', 'pendente')";
    $stmt_utilizados = $conn->prepare($sql_utilizados);
    $stmt_utilizados->bind_param('iisi', $funcionario_id, $empresa_id, $tipo_ausencia, $ano_atual);
    $stmt_utilizados->execute();
    $result_utilizados = $stmt_utilizados->get_result();
    $dados_utilizados = $result_utilizados->fetch_assoc();
    
    $dias_utilizados = $dados_utilizados['total'] ?: 0;
    $dias_restantes = max(0, $limite_legal - $dias_utilizados);
    $dentro_limite = ($dias_uteis <= $dias_restantes);
    
    return [
        'dentro_limite' => $dentro_limite,
        'limite_legal' => $limite_legal,
        'dias_utilizados' => $dias_utilizados,
        'dias_restantes' => $dias_restantes
    ];
}

/**
 * Gera relatório detalhado de direitos de ausência
 */
function gerarRelatorioDireitosAusencia($tipo_ausencia, $dias_uteis, $salario_base, $subsidiados, $empresa_id, $conn) {
    $direitos = calcularDireitosAusencia($tipo_ausencia, $dias_uteis, $salario_base, $subsidiados);
    $impacto = calcularImpactoSalarialAusencia($tipo_ausencia, $dias_uteis, $salario_base, 0, $empresa_id, $conn);
    $limites = verificarLimitesAusencia($tipo_ausencia, $dias_uteis, $empresa_id, $conn);
    
    $relatorio = [
        'tipo_ausencia' => $tipo_ausencia,
        'dias_uteis' => $dias_uteis,
        'direitos' => $direitos,
        'impacto_salarial' => $impacto,
        'limites' => $limites,
        'data_geracao' => date('Y-m-d H:i:s')
    ];
    
    return $relatorio;
}

/**
 * Gera explicação detalhada de uma ausência específica
 */
function gerarExplicacaoAusencia($tipo_ausencia, $dias_uteis, $salario_base, $total_subs, $dias_uteis_mes = 22) {
    $dias_uteis_calculo = ($dias_uteis_mes > 0) ? $dias_uteis_mes : 22;
    $salario_dia = $salario_base / $dias_uteis_calculo;
    $subsidios_dia = $total_subs / $dias_uteis_calculo;
    
    switch ($tipo_ausencia) {
        case 'Férias':
            return [
                'titulo' => 'Férias Anuais - Direito Adquirido',
                'explicacao' => "Férias de $dias_uteis dias úteis. Conforme legislação angolana, férias são remuneradas integralmente.",
                'calculo_salario' => "Salário: Não há desconto (100% remunerado)",
                'calculo_subsidios' => "Subsídios: Pagos se forem considerados remuneração permanente",
                'base_legal' => 'Lei Geral do Trabalho - Artigo 123'
            ];
            
        case 'Doença':
            $desconto_salario = ($salario_dia * $dias_uteis) * 0.5;
            $desconto_subsidios = $subsidios_dia * $dias_uteis;
            return [
                'titulo' => 'Baixa Médica - 50% Salário',
                'explicacao' => "Doença de $dias_uteis dias úteis. Nos primeiros 2 meses: 50% do salário base. Subsídios não pagos.",
                'calculo_salario' => "Desconto salário: " . number_format($salario_dia, 2) . " Kz/dia × $dias_uteis dias × 50% = " . number_format($desconto_salario, 2) . " Kz",
                'calculo_subsidios' => "Desconto subsídios: " . number_format($subsidios_dia, 2) . " Kz/dia × $dias_uteis dias = " . number_format($desconto_subsidios, 2) . " Kz (100% desconto)",
                'base_legal' => 'Lei Geral do Trabalho - Artigo 145'
            ];
            
        case 'Pessoal':
            $desconto_salario = $salario_dia * $dias_uteis;
            $desconto_subsidios = $subsidios_dia * $dias_uteis;
            return [
                'titulo' => 'Licença Pessoal - Sem Remuneração',
                'explicacao' => "Licença pessoal de $dias_uteis dias úteis. Sem remuneração conforme legislação.",
                'calculo_salario' => "Desconto salário: " . number_format($salario_dia, 2) . " Kz/dia × $dias_uteis dias = " . number_format($desconto_salario, 2) . " Kz (100% desconto)",
                'calculo_subsidios' => "Desconto subsídios: " . number_format($subsidios_dia, 2) . " Kz/dia × $dias_uteis dias = " . number_format($desconto_subsidios, 2) . " Kz (100% desconto)",
                'base_legal' => 'Lei Geral do Trabalho - Artigo 150'
            ];
            
        case 'Formação':
            return [
                'titulo' => 'Formação Profissional',
                'explicacao' => "Formação de $dias_uteis dias úteis promovida pela empresa. Remuneração integral mantida.",
                'calculo_salario' => "Salário: Não há desconto (100% remunerado)",
                'calculo_subsidios' => "Subsídios: Não há desconto (mantidos integralmente)",
                'base_legal' => 'Lei Geral do Trabalho - Artigo 155'
            ];
            
        case 'Outro':
            $desconto_salario = $salario_dia * $dias_uteis;
            $desconto_subsidios = $subsidios_dia * $dias_uteis;
            return [
                'titulo' => 'Outras Licenças - Sem Remuneração',
                'explicacao' => "Outra licença de $dias_uteis dias úteis. Sem remuneração por regra.",
                'calculo_salario' => "Desconto salário: " . number_format($salario_dia, 2) . " Kz/dia × $dias_uteis dias = " . number_format($desconto_salario, 2) . " Kz (100% desconto)",
                'calculo_subsidios' => "Desconto subsídios: " . number_format($subsidios_dia, 2) . " Kz/dia × $dias_uteis dias = " . number_format($desconto_subsidios, 2) . " Kz (100% desconto)",
                'base_legal' => 'Lei Geral do Trabalho - Disposições gerais'
            ];
            
        default:
            return [
                'titulo' => 'Ausência Não Reconhecida',
                'explicacao' => "Tipo de ausência não reconhecido. Consulte RH.",
                'calculo_salario' => "Desconto aplicado conforme política interna",
                'calculo_subsidios' => "Desconto aplicado conforme política interna",
                'base_legal' => 'Política interna da empresa'
            ];
    }
}
?> 