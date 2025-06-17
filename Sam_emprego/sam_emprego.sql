-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 04-Jun-2025 às 10:24
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sam_emprego`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `candidatos`
--

CREATE TABLE `candidatos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `formacao` text DEFAULT NULL,
  `experiencia` text DEFAULT NULL,
  `habilidades` text DEFAULT NULL,
  `curriculo_path` varchar(255) DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Ativo','Inativo','Pendente') DEFAULT 'Pendente',
  `perfil_completo` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabela atualizada para permitir o status Pendente durante o cadastro em duas etapas';

--
-- Extraindo dados da tabela `candidatos`
--

INSERT INTO `candidatos` (`id`, `nome`, `email`, `senha`, `telefone`, `data_nascimento`, `endereco`, `formacao`, `experiencia`, `habilidades`, `curriculo_path`, `data_registro`, `status`, `perfil_completo`) VALUES
(8, '', 'sam@gmail.com', '$2y$10$A7194sSiByVevEn8JEgMdervtIYbd7TJ.J2eKeucCPz2rqZ1szr.2', '922608606', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-16 17:47:17', 'Pendente', 0),
(9, 'Silvestre Luís', 'diogodm1225@gmail.com', '$2y$10$V3yINikoed1u2OMkpS6Shu1JpPP3Yrnq/QucVkGcNtEfTYzK.Nfoi', '922608606', '2004-12-12', 'Mutamba', 'Ensino Médio', '2 anos', 'Programação', 'curriculos/cv_683dcf02588b0.docx', '2025-05-16 18:00:14', 'Pendente', 1),
(10, '', 'diogodm12215@gmail.com', '$2y$10$gb99XicktUFnTLP4Bhhv9O9ycBJeXMspub8Dtbj1FwKcHf6bDjCDq', '922608606', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-16 21:20:50', 'Pendente', 0),
(11, 'Diogo Oliveira', 'rrh14213@gmail.com', '$2y$10$sjSre0.q5OPS3q7IkAsFoe7ljMlpuq4a48/bk3rHTtKygFcLG43R.', '11241252', '1800-12-12', 'Mutamba', 'Ensino Superior', '2 anos', 'Programação, TI', 'uploads/curriculos/cv_68288c218a7a1.pdf', '2025-05-17 12:12:55', 'Pendente', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `candidato_habilidades`
--

CREATE TABLE `candidato_habilidades` (
  `id` int(11) NOT NULL,
  `candidato_id` int(11) NOT NULL,
  `habilidade` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `candidaturas`
--

CREATE TABLE `candidaturas` (
  `id` int(11) NOT NULL,
  `candidato_id` int(11) NOT NULL,
  `vaga_id` int(11) NOT NULL,
  `data_candidatura` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pendente','Visualizada','Em análise','Entrevista','Aprovado','Rejeitado') DEFAULT 'Pendente',
  `notas` text DEFAULT NULL,
  `entrevista_data` date DEFAULT NULL,
  `entrevista_hora` time DEFAULT NULL,
  `entrevista_tipo` varchar(20) DEFAULT NULL,
  `entrevista_local` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `candidaturas`
--

INSERT INTO `candidaturas` (`id`, `candidato_id`, `vaga_id`, `data_candidatura`, `status`, `notas`, `entrevista_data`, `entrevista_hora`, `entrevista_tipo`, `entrevista_local`) VALUES
(1, 9, 15, '2025-05-31 16:19:10', 'Entrevista', NULL, NULL, NULL, NULL, NULL),
(19, 9, 14, '2025-06-03 10:11:12', 'Em análise', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `empresas_recrutamento`
--

CREATE TABLE `empresas_recrutamento` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `setor` varchar(100) DEFAULT NULL,
  `tamanho` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Ativo','Inativo','Pendente') DEFAULT 'Ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `empresas_recrutamento`
--

INSERT INTO `empresas_recrutamento` (`id`, `nome`, `logo`, `email`, `senha`, `telefone`, `endereco`, `descricao`, `setor`, `tamanho`, `website`, `data_registro`, `status`) VALUES
(1, 'SAM', 'uploads/logos_empresas/logo_1_1748885444.png', 'sam@gmail.com', '$2y$10$7h7Ztyl4fAkiHL07Q1WivOrqZrntAa.eNrdw/cf6Lye.kQaRvZfI6', '922608606', '', '', 'Saúde', '1-10', '', '2025-05-16 14:55:39', 'Ativo'),
(2, 'Sonangol', NULL, 'son@gmail.com', '$2y$10$GRM/bPhWN177PiWxG/Pclu9FoIhaqx4PvxIB2yAxhbJBOZl4F.DG2', '999999999', 'Sei lá', 'Empresa do pai do desi', 'Produção', '501+', 'https://www.sonangol.com', '2025-05-17 10:53:23', 'Ativo');

-- --------------------------------------------------------

--
-- Estrutura da tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `candidatura_id` int(11) NOT NULL,
  `remetente_tipo` enum('candidato','empresa') NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `vagas`
--

CREATE TABLE `vagas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `requisitos` text DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `localizacao` varchar(255) DEFAULT NULL,
  `tipo_contrato` varchar(50) NOT NULL,
  `salario_min` decimal(10,2) DEFAULT NULL,
  `salario_max` decimal(10,2) DEFAULT NULL,
  `data_publicacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_expiracao` date DEFAULT NULL,
  `status` enum('Aberta','Fechada','Pausada','Rascunho','Em análise','Finalizada') DEFAULT 'Aberta',
  `categoria` varchar(100) DEFAULT NULL,
  `localizacao_tipo` varchar(50) DEFAULT NULL,
  `periodo_salario` varchar(20) DEFAULT NULL,
  `metodo_pagamento` varchar(50) DEFAULT NULL,
  `idioma` varchar(50) DEFAULT NULL,
  `fuso_horario` varchar(20) DEFAULT NULL,
  `dias_uteis` varchar(30) DEFAULT NULL,
  `horas_semanais_min` int(11) DEFAULT NULL,
  `horas_semanais_max` int(11) DEFAULT NULL,
  `horas_diarias_min` int(11) DEFAULT NULL,
  `horas_diarias_max` int(11) DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fim` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `vagas`
--

INSERT INTO `vagas` (`id`, `empresa_id`, `titulo`, `descricao`, `requisitos`, `departamento`, `localizacao`, `tipo_contrato`, `salario_min`, `salario_max`, `data_publicacao`, `data_expiracao`, `status`, `categoria`, `localizacao_tipo`, `periodo_salario`, `metodo_pagamento`, `idioma`, `fuso_horario`, `dias_uteis`, `horas_semanais_min`, `horas_semanais_max`, `horas_diarias_min`, `horas_diarias_max`, `hora_inicio`, `hora_fim`) VALUES
(14, 1, 'dsada', 'fasfasfasfsa', 'fasfasfasfasf', 'Logística e Distribuição', 'remoto', 'meio_periodo', 1111.00, 111111.00, '2025-05-31 12:50:27', '2026-12-12', 'Aberta', 'Logística e Distribuição', 'nacional', 'mensal', 'transferencia', 'portugues_ingles', 'GMT+1', 'Segunda à Sexta', 12, 20, 1, 5, '12:00:00', '20:00:00'),
(15, 1, 'sdadadasd', 'dsadasd', 'dsadasdsad', 'Logística e Distribuição', 'remoto', 'efetivo', 122.00, 12222.00, '2025-05-31 12:52:34', '2026-12-12', 'Aberta', 'Logística e Distribuição', 'nacional', 'mensal', 'transferencia', 'portugues_ingles', 'GMT+0', 'Segunda à Sexta', 2, 4, 3, 7, '12:00:00', '20:00:00'),
(16, 2, 'Bancário', 'Mambo básico, ganhaste puto', 'Ser burro, andar a pé e passar fome', 'Logística e Distribuição', 'remoto', 'efetivo', 120000.00, 180000.00, '2025-06-02 17:37:56', '2025-12-12', 'Fechada', 'Logística e Distribuição', 'internacional', 'mensal', 'cheque', 'portugues_frances', 'GMT+1', 'Segunda à Sexta', 14, 17, 2, 4, '06:30:00', '14:00:00'),
(17, 1, 'dadasd', 'dasdas', 'dasdadasd', 'Recursos Humanos', 'remoto', 'efetivo', 12.00, 222.00, '2025-06-03 07:43:10', '2025-12-12', 'Aberta', 'Recursos Humanos', 'regional', 'mensal', 'transferencia', 'portugues_ingles', 'GMT+1', 'Segunda à Sexta', 12, 14, 2, 4, '12:22:00', '16:06:00');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `candidatos`
--
ALTER TABLE `candidatos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `candidato_habilidades`
--
ALTER TABLE `candidato_habilidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidato_id` (`candidato_id`);

--
-- Índices para tabela `candidaturas`
--
ALTER TABLE `candidaturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `candidato_vaga` (`candidato_id`,`vaga_id`),
  ADD KEY `vaga_id` (`vaga_id`),
  ADD KEY `idx_candidaturas_status` (`status`);

--
-- Índices para tabela `empresas_recrutamento`
--
ALTER TABLE `empresas_recrutamento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidatura_id` (`candidatura_id`);

--
-- Índices para tabela `vagas`
--
ALTER TABLE `vagas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `idx_vaga_status` (`status`),
  ADD KEY `idx_vaga_data` (`data_publicacao`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `candidatos`
--
ALTER TABLE `candidatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `candidato_habilidades`
--
ALTER TABLE `candidato_habilidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `candidaturas`
--
ALTER TABLE `candidaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `empresas_recrutamento`
--
ALTER TABLE `empresas_recrutamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `vagas`
--
ALTER TABLE `vagas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `candidato_habilidades`
--
ALTER TABLE `candidato_habilidades`
  ADD CONSTRAINT `candidato_habilidades_ibfk_1` FOREIGN KEY (`candidato_id`) REFERENCES `candidatos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `candidaturas`
--
ALTER TABLE `candidaturas`
  ADD CONSTRAINT `candidaturas_ibfk_1` FOREIGN KEY (`candidato_id`) REFERENCES `candidatos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `candidaturas_ibfk_2` FOREIGN KEY (`vaga_id`) REFERENCES `vagas` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`candidatura_id`) REFERENCES `candidaturas` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `vagas`
--
ALTER TABLE `vagas`
  ADD CONSTRAINT `vagas_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_recrutamento` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
