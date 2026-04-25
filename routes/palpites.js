const express = require('express');
const pool = require('../db/pool');
const { autenticar, apenasAdmin } = require('../middleware/auth');

const router = express.Router();

// GET /api/palpites — lista palpites (filtro por data e tipo)
router.get('/', autenticar, async (req, res) => {
  try {
    const { data, page = 1, limit = 8 } = req.query;
    const offset = (page - 1) * limit;
    const user = req.user;

    // Se nao for admin/vip ativo, so ve gratis
    let filtroTipo = '';
    if (user.perfil !== 'admin') {
      const u = await pool.query('SELECT perfil, status, expiracao FROM users WHERE id = $1', [user.id]);
      const usr = u.rows[0];
      const vipAtivo = usr && (usr.status === 'ativo' && usr.expiracao && new Date(usr.expiracao) >= new Date());
      if (!vipAtivo) filtroTipo = "AND tipo = 'gratis'";
    }

    let filtroData = '';
    const params = [];
    if (data) {
      params.push(data);
      filtroData = `WHERE data = $${params.length}`;
    }

    const where = filtroData || 'WHERE 1=1';
    const sql = `SELECT * FROM palpites ${where} ${filtroTipo} ORDER BY criado_em DESC LIMIT $${params.length + 1} OFFSET $${params.length + 2}`;
    params.push(limit, offset);

    const result = await pool.query(sql, params);

    // Total para paginacao
    const countSql = `SELECT COUNT(*) FROM palpites ${where} ${filtroTipo}`;
    const countResult = await pool.query(countSql, data ? [data] : []);

    res.json({ palpites: result.rows, total: parseInt(countResult.rows[0].count) });
  } catch (err) {
    console.error(err);
    res.status(500).json({ erro: 'Erro ao buscar palpites' });
  }
});

// GET /api/palpites/stats — dashboard de assertividade
router.get('/stats', async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT tipo,
        COUNT(*) as total,
        COUNT(*) FILTER (WHERE status = 'green') as greens,
        COUNT(*) FILTER (WHERE status = 'red') as reds
      FROM palpites
      GROUP BY tipo
    `);
    const stats = {};
    result.rows.forEach(r => {
      const finalizados = parseInt(r.greens) + parseInt(r.reds);
      stats[r.tipo] = {
        total: parseInt(r.total),
        greens: parseInt(r.greens),
        reds: parseInt(r.reds),
        assertividade: finalizados > 0 ? Math.round((r.greens / finalizados) * 100) : 0
      };
    });
    res.json(stats);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao calcular stats' });
  }
});

// POST /api/palpites — criar palpite (admin)
router.post('/', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { data, hora, confronto, mercado, odd, tipo } = req.body;
    const result = await pool.query(
      `INSERT INTO palpites (data, hora, confronto, mercado, odd, tipo)
       VALUES ($1, $2, $3, $4, $5, $6) RETURNING *`,
      [data, hora, confronto, mercado, odd, tipo || 'gratis']
    );
    res.status(201).json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao criar palpite' });
  }
});

// PUT /api/palpites/:id/status — marcar green/red (admin)
router.put('/:id/status', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { status } = req.body;
    const result = await pool.query(
      'UPDATE palpites SET status = $1 WHERE id = $2 RETURNING *',
      [status, req.params.id]
    );
    res.json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao atualizar status' });
  }
});

// PUT /api/palpites/:id/placar — atualizar placar (admin)
router.put('/:id/placar', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { placar } = req.body;
    const result = await pool.query(
      'UPDATE palpites SET placar = $1 WHERE id = $2 RETURNING *',
      [placar, req.params.id]
    );
    res.json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao atualizar placar' });
  }
});

// DELETE /api/palpites/:id (admin)
router.delete('/:id', autenticar, apenasAdmin, async (req, res) => {
  try {
    await pool.query('DELETE FROM palpites WHERE id = $1', [req.params.id]);
    res.json({ mensagem: 'Palpite removido' });
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao remover' });
  }
});

module.exports = router;
