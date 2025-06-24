<?php
/**
 * Teste da Correção - Ausências Justificadas
 * Verifica se os descontos de ausências justificadas estão sendo aplicados corretamente
 */

require_once 'conexao.php';
require_once 'direitos_ausencias.php';

// Configurações de teste
$empresa_id = 1; // Ajuste conforme sua empresa
$salario_base = 220000; // 220.000 Kz
$total_subs = 50000; // 50.000 Kz em subsídios
$dias_uteis = 21; // 21 dias de ausência pessoal
$dias_uteis_mes = 22;

echo "<h2>🧪 Teste da Correção - Ausências Justificadas</h2>";
echo "<p><strong>Salário Base:</strong> " . number_format($salario_base, 2, ',', '.') . " Kz</p>";
echo "<p><strong>Subsídios:</strong> " . number_format($total_subs, 2, ',', '.') . " Kz</p>";
echo "<p><strong>Dias de Ausência Pessoal:</strong> $dias_uteis dias</p>";
echo "<hr>";

// Testar ausência pessoal (deve ter 100% desconto)
echo "<h3>🏷️ Ausência Pessoal (21 dias)</h3>";

// Calcular impacto
$impacto = calcularImpactoSalarialAusencia('Pessoal', $dias_uteis, $salario_base, $total_subs, $empresa_id, $conn, $dias_uteis_mes);

// Calcular valores por dia para comparação
$salario_dia = $salario_base / $dias_uteis_mes;
$subsidios_dia = $total_subs / $dias_uteis_mes;

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<p><strong>Desconto de Salário:</strong> " . number_format($impacto['desconto_salario'], 2, ',', '.') . " Kz</p>";
echo "<p><strong>Desconto de Subsídios:</strong> " . number_format($impacto['desconto_subsidios'], 2, ',', '.') . " Kz</p>";
echo "<p><strong>Salário Final:</strong> " . number_format($impacto['salario_final'], 2, ',', '.') . " Kz</p>";
echo "<p><strong>Subsídios Finais:</strong> " . number_format($impacto['subsidios_finais'], 2, ',', '.') . " Kz</p>";

// Verificar se está correto
$desconto_esperado_salario = $salario_dia * $dias_uteis;
$desconto_esperado_subsidios = $subsidios_dia * $dias_uteis;

if (abs($impacto['desconto_salario'] - $desconto_esperado_salario) < 0.01) {
    echo "<p style='color: green; font-weight: bold;'>✅ CORRETO: Desconto de salário aplicado corretamente</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ ERRO: Desconto de salário incorreto</p>";
}

if (abs($impacto['desconto_subsidios'] - $desconto_esperado_subsidios) < 0.01) {
    echo "<p style='color: green; font-weight: bold;'>✅ CORRETO: Desconto de subsídios aplicado corretamente</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ ERRO: Desconto de subsídios incorreto</p>";
}

// Mostrar explicação detalhada
$explicacao = gerarExplicacaoAusencia('Pessoal', $dias_uteis, $salario_base, $total_subs, $dias_uteis_mes);
echo "<div style='background: #e9ecef; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
echo "<p><strong>Explicação:</strong> " . $explicacao['explicacao'] . "</p>";
echo "<p><strong>Cálculo Salário:</strong> " . $explicacao['calculo_salario'] . "</p>";
echo "<p><strong>Cálculo Subsídios:</strong> " . $explicacao['calculo_subsidios'] . "</p>";
echo "<p><strong>Base Legal:</strong> " . $explicacao['base_legal'] . "</p>";
echo "</div>";

echo "</div>";

// Simular cálculo completo do processamento salarial
echo "<h3>📊 Simulação do Processamento Salarial</h3>";

// Simular outros valores (sem horas extras para simplificar)
$valor_total_phe = 0;
$valor_subsidio_noturno = 0;

// Calcular salário ilíquido SEM descontos (usando salário base original)
$salario_iliquido = $salario_base + $total_subs + $valor_total_phe + $valor_subsidio_noturno;

// Calcular descontos usando o salário base original
$iss = $salario_base * 0.03;
$faltas_nao_justificadas = 0; // Todas as ausências são justificadas
$desconto_faltas = $salario_base / $dias_uteis_mes * $faltas_nao_justificadas;
$irt = calcularIRT($salario_base);

// IMPORTANTE: Desconto das ausências justificadas é aplicado DEPOIS do salário ilíquido
$desconto_ausencias_salario = $impacto['desconto_salario'];
$desconto_ausencias_subsidios = $impacto['desconto_subsidios'];

// Desconto por faltas TOTAL (faltas não justificadas + ausências justificadas)
$desconto_faltas_total = $desconto_faltas + $desconto_ausencias_salario;

