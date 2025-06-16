-- Atualiza a coluna de status da tabela 'candidatos' para incluir o valor 'Pendente'
ALTER TABLE `candidatos` 
MODIFY COLUMN `status` enum('Ativo','Inativo','Pendente') DEFAULT 'Pendente';

-- Adiciona um comentário à tabela para indicar a alteração
ALTER TABLE `candidatos` COMMENT = 'Tabela atualizada para permitir o status Pendente durante o cadastro em duas etapas'; 