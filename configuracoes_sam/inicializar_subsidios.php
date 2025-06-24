<?php

function inicializar_subsidios_empresa($empresa_id, $conn) {
    // Lista de todos os subsídios padrão que uma empresa deve ter
    $subsidios_default = [
        'alimentacao' => ['tipo' => 'opcional', 'valor_padrao' => 0.00, 'unidade' => 'valor_fixo', 'ativo' => 0],
        'transporte' => ['tipo' => 'opcional', 'valor_padrao' => 0.00, 'unidade' => 'valor_fixo', 'ativo' => 0],
        'comunicacao' => ['tipo' => 'opcional', 'valor_padrao' => 0.00, 'unidade' => 'valor_fixo', 'ativo' => 0],
        'saude' => ['tipo' => 'opcional', 'valor_padrao' => 0.00, 'unidade' => 'valor_fixo', 'ativo' => 0],
        'ferias' => ['tipo' => 'obrigatorio', 'valor_padrao' => 100.00, 'unidade' => 'percentual', 'ativo' => 1],
        'decimo_terceiro' => ['tipo' => 'obrigatorio', 'valor_padrao' => 100.00, 'unidade' => 'percentual', 'ativo' => 1],
        'noturno' => ['tipo' => 'obrigatorio', 'valor_padrao' => 35.00, 'unidade' => 'percentual', 'ativo' => 1],
        'horas_extras' => ['tipo' => 'obrigatorio', 'valor_padrao' => 50.00, 'unidade' => 'percentual', 'ativo' => 1],
        'risco' => ['tipo' => 'obrigatorio', 'valor_padrao' => 20.00, 'unidade' => 'percentual', 'ativo' => 1]
    ];

    // 1. Buscar todos os subsídios que já existem para a empresa
    $sql_check = "SELECT nome FROM subsidios_padrao WHERE empresa_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    if (!$stmt_check) {
        // Log ou trata o erro de preparação
        error_log("Erro ao preparar a consulta de verificação: " . $conn->error);
        return;
    }
    $stmt_check->bind_param("i", $empresa_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $existentes = [];
    while ($row = $result->fetch_assoc()) {
        $existentes[] = $row['nome'];
    }
    $stmt_check->close();

    // 2. Determinar quais subsídios estão faltando
    $faltando = array_diff_key($subsidios_default, array_flip($existentes));

    // 3. Se não faltar nenhum, não faz nada
    if (empty($faltando)) {
        return;
    }

    // --- MODO DE DEPURAÇÃO ATIVADO ---
    echo "<div style='background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; margin: 20px; border-radius: 5px;'>";
    echo "<h4>Relatório de Criação de Subsídios:</h4><ul>";

    $sql_insert = "INSERT INTO subsidios_padrao (empresa_id, nome, tipo, valor_padrao, unidade, ativo) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);

    if (!$stmt_insert) {
        echo "<li>ERRO FATAL: Falha ao preparar a consulta de inserção: " . htmlspecialchars($conn->error) . "</li>";
        echo "</ul></div>";
        return;
    }

    foreach ($faltando as $nome => $details) {
        // Tipos corretos: i=integer, s=string, d=double
        $stmt_insert->bind_param("issdsi",
            $empresa_id,
            $nome,
            $details['tipo'],
            $details['valor_padrao'],
            $details['unidade'],
            $details['ativo']
        );

        if ($stmt_insert->execute()) {
            echo "<li><span style='color:green; font-weight:bold;'>SUCESSO:</span> Subsídio '<strong>" . htmlspecialchars($nome) . "</strong>' foi criado.</li>";
        } else {
            // Verifica se o erro é de chave duplicada (caso a restrição UNIQUE exista)
            if ($conn->errno === 1062) {
                 echo "<li><span style='color:orange; font-weight:bold;'>AVISO:</span> Subsídio '<strong>" . htmlspecialchars($nome) . "</strong>' já existe na base de dados.</li>";
            } else {
                 echo "<li><span style='color:red; font-weight:bold;'>ERRO:</span> Falha ao criar '<strong>" . htmlspecialchars($nome) . "</strong>'. Causa: " . htmlspecialchars($stmt_insert->error) . " (Código: " . $conn->errno . ")</li>";
            }
        }
    }

    echo "</ul><p>Depuração concluída. Se vir erros, por favor, envie este relatório.</p></div>";
    // --- FIM DO MODO DE DEPURAÇÃO ---
    
    $stmt_insert->close();
}
?> 