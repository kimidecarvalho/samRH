# Sistema de Ausências - Legislação Angolana

## ✅ CORREÇÕES IMPLEMENTADAS

### Problemas Identificados e Corrigidos:

1. **❌ IRT sendo zerado incorretamente** - CORRIGIDO
   - O IRT agora é calculado corretamente sobre o salário base ajustado
   - Não é mais zerado para ausências justificadas

2. **❌ Todas as ausências tratadas igualmente** - CORRIGIDO
   - Cada tipo de ausência agora tem tratamento específico conforme legislação
   - Implementado switch case com regras específicas para cada tipo

3. **❌ Ausência "Pessoal" não descontava salário** - CORRIGIDO
   - Agora desconta corretamente o salário base por dia
   - Implementado cálculo: `salario_dia * dias_uteis`

4. **❌ Cálculo incorreto do impacto salarial** - CORRIGIDO
   - Impacto agora é calculado por dia, não por mês inteiro
   - Baseado em 22 dias úteis por mês

## 📋 Regras Implementadas

### 🏖️ FÉRIAS
- **Salário base**: ✅ 100% pago
- **Subsídio alimentação**: ❌ Depende do contrato ou prática da empresa
- **Subsídio transporte**: ❌ Depende do contrato ou prática da empresa
- **Outros subsídios**: ❌ Depende do contrato ou prática da empresa
- **Aprovação**: ❌ Automática (direito adquirido)
- **Documento**: ❌ Não requerido
- **Limite**: ✅ 22 dias úteis por ano
- **Resultado**: Funcionário recebe o salário-base e os complementos obrigatórios. Subsídios variam conforme o regulamento interno.

### 🤒 DOENÇA
- **Salário base**: ✅ 100% pago (até 6 meses)
- **Subsídio alimentação**: ❌ Pode ser suspenso (depende do contrato)
- **Subsídio transporte**: ❌ Pode ser suspenso (depende do contrato)
- **Outros subsídios**: ❌ Podem ser suspensos (depende do contrato)
- **Aprovação**: ✅ Manual (necessita validação)
- **Documento**: ✅ Atestado médico obrigatório
- **Limite**: ✅ 6 meses consecutivos com pagamento
- **Resultado**: Recebe o salário-base, mas pode perder subsídios conforme política interna.

### 👤 LICENÇA PESSOAL (sem remuneração)
- **Salário base**: ❌ Não pago
- **Subsídio alimentação**: ❌ Suspenso
- **Subsídio transporte**: ❌ Suspenso
- **Outros subsídios**: ❌ Suspensos
- **Aprovação**: ✅ Manual (por solicitação do trabalhador)
- **Documento**: ❌ Não obrigatório (mas pode ser solicitado pela empresa)
- **Limite**: ❌ Sem limite definido em lei – depende de acordo com a empresa
- **Resultado**: Não recebe salário nem subsídios durante a ausência.

### 📚 FORMAÇÃO (promovida pela empresa)
- **Salário base**: ✅ Pago (se promovida pela empresa)
- **Subsídio alimentação**: ✅ Pago (se promovida pela empresa e previsto no contrato)
- **Subsídio transporte**: ✅ Pago (se promovida pela empresa e previsto no contrato)
- **Outros subsídios**: ✅ Pagos (se promovida pela empresa e previsto no contrato)
- **Aprovação**: ✅ Manual (pela empresa)
- **Documento**: ✅ Comprovativo de participação na formação
- **Limite**: ❌ Não especificado por lei – depende do programa
- **Resultado**: Quando a formação é iniciativa da empresa, o funcionário mantém o salário e benefícios normalmente.

**ℹ️ Importante**: Caso a formação seja solicitada pelo trabalhador, a lei prevê até 60 dias de licença sem remuneração, mediante condições.

### ❓ OUTRAS LICENÇAS
- **Salário base**: ❌ Não pago (a menos que haja acordo interno)
- **Subsídio alimentação**: ❌ Suspenso
- **Subsídio transporte**: ❌ Suspenso
- **Outros subsídios**: ❌ Suspensos
- **Aprovação**: ✅ Manual
- **Documento**: ✅ Justificativa obrigatória
- **Limite**: ❌ Sem limite fixado em lei
- **Resultado**: Por regra, não há pagamento, salvo se houver previsão contratual (por exemplo, pagar 50% do salário).

## 🔧 Arquivos Modificados

### 1. `direitos_ausencias.php` (CORRIGIDO)
- **Função `calcularImpactoSalarialAusencia()`** - Completamente reescrita
  - Implementa switch case específico para cada tipo de ausência
  - Calcula impacto por dia, não por mês inteiro
  - Aplica regras corretas da legislação angolana

### 2. `processamento_salarial.php` (CORRIGIDO)
- **Cálculo do IRT** - Mantido correto sobre salário base ajustado
- **Aplicação de impactos** - Melhorada a lógica de cálculo
- **Comentários** - Adicionados para clareza

