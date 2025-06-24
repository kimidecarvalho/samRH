<?php
/**
 * Script de teste para verificar as regras de ausências
 * Execute este script para testar se as correções estão funcionando
 */

require_once 'config.php';
require_once 'direitos_ausencias.php';

// Dados de teste
$empresa_id = 8; // Empresa de teste
$salario_base = 220000.00; // Salário base de teste
$total_subs = 50000.00; // Subsídios de teste
$dias_uteis = 5; // 5 dias úteis de ausência

echo "<h2>Teste das Regras de Ausências - Legislação Angolana</h2>";
echo "<p><strong>Salário Base:</strong> " . number_format($salario_base, 2, ',', '.') . " Kz</p>";
echo "<p><strong>Subsídios:</strong> " . number_format($total_subs, 2, ',', '.') . " Kz</p>";
echo "<p><strong>Dias de Ausência:</strong> $dias_uteis dias úteis</p>";
echo "<hr>";

// Testar cada tipo de ausência
$tipos_ausencia = ['Férias', 'Doença', 'Pessoal', 'Formação', 'Outro'];

foreach ($tipos_ausencia as $tipo) {
    echo "<h3>🏷️ $tipo</h3>";
    
    // Calcular impacto
    $impacto = calcularImpactoSalarialAusencia($tipo, $dias_uteis, $salario_base, $total_subs, $empresa_id, $conn);
    
    // Calcular valores por dia para comparação
    $salario_dia = $salario_base / 22;
    $subsidios_dia = $total_subs / 22;
    
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
            if ($impacto['desconto_salario'] > 0) {
                $correto = false;
                $observacao = "❌ ERRO: Férias não devem descontar salário";
            } else {
                $observacao = "✅ CORRETO: Férias mantêm salário integral";
            }
            break;
            
        case 'Doença':
            if ($impacto['desconto_salario'] > 0) {
                $correto = false;
                $observacao = "❌ ERRO: Doença não deve descontar salário";
            } else {
                $observacao = "✅ CORRETO: Doença mantém salário integral";
            }
            break;
            
        case 'Pessoal':
            $desconto_esperado = $salario_dia * $dias_uteis;
            if (abs($impacto['desconto_salario'] - $desconto_esperado) > 1) {
                $correto = false;
                $observacao = "❌ ERRO: Pessoal deve descontar salário por dia";
            } else {
                $observacao = "✅ CORRETO: Pessoal desconta salário por dia";
            }
            break;
            
        case 'Formação':
            if ($impacto['desconto_salario'] > 0) {
                $correto = false;
                $observacao = "❌ ERRO: Formação não deve descontar salário";
            } else {
                $observacao = "✅ CORRETO: Formação mantém salário integral";
            }
            break;
            
        case 'Outro':
            $desconto_esperado = $salario_dia * $dias_uteis;
            if (abs($impacto['desconto_salario'] - $desconto_esperado) > 1) {
                $correto = false;
                $observacao = "❌ ERRO: Outro deve descontar salário por dia";
            } else {
                $observacao = "✅ CORRETO: Outro desconta salário por dia";
            }
            break;
    }
    
    echo "<p style='color: " . ($correto ? 'green' : 'red') . "; font-weight: bold;'>$observacao</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>📊 Resumo da Legislação</h3>";
echo "<ul>";
echo "<li><strong>Férias:</strong> 100% salário, subsídios conforme contrato</li>";
echo "<li><strong>Doença:</strong> 100% salário, subsídios podem ser suspensos</li>";
echo "<li><strong>Pessoal:</strong> SEM remuneração - desconta salário e subsídios</li>";
echo "<li><strong>Formação:</strong> 100% remuneração se promovida pela empresa</li>";
echo "<li><strong>Outro:</strong> SEM remuneração por regra - desconta salário e subsídios</li>";
echo "</ul>";

echo "<p><em>Teste concluído. Verifique se todos os tipos estão marcados como ✅ CORRETO.</em></p>";
?> 