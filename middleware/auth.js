const jwt = require('jsonwebtoken');

// Verifica se o token JWT e valido
function autenticar(req, res, next) {
  const header = req.headers.authorization;
  if (!header) return res.status(401).json({ erro: 'Token nao fornecido' });

  const token = header.replace('Bearer ', '');
  try {
    const payload = jwt.verify(token, process.env.JWT_SECRET);
    req.user = payload; // { id, email, perfil }
    next();
  } catch {
    return res.status(401).json({ erro: 'Token invalido ou expirado' });
  }
}

// Verifica se o usuario e admin
function apenasAdmin(req, res, next) {
  if (req.user.perfil !== 'admin') {
    return res.status(403).json({ erro: 'Acesso restrito a administradores' });
  }
  next();
}

module.exports = { autenticar, apenasAdmin };
