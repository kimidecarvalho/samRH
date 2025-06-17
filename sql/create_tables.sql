-- Create sam_emprego database if not exists
CREATE DATABASE IF NOT EXISTS sam_emprego;
USE sam_emprego;

-- Create adm table in sam_emprego
CREATE TABLE IF NOT EXISTS adm (
    id_adm INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create empresas_recrutamento table
CREATE TABLE IF NOT EXISTS empresas_recrutamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255),
    telefone VARCHAR(50),
    endereco TEXT,
    setor VARCHAR(100),
    tamanho VARCHAR(50),
    status VARCHAR(20) DEFAULT 'Ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
