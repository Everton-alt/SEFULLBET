require('dotenv').config();
const express = require('express');
const cors = require('cors');
const path = require('path');
const fs = require('fs');
const pool = require('./db/pool');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json({ limit: '50mb' }));

// Servir frontend (arquivos estaticos)
app.use(express.static(path.join(__dirname, 'public')));

// Rotas da API
app.use('/api/auth', require('./routes/auth'));
app.use('/api/palpites', require('./routes/palpites'));
app.use('/api/usuarios', require('./routes/usuarios'));
app.use('/api/vitorias', require('./routes/vitorias'));
app.use('/api/noticias', require('./routes/noticias'));
app.use('/api/importacao', require('./routes/importacao'));

// Health check
app.get('/api/health', (req, res) => res.json({ status: 'ok' }));

// Fallback: qualquer rota nao-API serve o index.html
app.get('*', (req, res) => {
  if (!req.path.startsWith('/api')) {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
  }
});

// Inicializar banco na primeira execucao e subir servidor
async function iniciar() {
  try {
    // Criar tabelas automaticamente se nao existirem
    const schema = fs.readFileSync(path.join(__dirname, 'db', 'schema.sql'), 'utf-8');
    await pool.query(schema);
    console.log('Banco de dados verificado.');

    // Criar admin padrao se nao existir
    const bcrypt = require('bcrypt');
    const adminEmail = process.env.ADMIN_EMAIL || 'admin@sefullbet.com';
    const adminPass = process.env.ADMIN_PASSWORD || '123456';
    const existe = await pool.query('SELECT id FROM users WHERE email = $1', [adminEmail]);
    if (existe.rows.length === 0) {
      const hash = await bcrypt.hash(adminPass, 10);
      await pool.query(
        `INSERT INTO users (nome, email, senha_hash, perfil, status)
         VALUES ('Administrador', $1, $2, 'admin', 'ativo')`,
        [adminEmail, hash]
      );
      console.log(`Admin criado: ${adminEmail}`);
    }

    app.listen(PORT, () => {
      console.log(`SeFull Bet rodando na porta ${PORT}`);
    });
  } catch (err) {
    console.error('Erro ao iniciar:', err);
    process.exit(1);
  }
}

iniciar();