### 3. `teste_ausencias.php` (NOVO)
- Script de teste para verificar se as regras estão funcionando
- Testa cada tipo de ausência individualmente
- Valida se os cálculos estão corretos

## 🧮 Como Funciona Agora

### Cálculo do Impacto Salarial:
```php
// Para cada tipo de ausência:
switch ($tipo_ausencia) {
    case 'Férias':
        $desconto_salario = 0; // Não desconta
        $desconto_subsidios = 0; // Não desconta
        break;
        
    case 'Doença':
        $desconto_salario = 0; // Não desconta
        $desconto_subsidios = 0; // Não desconta
        break;
        
    case 'Pessoal':
        $desconto_salario = $salario_dia * $dias_uteis; // Desconta por dia
        $desconto_subsidios = $subsidios_dia * $dias_uteis; // Desconta por dia
        break;
        
    case 'Formação':
        $desconto_salario = 0; // Não desconta
        $desconto_subsidios = 0; // Não desconta
        break;
        
    case 'Outro':
        $desconto_salario = $salario_dia * $dias_uteis; // Desconta por dia
        $desconto_subsidios = $subsidios_dia * $dias_uteis; // Desconta por dia
        break;
}
```

### Processamento Salarial:
1. **Salário base ajustado** = Salário base - Desconto por ausências
2. **Subsídios ajustados** = Subsídios - Desconto por ausências
3. **IRT** = Calculado sobre salário base ajustado (CORRETO)
4. **Salário líquido** = (Salário base ajustado + Subsídios ajustados) - Descontos

## ✅ Teste do Sistema

Execute o arquivo `teste_ausencias.php` para verificar se todas as regras estão funcionando corretamente. O script irá:

1. Testar cada tipo de ausência
2. Verificar se os descontos estão corretos
3. Validar se as regras da legislação estão sendo aplicadas
4. Mostrar resultado com ✅ CORRETO ou ❌ ERRO

## 🎯 Benefícios das Correções

1. **Conformidade Legal**: Sistema agora segue exatamente a legislação angolana
2. **Precisão**: Cálculos corretos por tipo de ausência
3. **Transparência**: Cada ausência tem tratamento específico
4. **Flexibilidade**: Fácil ajuste de políticas por empresa
5. **Auditoria**: Sistema de direitos mostra exatamente o que cada ausência impacta

## 📝 Próximos Passos

1. Execute o script SQL `inicializar_politicas_ausencia.sql` para configurar as políticas
2. Execute `teste_ausencias.php` para verificar se tudo está funcionando
3. Use o sistema normalmente - as regras serão aplicadas automaticamente
4. Monitore os resultados no processamento salarial

O sistema agora diferencia corretamente cada tipo de ausência conforme a legislação angolana! 🎉

## Visão Geral

O sistema de ausências foi completamente reformulado para implementar as regras específicas de cada tipo de ausência conforme a legislação trabalhista angolana. Anteriormente, todas as ausências justificadas faziam a mesma coisa - apenas cancelavam as faltas. Agora, cada tipo de ausência tem regras específicas de remuneração e subsídios.

## Tipos de Ausência e Suas Regras

### 🏖️ FÉRIAS
- **Salário base**: ✅ 100% pago
- **Subsídio alimentação**: ❌ Depende do contrato ou prática da empresa
- **Subsídio transporte**: ❌ Depende do contrato ou prática da empresa
- **Outros subsídios**: ❌ Depende do contrato ou prática da empresa
- **Aprovação**: ❌ Automática (direito adquirido)
- **Documento**: ❌ Não requerido
- **Limite**: ✅ 22 dias úteis por ano
- **Resultado**: Funcionário recebe o salário-base e os complementos obrigatórios. Subsídios variam conforme o regulamento interno.

### 🤒 DOENÇA
- **Salário base**: ✅ 100% pago (até 6 meses)
- **Subsídio alimentação**: ❌ Pode ser suspenso (depende do contrato)
- **Subsídio transporte**: ❌ Pode ser suspenso (depende do contrato)
- **Outros subsídios**: ❌ Podem ser suspensos (depende do contrato)
- **Aprovação**: ✅ Manual (necessita validação)
- **Documento**: ✅ Atestado médico obrigatório
- **Limite**: ✅ 6 meses consecutivos com pagamento
- **Resultado**: Recebe o salário-base, mas pode perder subsídios conforme política interna.

### 👤 LICENÇA PESSOAL (sem remuneração)
- **Salário base**: ❌ Não pago
- **Subsídio alimentação**: ❌ Suspenso
- **Subsídio transporte**: ❌ Suspenso
- **Outros subsídios**: ❌ Suspensos
- **Aprovação**: ✅ Manual (por solicitação do trabalhador)
- **Documento**: ❌ Não obrigatório (mas pode ser solicitado pela empresa)
- **Limite**: ❌ Sem limite definido em lei – depende de acordo com a empresa
- **Resultado**: Não recebe salário nem subsídios durante a ausência.

