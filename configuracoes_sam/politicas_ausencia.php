<?php
function inicializar_politicas_ausencia_empresa($empresa_id, $conn) {
    // Políticas atualizadas conforme legislação trabalhista
    $politicas_default = [
        [
            'tipo_ausencia' => 'Férias',
            'salario_base_percentual' => 100.00,
            'subsidio_alimentacao' => false, // Depende do contrato
            'subsidio_transporte' => false, // Depende do contrato
            'outros_subsidios' => false, // Depende do contrato
            'dias_maximos_ano' => 22,
            'requer_aprovacao' => false,
            'requer_documento' => false,
            'descricao_politica' => 'Férias anuais - direito adquirido. Salário-base pago, subsídios conforme regulamento interno.'
        ],
        [
            'tipo_ausencia' => 'Doença',
            'salario_base_percentual' => 100.00,
            'subsidio_alimentacao' => false, // Pode ser suspenso
            'subsidio_transporte' => false, // Pode ser suspenso
            'outros_subsidios' => false, // Podem ser suspensos
            'dias_maximos_ano' => 180, // 6 meses consecutivos
            'requer_aprovacao' => true,
            'requer_documento' => true,
            'descricao_politica' => 'Baixa médica - salário-base pago até 6 meses, subsídios conforme política interna.'
        ],
        [
            'tipo_ausencia' => 'Pessoal',
            'salario_base_percentual' => 0.00,
            'subsidio_alimentacao' => false,
            'subsidio_transporte' => false,
            'outros_subsidios' => false,
            'dias_maximos_ano' => 0, // Sem limite definido em lei
            'requer_aprovacao' => true,
            'requer_documento' => false,
            'descricao_politica' => 'Licença pessoal sem remuneração - por solicitação do trabalhador.'
        ],
        [
            'tipo_ausencia' => 'Formação',
            'salario_base_percentual' => 100.00,
            'subsidio_alimentacao' => true, // Se promovida pela empresa
            'subsidio_transporte' => true, // Se promovida pela empresa
            'outros_subsidios' => true, // Se promovida pela empresa
            'dias_maximos_ano' => 0, // Não especificado por lei
            'requer_aprovacao' => true,
            'requer_documento' => true,
            'descricao_politica' => 'Formação promovida pela empresa - remuneração integral mantida.'
        ],
        [
            'tipo_ausencia' => 'Outro',
            'salario_base_percentual' => 0.00, // Não pago por regra
            'subsidio_alimentacao' => false,
            'subsidio_transporte' => false,
            'outros_subsidios' => false,
            'dias_maximos_ano' => 0, // Sem limite fixado em lei
            'requer_aprovacao' => true,
            'requer_documento' => true,
            'descricao_politica' => 'Outras licenças - sem remuneração, salvo acordo interno.'
        ]
    ];

    // Verifica quais políticas já existem
    $sql_check = "SELECT tipo_ausencia FROM politicas_ausencia WHERE empresa_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check) {
        error_log("Erro ao preparar consulta de verificação de políticas: " . $conn->error);
        return;
    }
    $stmt_check->bind_param("i", $empresa_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $existentes = [];
    while ($row = $result->fetch_assoc()) {
        $existentes[] = $row['tipo_ausencia'];
    }
    $stmt_check->close();

    // Insere as políticas que faltam
    $sql_insert = "INSERT INTO politicas_ausencia (empresa_id, tipo_ausencia, salario_base_percentual, subsidio_alimentacao, subsidio_transporte, outros_subsidios, dias_maximos_ano, requer_aprovacao, requer_documento, descricao_politica) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    if (!$stmt_insert) {
        error_log("Erro ao preparar consulta de inserção de políticas: " . $conn->error);
        return;
    }
    foreach ($politicas_default as $politica) {
        if (in_array($politica['tipo_ausencia'], $existentes)) continue;
        $stmt_insert->bind_param(
            "isdddddiss",
            $empresa_id,
            $politica['tipo_ausencia'],
            $politica['salario_base_percentual'],
            $politica['subsidio_alimentacao'],
            $politica['subsidio_transporte'],
            $politica['outros_subsidios'],
            $politica['dias_maximos_ano'],
            $politica['requer_aprovacao'],
            $politica['requer_documento'],
            $politica['descricao_politica']
        );
        $stmt_insert->execute();
    }
    $stmt_insert->close();
}

function obter_politica_ausencia($empresa_id, $tipo_ausencia, $conn) {
    $sql = "SELECT * FROM politicas_ausencia WHERE empresa_id = ? AND tipo_ausencia = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Erro ao preparar consulta de política: " . $conn->error);
        return null;
    }
    $stmt->bind_param("is", $empresa_id, $tipo_ausencia);
    $stmt->execute();
    $result = $stmt->get_result();
    $politica = $result->fetch_assoc();
    $stmt->close();
    return $politica;
}

