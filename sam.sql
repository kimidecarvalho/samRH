-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 14, 2025 at 05:12 PM
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
(3, 'Diogo Oliveira', 'diogodm1225@gmail.com', '$2y$10$fPubpk27CMUX5Fgb1mLrg.Nx3SostAJfWqbbSJy2FjXmcapDQ2aZi', 2147483647, NULL, NULL, NULL, NULL, NULL),
(4, 'Kimi Carvalho', 'kienukimidecarvalho@gmail.com', '$2y$10$FqSfVOSIAp/gLuQ4V49US.y64.7ffr6F4d0BLDnPUhlhKKH3o5wXC', 2147483647, '', '', '', '2025-05-06', 'Administrador'),
(8, 'Freddy Teca', 'fr3ddyteca@gmail.com', '$2y$10$xIncaDUrTgBrFJfqipP4a.FuhwTXVg.lXb7K4chVAscRQwUGXghfK', 2147483647, NULL, NULL, NULL, NULL, NULL);

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
('vf3nptj0amriniiie2g19peoq3', 4, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '::1', '2025-06-12 10:38:06'),
('vv04n3d0n4pe7q54lhj4sc910m', 8, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '::1', '2025-05-30 23:34:10');

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
  `data_registro` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 2, 'Banco BIC', 'BIC', 0),
(3, 2, 'Banco Caixa Geral Angola', 'BCGA', 0),
(4, 2, 'Banco Comercial Angolano (BCA)', 'BCA', 0),
(6, 2, 'Banco de Desenvolvimento de Angola (BDA)', 'BDA', 0),
(7, 2, 'Banco de Poupança e Crédito (BPC)', 'BPC', 0),
(8, 2, 'Banco Económico', 'BE', 1),
(9, 2, 'Banco Fomento Angola (BFA)', 'BFA', 0),
(10, 2, 'Banco Millennium Atlântico', 'BMA', 0),
(11, 2, 'Banco Sol', 'SOL', 0),
(12, 2, 'Banco Valor', 'VALOR', 0),
(13, 2, 'Banco Yetu', 'YETU', 0),
(14, 2, 'Banco VTB África', 'VTB', 0),
(15, 2, 'Banco Angolano de Investimentos (BAI)', 'BAI', 0),
(17, 2, 'Banco de Poupança do Kimi', 'BPK', 0),
(18, 2, 'Kimi Arroz', 'KA', 0),
(19, 2, 'JosiBank', 'JBK', 1);

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
(1, 'Programador', 1, 333000.00, 2, '2025-05-30 21:03:34'),
(2, 'Vendas', 2, 220000.00, 2, '2025-05-30 21:08:09'),
(3, 'NNC', 3, 300000.00, 2, '2025-05-30 21:38:49'),
(7, 'Gestor de Produção', 1, 1500000.00, 2, '2025-06-05 13:38:09'),
(14, 'Centro', 9, 111222.00, 2, '2025-06-10 00:37:55'),
(15, 'limpador', 10, 230000.00, 2, '2025-06-10 22:14:06');

-- --------------------------------------------------------

--
-- Table structure for table `configuracoes_seguranca`
--

