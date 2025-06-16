-- Criação da tabela de empresas
CREATE TABLE IF NOT EXISTS empresas_recrutamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    endereco VARCHAR(255),
    descricao TEXT,
    setor VARCHAR(100),
    tamanho VARCHAR(50),
    website VARCHAR(255),
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Ativo', 'Inativo', 'Pendente') DEFAULT 'Ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criação da tabela de candidatos
CREATE TABLE IF NOT EXISTS candidatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    data_nascimento DATE,
    endereco VARCHAR(255),
    formacao TEXT,
    experiencia TEXT,
    habilidades TEXT,
    curriculo_path VARCHAR(255),
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Ativo', 'Inativo', 'Pendente') DEFAULT 'Ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criação da tabela de vagas
CREATE TABLE IF NOT EXISTS vagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    requisitos TEXT,
    departamento VARCHAR(100),
    localizacao VARCHAR(255),
    tipo_contrato VARCHAR(50) NOT NULL,
    salario_min DECIMAL(10,2),
    salario_max DECIMAL(10,2),
    data_publicacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_expiracao DATE,
    status ENUM('Aberta', 'Fechada', 'Pausada', 'Rascunho') DEFAULT 'Aberta',
    FOREIGN KEY (empresa_id) REFERENCES empresas_recrutamento(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criação da tabela de candidaturas
CREATE TABLE IF NOT EXISTS candidaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidato_id INT NOT NULL,
    vaga_id INT NOT NULL,
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pendente', 'Visualizada', 'Em análise', 'Entrevista', 'Aprovado', 'Rejeitado') DEFAULT 'Pendente',
    notas TEXT,
    FOREIGN KEY (candidato_id) REFERENCES candidatos(id) ON DELETE CASCADE,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE CASCADE,
    UNIQUE KEY candidato_vaga (candidato_id, vaga_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para otimização de consultas
CREATE INDEX idx_vaga_status ON vagas(status);
CREATE INDEX idx_vaga_data ON vagas(data_publicacao);
CREATE INDEX idx_candidaturas_status ON candidaturas(status);

-- Tabela para armazenar mensagens entre candidatos e empresas
CREATE TABLE IF NOT EXISTS mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidatura_id INT NOT NULL,
    remetente_tipo ENUM('candidato', 'empresa') NOT NULL,
    remetente_id INT NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lida BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 