require('dotenv').config();

const express = require('express');
const cors = require('cors');
const cookieParser = require('cookie-parser');

const authRoutes = require('./routes/auth');
const palpitesRoutes = require('./routes/palpites');
const importacaoRoutes = require('./routes/importacao');

const app = express();

app.set('trust proxy', 1);

app.use(cors({
  origin: true,
  credentials: true
}));

app.use(express.json({ limit: '50mb' }));
app.use(cookieParser());

app.use('/api/auth', authRoutes);
app.use('/api/palpites', palpitesRoutes);
app.use('/api/importacao', importacaoRoutes);

app.get('/', (req, res) => {
  res.json({ status: 'OK', mensagem: 'API SeFull Bet online' });
});

const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
  console.log(`Servidor rodando na porta ${PORT}`);
});
