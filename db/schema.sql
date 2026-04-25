-- ============================================================
-- SeFull Bet - Schema PostgreSQL
-- Cada usuario tem UUID unico
-- ============================================================

CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- USUARIOS (UUID como ID unico)
CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    perfil VARCHAR(20) DEFAULT 'gratis' CHECK (perfil IN ('gratis','vip','premium','admin')),
    status VARCHAR(20) DEFAULT 'pendente' CHECK (status IN ('pendente','ativo','inativo')),
    expiracao DATE,
    termo_status VARCHAR(50) DEFAULT 'ACEITO INTEGRALMENTE',
    criado_em TIMESTAMP DEFAULT NOW()
);

-- PALPITES / SINAIS
CREATE TABLE IF NOT EXISTS palpites (
    id SERIAL PRIMARY KEY,
    data DATE,
    hora VARCHAR(10),
    confronto VARCHAR(255),
    mercado VARCHAR(255),
    odd VARCHAR(20),
    tipo VARCHAR(20) DEFAULT 'gratis' CHECK (tipo IN ('gratis','vip')),
    status VARCHAR(20) DEFAULT 'live',
    placar VARCHAR(20) DEFAULT '',
    criado_em TIMESTAMP DEFAULT NOW()
);

-- VITORIAS
CREATE TABLE IF NOT EXISTS vitorias (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255),
    assunto TEXT,
    img1 TEXT,
    img2 TEXT,
    fixado BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT NOW()
);

-- NOTICIAS
CREATE TABLE IF NOT EXISTS noticias (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255),
    midia TEXT,
    conteudo TEXT,
    fixado BOOLEAN DEFAULT FALSE,
    data VARCHAR(20),
    criado_em TIMESTAMP DEFAULT NOW()
);

-- BASE HISTORICA (analisador de odds)
CREATE TABLE IF NOT EXISTS base_historica (
    id SERIAL PRIMARY KEY,
    hora VARCHAR(20),
    liga VARCHAR(255),
    casa VARCHAR(255),
    fora VARCHAR(255),
    odd_casa DECIMAL(6,3) DEFAULT 0,
    odd_empate DECIMAL(6,3) DEFAULT 0,
    odd_fora DECIMAL(6,3) DEFAULT 0,
    gol_casa INTEGER DEFAULT 0,
    gol_fora INTEGER DEFAULT 0,
    gols_total INTEGER DEFAULT 0,
    resultado VARCHAR(10),
    ambos_marcam VARCHAR(10),
    over_05 VARCHAR(5),
    over_15 VARCHAR(5),
    over_25 VARCHAR(5),
    over_35 VARCHAR(5),
    over_45 VARCHAR(5),
    dados_extra JSONB,
    criado_em TIMESTAMP DEFAULT NOW()
);

-- Indice para buscas do analisador
CREATE INDEX IF NOT EXISTS idx_base_odds ON base_historica (odd_casa, odd_empate, odd_fora);

-- Indice para deduplicacao na importacao
CREATE UNIQUE INDEX IF NOT EXISTS idx_base_unique ON base_historica (hora, liga, casa, fora);
