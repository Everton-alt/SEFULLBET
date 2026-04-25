# SeFull Bet - Passo a Passo Completo (GitHub + Render)

---

## PARTE 1: Preparar o computador

### 1.1 Instalar Git (se ainda nao tem)

1. Acesse: https://git-scm.com/download/win
2. Baixe o instalador para Windows
3. Instale clicando "Next" em tudo (opcoes padrao)
4. Depois de instalar, abra o **Git Bash** (aparece no menu Iniciar)

### 1.2 Configurar Git (so precisa fazer 1 vez)

Abra o **Git Bash** e digite estes 2 comandos (troque pelo seu nome e email):

```bash
git config --global user.name "Everton"
git config --global user.email "evertoncl2000@gmail.com"
```

---

## PARTE 2: Criar repositorio no GitHub

### 2.1 Criar conta (se nao tem)

1. Acesse https://github.com
2. Clique "Sign up" e crie sua conta

### 2.2 Criar repositorio novo

1. Logado no GitHub, clique no **+** no canto superior direito
2. Clique **"New repository"**
3. Preencha:
   - **Repository name:** `sefull-bet`
   - **Description:** `SeFull Bet - Plataforma de sinais`
   - **Visibilidade:** `Private` (IMPORTANTE: marque Private para nao expor o codigo)
   - **NÃO** marque "Add a README file"
   - **NÃO** marque "Add .gitignore"
4. Clique **"Create repository"**
5. O GitHub vai mostrar uma pagina com comandos. **Deixe essa pagina aberta.**

---

## PARTE 3: Copiar os arquivos e enviar ao GitHub

### 3.1 Criar pasta do projeto no seu PC

1. Crie uma pasta nova no Desktop chamada `sefull-bet`
2. Copie TODOS estes arquivos para dentro dela, respeitando a estrutura:

```
sefull-bet/
├── server.js
├── package.json
├── .env.example
├── .gitignore
├── DEPLOY.md
│
├── db/
│   ├── pool.js
│   ├── init.js
│   └── schema.sql
│
├── middleware/
│   └── auth.js
│
├── routes/
│   ├── auth.js
│   ├── palpites.js
│   ├── usuarios.js
│   ├── vitorias.js
│   ├── noticias.js
│   └── importacao.js
│
└── public/
    ├── index.html
    ├── login.html
    ├── cadastro.html
    ├── feed.html
    ├── admin.html
    ├── termo.html
    └── js/
        └── api.js
```

### 3.2 Enviar para o GitHub (Git Bash)

Abra o **Git Bash**, navegue ate a pasta e execute estes comandos **um por um**:

```bash
cd ~/Desktop/sefull-bet

git init

git add .

git commit -m "SeFull Bet v1 - backend + frontend"

git branch -M main

git remote add origin https://github.com/SEU-USUARIO/sefull-bet.git

git push -u origin main
```

**IMPORTANTE:** Troque `SEU-USUARIO` pelo seu nome de usuario do GitHub.

O Git vai pedir seu login. Se pedir uma "Personal Access Token" em vez de senha:
1. Va em GitHub > Settings > Developer settings > Personal access tokens > Tokens (classic)
2. Clique "Generate new token (classic)"
3. Marque o checkbox `repo`
4. Clique "Generate token"
5. Copie o token gerado e use como senha no Git Bash

---

## PARTE 4: Criar banco de dados no Render

### 4.1 Criar conta no Render

1. Acesse https://render.com
2. Clique "Get Started for Free"
3. Faca login com sua conta GitHub (mais facil!)

### 4.2 Criar PostgreSQL

1. No Dashboard do Render, clique **"New +"** (botao roxo no topo)
2. Selecione **"PostgreSQL"**
3. Preencha:
   - **Name:** `sefull-bet-db`
   - **Database:** `sefullbet`
   - **User:** (deixe o padrao)
   - **Region:** `Oregon (US West)` ou `Ohio (US East)`
   - **PostgreSQL Version:** 16
   - **Instance Type:** `Free`
