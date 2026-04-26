const express = require('express');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const pool = require('../db/pool');
const { autenticar } = require('../middleware/auth');

const router = express.Router();

// POST /api/auth/cadastro
router.post('/cadastro', async (req, res) => {
  try {
    const { nome, email, senha, perfil } = req.body;
    if (!nome || !email || !senha) {
      return res.status(400).json({ erro: 'Nome, email e senha sao obrigatorios' });
    }

    const existe = await pool.query('SELECT id FROM users WHERE email = $1', [email.toLowerCase()]);
    if (existe.rows.length > 0) {
      return res.status(409).json({ erro: 'E-mail ja cadastrado' });
    }

    const hash = await bcrypt.hash(senha, 10);
    const result = await pool.query
      `INSERT INTO users (nome, email, senha_hash, perfil, status)
       VALUES ($1, $2, $3, gratis, 'pendente')
       RETURNING id, nome, email, perfil, status, criado_em`,
      [nome, email.toLowerCase(), hash, perfil || 'gratis']
    );

    res.status(201).json({ mensagem: 'Conta criada com sucesso', usuario: result.rows[0] });
  } catch (err) {
    console.error('Erro no cadastro:', err);
    res.status(500).json({ erro: 'Erro interno do servidor' });
  }
});

// POST /api/auth/login
router.post('/login', async (req, res) => {
  try {
    const { email, senha } = req.body;
    if (!email || !senha) {
      return res.status(400).json({ erro: 'Email e senha sao obrigatorios' });
    }

    const result = await pool.query(
      'SELECT id, nome, email, senha_hash, perfil, status, expiracao FROM users WHERE email = $1',
      [email.toLowerCase()]
    );

    if (result.rows.length === 0) {
      return res.status(401).json({ erro: 'Usuario ou senha incorretos' });
    }

    const user = result.rows[0];
    const senhaValida = await bcrypt.compare(senha, user.senha_hash);
    if (!senhaValida) {
      return res.status(401).json({ erro: 'Usuario ou senha incorretos' });
    }

    const token = jwt.sign(
      { id: user.id, email: user.email, perfil: user.perfil },
      process.env.JWT_SECRET,
      { expiresIn: '7d' }
    );

    res.json({
      token,
      usuario: {
        id: user.id,
        nome: user.nome,
        email: user.email,
        perfil: user.perfil,
        status: user.status,
        expiracao: user.expiracao
      }
    });
  } catch (err) {
    console.error('Erro no login:', err);
    res.status(500).json({ erro: 'Erro interno do servidor' });
  }
});

// GET /api/auth/me — retorna dados do usuario logado
router.get('/me', autenticar, async (req, res) => {
  try {
    const result = await pool.query(
      'SELECT id, nome, email, perfil, status, expiracao FROM users WHERE id = $1',
      [req.user.id]
    );
    if (result.rows.length === 0) return res.status(404).json({ erro: 'Usuario nao encontrado' });
    res.json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro interno' });
  }
});

module.exports = router;
