<?php
include 'config.php';

// Script para atualizar políticas de ausência existentes
echo "<h2>Atualizando Políticas de Ausência</h2>";

// Buscar todas as empresas
$sql_empresas = "SELECT DISTINCT empresa_id FROM politicas_ausencia";
$result_empresas = mysqli_query($conn, $sql_empresas);

if (!$result_empresas) {
    echo "Erro ao buscar empresas: " . mysqli_error($conn);
    exit;
}

$empresas = [];
while ($row = mysqli_fetch_assoc($result_empresas)) {
    $empresas[] = $row['empresa_id'];
}

if (empty($empresas)) {
    echo "Nenhuma empresa encontrada com políticas de ausência.";
    exit;
}

// Políticas atualizadas conforme legislação trabalhista
$politicas_atualizadas = [
    'Férias' => [
        'salario_base_percentual' => 100.00,
        'subsidio_alimentacao' => false,
        'subsidio_transporte' => false,
        'outros_subsidios' => false,
        'dias_maximos_ano' => 22,
        'requer_aprovacao' => false,
        'requer_documento' => false,
        'descricao_politica' => 'Férias anuais - direito adquirido. Salário-base pago, subsídios conforme regulamento interno.'
    ],
    'Doença' => [
        'salario_base_percentual' => 100.00,
        'subsidio_alimentacao' => false,
        'subsidio_transporte' => false,
        'outros_subsidios' => false,
        'dias_maximos_ano' => 180,
        'requer_aprovacao' => true,
        'requer_documento' => true,
        'descricao_politica' => 'Baixa médica - salário-base pago até 6 meses, subsídios conforme política interna.'
    ],
    'Pessoal' => [
        'salario_base_percentual' => 0.00,
        'subsidio_alimentacao' => false,
        'subsidio_transporte' => false,
        'outros_subsidios' => false,
        'dias_maximos_ano' => 0,
        'requer_aprovacao' => true,
        'requer_documento' => false,
        'descricao_politica' => 'Licença pessoal sem remuneração - por solicitação do trabalhador.'
    ],
    'Formação' => [
        'salario_base_percentual' => 100.00,
        'subsidio_alimentacao' => true,
        'subsidio_transporte' => true,
        'outros_subsidios' => true,
        'dias_maximos_ano' => 0,
        'requer_aprovacao' => true,
        'requer_documento' => true,
        'descricao_politica' => 'Formação promovida pela empresa - remuneração integral mantida.'
    ],
    'Outro' => [
        'salario_base_percentual' => 0.00,
        'subsidio_alimentacao' => false,
        'subsidio_transporte' => false,
        'outros_subsidios' => false,
        'dias_maximos_ano' => 0,
        'requer_aprovacao' => true,
        'requer_documento' => true,
        'descricao_politica' => 'Outras licenças - sem remuneração, salvo acordo interno.'
    ]
];

$sql_update = "UPDATE politicas_ausencia SET 
    salario_base_percentual = ?, 
    subsidio_alimentacao = ?, 
    subsidio_transporte = ?, 
    outros_subsidios = ?, 
    dias_maximos_ano = ?, 
    requer_aprovacao = ?, 
    requer_documento = ?, 
    descricao_politica = ?
    WHERE empresa_id = ? AND tipo_ausencia = ?";

$stmt_update = mysqli_prepare($conn, $sql_update);

if (!$stmt_update) {
    echo "Erro ao preparar consulta de atualização: " . mysqli_error($conn);
    exit;
}

$total_atualizadas = 0;

foreach ($empresas as $empresa_id) {
    echo "<h3>Empresa ID: $empresa_id</h3>";
    
    foreach ($politicas_atualizadas as $tipo => $politica) {
        mysqli_stmt_bind_param($stmt_update, 
            "ddddiissis",
            $politica['salario_base_percentual'],
            $politica['subsidio_alimentacao'],
            $politica['subsidio_transporte'],
            $politica['outros_subsidios'],
            $politica['dias_maximos_ano'],
            $politica['requer_aprovacao'],
            $politica['requer_documento'],
            $politica['descricao_politica'],
            $empresa_id,
            $tipo
        );
        
        if (mysqli_stmt_execute($stmt_update)) {
            echo "✅ <strong>$tipo</strong> atualizada com sucesso<br>";
            $total_atualizadas++;
        } else {
            echo "❌ Erro ao atualizar <strong>$tipo</strong>: " . mysqli_stmt_error($stmt_update) . "<br>";
        }
    }
    echo "<br>";
}

mysqli_stmt_close($stmt_update);

echo "<h3>Resumo</h3>";
echo "Total de políticas atualizadas: <strong>$total_atualizadas</strong><br>";
echo "Empresas processadas: <strong>" . count($empresas) . "</strong><br>";

echo "<h3>Políticas Atualizadas Conforme Legislação:</h3>";
echo "<ul>";
echo "<li><strong>Férias:</strong> Salário 100%, subsídios conforme contrato</li>";
echo "<li><strong>Doença:</strong> Salário 100% (6 meses), subsídios podem ser suspensos</li>";
echo "<li><strong>Pessoal:</strong> Sem remuneração</li>";
echo "<li><strong>Formação:</strong> Salário 100% se promovida pela empresa</li>";
echo "<li><strong>Outro:</strong> Sem remuneração por regra</li>";
echo "</ul>";

echo "<p><strong>✅ Atualização concluída!</strong></p>";
?> 