const { Pool } = require('pg');

// 🔍 Verificação obrigatória
if (!process.env.DATABASE_URL) {
  throw new Error('DATABASE_URL nao definida no ambiente');
}

// 🔗 Configuração do pool (Render usa SSL)
const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  ssl: {
    rejectUnauthorized: false
  }
});

// 🔄 Teste automático de conexão (opcional mas recomendado)
pool.connect()
  .then(client => {
    console.log('✅ Conectado ao PostgreSQL');
    client.release();
  })
  .catch(err => {
    console.error('❌ Erro ao conectar no PostgreSQL:', err.message);
  });

module.exports = pool;
