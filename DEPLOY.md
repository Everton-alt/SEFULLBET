# Guia de Deploy - SeFull Bet no Render

## Passo 1: Subir para o GitHub

```bash
# No terminal, na pasta do projeto:
git init
git add .
git commit -m "SeFull Bet v1 - backend + frontend"
git branch -M main
git remote add origin https://github.com/SEU-USUARIO/sefull-bet.git
git push -u origin main
```

## Passo 2: Criar Banco PostgreSQL no Render

1. Acesse [render.com](https://render.com) e faca login
2. Clique **New** > **PostgreSQL**
3. Preencha:
   - **Name:** `sefull-bet-db`
   - **Region:** Ohio (ou a mais proxima)
   - **Plan:** Free
4. Clique **Create Database**
5. Copie a **Internal Database URL** (vai usar no proximo passo)

## Passo 3: Criar Web Service no Render

1. Clique **New** > **Web Service**
2. Conecte seu repositorio GitHub `sefull-bet`
3. Preencha:
   - **Name:** `sefull-bet`
   - **Region:** Mesma do banco
   - **Runtime:** Node
   - **Build Command:** `npm install`
   - **Start Command:** `node server.js`
   - **Plan:** Free
4. Em **Environment Variables**, adicione:

| Variavel | Valor |
|---|---|
| `DATABASE_URL` | (a Internal Database URL copiada no passo 2) |
| `JWT_SECRET` | (uma string aleatoria longa, ex: `minha-chave-secreta-super-longa-2026`) |
| `ADMIN_EMAIL` | `admin@sefullbet.com` |
| `ADMIN_PASSWORD` | (escolha uma senha segura para o admin) |

5. Clique **Create Web Service**

## Passo 4: Verificar

- O Render vai instalar dependencias e iniciar o servidor
- Na primeira execucao, as tabelas sao criadas automaticamente
- O admin padrao e criado com o email/senha das variaveis de ambiente
- Acesse `https://sefull-bet.onrender.com` para ver o site

## Passo 5: Primeiro Login

- Acesse `/login.html`
- Use o email e senha que voce configurou em `ADMIN_EMAIL` e `ADMIN_PASSWORD`
- Voce sera redirecionado para o painel admin

## Estrutura do Projeto

```
sefull-bet/
├── server.js           ← Servidor Express (ponto de entrada)
├── package.json        ← Dependencias
├── .env.example        ← Modelo das variaveis de ambiente
├── .gitignore          ← Ignora node_modules e .env
├── db/
│   ├── pool.js         ← Conexao PostgreSQL
│   ├── init.js         ← Script de inicializacao manual
│   └── schema.sql      ← Tabelas do banco
├── middleware/
│   └── auth.js         ← JWT + verificacao admin
├── routes/
│   ├── auth.js         ← Login e Cadastro
│   ├── palpites.js     ← CRUD de sinais
│   ├── usuarios.js     ← Gestao de membros
│   ├── vitorias.js     ← CRUD de vitorias
│   ├── noticias.js     ← CRUD de noticias
│   └── importacao.js   ← Importacao Excel + Analisador
└── public/             ← Frontend (servido como arquivos estaticos)
    ├── index.html      ← Landing page
    ├── login.html      ← Tela de login
    ├── cadastro.html   ← Registro de usuario
    ├── feed.html       ← Feed principal (usuarios)
    ├── admin.html      ← Painel administrativo
    ├── termo.html      ← Termos de uso
    └── js/
        └── api.js      ← Utilitario de fetch compartilhado

```

## Rotas da API

| Metodo | Rota | Acesso | Descricao |
|---|---|---|---|
| POST | `/api/auth/cadastro` | Publico | Criar conta |
| POST | `/api/auth/login` | Publico | Login (retorna JWT) |
| GET | `/api/auth/me` | Logado | Dados do usuario |
| GET | `/api/palpites` | Logado | Listar palpites |
| GET | `/api/palpites/stats` | Publico | Dashboard assertividade |
| POST | `/api/palpites` | Admin | Criar palpite |
| PUT | `/api/palpites/:id/status` | Admin | Marcar green/red |
| PUT | `/api/palpites/:id/placar` | Admin | Atualizar placar |
| DELETE | `/api/palpites/:id` | Admin | Remover palpite |
| GET | `/api/usuarios` | Admin | Listar membros |
| PUT | `/api/usuarios/:id/perfil` | Admin | Alterar perfil |
| PUT | `/api/usuarios/:id/expiracao` | Admin | Alterar expiracao |
| PUT | `/api/usuarios/:id/ativar30d` | Admin | Ativar +30 dias |
| DELETE | `/api/usuarios/:id` | Admin | Remover membro |
| GET | `/api/vitorias` | Publico | Listar vitorias |
| POST | `/api/vitorias` | Admin | Criar vitoria |
| PUT | `/api/vitorias/:id` | Admin | Editar vitoria |
| PUT | `/api/vitorias/:id/fixar` | Admin | Fixar/desfixar |
| DELETE | `/api/vitorias/:id` | Admin | Remover vitoria |
| GET | `/api/noticias` | Publico | Listar noticias |
| POST | `/api/noticias` | Admin | Criar noticia |
| PUT | `/api/noticias/:id` | Admin | Editar noticia |
| PUT | `/api/noticias/:id/fixar` | Admin | Fixar/desfixar |
| DELETE | `/api/noticias/:id` | Admin | Remover noticia |
| POST | `/api/importacao/base_historica` | Admin | Importar dados |
| POST | `/api/importacao/palpites` | Admin | Importar palpites |
| DELETE | `/api/importacao/base_historica` | Admin | Limpar base |
| GET | `/api/importacao/analisar` | Logado | Analisador de odds |

## Seguranca Implementada

- Senhas com hash bcrypt (nunca em texto puro)
- Autenticacao via JWT com expiracao de 7 dias
- Credencial admin em variavel de ambiente (nao no codigo)
- Middleware de admin protege todas as rotas administrativas
- Protecao XSS com funcao `esc()` no frontend
- Painel admin verifica perfil antes de renderizar
- IDs de usuario com UUID v4 (unicos e nao sequenciais)
