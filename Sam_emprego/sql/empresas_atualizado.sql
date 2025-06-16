-- Adicionando campos de email e senha na tabela empresas_recrutamento
ALTER TABLE `empresas_recrutamento`
ADD COLUMN `email` varchar(150) NOT NULL AFTER `descricao`,
ADD COLUMN `senha` varchar(255) NOT NULL AFTER `email`,
ADD COLUMN `site` varchar(255) DEFAULT NULL AFTER `senha`,
ADD UNIQUE KEY `email` (`email`);

-- Adicionando um registro inicial para site_empresa_id (se necessário)
-- Como estamos usando site_empresa_id=1 no código PHP, precisamos garantir que esse registro exista
ALTER TABLE `empresas_recrutamento` MODIFY COLUMN `site_empresa_id` int(11) DEFAULT 1; 