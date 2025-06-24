<?php
require_once 'config.php';
require_once 'direitos_ausencias.php';

echo "<h2>Diagnóstico - Funcionário Josilde Costa</h2>";

// Buscar dados do funcionário Josilde Costa
$sql = "SELECT * FROM funcionario WHERE nome LIKE '%Josilde%' OR nome LIKE '%Costa%'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $funcionario = $result->fetch_assoc();
    echo "<h3>Dados do Funcionário:</h3>";
    echo "<p><strong>ID:</strong> " . $funcionario['id_fun'] . "</p>";
    echo "<p><strong>Nome:</strong> " . $funcionario['nome'] . "</p>";
    echo "<p><strong>Salário Base:</strong> " . number_format($funcionario['salario_base'], 2, ',', '.') . " Kz</p>";
    echo "<p><strong>Empresa ID:</strong> " . $funcionario['empresa_id'] . "</p>";
    
    $funcionario_id = $funcionario['id_fun'];
    $empresa_id = $funcionario['empresa_id'];
    $salario_base = $funcionario['salario_base'];
    
    // Verificar ausências
    echo "<h3>Ausências Registradas:</h3>";
    $sql_ausencias = "SELECT * FROM ausencias WHERE funcionario_id = ? AND empresa_id = ?";
    $stmt = $conn->prepare($sql_ausencias);
    $stmt->bind_param('ii', $funcionario_id, $empresa_id);
    $stmt->execute();
    $result_ausencias = $stmt->get_result();
    
    if ($result_ausencias->num_rows > 0) {
        while ($ausencia = $result_ausencias->fetch_assoc()) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
            echo "<p><strong>Tipo:</strong> " . $ausencia['tipo_ausencia'] . "</p>";
            echo "<p><strong>Período:</strong> " . $ausencia['data_inicio'] . " a " . $ausencia['data_fim'] . "</p>";
            echo "<p><strong>Dias Úteis:</strong> " . $ausencia['dias_uteis'] . "</p>";
            echo "<p><strong>Status:</strong> " . $ausencia['status_justificacao'] . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>Nenhuma ausência encontrada.</p>";
    }
    
    // Verificar política de ausência para "Pessoal"
    echo "<h3>Política de Ausência - Pessoal:</h3>";
    $sql_politica = "SELECT * FROM politicas_ausencia WHERE empresa_id = ? AND tipo_ausencia = 'Pessoal'";
    $stmt = $conn->prepare($sql_politica);
    $stmt->bind_param('i', $empresa_id);
    $stmt->execute();
    $result_politica = $stmt->get_result();
    $politica = $result_politica->fetch_assoc();
    
    if ($politica) {
        echo "<p><strong>Salário Base Percentual:</strong> " . $politica['salario_base_percentual'] . "%</p>";
        echo "<p><strong>Subsídio Alimentação:</strong> " . ($politica['subsidio_alimentacao'] ? 'Sim' : 'Não') . "</p>";
        echo "<p><strong>Dias Máximos:</strong> " . $politica['dias_maximos_ano'] . "</p>";
    } else {
        echo "<p>Política não encontrada.</p>";
    }
    
    // Simular cálculo de impacto
    echo "<h3>Simulação de Cálculo:</h3>";
    
    // Buscar ausências do mês atual
    $mes_atual = date('m');
    $ano_atual = date('Y');
    
    $sql_ausencias_mes = "SELECT * FROM ausencias 
                         WHERE funcionario_id = ? 
                         AND empresa_id = ? 
                         AND status_justificacao IN ('aprovada', 'pendente')
                         AND (
                             (MONTH(data_inicio) = ? AND YEAR(data_inicio) = ?) OR
                             (MONTH(data_fim) = ? AND YEAR(data_fim) = ?) OR
                             (data_inicio <= ? AND data_fim >= ?)
                         )";
    
    $data_inicio_mes = "$ano_atual-$mes_atual-01";
    $data_fim_mes = date('Y-m-t', strtotime($data_inicio_mes));
    
    $stmt = $conn->prepare($sql_ausencias_mes);
    $stmt->bind_param('iiiiiiss', $funcionario_id, $empresa_id, $mes_atual, $ano_atual, $mes_atual, $ano_atual, $data_inicio_mes, $data_fim_mes);
    $stmt->execute();
    $result_ausencias_mes = $stmt->get_result();
    
    $total_dias_ausencias = 0;
    $impacto_total = [
        'desconto_salario' => 0,
        'desconto_subsidios' => 0
    ];
    
    echo "<p><strong>Ausências do mês atual:</strong></p>";
    
    while ($ausencia = $result_ausencias_mes->fetch_assoc()) {
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
            
            $total_dias_ausencias += $dias_sobreposicao;
            
            echo "<div style='border: 1px solid #ccc; padding: 8px; margin: 3px 0;'>";
            echo "<p><strong>Tipo:</strong> " . $ausencia['tipo_ausencia'] . "</p>";
            echo "<p><strong>Dias no mês:</strong> $dias_sobreposicao</p>";
            echo "<p><strong>Período efetivo:</strong> " . $inicio_efetivo->format('d/m/Y') . " - " . $fim_efetivo->format('d/m/Y') . "</p>";
            
            // Calcular impacto
            $impacto = calcularImpactoSalarialAusencia(
                $ausencia['tipo_ausencia'], 
                $dias_sobreposicao, 
                $salario_base, 
                0, // sem subsídios por enquanto
                $empresa_id, 
                $conn,
                22 // dias úteis padrão
            );
            
            $impacto_total['desconto_salario'] += $impacto['desconto_salario'];
            $impacto_total['desconto_subsidios'] += $impacto['desconto_subsidios'];
            
            echo "<p><strong>Desconto Salário:</strong> " . number_format($impacto['desconto_salario'], 2, ',', '.') . " Kz</p>";
            echo "<p><strong>Desconto Subsídios:</strong> " . number_format($impacto['desconto_subsidios'], 2, ',', '.') . " Kz</p>";
            echo "</div>";
        }
    }
    
    echo "<h3>Resultado Final:</h3>";
    echo "<p><strong>Total de dias de ausência:</strong> $total_dias_ausencias</p>";
    echo "<p><strong>Salário Base Original:</strong> " . number_format($salario_base, 2, ',', '.') . " Kz</p>";
    echo "<p><strong>Desconto Total Salário:</strong> " . number_format($impacto_total['desconto_salario'], 2, ',', '.') . " Kz</p>";
    echo "<p><strong>Desconto Total Subsídios:</strong> " . number_format($impacto_total['desconto_subsidios'], 2, ',', '.') . " Kz</p>";
    
    $salario_ajustado = $salario_base - $impacto_total['desconto_salario'];
    echo "<p><strong>Salário Ajustado:</strong> " . number_format($salario_ajustado, 2, ',', '.') . " Kz</p>";
    
    if ($salario_ajustado <= 0) {
        echo "<p style='color: red; font-weight: bold;'>⚠️ PROBLEMA: Salário ajustado é zero ou negativo!</p>";
        echo "<p>Isso indica que o desconto está sendo maior que o salário base.</p>";
    }
    
} else {
    echo "<p>Funcionário Josilde Costa não encontrado.</p>";
}

echo "<hr>";
echo "<p><a href='processamento_salarial.php'>Voltar ao Processamento Salarial</a></p>";
?> 