4. Clique **"Create Database"**
5. Aguarde 1-2 minutos ate o status ficar **"Available"**
6. Na pagina do banco, procure e **COPIE** a **"Internal Database URL"**
   - Vai ser algo tipo: `postgresql://sefullbet_user:senha123@dpg-xxxxx/sefullbet`
   - **GUARDE ESSE LINK** — voce vai usar no proximo passo

---

## PARTE 5: Criar o Web Service no Render

### 5.1 Novo Web Service

1. No Dashboard, clique **"New +"** > **"Web Service"**
2. Selecione **"Build and deploy from a Git repository"**
3. Conecte seu GitHub se ainda nao conectou (clique "Connect account")
4. Encontre o repositorio `sefull-bet` e clique **"Connect"**

### 5.2 Configurar o servico

Preencha:

| Campo | Valor |
|-------|-------|
| **Name** | `sefull-bet` |
| **Region** | A mesma do banco de dados |
| **Branch** | `main` |
| **Runtime** | `Node` |
| **Build Command** | `npm install` |
| **Start Command** | `node server.js` |
| **Instance Type** | `Free` |

### 5.3 Variaveis de Ambiente (MAIS IMPORTANTE!)

Role para baixo ate **"Environment Variables"** e clique **"Add Environment Variable"** para cada uma:

| Key | Value |
|-----|-------|
| `DATABASE_URL` | **(cole a Internal Database URL que voce copiou no passo 4.2)** |
| `JWT_SECRET` | `sefullbet-chave-secreta-2026-ultra-segura` |
| `ADMIN_EMAIL` | `admin@sefullbet.com` |
| `ADMIN_PASSWORD` | **(escolha uma senha forte para o admin, ex: SeFull@2026!)** |

### 5.4 Criar!

1. Clique **"Create Web Service"**
2. O Render vai:
   - Clonar seu repositorio
   - Rodar `npm install`
   - Iniciar `node server.js`
3. Aguarde 2-3 minutos ate ver nos logs:
   ```
   Banco de dados verificado.
   Admin criado: admin@sefullbet.com
   SeFull Bet rodando na porta 10000
   ```
4. O URL do seu site vai ser: `https://sefull-bet.onrender.com`

---

## PARTE 6: Testar tudo

### 6.1 Acessar o site
- Abra `https://sefull-bet.onrender.com` → Landing page

### 6.2 Login admin
- Acesse `https://sefull-bet.onrender.com/login.html`
- Email: o que voce colocou em `ADMIN_EMAIL`
- Senha: o que voce colocou em `ADMIN_PASSWORD`
- Deve ir para o painel admin

### 6.3 Criar um usuario teste
- Acesse `https://sefull-bet.onrender.com/cadastro.html`
- Crie uma conta com email diferente do admin
- O sistema vai mostrar o UUID do usuario

### 6.4 Testar palpites
- No admin, crie um palpite
- No feed (logado com usuario comum), veja se aparece

---

## DICAS IMPORTANTES

### O plano Free do Render tem limitacoes:
- O site "dorme" apos 15 minutos sem acesso
- A primeira visita apos "dormir" demora ~30 segundos para acordar
- O banco de dados Free expira apos 90 dias (depois precisa recriar ou fazer upgrade)

### Para atualizar o site depois:
```bash
cd ~/Desktop/sefull-bet
git add .
git commit -m "descricao do que mudou"
git push
```
O Render detecta o push e faz deploy automatico!

### Se algo der errado:
- No Render, va em seu Web Service > **"Logs"** para ver os erros
- Erros comuns:
  - `DATABASE_URL not set` → Variavel de ambiente nao configurada
  - `relation "users" does not exist` → Banco nao inicializou (verifique a URL)
  - `ECONNREFUSED` → Verifique se usou a **Internal** URL (nao a External)
