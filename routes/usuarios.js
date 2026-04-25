// POST /api/usuarios — Rota para o usuário se cadastrar sozinho
router.post('/', async (req, res) => {
  try {
    const { nome, email, senha, perfil } = req.body;

    // Verifica duplicidade
    const userExistente = await pool.query('SELECT id FROM users WHERE email = $1', [email]);
    if (userExistente.rows.length > 0) {
      return res.status(400).json({ erro: 'Este e-mail já está cadastrado.' });
    }

    // Insere com status PENDENTE e expiração NULA
    // Assim ele não tem acesso VIP/Platinum até você autorizar
    const result = await pool.query(
      `INSERT INTO users (nome, email, senha, perfil, status, expiracao, criado_em) 
       VALUES ($1, $2, $3, $4, 'pendente', NULL, NOW()) 
       RETURNING id, nome, email, perfil, status`,
      [nome, email, senha, perfil]
    );

    res.status(201).json(result.rows[0]);
  } catch (err) {
    res.status(500).json({ erro: 'Erro ao processar cadastro inicial.' });
  }
});