CREATE TABLE `configuracoes_seguranca` (
  `id` int(11) NOT NULL,
  `adm_id` int(11) NOT NULL,
  `dois_fatores` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `configuracoes_seguranca`
--

INSERT INTO `configuracoes_seguranca` (`id`, `adm_id`, `dois_fatores`) VALUES
(1, 4, 0);

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
(1, 'TI', 2, '2025-05-30 21:02:27'),
(2, 'Marketing', 2, '2025-05-30 21:07:41'),
(3, 'ORG', 2, '2025-05-30 21:12:58'),
(6, 'Saidas', 2, '2025-06-07 13:04:43'),
(7, 'KK2', 2, '2025-06-09 13:54:21'),
(9, 'Organização', 2, '2025-06-10 00:37:40'),
(10, 'Limpeza', 2, '2025-06-10 22:13:22');

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
(1, 4, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '::1', '2025-05-30 15:02:19', '2025-06-12 22:51:08'),
(4, 8, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '::1', '2025-05-30 23:32:40', '2025-05-30 23:32:40');

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

--
-- Dumping data for table `documentos`
--

INSERT INTO `documentos` (`id_documento`, `titulo`, `tipo`, `data`, `descricao`, `anexo`, `num_funcionario`, `folder`) VALUES
(1, 'CV - Kimi Carvalho - Jun..pdf', 'pdf', '2025-06-02', 'Documento enviado', '683dd4fb47c787.53469443.pdf', 1, 'documentacao'),
(2, 'CV - Kimi Carvalho.pdf', 'pdf', '2025-06-02', 'Documento enviado', '683dfe904b82e5.77933045.pdf', 1, 'documentacao');

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
(2, 'Carvalho Lda', '18041959', 'rua Pedro de Castro Van-Dunem Loy, Casa 4, Vila Ecocampo', 'kienukimidecarvalho@gmail.com', '924135515', 'servicos', 1, '2025-05-27', 4),
(4, 'Lil Teca SA', '12345', 'Nova Vida', 'fr3ddyteca@gmail.com', '975851987', 'tecnologia', 1, '2025-05-31', 8);

--
-- Triggers `empresa`
--
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
(1, '2024-01-01', 'Dia de Ano Novo'),
(2, '2024-02-04', 'Dia do Início da Luta Armada de Libertação Nacional'),
(3, '2024-03-08', 'Dia Internacional da Mulher'),
(4, '2024-04-04', 'Dia da Paz e Reconciliação Nacional'),
(5, '2024-05-01', 'Dia Internacional do Trabalhador'),
(6, '2024-06-01', 'Dia Internacional da Criança'),
(7, '2024-09-17', 'Dia do Fundador da Nação e dos Heróis Nacionais'),
(8, '2024-11-02', 'Dia dos Finados'),
(9, '2024-11-11', 'Dia da Independência Nacional'),
(10, '2024-12-25', 'Dia de Natal e da Família'),
(11, '2024-02-13', 'Carnaval'),
(12, '2024-01-01', 'Dia de Ano Novo'),
(13, '2024-02-04', 'Dia do Início da Luta Armada de Libertação Nacional'),
(14, '2024-03-08', 'Dia Internacional da Mulher'),
(15, '2024-04-04', 'Dia da Paz e Reconciliação Nacional'),
(16, '2024-05-01', 'Dia Internacional do Trabalhador'),
(17, '2024-06-01', 'Dia Internacional da Criança'),
(18, '2024-09-17', 'Dia do Fundador da Nação e dos Heróis Nacionais'),
(19, '2024-11-02', 'Dia dos Finados'),
(20, '2024-11-11', 'Dia da Independência Nacional'),
(21, '2024-12-25', 'Dia de Natal e da Família'),
(22, '2024-02-13', 'Carnaval');

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
(1, 'EMP-0001', 'Kimi Carvalho', NULL, '32432432423', '2025-05-06', '2025-06-30', '2006-12-05', 'angola', 'rua Pedro de Castro Van-Dunem Loy, Casa 4, Vila Ecocampo', 'Masculino', 6, '', '', '924135515', 'kienukimidecarvalho@gmail.com', 'Ativo', '2', '2', 'Efetivo', '1111', 'BE', '678786', 220000.00, '2432432432', '2025-05-28', 2, 'pendente_biometria', NULL),
(7, 'EMP-0002', 'Jorge Mundula', NULL, '32432432423322', '2025-05-05', '2025-06-05', '2025-05-06', 'angola', 'Fubu', 'Masculino', 2, '', '', '924135515', 'jorgemundula@gmail.com', 'Terminado', '6', '3', 'Efetivo', '111111154234', 'BPK', '432432432425', 90000.00, '2.432432432431231e20', '2025-05-31', 2, 'pendente_biometria', '2025-06-03 02:56:53'),
(8, 'EMP-0003', 'Diogo Oliveira', NULL, '32432432423334', '2025-06-01', '2028-10-18', '2006-02-13', 'angola', 'Nova Vida 111', 'Masculino', 1, '924133685', 'Kimi Carvalho', '924135515', 'diogo@gmail.com', 'Ativo', '2', '2', 'Efetivo', '111123432', 'BAI', '324324324235325', 220000.00, '243243243232', '2025-06-02', 2, 'pendente_biometria', NULL),
(12, 'EMP-0004', 'Kelson Mota', NULL, '32453453242', '2025-05-25', '2025-06-10', '2003-07-17', 'angola', 'Fubu Praça', 'Masculino', 1, '', '', '999999299', 'kelson@gmail.com', 'Terminado', '1', '1', 'Efetivo', '1111324324', 'BAI', '1244324', 333000.00, '2.432432432324324e16', '2025-06-02', 2, 'pendente_biometria', '2025-06-03 22:31:31'),
(13, 'EMP-0005', 'Josilde Costa', NULL, '12345', '2025-06-01', '2025-06-04', '2005-06-25', 'angola', 'Kilamba ', 'Masculino', 1, '', '', '923456723', 'josilde@gmail.com', 'Ativo', '3', '3', 'Efetivo', '542344', 'BAI', '1234421413432', 300000.00, '3423432423', '2025-06-02', 2, 'pendente_biometria', NULL),
(43, 'EMP-0006', 'Maros', 'fotos/func_683f7a7adc285.png', '12345678LA10', '2025-06-02', '2025-06-04', '2025-06-01', 'angola', 'Fubu Praça', 'Masculino', 2, '924135515', 'Kimi', '987672434', 'marcos@gmail.com', 'Ativo', '3', '3', 'Efetivo', '5423344', 'BAI', '3243324', 300000.00, '19453232', '2025-06-03', 2, 'pendente_biometria', NULL),
(44, 'EMP-0007', 'Maria Cose', NULL, '32556', '2025-06-03', '2025-06-21', '2025-06-03', 'angola', 'KK', 'Feminino', 3, '', '', '999435789', 'maria@gmail.com', 'Ativo', '14', '9', 'Efetivo', '324589', 'JBK', '876543', 111222.00, '957432432', '2025-06-05', 2, 'pendente_biometria', NULL);

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
(1, 1, '08:00:00', '16:00:00', '2025-06-12 11:41:56', '2025-06-12 11:41:56'),
(2, 8, '08:00:00', '16:00:00', '2025-06-12 11:46:06', '2025-06-12 11:46:06'),
(3, 13, '09:00:00', '16:00:00', '2025-06-12 11:46:06', '2025-06-12 15:08:42'),
(4, 43, '10:00:00', '16:00:00', '2025-06-12 11:46:06', '2025-06-12 11:50:43'),
(5, 44, '10:00:00', '17:00:00', '2025-06-12 11:46:06', '2025-06-12 11:46:27');

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

--
-- Dumping data for table `log_atividades`
--

INSERT INTO `log_atividades` (`id`, `adm_id`, `acao`, `ip_address`, `data_hora`) VALUES
(1, 4, 'Atualização de Perfil', '::1', '2025-05-30 16:31:24'),
(2, 4, 'Login Efetuado', '::1', '2025-05-30 17:05:31'),
(3, 4, 'Login Efetuado', '::1', '2025-05-30 19:31:03'),
(4, 4, 'Login Efetuado', '::1', '2025-05-30 19:32:59'),
(5, 4, 'Login Efetuado', '::1', '2025-05-30 23:26:10'),
(6, 8, 'Login Efetuado', '::1', '2025-05-30 23:34:06'),
(7, 4, 'Login Efetuado', '::1', '2025-06-02 19:06:12'),
(8, 4, 'Login Efetuado', '::1', '2025-06-11 10:01:42'),
(9, 4, 'Login Efetuado', '::1', '2025-06-12 10:20:59'),
(10, 4, 'Login Efetuado', '::1', '2025-06-12 10:29:14'),
(11, 4, 'Login Efetuado', '::1', '2025-06-12 10:30:20'),
(12, 4, 'Login Efetuado', '::1', '2025-06-12 10:34:01');

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
(1, 2, 8, '2025-06-12', '08:00:00', '12:56:00', 'saida', 'presente', NULL, NULL, '', '2025-06-12 10:51:14', '2025-06-12 23:51:04'),
(2, 2, 1, '2025-06-12', '12:56:00', '15:57:00', 'saida', 'atrasado', NULL, NULL, '', '2025-06-12 10:57:22', '2025-06-12 23:51:04'),
(3, 2, 44, '2025-06-12', '08:40:00', NULL, 'entrada', 'presente', NULL, NULL, '', '2025-06-12 11:47:00', '2025-06-12 23:51:04'),
(4, 2, 43, '2025-06-12', '08:41:00', '13:51:00', 'saida', 'ausente', NULL, NULL, '', '2025-06-12 11:51:09', '2025-06-12 23:51:04'),
(5, 2, 13, '2025-06-12', '08:01:00', NULL, 'entrada', 'atrasado', NULL, NULL, '', '2025-06-12 11:52:37', '2025-06-12 23:51:04'),
(6, 2, 1, '2025-06-13', '08:01:00', NULL, 'entrada', 'atrasado', NULL, NULL, '', '2025-06-12 12:01:50', '2025-06-12 23:51:04'),
(7, 2, 8, '2025-06-13', '07:59:00', NULL, 'entrada', 'presente', NULL, NULL, '', '2025-06-12 12:02:15', '2025-06-12 23:51:04'),
(8, 2, 43, '2025-06-13', '07:03:00', NULL, 'entrada', 'presente', NULL, NULL, '', '2025-06-12 12:04:02', '2025-06-12 23:51:04'),
(9, 2, 1, '2025-06-14', '08:01:00', '18:39:00', 'saida', 'presente', NULL, NULL, '', '2025-06-14 13:39:51', '2025-06-14 13:40:01');

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
(1, 8, 1, 'opcional', 1, '2025-06-11 17:45:06', '2025-06-14 13:35:59', 0.00),
(2, 13, 1, 'opcional', 0, '2025-06-11 17:47:06', '2025-06-13 22:10:22', 0.00),
(3, 8, 2, 'opcional', 1, '2025-06-11 21:24:46', '2025-06-14 13:36:07', 0.00),
(4, 13, 2, 'opcional', 0, '2025-06-11 21:24:47', '2025-06-13 22:10:23', 0.00),
(5, 1, 2, 'opcional', 1, '2025-06-11 21:24:49', '2025-06-14 13:36:07', 0.00),
(6, 1, 1, 'opcional', 1, '2025-06-11 22:07:04', '2025-06-14 13:35:58', 0.00),
(7, 8, 4, 'opcional', 0, '2025-06-11 22:07:41', '2025-06-13 22:10:26', 0.00),
(8, 43, 1, 'opcional', 0, '2025-06-12 02:24:30', '2025-06-13 22:10:22', 0.00),
(9, 43, 4, 'opcional', 0, '2025-06-12 02:24:34', '2025-06-13 22:10:26', 0.00),
(10, 44, 4, 'opcional', 0, '2025-06-12 02:24:45', '2025-06-13 22:10:26', 0.00),
(11, 1, 4, 'opcional', 0, '2025-06-12 02:29:58', '2025-06-13 22:10:26', 0.00),
(12, 1, 3, 'opcional', 1, '2025-06-12 02:48:55', '2025-06-14 13:36:03', 0.00),
(13, 44, 3, 'opcional', 0, '2025-06-12 02:48:56', '2025-06-13 22:10:24', 0.00),
(14, 44, 1, 'opcional', 0, '2025-06-12 02:54:13', '2025-06-13 22:10:22', 0.00),
(15, 44, 2, 'opcional', 0, '2025-06-12 02:58:16', '2025-06-13 22:10:23', 0.00),
(16, 43, 2, 'opcional', 0, '2025-06-12 02:58:16', '2025-06-13 22:10:23', 0.00),
(17, 8, 3, 'opcional', 1, '2025-06-12 02:59:03', '2025-06-14 13:36:03', 0.00),
(18, 43, 3, 'opcional', 0, '2025-06-12 02:59:08', '2025-06-13 22:10:24', 0.00),
(19, 13, 3, 'opcional', 0, '2025-06-12 02:59:08', '2025-06-13 22:10:24', 0.00),
(20, 13, 4, 'opcional', 0, '2025-06-12 03:00:02', '2025-06-13 22:10:26', 0.00);

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
(1, 2, 'alimentacao', 'opcional', 750.00, 'valor_fixo', 1, '2025-06-11 17:20:23'),
(2, 2, 'transporte', 'opcional', 1000.00, 'valor_fixo', 1, '2025-06-11 17:24:39'),
(3, 2, 'comunicacao', 'opcional', 1000.00, 'valor_fixo', 1, '2025-06-11 21:17:14'),
(4, 2, 'saude', 'opcional', 1000.00, 'valor_fixo', 0, '2025-06-11 21:24:39'),
(5, 2, 'ferias', 'obrigatorio', 100.00, 'percentual', 1, '2025-06-14 15:05:43'),
(6, 2, 'decimo_terceiro', 'obrigatorio', 100.00, 'percentual', 1, '2025-06-14 15:05:43'),
(7, 2, 'noturno', 'obrigatorio', 35.00, 'percentual', 1, '2025-06-14 15:05:43'),
(8, 2, 'risco', 'obrigatorio', 20.00, 'percentual', 1, '2025-06-14 15:05:43');

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
-- Indexes for table `horarios_funcionarios`
--
ALTER TABLE `horarios_funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `funcionario_id` (`funcionario_id`);

--
-- Indexes for table `log_atividades`
--
ALTER TABLE `log_atividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adm_id` (`adm_id`);

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
  ADD KEY `empresa_id` (`empresa_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adm`
--
ALTER TABLE `adm`
  MODIFY `id_adm` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ausencias`
--
ALTER TABLE `ausencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bancos_ativos`
--
ALTER TABLE `bancos_ativos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `beneficios`
--
ALTER TABLE `beneficios`
  MODIFY `id_beneficio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cargos`
--
ALTER TABLE `cargos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `configuracoes_seguranca`
--
ALTER TABLE `configuracoes_seguranca`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dispositivos_confiaveis`
--
ALTER TABLE `dispositivos_confiaveis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `empresa`
--
ALTER TABLE `empresa`
  MODIFY `id_empresa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `falta`
--
ALTER TABLE `falta`
  MODIFY `id_falta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feriados_angola`
--
ALTER TABLE `feriados_angola`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `funcionario`
--
ALTER TABLE `funcionario`
  MODIFY `id_fun` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `horarios_funcionarios`
--
ALTER TABLE `horarios_funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `log_atividades`
--
ALTER TABLE `log_atividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `subsidios_funcionarios`
--
ALTER TABLE `subsidios_funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `subsidios_padrao`
--
ALTER TABLE `subsidios_padrao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
-- Constraints for table `horarios_funcionarios`
--
ALTER TABLE `horarios_funcionarios`
  ADD CONSTRAINT `horarios_funcionarios_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id_fun`);

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