function calcular_impacto_salarial_ausencia($tipo_ausencia, $dias_uteis, $salario_base, $politica) {
    if (!$politica) {
        return [
            'salario_final' => 0,
            'percentual_pago' => 0,
            'subsidios_pagos' => []
        ];
    }
    
    $percentual_pago = $politica['salario_base_percentual'];
    $salario_final = ($salario_base * $percentual_pago / 100);
    
    $subsidios_pagos = [];
    if ($politica['subsidio_alimentacao']) $subsidios_pagos[] = 'alimentacao';
    if ($politica['subsidio_transporte']) $subsidios_pagos[] = 'transporte';
    if ($politica['outros_subsidios']) $subsidios_pagos[] = 'outros';
    
    return [
        'salario_final' => $salario_final,
        'percentual_pago' => $percentual_pago,
        'subsidios_pagos' => $subsidios_pagos
    ];
}

// Função para obter descrição detalhada da política
function obter_descricao_politica($tipo_ausencia) {
    $descricoes = [
        'Férias' => [
            'titulo' => '🏖️ FÉRIAS',
            'salario' => '✅ 100% pago',
            'alimentacao' => '❌ Depende do contrato ou prática da empresa',
            'transporte' => '❌ Depende do contrato ou prática da empresa',
            'outros' => '❌ Depende do contrato ou prática da empresa',
            'aprovacao' => '❌ Automática (direito adquirido)',
            'documento' => '❌ Não requerido',
            'limite' => '✅ 22 dias úteis por ano',
            'resultado' => 'Funcionário recebe o salário-base e os complementos obrigatórios. Subsídios variam conforme o regulamento interno.'
        ],
        'Doença' => [
            'titulo' => '🤒 DOENÇA',
            'salario' => '✅ 100% pago (até 6 meses)',
            'alimentacao' => '❌ Pode ser suspenso (depende do contrato)',
            'transporte' => '❌ Pode ser suspenso (depende do contrato)',
            'outros' => '❌ Podem ser suspensos (depende do contrato)',
            'aprovacao' => '✅ Manual (necessita validação)',
            'documento' => '✅ Atestado médico obrigatório',
            'limite' => '✅ 6 meses consecutivos com pagamento',
            'resultado' => 'Recebe o salário-base, mas pode perder subsídios conforme política interna.'
        ],
        'Pessoal' => [
            'titulo' => '👤 LICENÇA PESSOAL (sem remuneração)',
            'salario' => '❌ Não pago',
            'alimentacao' => '❌ Suspenso',
            'transporte' => '❌ Suspenso',
            'outros' => '❌ Suspensos',
            'aprovacao' => '✅ Manual (por solicitação do trabalhador)',
            'documento' => '❌ Não obrigatório (mas pode ser solicitado pela empresa)',
            'limite' => '❌ Sem limite definido em lei – depende de acordo com a empresa',
            'resultado' => 'Não recebe salário nem subsídios durante a ausência.'
        ],
        'Formação' => [
            'titulo' => '📚 FORMAÇÃO (promovida pela empresa)',
            'salario' => '✅ Pago (se promovida pela empresa)',
            'alimentacao' => '✅ Pago (se promovida pela empresa e previsto no contrato)',
            'transporte' => '✅ Pago (se promovida pela empresa e previsto no contrato)',
            'outros' => '✅ Pagos (se promovida pela empresa e previsto no contrato)',
            'aprovacao' => '✅ Manual (pela empresa)',
            'documento' => '✅ Comprovativo de participação na formação',
            'limite' => '❌ Não especificado por lei – depende do programa',
            'resultado' => 'Quando a formação é iniciativa da empresa, o funcionário mantém o salário e benefícios normalmente.',
            'nota' => 'ℹ️ Importante: Caso a formação seja solicitada pelo trabalhador, a lei prevê até 60 dias de licença sem remuneração, mediante condições.'
        ],
        'Outro' => [
            'titulo' => '❓ OUTRAS LICENÇAS',
            'salario' => '❌ Não pago (a menos que haja acordo interno)',
            'alimentacao' => '❌ Suspenso',
            'transporte' => '❌ Suspenso',
            'outros' => '❌ Suspensos',
            'aprovacao' => '✅ Manual',
            'documento' => '✅ Justificativa obrigatória',
            'limite' => '❌ Sem limite fixado em lei',
            'resultado' => 'Por regra, não há pagamento, salvo se houver previsão contratual (por exemplo, pagar 50% do salário).'
        ]
    ];
    
    return isset($descricoes[$tipo_ausencia]) ? $descricoes[$tipo_ausencia] : null;
}
?> 