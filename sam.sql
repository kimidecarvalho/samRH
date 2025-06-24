-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2025 at 08:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sam`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `formata_data_pt` (`data` DATE) RETURNS VARCHAR(10) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    RETURN DATE_FORMAT(data, '%d-%m-%Y');
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `adm`
--

CREATE TABLE `adm` (
  `id_adm` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` int(11) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `matricula` varchar(50) DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `nivel_acesso` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `adm`
--

INSERT INTO `adm` (`id_adm`, `nome`, `email`, `senha`, `telefone`, `cargo`, `departamento`, `matricula`, `data_admissao`, `nivel_acesso`) VALUES
(11, 'Jorge', 'maguinhomast2005@gmail.com', '$2y$10$PxLO./tLULTLPJ.5QlkfIexwvEjZ0OBWSDSw45CAMLW6bB9kUWjre', 2147483647, NULL, NULL, NULL, NULL, NULL),
(13, 'Diogo Oliveira', 'diogodm1225@gmail.com', '$2y$10$D.n4AFzFAqEYUp3s/.rNB..QEYG7qU8yumG6AFL70zHWHBXnJzt3y', 2147483647, NULL, NULL, NULL, NULL, NULL),
(14, 'Diogo Oliveira', 'maguinhomast5@gmail.com', '$2y$10$s0ASQ1WIFYaCSmHR7RYNyeFR0JuJCGmXVZNgfZM2rBoP1Ckx35C5y', 2147483647, NULL, NULL, NULL, NULL, NULL);

--
-- Triggers `adm`
--
DELIMITER $$
CREATE TRIGGER `delete_adm_app` AFTER DELETE ON `adm` FOR EACH ROW BEGIN
    DELETE FROM `app_empresas`.`empresas` WHERE `email` = OLD.email;
    -- A exclusão em cascata vai automaticamente remover os funcionários relacionados
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_adm_app` AFTER UPDATE ON `adm` FOR EACH ROW BEGIN
    -- Se o email foi alterado, precisamos encontrar o registro pelo email antigo
    IF OLD.email != NEW.email THEN
        -- Atualizar a empresa correspondente no app_empresas
        UPDATE `app_empresas`.`empresas` 
        SET `nome` = NEW.nome, 
            `email` = NEW.email
        WHERE `email` = OLD.email;
    ELSE
        -- Se o email não mudou, apenas atualizar outros dados
        UPDATE `app_empresas`.`empresas` 
        SET `nome` = NEW.nome
        WHERE `email` = NEW.email;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `adm_sessions`
--

CREATE TABLE `adm_sessions` (
  `session_id` varchar(255) NOT NULL,
  `adm_id` int(11) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adm_sessions`
--

INSERT INTO `adm_sessions` (`session_id`, `adm_id`, `user_agent`, `ip_address`, `last_activity`) VALUES
('1c6b3ju2o3mnh6gmq42japu4u1', 11, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '::1', '2025-06-17 15:31:41'),
('4lrs0vrmre0415o1042uomv194', 14, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '::1', '2025-06-17 17:18:55'),
('u6jah840feq4jio4mgv53g8tv6', 13, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '::1', '2025-06-17 17:11:13');

-- --------------------------------------------------------

--
-- Table structure for table `ausencias`
--

