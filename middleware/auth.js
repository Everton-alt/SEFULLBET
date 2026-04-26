const jwt = require('jsonwebtoken');

function autenticar(req, res, next) {
  const token = req.cookies?.token;

  if (!token) {
    return res.status(401).json({ erro: 'Nao autenticado' });
  }

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    req.user = decoded;
    next();
  } catch (err) {
    return res.status(401).json({ erro: 'Token invalido' });
  }
}

module.exports = { autenticar };
