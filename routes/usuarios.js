const express = require('express');
const pool = require('../db/pool');
const { autenticar, apenasAdmin } = require('../middleware/auth');

const router = express.Router();

// GET /api/usuarios — listar todos (admin)
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

// PUT /api/usuarios/:id/perfil — alterar perfil (admin)
router.put('/:id/perfil', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { perfil } = req.body;
    const result = await pool.query(
      'UPDATE users SET perfil = $1 WHERE id = $2 RETURNING id, nome, email, perfil, status, expiracao',
      [perfil, req.params.id]
    );
    res.json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao alterar perfil' });
  }
});

// PUT /api/usuarios/:id/expiracao — alterar data de expiracao (admin)
router.put('/:id/expiracao', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { expiracao } = req.body;
    const result = await pool.query(
      'UPDATE users SET expiracao = $1 WHERE id = $2 RETURNING id, nome, email, perfil, status, expiracao',
      [expiracao, req.params.id]
    );
    res.json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao alterar expiracao' });
  }
});

// PUT /api/usuarios/:id/ativar30d — ativar +30 dias (admin)
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

// DELETE /api/usuarios/:id (admin)
router.delete('/:id', autenticar, apenasAdmin, async (req, res) => {
  try {
    await pool.query('DELETE FROM users WHERE id = $1', [req.params.id]);
    res.json({ mensagem: 'Usuario removido' });
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao remover usuario' });
  }
});

module.exports = router;