CREATE TABLE `ausencias` (
  `id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo_ausencia` varchar(50) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `dias_uteis` int(11) NOT NULL,
  `justificacao` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_registro` datetime NOT NULL,
  `justificada` tinyint(1) DEFAULT 0,
  `tipo_justificacao` enum('Férias','Doença','Pessoal','Formação','Outro') DEFAULT NULL,
  `documentos_justificacao` text DEFAULT NULL,
  `aprovada_por` int(11) DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `impacto_salarial` enum('sem_impacto','desconto_parcial','desconto_total') DEFAULT 'sem_impacto',
  `percentual_pagamento` decimal(5,2) DEFAULT 100.00,
  `status_justificacao` enum('pendente','aprovada','rejeitada') DEFAULT 'pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ausencias`
--

INSERT INTO `ausencias` (`id`, `funcionario_id`, `empresa_id`, `tipo_ausencia`, `data_inicio`, `data_fim`, `dias_uteis`, `justificacao`, `observacoes`, `data_registro`, `justificada`, `tipo_justificacao`, `documentos_justificacao`, `aprovada_por`, `data_aprovacao`, `impacto_salarial`, `percentual_pagamento`, `status_justificacao`) VALUES
(6, 51, 8, 'Doença', '2025-05-01', '2025-07-10', 51, '', '', '2025-06-21 23:56:37', 0, NULL, NULL, 1, '2025-06-21 23:46:29', 'sem_impacto', 100.00, 'aprovada'),
(7, 52, 8, 'Formação', '2025-05-10', '2025-08-10', 65, '', '', '2025-06-22 00:49:16', 0, 'Formação', NULL, NULL, NULL, 'sem_impacto', 100.00, 'pendente'),
(8, 53, 8, 'Pessoal', '2025-06-23', '2025-06-24', 2, '', '', '2025-06-22 01:28:56', 0, 'Pessoal', NULL, NULL, NULL, 'sem_impacto', 100.00, 'pendente'),
(9, 55, 8, 'Pessoal', '2025-05-10', '2025-07-10', 44, '', '', '2025-06-22 20:59:02', 0, 'Pessoal', NULL, NULL, NULL, 'sem_impacto', 100.00, 'pendente');

-- --------------------------------------------------------

--
-- Table structure for table `bancos_ativos`
--

CREATE TABLE `bancos_ativos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `banco_nome` varchar(100) NOT NULL,
  `banco_codigo` varchar(10) NOT NULL,
  `ativo` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bancos_ativos`
--

INSERT INTO `bancos_ativos` (`id`, `empresa_id`, `banco_nome`, `banco_codigo`, `ativo`) VALUES
(4, 8, 'JosiBank', 'JB', 1);

-- --------------------------------------------------------

--
-- Table structure for table `beneficios`
--

CREATE TABLE `beneficios` (
  `id_beneficio` int(11) NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `fun_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cargos`
--

CREATE TABLE `cargos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `departamento_id` int(11) NOT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cargos`
--

INSERT INTO `cargos` (`id`, `nome`, `departamento_id`, `salario_base`, `empresa_id`, `created_at`) VALUES
(2, 'Programador', 12, 220000.00, 8, '2025-06-20 19:14:49');

-- --------------------------------------------------------

--
-- Table structure for table `configuracoes_seguranca`
--

CREATE TABLE `configuracoes_seguranca` (
  `id` int(11) NOT NULL,
  `adm_id` int(11) NOT NULL,
  `dois_fatores` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departamentos`
--

CREATE TABLE `departamentos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departamentos`
--

INSERT INTO `departamentos` (`id`, `nome`, `empresa_id`, `created_at`) VALUES
(12, 'TI', 8, '2025-06-20 19:14:37');

-- --------------------------------------------------------

--
-- Table structure for table `dispositivos_confiaveis`
--

CREATE TABLE `dispositivos_confiaveis` (
  `id` int(11) NOT NULL,
  `adm_id` int(11) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acesso` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dispositivos_confiaveis`
--

INSERT INTO `dispositivos_confiaveis` (`id`, `adm_id`, `user_agent`, `ip_address`, `data_criacao`, `ultimo_acesso`) VALUES
(3, 11, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '::1', '2025-06-17 15:31:41', '2025-06-20 20:06:39'),
(4, 13, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '::1', '2025-06-17 17:11:14', '2025-06-23 16:42:40'),
(5, 14, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '::1', '2025-06-17 17:18:55', '2025-06-23 16:36:46');

-- --------------------------------------------------------

--
-- Table structure for table `documentos`
--

CREATE TABLE `documentos` (
  `id_documento` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `data` date NOT NULL,
  `descricao` text DEFAULT NULL,
  `anexo` varchar(255) NOT NULL,
  `num_funcionario` int(11) NOT NULL,
  `folder` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `empresa`
--

CREATE TABLE `empresa` (
  `id_empresa` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `nipc` varchar(20) NOT NULL,
  `endereco` varchar(255) NOT NULL,
  `email_corp` varchar(255) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `setor_atuacao` varchar(100) NOT NULL,
  `num_fun` int(11) NOT NULL,
  `data_cadastro` date NOT NULL DEFAULT curdate(),
  `adm_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `empresa`
--

INSERT INTO `empresa` (`id_empresa`, `nome`, `nipc`, `endereco`, `email_corp`, `telefone`, `setor_atuacao`, `num_fun`, `data_cadastro`, `adm_id`) VALUES
(7, 'SAM', '32423424', 'Angola', 'SAM@gmail.com', '922608606', 'servicos', 11, '2025-06-17', 11),
(8, 'Pitruca', '23123', 'Angola', 'pt@gmail.com', '999999999', 'educacao', 51, '2025-06-17', 13),
(9, 'Sonangol', '12312313', 'Angola', 'sg@gmail.com', '922608606', 'industria', 51, '2025-06-17', 14);

--
-- Triggers `empresa`
--
DELIMITER $$
CREATE TRIGGER `after_empresa_insert` AFTER INSERT ON `empresa` FOR EACH ROW BEGIN
    -- Get the admin password from sam.adm
    SELECT senha INTO @admin_senha FROM sam.adm WHERE id_adm = NEW.adm_id;

    -- Insert into sam_emprego.empresas_recrutamento with admin password
    INSERT INTO sam_emprego.empresas_recrutamento (
        nome,
        email,
        senha,
        telefone,
        endereco,
        setor,
        tamanho,
        status
    )
    VALUES (
        NEW.nome,
        NEW.email_corp,
        @admin_senha,
        NEW.telefone,
        NEW.endereco,
        NEW.setor_atuacao,
        NEW.num_fun,
        'Ativo'
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_empresa_update` AFTER UPDATE ON `empresa` FOR EACH ROW BEGIN
    -- Get the admin password from sam.adm
    SELECT senha INTO @admin_senha FROM sam.adm WHERE id_adm = NEW.adm_id;

    -- Update sam_emprego.empresas_recrutamento
    UPDATE sam_emprego.empresas_recrutamento
    SET 
        nome = NEW.nome,
        email = NEW.email_corp,
        senha = @admin_senha,
        telefone = NEW.telefone,
        endereco = NEW.endereco,
        setor = NEW.setor_atuacao,
        tamanho = NEW.num_fun
    WHERE email = OLD.email_corp;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `delete_empresa_app` AFTER DELETE ON `empresa` FOR EACH ROW BEGIN    
    -- Excluir a empresa correspondente no app_empresas
    DELETE FROM `app_empresas`.`empresas` WHERE `site_empresa_id` = OLD.id_empresa;
    -- A exclusão em cascata vai automaticamente remover os funcionários relacionados
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `insert_empresa_app` AFTER INSERT ON `empresa` FOR EACH ROW BEGIN
    -- Inserir nova empresa no app_empresas quando criada no site
    INSERT INTO `app_empresas`.`empresas` 
    (`nome`, `email`, `senha`, `data_cadastro`, `site_empresa_id`) 
    VALUES 
    (NEW.nome, NEW.email_corp, '$2y$10$gVkC1tSsNFcgkuHgWA8Y0esHFKcuNWbljVEAyWjzSWl/UdfKVSERy', NOW(), NEW.id_empresa);
    -- Nota: A senha é um placeholder, deverá ser definida via API ou outro meio
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `falta`
--

CREATE TABLE `falta` (
  `id_falta` int(11) NOT NULL,
  `data` date NOT NULL,
  `motivo` text NOT NULL,
  `justificada` enum('Sim','Não') NOT NULL,
  `fun_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feriados_angola`
--

CREATE TABLE `feriados_angola` (
  `id` int(11) NOT NULL,
  `data_feriado` date NOT NULL,
  `nome_feriado` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feriados_angola`
--

INSERT INTO `feriados_angola` (`id`, `data_feriado`, `nome_feriado`) VALUES
(23, '2025-01-01', 'Ano Novo'),
(24, '2025-02-04', 'Dia do Início da Luta Armada de Libertação Nacional'),
(25, '2025-03-04', 'Carnaval'),
(26, '2025-03-08', 'Dia Internacional da Mulher'),
(27, '2025-03-23', 'Dia da Libertação da África Austral'),
(28, '2025-04-04', 'Dia da Paz e da Reconciliação Nacional'),
(29, '2025-04-18', 'Sexta-feira Santa'),
(30, '2025-05-01', 'Dia do Trabalhador'),
(31, '2025-09-17', 'Dia do Fundador da Nação e do Herói Nacional'),
(32, '2025-11-02', 'Dia dos Finados'),
(33, '2025-11-11', 'Dia da Independência Nacional'),
(34, '2025-12-25', 'Dia de Natal');

-- --------------------------------------------------------

--
-- Stand-in structure for view `feriados_angola_formatados`
-- (See below for the actual view)
--
CREATE TABLE `feriados_angola_formatados` (
`id` int(11)
,`data_feriado` date
,`data_feriado_pt` varchar(10)
,`nome_feriado` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `funcionario`
--

CREATE TABLE `funcionario` (
  `id_fun` int(11) NOT NULL,
  `num_mecanografico` varchar(20) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `bi` varchar(14) NOT NULL,
  `emissao_bi` date NOT NULL,
  `validade_bi` date NOT NULL,
  `data_nascimento` date NOT NULL,
  `pais` varchar(50) NOT NULL,
  `morada` varchar(255) NOT NULL,
  `genero` enum('Masculino','Feminino') NOT NULL,
  `num_agregados` int(11) NOT NULL DEFAULT 0,
  `contato_emergencia` varchar(20) NOT NULL,
  `nome_contato_emergencia` varchar(100) NOT NULL,
  `telemovel` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `estado` enum('Ativo','Inativo','Terminado') NOT NULL DEFAULT 'Ativo',
  `cargo` varchar(100) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `tipo_trabalhador` enum('Efetivo','Temporário','Estagiário','Autônomo','Freelancer','Terceirizado','Intermitente','Voluntário') NOT NULL,
  `num_conta_bancaria` varchar(30) NOT NULL,
  `banco` varchar(10) NOT NULL,
  `iban` varchar(35) NOT NULL,
  `salario_base` decimal(10,2) NOT NULL DEFAULT 0.00,
  `num_ss` varchar(30) NOT NULL,
  `data_admissao` date NOT NULL DEFAULT curdate(),
  `empresa_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'pendente_biometria',
  `data_termino` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `funcionario`
--

INSERT INTO `funcionario` (`id_fun`, `num_mecanografico`, `nome`, `foto`, `bi`, `emissao_bi`, `validade_bi`, `data_nascimento`, `pais`, `morada`, `genero`, `num_agregados`, `contato_emergencia`, `nome_contato_emergencia`, `telemovel`, `email`, `estado`, `cargo`, `departamento`, `tipo_trabalhador`, `num_conta_bancaria`, `banco`, `iban`, `salario_base`, `num_ss`, `data_admissao`, `empresa_id`, `status`, `data_termino`) VALUES
(51, 'EMP-0001', 'Kimi Carvalho', NULL, '32432432423', '2025-06-04', '2025-06-24', '2025-06-18', 'angola', 'rua Pedro de Castro Van-Dunem Loy, Casa 4, Vila Ecocampo', 'Masculino', 12, '924135515', 'Kimi Carvalho', '924135515', 'kienukimidecarvalho@gmail.com', 'Ativo', '2', '12', 'Efetivo', '542344', 'JB', '12312312', 220000.00, '31231231', '2025-06-20', 8, 'pendente_biometria', NULL),
(52, 'EMP-0002', 'Jorge Mundula', NULL, '324253453242', '2025-06-09', '2025-06-27', '2025-06-02', 'angola', 'rua Pedro de Castro Van-Dunem Loy, Casa 4, Vila Ecocampo', 'Masculino', 12, '924135515', 'Kimi', '999999999', 'jorge@gmail.com', 'Ativo', '2', '12', 'Efetivo', '1111324324', 'JB', '1231', 220000.00, '12313', '2025-06-21', 8, 'pendente_biometria', NULL),
(53, 'EMP-0003', 'Maria Cose', NULL, '6434324', '2025-06-01', '2025-06-29', '2025-06-02', 'angola', 'Kilamba KK', 'Masculino', 3, '92413515', 'Kimi Carvalho', '999999345', 'maria@gmail.com', 'Ativo', '2', '12', 'Efetivo', '924589', 'JB', '124124342343', 220000.00, '999432423', '2025-06-21', 8, 'pendente_biometria', NULL),
(54, 'EMP-0004', 'Kelly Caetano', NULL, '762425345', '2025-06-11', '2025-06-25', '2025-06-03', 'angola', 'Kilamba', 'Masculino', 4, '924135515', 'Kimi', '999123999', 'kelly@gmail.com', 'Ativo', '2', '12', 'Efetivo', '3453534', 'JB', '32432', 220000.00, '234324', '2025-06-21', 8, 'pendente_biometria', NULL),
(55, 'EMP-0005', 'Josilde Costa', NULL, '9954645', '2025-06-10', '2025-07-01', '2025-06-09', 'angola', 'Kilamba ', 'Masculino', 2, '924135515', 'Kimi', '999345992', 'josilde@gmail.com', 'Ativo', '2', '12', 'Efetivo', '5432', 'JB', '43454353', 220000.00, '2343234', '2025-06-21', 8, 'pendente_biometria', NULL);

--
-- Triggers `funcionario`
--
DELIMITER $$
CREATE TRIGGER `delete_funcionario_app` AFTER DELETE ON `funcionario` FOR EACH ROW BEGIN
    -- Excluir funcionário no app_empresas
    DELETE FROM `app_empresas`.`employees` WHERE id = OLD.num_mecanografico;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `gerar_num_mecanografico` BEFORE INSERT ON `funcionario` FOR EACH ROW BEGIN
    DECLARE ultimo_num INT;
    DECLARE novo_num VARCHAR(20);

    -- Busca o último número mecanográfico cadastrado
    SELECT IFNULL(MAX(CAST(SUBSTRING(num_mecanografico, 5, 4) AS UNSIGNED)), 0) + 1 
    INTO ultimo_num FROM funcionario;

    -- Formata o novo número mecanográfico no padrão EMP-000X
    SET novo_num = CONCAT('EMP-', LPAD(ultimo_num, 4, '0'));

    -- Atribui o número mecanográfico ao novo funcionário
    SET NEW.num_mecanografico = novo_num;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `sync_funcionario_app` AFTER INSERT ON `funcionario` FOR EACH ROW BEGIN
    DECLARE app_empresa_id INT;
    
    -- Encontrar o ID da empresa no app_empresas
    SELECT id INTO app_empresa_id 
    FROM `app_empresas`.`empresas` 
    WHERE site_empresa_id = NEW.empresa_id
    LIMIT 1;
    
    IF app_empresa_id IS NOT NULL THEN
        -- Inserir funcionário no app_empresas
        INSERT INTO `app_empresas`.`employees` 
        (`id`, `name`, `position`, `department`, `digital_signature`, `empresa_id`) 
        VALUES 
        (NEW.num_mecanografico, NEW.nome, NEW.cargo, NEW.departamento, 0, app_empresa_id);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_funcionario_app` AFTER UPDATE ON `funcionario` FOR EACH ROW BEGIN
    -- Atualizar funcionário no app_empresas
    UPDATE `app_empresas`.`employees` 
    SET `name` = NEW.nome, 
        `position` = NEW.cargo, 
        `department` = NEW.departamento
    WHERE id = NEW.num_mecanografico;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `historico_ajustes_salariais`
--

CREATE TABLE `historico_ajustes_salariais` (
  `id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `tipo_ajuste` enum('justificacao_ausencia','correcao_falta','reajuste_manual') NOT NULL,
  `descricao` text DEFAULT NULL,
  `valor_anterior` decimal(10,2) DEFAULT NULL,
  `valor_novo` decimal(10,2) DEFAULT NULL,
  `diferenca` decimal(10,2) DEFAULT NULL,
  `justificacao_id` int(11) DEFAULT NULL,
  `data_ajuste` timestamp NOT NULL DEFAULT current_timestamp(),
  `realizado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `horarios_funcionarios`
--

CREATE TABLE `horarios_funcionarios` (
  `id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `hora_entrada` time NOT NULL,
  `hora_saida` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `horarios_funcionarios`
--

INSERT INTO `horarios_funcionarios` (`id`, `funcionario_id`, `hora_entrada`, `hora_saida`, `created_at`, `updated_at`) VALUES
(3, 51, '08:00:00', '10:00:00', '2025-06-20 19:15:58', '2025-06-20 20:41:12'),
(6, 52, '08:00:00', '16:00:00', '2025-06-21 19:33:54', '2025-06-21 19:33:54'),
(7, 53, '08:00:00', '16:00:00', '2025-06-21 20:28:29', '2025-06-21 20:28:29'),
(8, 54, '08:00:00', '16:00:00', '2025-06-21 20:29:54', '2025-06-21 20:29:54'),
(9, 55, '08:00:00', '16:00:00', '2025-06-21 20:31:08', '2025-06-21 20:31:08');

-- --------------------------------------------------------

--
-- Table structure for table `justificacoes_faltas`
--

CREATE TABLE `justificacoes_faltas` (
  `id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `data_falta` date NOT NULL,
  `tipo_justificacao` enum('Férias','Doença','Pessoal','Formação','Outro') NOT NULL,
  `descricao` text DEFAULT NULL,
  `documentos_anexos` text DEFAULT NULL,
  `status` enum('pendente','aprovada','rejeitada') DEFAULT 'pendente',
  `aprovada_por` int(11) DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `impacto_salarial` enum('sem_impacto','desconto_parcial','desconto_total') DEFAULT 'sem_impacto',
  `percentual_pagamento` decimal(5,2) DEFAULT 100.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_atividades`
--

CREATE TABLE `log_atividades` (
  `id` int(11) NOT NULL,
  `adm_id` int(11) NOT NULL,
  `acao` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `politicas_ausencia`
--

CREATE TABLE `politicas_ausencia` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo_ausencia` enum('Férias','Doença','Pessoal','Formação','Outro') NOT NULL,
  `salario_base_percentual` decimal(5,2) DEFAULT 100.00,
  `subsidio_alimentacao` tinyint(1) DEFAULT 1,
  `subsidio_transporte` tinyint(1) DEFAULT 1,
  `outros_subsidios` tinyint(1) DEFAULT 1,
  `dias_maximos_ano` int(11) DEFAULT 0,
  `requer_aprovacao` tinyint(1) DEFAULT 0,
  `requer_documento` tinyint(1) DEFAULT 0,
  `descricao_politica` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `politicas_ausencia`
--

INSERT INTO `politicas_ausencia` (`id`, `empresa_id`, `tipo_ausencia`, `salario_base_percentual`, `subsidio_alimentacao`, `subsidio_transporte`, `outros_subsidios`, `dias_maximos_ano`, `requer_aprovacao`, `requer_documento`, `descricao_politica`, `data_criacao`, `data_atualizacao`) VALUES
(1, 1, 'Férias', 100.00, 0, 0, 0, 22, 0, 0, 'Férias anuais - direito adquirido. Salário-base pago, subsídios conforme regulamento interno.', '2025-06-21 22:30:02', '2025-06-22 20:35:38'),
(2, 1, 'Doença', 100.00, 0, 0, 0, 180, 1, 1, 'Baixa médica - salário-base pago até 6 meses, subsídios conforme política interna.', '2025-06-21 22:30:02', '2025-06-22 20:35:38'),
(3, 1, 'Pessoal', 0.00, 0, 0, 0, 0, 1, 0, 'Licença pessoal sem remuneração - por solicitação do trabalhador.', '2025-06-21 22:30:02', '2025-06-22 20:35:38'),
(4, 1, 'Formação', 100.00, 1, 1, 1, 0, 1, 1, 'Formação promovida pela empresa - remuneração integral mantida.', '2025-06-21 22:30:02', '2025-06-22 20:35:38'),
(5, 1, 'Outro', 0.00, 0, 0, 0, 0, 1, 1, 'Outras licenças - sem remuneração, salvo acordo interno.', '2025-06-21 22:30:02', '2025-06-22 20:35:38'),
(6, 8, 'Férias', 100.00, 0, 0, 0, 22, 0, 0, 'Férias anuais - direito adquirido. Salário-base pago, subsídios conforme regulamento interno.', '2025-06-21 22:33:24', '2025-06-22 20:35:37'),
(7, 8, 'Doença', 100.00, 0, 0, 0, 180, 1, 1, 'Baixa médica - salário-base pago até 6 meses, subsídios conforme política interna.', '2025-06-21 22:33:25', '2025-06-22 20:35:37'),
(8, 8, 'Pessoal', 0.00, 0, 0, 0, 0, 1, 0, 'Licença pessoal sem remuneração - por solicitação do trabalhador.', '2025-06-21 22:33:25', '2025-06-22 20:35:37'),
(9, 8, 'Formação', 100.00, 1, 1, 1, 0, 1, 1, 'Formação promovida pela empresa - remuneração integral mantida.', '2025-06-21 22:33:26', '2025-06-22 20:35:37'),
(10, 8, 'Outro', 0.00, 0, 0, 0, 0, 1, 1, 'Outras licenças - sem remuneração, salvo acordo interno.', '2025-06-21 22:33:26', '2025-06-22 20:35:37'),
(11, 7, 'Férias', 100.00, 0, 0, 0, 22, 0, 0, 'Férias anuais - direito adquirido. Salário-base pago, subsídios conforme regulamento interno.', '2025-06-22 19:56:14', '2025-06-22 19:56:14'),
(12, 9, 'Férias', 100.00, 0, 0, 0, 22, 0, 0, 'Férias anuais - direito adquirido. Salário-base pago, subsídios conforme regulamento interno.', '2025-06-22 19:56:14', '2025-06-22 19:56:14'),
(14, 7, 'Doença', 100.00, 0, 0, 0, 180, 1, 1, 'Baixa médica - salário-base pago até 6 meses, subsídios conforme política interna.', '2025-06-22 19:56:14', '2025-06-22 19:56:14'),
(15, 9, 'Doença', 100.00, 0, 0, 0, 180, 1, 1, 'Baixa médica - salário-base pago até 6 meses, subsídios conforme política interna.', '2025-06-22 19:56:14', '2025-06-22 19:56:14'),
(17, 7, 'Pessoal', 0.00, 0, 0, 0, 0, 1, 0, 'Licença pessoal sem remuneração - por solicitação do trabalhador.', '2025-06-22 19:56:14', '2025-06-22 19:56:14'),
(18, 9, 'Pessoal', 0.00, 0, 0, 0, 0, 1, 0, 'Licença pessoal sem remuneração - por solicitação do trabalhador.', '2025-06-22 19:56:14', '2025-06-22 19:56:14'),
(20, 7, 'Formação', 100.00, 1, 1, 1, 0, 1, 1, 'Formação promovida pela empresa - remuneração integral mantida.', '2025-06-22 19:56:14', '2025-06-22 19:56:14'),
(21, 9, 'Formação', 100.00, 1, 1, 1, 0, 1, 1, 'Formação promovida pela empresa - remuneração integral mantida.', '2025-06-22 19:56:14', '2025-06-22 19:56:14'),
(23, 7, 'Outro', 0.00, 0, 0, 0, 0, 1, 1, 'Outras licenças - sem remuneração, salvo acordo interno.', '2025-06-22 19:56:14', '2025-06-22 19:56:14'),
(24, 9, 'Outro', 0.00, 0, 0, 0, 0, 1, 1, 'Outras licenças - sem remuneração, salvo acordo interno.', '2025-06-22 19:56:14', '2025-06-22 19:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `politicas_trabalho`
--

CREATE TABLE `politicas_trabalho` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo` enum('horario','homeoffice','vestimenta') NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descricao` text NOT NULL,
  `valor` varchar(255) NOT NULL,
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `processamento_salarial`
--

CREATE TABLE `processamento_salarial` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `mes_referencia` date NOT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `total_subsidios` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_descontos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `salario_liquido` decimal(10,2) NOT NULL,
  `status` enum('pendente','processado','pago') NOT NULL DEFAULT 'pendente',
  `data_processamento` timestamp NULL DEFAULT NULL,
  `data_pagamento` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `redefinicao_senha`
--

CREATE TABLE `redefinicao_senha` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `data_expiracao` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registros_ponto`
--

CREATE TABLE `registros_ponto` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_saida` time DEFAULT NULL,
  `tipo_registro` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `entrada` datetime DEFAULT NULL,
  `saida` datetime DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registros_ponto`
--

INSERT INTO `registros_ponto` (`id`, `empresa_id`, `funcionario_id`, `data`, `hora_entrada`, `hora_saida`, `tipo_registro`, `status`, `entrada`, `saida`, `observacao`, `created_at`, `updated_at`) VALUES
(1, 8, 51, '2025-06-21', '00:51:00', '10:51:00', 'saida', 'presente', NULL, NULL, '', '2025-06-20 22:51:31', '2025-06-20 22:51:49'),
(2, 8, 52, '2025-06-21', '21:34:00', NULL, 'entrada', 'atrasado', NULL, NULL, '', '2025-06-21 19:35:13', '2025-06-21 19:35:13'),
(3, 8, 51, '2025-06-22', NULL, NULL, 'entrada', 'presente', '0000-00-00 00:00:00', NULL, '', '2025-06-21 22:13:22', '2025-06-21 22:13:22'),
(4, 8, 53, '2025-06-22', NULL, NULL, 'entrada', 'presente', '0000-00-00 00:00:00', NULL, '', '2025-06-21 22:14:28', '2025-06-21 22:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `subsidios_funcionarios`
--

CREATE TABLE `subsidios_funcionarios` (
  `id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `subsidio_id` int(11) NOT NULL,
  `tipo_subsidio` enum('obrigatorio','opcional','personalizado') NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `valor` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subsidios_funcionarios`
--

INSERT INTO `subsidios_funcionarios` (`id`, `funcionario_id`, `subsidio_id`, `tipo_subsidio`, `ativo`, `created_at`, `updated_at`, `valor`) VALUES
(1, 51, 1, 'opcional', 1, '2025-06-20 20:40:36', '2025-06-20 20:40:36', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `subsidios_padrao`
--

CREATE TABLE `subsidios_padrao` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('obrigatorio','opcional') NOT NULL,
  `valor_padrao` decimal(10,2) NOT NULL,
  `unidade` enum('percentual','valor_fixo') NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subsidios_padrao`
--

INSERT INTO `subsidios_padrao` (`id`, `empresa_id`, `nome`, `tipo`, `valor_padrao`, `unidade`, `ativo`, `created_at`) VALUES
(1, 8, 'alimentacao', 'opcional', 750.00, 'valor_fixo', 1, '2025-06-11 16:20:23'),
(2, 8, 'transporte', 'opcional', 1000.00, 'valor_fixo', 1, '2025-06-11 16:24:39'),
(3, 8, 'comunicacao', 'opcional', 1000.00, 'valor_fixo', 1, '2025-06-11 20:17:14'),
(4, 8, 'saude', 'opcional', 1500.00, 'valor_fixo', 0, '2025-06-11 20:24:39'),
(5, 8, 'ferias', 'obrigatorio', 100.00, 'percentual', 1, '2025-06-14 14:05:43'),
(6, 8, 'decimo_terceiro', 'obrigatorio', 100.00, 'percentual', 1, '2025-06-14 14:05:43'),
(7, 8, 'noturno', 'obrigatorio', 46.00, 'percentual', 1, '2025-06-14 14:05:43'),
(8, 8, 'risco', 'obrigatorio', 12.00, 'percentual', 1, '2025-06-14 14:05:43'),
(9, 8, 'horas_extras', 'obrigatorio', 42.00, 'percentual', 1, '2025-06-16 20:37:24'),
(23, 9, 'noturno', 'obrigatorio', 35.00, 'percentual', 1, '2025-06-20 19:28:42'),
(24, 9, 'horas_extras', 'obrigatorio', 30.00, 'percentual', 1, '2025-06-20 19:28:42'),
(25, 9, 'risco', 'obrigatorio', 25.00, 'percentual', 1, '2025-06-20 19:28:42'),
(26, 9, 'alimentacao', 'opcional', 0.00, 'valor_fixo', 1, '2025-06-20 20:00:55'),
(27, 9, 'transporte', 'opcional', 0.00, 'valor_fixo', 1, '2025-06-20 20:00:55'),
(28, 9, 'comunicacao', 'opcional', 0.00, 'valor_fixo', 0, '2025-06-20 20:00:56'),
(29, 9, 'saude', 'opcional', 0.00, 'valor_fixo', 0, '2025-06-20 20:00:56'),
(30, 9, 'ferias', 'obrigatorio', 100.00, 'percentual', 1, '2025-06-20 20:00:56'),
(31, 9, 'decimo_terceiro', 'obrigatorio', 100.00, 'percentual', 1, '2025-06-20 20:00:56'),
(50, 7, 'alimentacao', 'opcional', 0.00, 'valor_fixo', 1, '2025-06-20 20:27:57'),
(51, 7, 'transporte', 'opcional', 0.00, 'valor_fixo', 1, '2025-06-20 20:27:57'),
(52, 7, 'comunicacao', 'opcional', 0.00, 'valor_fixo', 1, '2025-06-20 20:27:57'),
(53, 7, 'saude', 'opcional', 0.00, 'valor_fixo', 1, '2025-06-20 20:27:57'),
(54, 7, 'ferias', 'obrigatorio', 100.00, 'percentual', 1, '2025-06-20 20:27:57'),
(55, 7, 'decimo_terceiro', 'obrigatorio', 100.00, 'percentual', 1, '2025-06-20 20:27:57'),
(56, 7, 'noturno', 'obrigatorio', 35.00, 'percentual', 1, '2025-06-20 20:27:57'),
(57, 7, 'horas_extras', 'obrigatorio', 50.00, 'percentual', 1, '2025-06-20 20:27:57'),
(58, 7, 'risco', 'obrigatorio', 20.00, 'percentual', 1, '2025-06-20 20:27:57');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_funcionarios_terminados`
-- (See below for the actual view)
--
CREATE TABLE `vw_funcionarios_terminados` (
`id_fun` int(11)
,`num_mecanografico` varchar(20)
,`nome` varchar(100)
,`foto` varchar(255)
,`bi` varchar(14)
,`emissao_bi` date
,`validade_bi` date
,`data_nascimento` date
,`pais` varchar(50)
,`morada` varchar(255)
,`genero` enum('Masculino','Feminino')
,`num_agregados` int(11)
,`telemovel` varchar(20)
,`email` varchar(150)
,`estado` enum('Ativo','Inativo','Terminado')
,`data_termino` datetime
,`cargo_nome` varchar(100)
,`departamento_nome` varchar(100)
,`tipo_trabalhador` enum('Efetivo','Temporário','Estagiário','Autônomo','Freelancer','Terceirizado','Intermitente','Voluntário')
,`num_ss` varchar(30)
,`data_admissao` date
,`dias_terminado` int(7)
);

-- --------------------------------------------------------

--
-- Structure for view `feriados_angola_formatados`
--
DROP TABLE IF EXISTS `feriados_angola_formatados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `feriados_angola_formatados`  AS SELECT `feriados_angola`.`id` AS `id`, `feriados_angola`.`data_feriado` AS `data_feriado`, `formata_data_pt`(`feriados_angola`.`data_feriado`) AS `data_feriado_pt`, `feriados_angola`.`nome_feriado` AS `nome_feriado` FROM `feriados_angola` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_funcionarios_terminados`
--
DROP TABLE IF EXISTS `vw_funcionarios_terminados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_funcionarios_terminados`  AS SELECT `f`.`id_fun` AS `id_fun`, `f`.`num_mecanografico` AS `num_mecanografico`, `f`.`nome` AS `nome`, `f`.`foto` AS `foto`, `f`.`bi` AS `bi`, `f`.`emissao_bi` AS `emissao_bi`, `f`.`validade_bi` AS `validade_bi`, `f`.`data_nascimento` AS `data_nascimento`, `f`.`pais` AS `pais`, `f`.`morada` AS `morada`, `f`.`genero` AS `genero`, `f`.`num_agregados` AS `num_agregados`, `f`.`telemovel` AS `telemovel`, `f`.`email` AS `email`, `f`.`estado` AS `estado`, `f`.`data_termino` AS `data_termino`, `c`.`nome` AS `cargo_nome`, `d`.`nome` AS `departamento_nome`, `f`.`tipo_trabalhador` AS `tipo_trabalhador`, `f`.`num_ss` AS `num_ss`, `f`.`data_admissao` AS `data_admissao`, to_days(curdate()) - to_days(`f`.`data_termino`) AS `dias_terminado` FROM ((`funcionario` `f` left join `cargos` `c` on(`f`.`cargo` = `c`.`id`)) left join `departamentos` `d` on(`f`.`departamento` = `d`.`id`)) WHERE `f`.`estado` = 'Terminado' ORDER BY `f`.`data_termino` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adm`
--
ALTER TABLE `adm`
  ADD PRIMARY KEY (`id_adm`),
  ADD UNIQUE KEY `email_UNIQUE` (`email`);

--
-- Indexes for table `adm_sessions`
--
ALTER TABLE `adm_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `unique_session` (`adm_id`,`user_agent`,`ip_address`);

--
-- Indexes for table `ausencias`
--
ALTER TABLE `ausencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `funcionario_id` (`funcionario_id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indexes for table `bancos_ativos`
--
ALTER TABLE `bancos_ativos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indexes for table `beneficios`
--
ALTER TABLE `beneficios`
  ADD PRIMARY KEY (`id_beneficio`),
  ADD KEY `fk_beneficios_funcionario1_idx` (`fun_id`);

--
-- Indexes for table `cargos`
--
ALTER TABLE `cargos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `departamento_id` (`departamento_id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indexes for table `configuracoes_seguranca`
--
ALTER TABLE `configuracoes_seguranca`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adm_id` (`adm_id`);

--
-- Indexes for table `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indexes for table `dispositivos_confiaveis`
--
ALTER TABLE `dispositivos_confiaveis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dispositivo` (`adm_id`,`user_agent`,`ip_address`);

--
-- Indexes for table `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `num_funcionario` (`num_funcionario`);

--
-- Indexes for table `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`id_empresa`,`adm_id`),
  ADD UNIQUE KEY `nipc_UNIQUE` (`nipc`),
  ADD KEY `fk_empresa_adm_idx` (`adm_id`);

--
-- Indexes for table `falta`
--
ALTER TABLE `falta`
  ADD PRIMARY KEY (`id_falta`),
  ADD KEY `fk_falta_funcionario1_idx` (`fun_id`);

--
-- Indexes for table `feriados_angola`
--
ALTER TABLE `feriados_angola`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `funcionario`
--
ALTER TABLE `funcionario`
  ADD PRIMARY KEY (`id_fun`),
  ADD UNIQUE KEY `bi` (`bi`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `num_conta_bancaria` (`num_conta_bancaria`),
  ADD UNIQUE KEY `iban` (`iban`),
  ADD UNIQUE KEY `num_ss` (`num_ss`),
  ADD UNIQUE KEY `num_mecanografico` (`num_mecanografico`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `idx_estado_data_termino` (`estado`,`data_termino`);

--
-- Indexes for table `historico_ajustes_salariais`
--
ALTER TABLE `historico_ajustes_salariais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `funcionario_id` (`funcionario_id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `mes` (`mes`,`ano`),
  ADD KEY `tipo_ajuste` (`tipo_ajuste`);

--
-- Indexes for table `horarios_funcionarios`
--
ALTER TABLE `horarios_funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `funcionario_id` (`funcionario_id`);

--
-- Indexes for table `justificacoes_faltas`
--
ALTER TABLE `justificacoes_faltas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `funcionario_id` (`funcionario_id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `data_falta` (`data_falta`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `log_atividades`
--
ALTER TABLE `log_atividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adm_id` (`adm_id`);

--
-- Indexes for table `politicas_ausencia`
--
ALTER TABLE `politicas_ausencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `empresa_id` (`empresa_id`,`tipo_ausencia`),
  ADD KEY `empresa_id_2` (`empresa_id`);

--
-- Indexes for table `politicas_trabalho`
--
ALTER TABLE `politicas_trabalho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indexes for table `processamento_salarial`
--
ALTER TABLE `processamento_salarial`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `funcionario_mes` (`funcionario_id`,`mes_referencia`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indexes for table `redefinicao_senha`
--
ALTER TABLE `redefinicao_senha`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registros_ponto`
--
ALTER TABLE `registros_ponto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `funcionario_id` (`funcionario_id`,`data`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `data` (`data`);

--
-- Indexes for table `subsidios_funcionarios`
--
ALTER TABLE `subsidios_funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `funcionario_subsidio` (`funcionario_id`,`subsidio_id`),
  ADD KEY `subsidio_id` (`subsidio_id`);

--
-- Indexes for table `subsidios_padrao`
--
ALTER TABLE `subsidios_padrao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_empresa_nome` (`empresa_id`,`nome`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adm`
--
ALTER TABLE `adm`
  MODIFY `id_adm` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `ausencias`
--
ALTER TABLE `ausencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `bancos_ativos`
--
ALTER TABLE `bancos_ativos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `beneficios`
--
ALTER TABLE `beneficios`
  MODIFY `id_beneficio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cargos`
--
ALTER TABLE `cargos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `configuracoes_seguranca`
--
ALTER TABLE `configuracoes_seguranca`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `dispositivos_confiaveis`
--
ALTER TABLE `dispositivos_confiaveis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `empresa`
--
ALTER TABLE `empresa`
  MODIFY `id_empresa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `falta`
--
ALTER TABLE `falta`
  MODIFY `id_falta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feriados_angola`
--
ALTER TABLE `feriados_angola`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `funcionario`
--
ALTER TABLE `funcionario`
  MODIFY `id_fun` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `historico_ajustes_salariais`
--
ALTER TABLE `historico_ajustes_salariais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `horarios_funcionarios`
--
ALTER TABLE `horarios_funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `justificacoes_faltas`
--
ALTER TABLE `justificacoes_faltas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_atividades`
--
ALTER TABLE `log_atividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `politicas_ausencia`
--
ALTER TABLE `politicas_ausencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `politicas_trabalho`
--
ALTER TABLE `politicas_trabalho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `processamento_salarial`
--
ALTER TABLE `processamento_salarial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `redefinicao_senha`
--
ALTER TABLE `redefinicao_senha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registros_ponto`
--
ALTER TABLE `registros_ponto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subsidios_funcionarios`
--
ALTER TABLE `subsidios_funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subsidios_padrao`
--
ALTER TABLE `subsidios_padrao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adm_sessions`
--
ALTER TABLE `adm_sessions`
  ADD CONSTRAINT `adm_sessions_ibfk_1` FOREIGN KEY (`adm_id`) REFERENCES `adm` (`id_adm`) ON DELETE CASCADE;

--
-- Constraints for table `ausencias`
--
ALTER TABLE `ausencias`
  ADD CONSTRAINT `ausencias_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_fun`),
  ADD CONSTRAINT `ausencias_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id_empresa`);

--
-- Constraints for table `bancos_ativos`
--
ALTER TABLE `bancos_ativos`
  ADD CONSTRAINT `bancos_ativos_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id_empresa`);

--
-- Constraints for table `beneficios`
--
ALTER TABLE `beneficios`
  ADD CONSTRAINT `beneficios_ibfk_1` FOREIGN KEY (`fun_id`) REFERENCES `funcionario` (`id_fun`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `cargos`
--
ALTER TABLE `cargos`
  ADD CONSTRAINT `cargos_ibfk_1` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`),
  ADD CONSTRAINT `cargos_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id_empresa`);

--
-- Constraints for table `configuracoes_seguranca`
--
ALTER TABLE `configuracoes_seguranca`
  ADD CONSTRAINT `configuracoes_seguranca_ibfk_1` FOREIGN KEY (`adm_id`) REFERENCES `adm` (`id_adm`) ON DELETE CASCADE;

--
-- Constraints for table `departamentos`
--
ALTER TABLE `departamentos`
  ADD CONSTRAINT `departamentos_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id_empresa`);

--
-- Constraints for table `dispositivos_confiaveis`
--
ALTER TABLE `dispositivos_confiaveis`
  ADD CONSTRAINT `dispositivos_confiaveis_ibfk_1` FOREIGN KEY (`adm_id`) REFERENCES `adm` (`id_adm`) ON DELETE CASCADE;

--
-- Constraints for table `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`num_funcionario`) REFERENCES `funcionario` (`id_fun`) ON DELETE CASCADE;

--
-- Constraints for table `empresa`
--
ALTER TABLE `empresa`
  ADD CONSTRAINT `fk_empresa_adm` FOREIGN KEY (`adm_id`) REFERENCES `adm` (`id_adm`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `falta`
--
ALTER TABLE `falta`
  ADD CONSTRAINT `falta_ibfk_1` FOREIGN KEY (`fun_id`) REFERENCES `funcionario` (`id_fun`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `funcionario`
--
ALTER TABLE `funcionario`
  ADD CONSTRAINT `funcionario_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id_empresa`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `historico_ajustes_salariais`
--
ALTER TABLE `historico_ajustes_salariais`
  ADD CONSTRAINT `historico_ajustes_salariais_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_fun`) ON DELETE CASCADE;

--
-- Constraints for table `horarios_funcionarios`
--
ALTER TABLE `horarios_funcionarios`
  ADD CONSTRAINT `horarios_funcionarios_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_fun`);

--
-- Constraints for table `justificacoes_faltas`
--
ALTER TABLE `justificacoes_faltas`
  ADD CONSTRAINT `justificacoes_faltas_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_fun`) ON DELETE CASCADE;

--
-- Constraints for table `log_atividades`
--
ALTER TABLE `log_atividades`
  ADD CONSTRAINT `log_atividades_ibfk_1` FOREIGN KEY (`adm_id`) REFERENCES `adm` (`id_adm`) ON DELETE CASCADE;

--
-- Constraints for table `politicas_trabalho`
--
ALTER TABLE `politicas_trabalho`
  ADD CONSTRAINT `politicas_trabalho_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id_empresa`);

--
-- Constraints for table `processamento_salarial`
--
ALTER TABLE `processamento_salarial`
  ADD CONSTRAINT `fk_processamento_salarial_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id_empresa`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_processamento_salarial_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_fun`) ON DELETE CASCADE;

--
-- Constraints for table `registros_ponto`
--
ALTER TABLE `registros_ponto`
  ADD CONSTRAINT `registros_ponto_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_fun`);

--
-- Constraints for table `subsidios_funcionarios`
--
ALTER TABLE `subsidios_funcionarios`
  ADD CONSTRAINT `fk_subsidios_funcionarios_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_fun`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subsidios_funcionarios_subsidio` FOREIGN KEY (`subsidio_id`) REFERENCES `subsidios_padrao` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subsidios_padrao`
--
ALTER TABLE `subsidios_padrao`
  ADD CONSTRAINT `fk_subsidios_padrao_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id_empresa`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;