### 📚 FORMAÇÃO (promovida pela empresa)
- **Salário base**: ✅ Pago (se promovida pela empresa)
- **Subsídio alimentação**: ✅ Pago (se promovida pela empresa e previsto no contrato)
- **Subsídio transporte**: ✅ Pago (se promovida pela empresa e previsto no contrato)
- **Outros subsídios**: ✅ Pagos (se promovida pela empresa e previsto no contrato)
- **Aprovação**: ✅ Manual (pela empresa)
- **Documento**: ✅ Comprovativo de participação na formação
- **Limite**: ❌ Não especificado por lei – depende do programa
- **Resultado**: Quando a formação é iniciativa da empresa, o funcionário mantém o salário e benefícios normalmente.

**ℹ️ Importante**: Caso a formação seja solicitada pelo trabalhador, a lei prevê até 60 dias de licença sem remuneração, mediante condições.

### ❓ OUTRAS LICENÇAS
- **Salário base**: ❌ Não pago (a menos que haja acordo interno)
- **Subsídio alimentação**: ❌ Suspenso
- **Subsídio transporte**: ❌ Suspenso
- **Outros subsídios**: ❌ Suspensos
- **Aprovação**: ✅ Manual
- **Documento**: ✅ Justificativa obrigatória
- **Limite**: ❌ Sem limite fixado em lei

## Arquivos Modificados

### 1. `direitos_ausencias.php` (NOVO)
Sistema completo de cálculo de direitos de ausências com funções específicas para cada tipo:
- `calcularDireitosAusencia()` - Função principal
- `calcularDireitosFerias()` - Regras para férias
- `calcularDireitosDoenca()` - Regras para doença
- `calcularDireitosPessoal()` - Regras para licença pessoal
- `calcularDireitosFormacao()` - Regras para formação
- `calcularDireitosOutro()` - Regras para outras licenças
- `calcularImpactoSalarialAusencia()` - Calcula impacto no salário
- `verificarLimitesAusencia()` - Verifica limites legais

### 2. `processamento_salarial.php` (MODIFICADO)
- Adicionado require do arquivo de direitos
- Implementado cálculo de impacto salarial específico por tipo de ausência
- Modificado cálculo do salário líquido para aplicar descontos específicos
- Adicionados campos para salário base ajustado e subsídios ajustados

### 3. `ausencias.php` (MODIFICADO)
- Busca dados reais do funcionário (salário base e subsídios)
- Determina automaticamente se formação é promovida pela empresa
- Verifica status de justificação
- Melhorada interface de visualização de direitos

### 4. `ver_direitos_ajax.php` (MODIFICADO)
- Interface melhorada para exibição dos direitos
- Layout mais organizado e informativo
- Exibe base legal e observações específicas

### 5. `inicializar_politicas_ausencia.sql` (NOVO)
Script SQL para configurar as políticas padrão no banco de dados conforme a legislação.

## Como Usar

### 1. Configuração Inicial
Execute o script SQL para configurar as políticas:
```sql
-- Execute o arquivo inicializar_politicas_ausencia.sql
```

### 2. Registro de Ausências
Ao registrar uma ausência:
1. Selecione o tipo de ausência
2. O sistema automaticamente aplicará as regras corretas
3. Use o botão "Ver Direitos" para consultar os direitos específicos

### 3. Processamento Salarial
O sistema automaticamente:
- Calcula o impacto salarial específico para cada tipo de ausência
- Aplica os descontos corretos no salário base e subsídios
- Mantém registro detalhado dos impactos

### 4. Consulta de Direitos
Clique no botão "Ver Direitos" (ícone de balança) para ver:
- Direitos específicos do tipo de ausência
- Remuneração aplicável
- Subsídios mantidos/suspensos
- Observações importantes
- Base legal

## Base Legal

O sistema implementa as seguintes disposições da Lei Geral do Trabalho de Angola:
- **Artigo 123**: Férias anuais
- **Artigo 145**: Baixa médica
- **Artigo 150**: Licença pessoal
- **Artigo 155**: Formação profissional

## Benefícios da Implementação

1. **Conformidade Legal**: Sistema totalmente alinhado com a legislação angolana
2. **Precisão nos Cálculos**: Cada tipo de ausência tem regras específicas
3. **Transparência**: Funcionários podem consultar seus direitos
4. **Flexibilidade**: Políticas podem ser ajustadas por empresa
5. **Auditoria**: Registro detalhado de todos os impactos salariais

## Próximos Passos

1. **Testes**: Validar cálculos com casos reais
2. **Treinamento**: Capacitar usuários do sistema
3. **Documentação**: Criar manual do usuário
4. **Melhorias**: Implementar cálculo automático de meses de doença
5. **Relatórios**: Criar relatórios específicos por tipo de ausência

## Suporte

Para dúvidas ou problemas:
1. Consulte a documentação
2. Verifique as políticas configuradas no banco
3. Teste com dados de exemplo
4. Entre em contato com o suporte técnico 