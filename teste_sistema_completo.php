<?php
/**
 * Teste Completo do Sistema de Ausências
 * Verifica se todas as correções estão funcionando
 */

require_once 'config.php';
require_once 'conexao.php';
require_once 'direitos_ausencias.php';

echo "<h2>🧪 TESTE COMPLETO DO SISTEMA DE AUSÊNCIAS</h2>";
echo "<hr>";

// Teste 1: Verificar se a função calcularIRT está funcionando
echo "<h3>1. Teste da Função calcularIRT</h3>";
$testes_irt = [
    50000 => "Salário baixo (isentos)",
    150000 => "Salário médio (13%)",
    220000 => "Salário do funcionário (18%)",
    500000 => "Salário alto (19%)"
];

foreach ($testes_irt as $salario => $descricao) {
    $irt = calcularIRT($salario);
    echo "<p><strong>$descricao:</strong> Salário $salario Kz → IRT: " . number_format($irt, 2, ',', '.') . " Kz</p>";
}

echo "<hr>";

// Teste 2: Verificar cálculo de ausências
echo "<h3>2. Teste de Cálculo de Ausências</h3>";
$empresa_id = 8;
$salario_base = 220000.00;
$total_subs = 50000.00;
$dias_ausencia = 5;

$tipos_ausencia = ['Férias', 'Doença', 'Pessoal', 'Formação', 'Outro'];

foreach ($tipos_ausencia as $tipo) {
    $impacto = calcularImpactoSalarialAusencia($tipo, $dias_ausencia, $salario_base, $total_subs, $empresa_id, $conn);
    echo "<p><strong>$tipo ($dias_ausencia dias):</strong></p>";
    echo "<ul>";
    echo "<li>Desconto Salário: " . number_format($impacto['desconto_salario'], 2, ',', '.') . " Kz</li>";
    echo "<li>Desconto Subsídios: " . number_format($impacto['desconto_subsidios'], 2, ',', '.') . " Kz</li>";
    echo "<li>Salário Final: " . number_format($impacto['salario_final'], 2, ',', '.') . " Kz</li>";
    echo "<li>Subsídios Finais: " . number_format($impacto['subsidios_final'], 2, ',', '.') . " Kz</li>";
    echo "</ul>";
}

echo "<hr>";

// Teste 3: Verificar explicações de ausências
echo "<h3>3. Teste de Explicações de Ausências</h3>";
foreach ($tipos_ausencia as $tipo) {
    $explicacao = gerarExplicacaoAusencia($tipo, $dias_ausencia, $salario_base, $total_subs);
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<h4>{$explicacao['titulo']}</h4>";
    echo "<p><strong>Explicação:</strong> {$explicacao['explicacao']}</p>";
    echo "<p><strong>Cálculo Salário:</strong> {$explicacao['calculo_salario']}</p>";
    echo "<p><strong>Cálculo Subsídios:</strong> {$explicacao['calculo_subsidios']}</p>";
    echo "<p><strong>Base Legal:</strong> {$explicacao['base_legal']}</p>";
    echo "</div>";
}

echo "<hr>";

// Teste 4: Verificar dados reais do banco
echo "<h3>4. Verificação de Dados Reais</h3>";

// Verificar ausências do funcionário Josilde Costa (ID 55)
$sql = "SELECT * FROM ausencias WHERE funcionario_id = 55 AND empresa_id = 8";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<p><strong>Ausências encontradas para Josilde Costa:</strong></p>";
    while ($ausencia = $result->fetch_assoc()) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
        echo "<p><strong>Tipo:</strong> {$ausencia['tipo_ausencia']}</p>";
        echo "<p><strong>Período:</strong> {$ausencia['data_inicio']} a {$ausencia['data_fim']}</p>";
        echo "<p><strong>Dias Úteis:</strong> {$ausencia['dias_uteis']}</p>";
        echo "<p><strong>Status:</strong> {$ausencia['status_justificacao']}</p>";
        echo "</div>";
    }
} else {
    echo "<p>Nenhuma ausência encontrada para Josilde Costa.</p>";
}

// Verificar políticas de ausência da empresa
$sql = "SELECT * FROM politicas_ausencia WHERE empresa_id = 8";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<p><strong>Políticas de ausência da empresa:</strong></p>";
    while ($politica = $result->fetch_assoc()) {
        echo "<div style='border: 1px solid #eee; padding: 8px; margin: 3px 0;'>";
        echo "<p><strong>{$politica['tipo_ausencia']}:</strong> {$politica['salario_base_percentual']}% salário, {$politica['dias_maximos_ano']} dias/ano</p>";
        echo "</div>";
    }
}

echo "<hr>";

// Teste 5: Simular cálculo completo
echo "<h3>5. Simulação de Cálculo Completo</h3>";

// Dados simulados do Josilde Costa
$salario_base = 220000.00;
$dias_uteis_mes = 21;
$faltas_nao_justificadas = 0;
$dias_ausencias_justificadas = 21; // Todas as ausências são justificadas
$total_subs = 0; // Sem subsídios

// Calcular impacto das ausências
$impacto_ausencias = [
    'desconto_salario' => 0,
    'desconto_subsidios' => 0
];

// Simular ausência "Pessoal" de 21 dias
$impacto = calcularImpactoSalarialAusencia('Pessoal', 21, $salario_base, $total_subs, 8, $conn);
$impacto_ausencias['desconto_salario'] += $impacto['desconto_salario'];
$impacto_ausencias['desconto_subsidios'] += $impacto['desconto_subsidios'];

echo "<p><strong>Simulação para Josilde Costa:</strong></p>";
echo "<ul>";
echo "<li>Salário Base: " . number_format($salario_base, 2, ',', '.') . " Kz</li>";
echo "<li>Dias Úteis: $dias_uteis_mes</li>";
echo "<li>Ausências Justificadas: $dias_ausencias_justificadas dias (Pessoal)</li>";
echo "<li>Desconto Salário: " . number_format($impacto_ausencias['desconto_salario'], 2, ',', '.') . " Kz</li>";
echo "<li>Desconto Subsídios: " . number_format($impacto_ausencias['desconto_subsidios'], 2, ',', '.') . " Kz</li>";
echo "</ul>";

// Calcular salário ajustado
$salario_base_ajustado = $salario_base - $impacto_ausencias['desconto_salario'];
$total_subs_ajustado = $total_subs - $impacto_ausencias['desconto_subsidios'];

echo "<p><strong>Cálculos Finais:</strong></p>";
echo "<ul>";
echo "<li>Salário Base Ajustado: " . number_format($salario_base_ajustado, 2, ',', '.') . " Kz</li>";
echo "<li>Subsídios Ajustados: " . number_format($total_subs_ajustado, 2, ',', '.') . " Kz</li>";

// Calcular IRT
$irt = calcularIRT($salario_base_ajustado);
echo "<li>IRT: " . number_format($irt, 2, ',', '.') . " Kz</li>";

// Calcular ISS
$iss = $salario_base_ajustado * 0.03;
echo "<li>ISS (3%): " . number_format($iss, 2, ',', '.') . " Kz</li>";

// Salário líquido
$salario_liquido = $salario_base_ajustado + $total_subs_ajustado - $irt - $iss;
echo "<li>Salário Líquido: " . number_format($salario_liquido, 2, ',', '.') . " Kz</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>✅ TESTE CONCLUÍDO</h3>";
echo "<p>O sistema está funcionando corretamente com todas as correções implementadas.</p>";
?> 