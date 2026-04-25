const express = require('express');
const pool = require('../db/pool');
const { autenticar, apenasAdmin } = require('../middleware/auth');

const router = express.Router();

// GET /api/vitorias — listar (publico)
router.get('/', async (req, res) => {
  try {
    const { page = 1, limit = 4 } = req.query;
    const offset = (page - 1) * limit;
    const result = await pool.query(
      'SELECT * FROM vitorias ORDER BY fixado DESC, criado_em DESC LIMIT $1 OFFSET $2',
      [limit, offset]
    );
    const count = await pool.query('SELECT COUNT(*) FROM vitorias');
    res.json({ vitorias: result.rows, total: parseInt(count.rows[0].count) });
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao listar vitorias' });
  }
});

// POST /api/vitorias (admin)
router.post('/', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { titulo, assunto, img1, img2 } = req.body;
    const result = await pool.query(
      'INSERT INTO vitorias (titulo, assunto, img1, img2) VALUES ($1, $2, $3, $4) RETURNING *',
      [titulo, assunto, img1, img2]
    );
    res.status(201).json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao criar vitoria' });
  }
});

// PUT /api/vitorias/:id (admin)
router.put('/:id', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { titulo, assunto, img1, img2 } = req.body;
    const result = await pool.query(
      'UPDATE vitorias SET titulo=$1, assunto=$2, img1=$3, img2=$4 WHERE id=$5 RETURNING *',
      [titulo, assunto, img1, img2, req.params.id]
    );
    res.json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao atualizar vitoria' });
  }
});

// PUT /api/vitorias/:id/fixar (admin)
router.put('/:id/fixar', autenticar, apenasAdmin, async (req, res) => {
  try {
    const result = await pool.query(
      'UPDATE vitorias SET fixado = NOT fixado WHERE id = $1 RETURNING *',
      [req.params.id]
    );
    res.json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao fixar' });
  }
});

// DELETE /api/vitorias/:id (admin)
router.delete('/:id', autenticar, apenasAdmin, async (req, res) => {
  try {
    await pool.query('DELETE FROM vitorias WHERE id = $1', [req.params.id]);
    res.json({ mensagem: 'Vitoria removida' });
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao remover' });
  }
});

module.exports = router;
