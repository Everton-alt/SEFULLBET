-- Extensão para gerar UUIDs se necessário, ou usamos sequenciais com prefixo no PHP
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- TABELA DE USUÁRIOS (U-XXXXXX)
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    codigo_usuario VARCHAR(10) UNIQUE, -- Ex: U-123456
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    login VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil VARCHAR(20) CHECK (perfil IN ('Grátis', 'VIP', 'Platinum', 'Supervisor', 'Admin')),
    status_aprovacao VARCHAR(20) DEFAULT 'Ativo', -- 'Ativo' ou 'Aguardando Aprovação'
    creditos INT DEFAULT 1,
    data_expiracao DATE,
    indicado_por_id INT REFERENCES usuarios(id),
    termos_aceitos BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABELA DE DADOS IMPORTADOS (Planilha 17 Colunas)
CREATE TABLE dados_analisador (
    id_planilha VARCHAR(50) PRIMARY KEY, -- ID que vem do Excel
    liga VARCHAR(50),
    casa VARCHAR(100),
    fora VARCHAR(100),
    odd_casa DECIMAL(10,2),
    odd_empate DECIMAL(10,2),
    odd_fora DECIMAL(10,2),
    gol_casa INT,
    gol_fora INT,
    gols_total INT,
    resultado VARCHAR(10),
    ambos_marcam VARCHAR(5),
    over_05 VARCHAR(5),
    over_15 VARCHAR(5),
    over_25 VARCHAR(5),
    over_35 VARCHAR(5),
    over_45 VARCHAR(5),
    data_importacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABELA DE PALPITES (P-XXXXXX)
CREATE TABLE palpites (
    id SERIAL PRIMARY KEY,
    codigo_palpite VARCHAR(10) UNIQUE,
    data DATE DEFAULT CURRENT_DATE,
    hora TIME,
    confronto VARCHAR(150),
    mercado VARCHAR(100),
    valor_mercado VARCHAR(50),
    placar VARCHAR(20),
    odd DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'Pendente', -- 'Pendente', 'Green', 'Red'
    categoria VARCHAR(10), -- 'Grátis' ou 'VIP'
    exclusao_pendente BOOLEAN DEFAULT FALSE,
    postado_por INT REFERENCES usuarios(id)
);

-- TABELA DE GREENS (G-XXXXXX)
CREATE TABLE greens (
    id SERIAL PRIMARY KEY,
    codigo_green VARCHAR(10) UNIQUE,
    titulo VARCHAR(200),
    url_foto_topo TEXT,
    url_foto_miniatura TEXT,
    texto_completo TEXT,
    exclusao_pendente BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABELA DE NOTAS
CREATE TABLE notas (
    id SERIAL PRIMARY KEY,
    conteudo TEXT,
    exclusao_pendente BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABELA DE LOGS DE AUDITORIA (Para ações do Supervisor)
CREATE TABLE logs_auditoria (
    id SERIAL PRIMARY KEY,
    supervisor_id INT REFERENCES usuarios(id),
    acao TEXT,
    usuario_afetado_id INT,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);