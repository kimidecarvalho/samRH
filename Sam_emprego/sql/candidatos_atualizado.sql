-- Adicionando campo de senha na tabela candidatos
ALTER TABLE `candidatos`
ADD COLUMN `senha` varchar(255) NOT NULL AFTER `email`;

-- Criando pasta para armazenar os currículos
-- Nota: Esta instrução deve ser executada no sistema operacional, não no SQL
-- mkdir -p uploads/curriculos 