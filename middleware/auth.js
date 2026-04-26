const jwt = require('jsonwebtoken');

function autenticar(req, res, next) {
  try {
    let token = req.cookies?.token;

    if (!token && req.headers.authorization) {
      const parts = req.headers.authorization.split(' ');
      if (parts.length === 2 && parts[0] === 'Bearer') {
        token = parts[1];
      }
    }

    if (!token) {
      return res.status(401).json({ erro: 'Nao autenticado' });
    }

    if (!process.env.JWT_SECRET) {
      return res.status(500).json({ erro: 'JWT_SECRET nao configurado' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET);

    req.user = {
      id: decoded.id,
      email: decoded.email,
      perfil: decoded.perfil
    };

    next();

  } catch (err) {
    return res.status(401).json({ erro: 'Token invalido ou expirado' });
  }
}

function apenasAdmin(req, res, next) {
  if (!req.user || req.user.perfil !== 'admin') {
    return res.status(403).json({ erro: 'Acesso negado' });
  }

  next();
}

module.exports = {
  autenticar,
  apenasAdmin
};