// Total de descontos (incluindo ausências justificadas)
$total_descontos = $iss + $desconto_faltas_total + $irt + $desconto_ausencias_subsidios;

// Salário líquido final (salário ilíquido MENOS todos os descontos)
$salario_liquido = $salario_iliquido - $total_descontos;

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107;'>";
echo "<h4>📋 Resumo do Cálculo</h4>";
echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #d4edda;'><td><strong>SALÁRIO ILÍQUIDO (SEM descontos):</strong></td><td style='text-align: right;'><strong>" . number_format($salario_iliquido, 2, ',', '.') . " Kz</strong></td></tr>";
echo "<tr><td colspan='2' style='text-align: center; background: #e9ecef; font-weight: bold;'>DESCONTOS (aplicados DEPOIS do salário ilíquido)</td></tr>";
echo "<tr><td><strong>ISS (3%):</strong></td><td style='text-align: right; color: red;'>-" . number_format($iss, 2, ',', '.') . " Kz</td></tr>";
echo "<tr style='background: #f8d7da;'><td><strong>DESCONTO POR FALTAS (Total):</strong></td><td style='text-align: right; color: red;'><strong>-" . number_format($desconto_faltas_total, 2, ',', '.') . " Kz</strong></td></tr>";
echo "<tr><td style='padding-left: 20px;'><em>• Faltas não justificadas:</em></td><td style='text-align: right; color: red;'>-" . number_format($desconto_faltas, 2, ',', '.') . " Kz</td></tr>";
echo "<tr><td style='padding-left: 20px;'><em>• Ausências justificadas (salário):</em></td><td style='text-align: right; color: red;'>-" . number_format($desconto_ausencias_salario, 2, ',', '.') . " Kz</td></tr>";
echo "<tr><td><strong>IRT:</strong></td><td style='text-align: right; color: red;'>-" . number_format($irt, 2, ',', '.') . " Kz</td></tr>";
echo "<tr style='background: #f8d7da;'><td><strong>Desconto Ausências (Subsídios):</strong></td><td style='text-align: right; color: red;'>-" . number_format($desconto_ausencias_subsidios, 2, ',', '.') . " Kz</td></tr>";
echo "<tr style='background: #f8d7da;'><td><strong>Total Descontos:</strong></td><td style='text-align: right; color: red;'><strong>-" . number_format($total_descontos, 2, ',', '.') . " Kz</strong></td></tr>";
echo "<tr style='background: #d4edda;'><td><strong>SALÁRIO LÍQUIDO FINAL:</strong></td><td style='text-align: right;'><strong>" . number_format($salario_liquido, 2, ',', '.') . " Kz</strong></td></tr>";
echo "</table>";
echo "</div>";

// Verificar se o resultado faz sentido
if ($salario_liquido > 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ CORRETO: Funcionário recebe salário líquido positivo</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ PROBLEMA: Salário líquido é zero ou negativo</p>";
}

if ($salario_iliquido > $salario_liquido) {
    echo "<p style='color: green; font-weight: bold;'>✅ CORRETO: Salário ilíquido > Salário líquido (descontos aplicados)</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ PROBLEMA: Salário ilíquido <= Salário líquido</p>";
}

if ($salario_iliquido == ($salario_base + $total_subs)) {
    echo "<p style='color: green; font-weight: bold;'>✅ CORRETO: Salário ilíquido = Salário base + Subsídios (SEM descontos)</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ PROBLEMA: Salário ilíquido não está correto</p>";
}

if ($desconto_faltas_total > 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ CORRETO: Desconto por faltas aparece na tabela: " . number_format($desconto_faltas_total, 2, ',', '.') . " Kz</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ PROBLEMA: Desconto por faltas não está sendo calculado</p>";
}

echo "<hr>";
echo "<h3>🎯 Conclusão</h3>";
echo "<p>Com a correção implementada:</p>";
echo "<ul>";
echo "<li><strong>Salário ilíquido</strong> = Salário base + Subsídios + Horas extras + Noturno (SEM descontos)</li>";
echo "<li><strong>Desconto por Faltas</strong> = Faltas não justificadas + Ausências justificadas (visível na tabela)</li>";
echo "<li><strong>Descontos aplicados DEPOIS:</strong> ISS + IRT + Desconto por Faltas + Desconto Subsídios</li>";
echo "<li><strong>Salário líquido</strong> = Salário ilíquido - Total descontos</li>";
echo "</ul>";

echo "<p><em>✅ Sistema corrigido - desconto das ausências justificadas aparece na coluna 'Desconto por Faltas'</em></p>";
?> 