const express = require('express');
const pool = require('../db/pool');
const { autenticar, apenasAdmin } = require('../middleware/auth');

const router = express.Router();

// GET /api/noticias — listar (publico)
router.get('/', async (req, res) => {
  try {
    const { page = 1, limit = 3 } = req.query;
    const offset = (page - 1) * limit;
    const result = await pool.query(
      'SELECT * FROM noticias ORDER BY fixado DESC, criado_em DESC LIMIT $1 OFFSET $2',
      [limit, offset]
    );
    const count = await pool.query('SELECT COUNT(*) FROM noticias');
    res.json({ noticias: result.rows, total: parseInt(count.rows[0].count) });
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao listar noticias' });
  }
});

// POST /api/noticias (admin)
router.post('/', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { titulo, midia, conteudo } = req.body;
    const data = new Date().toLocaleDateString('pt-BR');
    const result = await pool.query(
      'INSERT INTO noticias (titulo, midia, conteudo, data) VALUES ($1, $2, $3, $4) RETURNING *',
      [titulo, midia, conteudo, data]
    );
    res.status(201).json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao criar noticia' });
  }
});

// PUT /api/noticias/:id (admin)
router.put('/:id', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { titulo, midia, conteudo } = req.body;
    const result = await pool.query(
      'UPDATE noticias SET titulo=$1, midia=$2, conteudo=$3 WHERE id=$4 RETURNING *',
      [titulo, midia, conteudo, req.params.id]
    );
    res.json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao atualizar' });
  }
});

// PUT /api/noticias/:id/fixar (admin)
router.put('/:id/fixar', autenticar, apenasAdmin, async (req, res) => {
  try {
    const result = await pool.query(
      'UPDATE noticias SET fixado = NOT fixado WHERE id = $1 RETURNING *',
      [req.params.id]
    );
    res.json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao fixar' });
  }
});

// DELETE /api/noticias/:id (admin)
router.delete('/:id', autenticar, apenasAdmin, async (req, res) => {
  try {
    await pool.query('DELETE FROM noticias WHERE id = $1', [req.params.id]);
    res.json({ mensagem: 'Noticia removida' });
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao remover' });
  }
});

module.exports = router;
