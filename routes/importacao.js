const express = require('express');
const pool = require('../db/pool');
const { autenticar, apenasAdmin } = require('../middleware/auth');

const router = express.Router();

// POST /api/importacao/base_historica — importar dados do Excel (admin)
router.post('/base_historica', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { registros } = req.body; // array de objetos normalizados
    if (!Array.isArray(registros) || registros.length === 0) {
      return res.status(400).json({ erro: 'Nenhum registro enviado' });
    }

    let novos = 0, duplicados = 0, erros = 0;

    for (const r of registros) {
      try {
        const result = await pool.query(
          `INSERT INTO base_historica (hora, liga, casa, fora, odd_casa, odd_empate, odd_fora,
            gol_casa, gol_fora, gols_total, resultado, ambos_marcam,
            over_05, over_15, over_25, over_35, over_45)
           VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17)
           ON CONFLICT (hora, liga, casa, fora) DO NOTHING
           RETURNING id`,
          [
            r.hora || '', r.liga || '', r.casa || '', r.fora || '',
            parseFloat(r.odd_casa) || 0, parseFloat(r.odd_empate) || 0, parseFloat(r.odd_fora) || 0,
            parseInt(r.gol_casa) || 0, parseInt(r.gol_fora) || 0, parseInt(r.gols_total) || 0,
            r.resultado || '', r.ambos_marcam || '',
            r.over_05 || '', r.over_15 || '', r.over_25 || '', r.over_35 || '', r.over_45 || ''
          ]
        );
        if (result.rows.length > 0) novos++;
        else duplicados++;
      } catch (e) {
        erros++;
      }
    }

    const count = await pool.query('SELECT COUNT(*) FROM base_historica');

    res.json({
      novos,
      duplicados,
      erros,
      total_base: parseInt(count.rows[0].count)
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ erro: 'Erro na importacao' });
  }
});

// POST /api/importacao/palpites — importar palpites (admin)
router.post('/palpites', autenticar, apenasAdmin, async (req, res) => {
  try {
    const { registros } = req.body;
    let novos = 0, erros = 0;

    for (const r of registros) {
      try {
        await pool.query(
          `INSERT INTO palpites (data, hora, confronto, mercado, odd, tipo)
           VALUES ($1, $2, $3, $4, $5, $6)`,
          [r.data || null, r.hora || '', r.confronto || '', r.mercado || '', r.odd || '', r.tipo || 'gratis']
        );
        novos++;
      } catch (e) { erros++; }
    }

    res.json({ novos, erros });
  } catch (err) {
    res.status(500).json({ erro: 'Erro na importacao' });
  }
});

// DELETE /api/importacao/base_historica — limpar base (admin)
router.delete('/base_historica', autenticar, apenasAdmin, async (req, res) => {
  try {
    await pool.query('TRUNCATE base_historica RESTART IDENTITY');
    res.json({ mensagem: 'Base historica limpa' });
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao limpar' });
  }
});

// GET /api/importacao/analisar — analisador de odds
router.get('/analisar', autenticar, async (req, res) => {
  try {
    const { odd_casa, odd_empate, odd_fora } = req.query;
    const c = parseFloat(odd_casa);
    const e = parseFloat(odd_empate);
    const f = parseFloat(odd_fora);

    if (isNaN(c) || isNaN(e) || isNaN(f)) {
      return res.status(400).json({ erro: 'Informe as 3 odds' });
    }

    // Busca similares com tolerancia progressiva
    let similares = [];
    const tolerancias = [0.10, 0.20, 0.35, 0.50];

    for (const tol of tolerancias) {
      const result = await pool.query(
        `SELECT * FROM base_historica
         WHERE odd_casa BETWEEN $1 AND $2
           AND odd_empate BETWEEN $3 AND $4
           AND odd_fora BETWEEN $5 AND $6
           AND odd_casa > 0 AND odd_empate > 0 AND odd_fora > 0
         LIMIT 200`,
        [c - tol, c + tol, e - tol, e + tol, f - tol, f + tol]
      );
      similares = result.rows;
      if (similares.length >= 10) break;
    }

    if (similares.length === 0) {
      return res.json({ similares: 0, mensagem: 'Nenhum jogo similar encontrado' });
    }

    // Calcular estatisticas em um unico loop
    const total = similares.length;
    let stats = {
      H: 0, D: 0, A: 0, btts: 0,
      o05: 0, o15: 0, o25: 0, o35: 0, o45: 0,
      somaGolC: 0, somaGolF: 0
    };

    similares.forEach(j => {
      const res = (j.resultado || '').toUpperCase();
      if (res === 'H') stats.H++;
      else if (res === 'D') stats.D++;
      else if (res === 'A') stats.A++;

      const am = (j.ambos_marcam || '').toUpperCase();
      if (['S', 'SIM', 'YES', '1'].includes(am)) stats.btts++;

      const gt = j.gols_total || (j.gol_casa + j.gol_fora);
      if (gt > 0.5) stats.o05++;
      if (gt > 1.5) stats.o15++;
      if (gt > 2.5) stats.o25++;
      if (gt > 3.5) stats.o35++;
      if (gt > 4.5) stats.o45++;

      stats.somaGolC += j.gol_casa || 0;
      stats.somaGolF += j.gol_fora || 0;
    });

    const pct = (v) => parseFloat(((v / total) * 100).toFixed(1));

    res.json({
      amostra: total,
      principal: {
        casa: pct(stats.H), empate: pct(stats.D), fora: pct(stats.A), ambos: pct(stats.btts)
      },
      dupla: {
        '1X': pct(stats.H + stats.D), '12': pct(stats.H + stats.A), 'X2': pct(stats.D + stats.A)
      },
      over: {
        '0.5': pct(stats.o05), '1.5': pct(stats.o15), '2.5': pct(stats.o25),
        '3.5': pct(stats.o35), '4.5': pct(stats.o45)
      },
      under: {
        '0.5': pct(total - stats.o05), '1.5': pct(total - stats.o15), '2.5': pct(total - stats.o25),
        '3.5': pct(total - stats.o35), '4.5': pct(total - stats.o45)
      },
      medias: {
        xg_casa: (stats.somaGolC / total).toFixed(2),
        xg_fora: (stats.somaGolF / total).toFixed(2),
        total_esperado: ((stats.somaGolC + stats.somaGolF) / total).toFixed(2)
      }
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ erro: 'Erro na analise' });
  }
});

module.exports = router;
