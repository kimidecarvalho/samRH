<?php
/**
 * Teste da Legislação Angolana - Ausências
 * Verifica se os cálculos estão corretos conforme a legislação
 */

require_once 'conexao.php';
require_once 'direitos_ausencias.php';

// Configurações de teste
$empresa_id = 1; // Ajuste conforme sua empresa
$salario_base = 220000; // 220.000 Kz
$total_subs = 50000; // 50.000 Kz em subsídios
$dias_uteis = 5; // 5 dias de ausência
$dias_uteis_mes = 22;

echo "<h2>🧪 Teste da Legislação Angolana - Ausências</h2>";
echo "<p><strong>Salário Base:</strong> " . number_format($salario_base, 2, ',', '.') . " Kz</p>";
echo "<p><strong>Subsídios:</strong> " . number_format($total_subs, 2, ',', '.') . " Kz</p>";
echo "<p><strong>Dias de Ausência:</strong> $dias_uteis dias</p>";
echo "<hr>";

// Testar cada tipo de ausência
$tipos_ausencia = ['Férias', 'Doença', 'Pessoal', 'Formação', 'Outro'];

foreach ($tipos_ausencia as $tipo) {
    echo "<h3>🏷️ $tipo</h3>";
    
    // Calcular impacto
    $impacto = calcularImpactoSalarialAusencia($tipo, $dias_uteis, $salario_base, $total_subs, $empresa_id, $conn, $dias_uteis_mes);
    
    // Calcular valores por dia para comparação
    $salario_dia = $salario_base / $dias_uteis_mes;
    $subsidios_dia = $total_subs / $dias_uteis_mes;
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<p><strong>Desconto de Salário:</strong> " . number_format($impacto['desconto_salario'], 2, ',', '.') . " Kz</p>";
    echo "<p><strong>Desconto de Subsídios:</strong> " . number_format($impacto['desconto_subsidios'], 2, ',', '.') . " Kz</p>";
    echo "<p><strong>Salário Final:</strong> " . number_format($impacto['salario_final'], 2, ',', '.') . " Kz</p>";
    echo "<p><strong>Subsídios Finais:</strong> " . number_format($impacto['subsidios_finais'], 2, ',', '.') . " Kz</p>";
    
    // Verificar se está correto conforme legislação
    $correto = true;
    $observacao = "";
    
    switch ($tipo) {
        case 'Férias':
            if ($impacto['desconto_salario'] > 0.01) {
                $correto = false;
                $observacao = "❌ ERRO: Férias devem ter 0% desconto no salário";
            } else {
                $observacao = "✅ CORRETO: Férias com 0% desconto no salário";
            }
            break;
            
        case 'Doença':
            $desconto_esperado = ($salario_dia * $dias_uteis) * 0.5;
            if (abs($impacto['desconto_salario'] - $desconto_esperado) > 0.01) {
                $correto = false;
                $observacao = "❌ ERRO: Doença deve ter 50% desconto no salário";
            } else {
                $observacao = "✅ CORRETO: Doença com 50% desconto no salário";
            }
            break;
            
        case 'Pessoal':
            $desconto_esperado = $salario_dia * $dias_uteis;
            if (abs($impacto['desconto_salario'] - $desconto_esperado) > 0.01) {
                $correto = false;
                $observacao = "❌ ERRO: Pessoal deve ter 100% desconto no salário";
            } else {
                $observacao = "✅ CORRETO: Pessoal com 100% desconto no salário";
            }
            break;
            
        case 'Formação':
            if ($impacto['desconto_salario'] > 0.01) {
                $correto = false;
                $observacao = "❌ ERRO: Formação promovida pela empresa deve ter 0% desconto";
            } else {
                $observacao = "✅ CORRETO: Formação com 0% desconto no salário";
            }
            break;
            
        case 'Outro':
            $desconto_esperado = $salario_dia * $dias_uteis;
            if (abs($impacto['desconto_salario'] - $desconto_esperado) > 0.01) {
                $correto = false;
                $observacao = "❌ ERRO: Outro deve ter 100% desconto no salário";
            } else {
                $observacao = "✅ CORRETO: Outro com 100% desconto no salário";
            }
            break;
    }
    
    echo "<p style='color: " . ($correto ? 'green' : 'red') . "; font-weight: bold;'>$observacao</p>";
    
    // Mostrar explicação detalhada
    $explicacao = gerarExplicacaoAusencia($tipo, $dias_uteis, $salario_base, $total_subs, $dias_uteis_mes);
    echo "<div style='background: #e9ecef; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
    echo "<p><strong>Explicação:</strong> " . $explicacao['explicacao'] . "</p>";
    echo "<p><strong>Cálculo Salário:</strong> " . $explicacao['calculo_salario'] . "</p>";
    echo "<p><strong>Cálculo Subsídios:</strong> " . $explicacao['calculo_subsidios'] . "</p>";
    echo "<p><strong>Base Legal:</strong> " . $explicacao['base_legal'] . "</p>";
    echo "</div>";
    
    echo "</div>";
}

echo "<hr>";
echo "<h3>📋 Resumo da Legislação Angolana</h3>";
echo "<ul>";
echo "<li><strong>Férias:</strong> 100% salário + subsídios conforme contrato</li>";
echo "<li><strong>Doença:</strong> 50% salário nos primeiros 2 meses + subsídios não pagos</li>";
echo "<li><strong>Pessoal:</strong> 0% salário + subsídios não pagos</li>";
echo "<li><strong>Formação:</strong> 100% salário se promovida pela empresa</li>";
echo "<li><strong>Outro:</strong> 0% salário + subsídios não pagos</li>";
echo "</ul>";

echo "<p><em>✅ Sistema atualizado conforme Lei Geral do Trabalho de Angola</em></p>";
?> 