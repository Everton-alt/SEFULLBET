require('dotenv').config();
const fs = require('fs');
const path = require('path');
const bcrypt = require('bcrypt');
const pool = require('./pool');

async function init() {
  console.log('Criando tabelas...');
  const schema = fs.readFileSync(path.join(__dirname, 'schema.sql'), 'utf-8');
  await pool.query(schema);
  console.log('Tabelas criadas.');

  // Criar admin padrao se nao existir
  const adminEmail = process.env.ADMIN_EMAIL || 'admin@sefullbet.com';
  const adminPass = process.env.ADMIN_PASSWORD || '123456';

  const existe = await pool.query('SELECT id FROM users WHERE email = $1', [adminEmail]);
  if (existe.rows.length === 0) {
    const hash = await bcrypt.hash(adminPass, 10);
    await pool.query(
      `INSERT INTO users (nome, email, senha_hash, perfil, status)
       VALUES ($1, $2, $3, 'admin', 'ativo')`,
      ['Administrador', adminEmail, hash]
    );
    console.log(`Admin criado: ${adminEmail}`);
  } else {
    console.log('Admin ja existe.');
  }

  await pool.end();
  console.log('Banco inicializado com sucesso!');
}

init().catch(err => { console.error(err); process.exit(1); });
