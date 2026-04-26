require('dotenv').config();

const express = require('express');
const cors = require('cors');
const cookieParser = require('cookie-parser');
const path = require('path');

const authRoutes = require('./routes/auth');
const palpitesRoutes = require('./routes/palpites');
const importacaoRoutes = require('./routes/importacao');
const usuariosRoutes = require('./routes/usuarios');
const vitoriasRoutes = require('./routes/vitorias');
const noticiasRoutes = require('./routes/noticias');

const app = express();

app.set('trust proxy', 1);

app.use(cors({
  origin: true,
  credentials: true
}));

app.use(express.json({ limit: '50mb' }));
app.use(cookieParser());

app.use(express.static(path.join(__dirname, 'public')));

app.use('/api/auth', authRoutes);
app.use('/api/palpites', palpitesRoutes);
app.use('/api/importacao', importacaoRoutes);
app.use('/api/usuarios', usuariosRoutes);
app.use('/api/vitorias', vitoriasRoutes);
app.use('/api/noticias', noticiasRoutes);

app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
  console.log(`Servidor rodando na porta ${PORT}`);
});
