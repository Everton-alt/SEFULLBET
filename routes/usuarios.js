const express = require('express');
const pool = require('../db/pool');
const { autenticar, apenasAdmin } = require('../middleware/auth');

const router = express.Router();

// --- ROTA DE AUTOCADASTRO (PÚBLICA) ---
router.post('/', async (req, res) => {
  try {
    const { nome, email, senha, perfil } = req.body;

    // Verifica se o e-mail já existe
    const existe = await pool.query('SELECT id FROM users WHERE email = $1', [email]);
    if (existe.rows.length > 0) {
      return res.status(400).json({ erro: 'Este e-mail já está cadastrado.' });
    }

    // Insere como 'pendente' para ativação manual posterior
    const result = await pool.query(
      `INSERT INTO users (nome, email, senha, perfil, status, expiracao, criado_em) 
       VALUES ($1, $2, $3, $4, 'pendente', NULL, NOW()) 
       RETURNING id, nome, email, perfil, status`,
      [nome, email, senha, perfil]
    );

    res.status(201).json(result.rows[0]);
  } catch (err) {
    console.error(err);
    res.status(500).json({ erro: 'Erro ao criar conta no servidor.' });
  }
});

// --- ROTAS PROTEGIDAS (APENAS ADMIN) ---

// GET /api/usuarios — listar todos
router.get('/', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { page = 1, limit = 8 } = req.query;
    const offset = (page - 1) * limit;
    const result = await pool.query(
      'SELECT id, nome, email, perfil, status, expiracao, criado_em FROM users ORDER BY criado_em DESC LIMIT $1 OFFSET $2',
      [limit, offset]
    );
    const count = await pool.query('SELECT COUNT(*) FROM users');
    res.json({ usuarios: result.rows, total: parseInt(count.rows[0].count) });
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao listar usuarios' });
  }
});

// PUT /api/usuarios/:id/ativar30d — ativar +30 dias
router.put('/:id/ativar30d', autenticar, apenasAdmin, async (req, res) => {
  try {
    const novaData = new Date();
    novaData.setDate(novaData.getDate() + 30);
    const result = await pool.query(
      `UPDATE users SET status = 'ativo', expiracao = $1 WHERE id = $2
       RETURNING id, nome, email, perfil, status, expiracao`,
      [novaData.toISOString().split('T')[0], req.params.id]
    );
    res.json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao ativar usuario' });
  }
});

// DELETE /api/usuarios/:id
router.delete('/:id', autenticar, apenasAdmin, async (req, res) => {
  try {
    await pool.query('DELETE FROM users WHERE id = $1', [req.params.id]);
    res.json({ mensagem: 'Usuario removido' });
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao remover usuario' });
  }
});

module.exports = router;
