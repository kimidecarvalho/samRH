-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16-Maio-2025 às 20:09
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.0.30

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
(9, 'Silvestre Luís', 'diogodm1225@gmail.com', '$2y$10$V3yINikoed1u2OMkpS6Shu1JpPP3Yrnq/QucVkGcNtEfTYzK.Nfoi', '922608606', '2004-12-12', 'Mutamba', 'Ensino Médio', '2 anos', 'Programação', NULL, '2025-05-16 18:00:14', 'Pendente', 1);

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
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `empresas_recrutamento`
--

CREATE TABLE `empresas_recrutamento` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
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

INSERT INTO `empresas_recrutamento` (`id`, `nome`, `email`, `senha`, `telefone`, `endereco`, `descricao`, `setor`, `tamanho`, `website`, `data_registro`, `status`) VALUES
(1, 'SAM', 'sam@gmail.com', '$2y$10$7h7Ztyl4fAkiHL07Q1WivOrqZrntAa.eNrdw/cf6Lye.kQaRvZfI6', '922608606', '', '', 'Saúde', '1-10', '', '2025-05-16 14:55:39', 'Ativo');

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
  `status` enum('Aberta','Fechada','Pausada','Rascunho') DEFAULT 'Aberta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `candidaturas`
--
ALTER TABLE `candidaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `empresas_recrutamento`
--
ALTER TABLE `empresas_recrutamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `vagas`
--
ALTER TABLE `vagas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para despejos de tabelas
--

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
