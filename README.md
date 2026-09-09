# TurmaFlow

TurmaFlow e uma aplicacao web simples para organizar turmas universitarias. A plataforma permite centralizar horario, tarefas, avaliacoes e materiais de apoio num unico espaco partilhado por alunos e administradores da turma.

## Funcionalidades

- Registo e login de utilizadores.
- Criacao de turmas com codigo de convite.
- Entrada em turmas existentes atraves de codigo.
- Dashboard com as turmas do utilizador.
- Horario semanal por turma.
- Gestao de aulas por administradores.
- Listagem de tarefas, testes, exames e projetos.
- Gestao de tarefas por administradores.
- Repositorio de materiais com links externos.
- Recuperacao de password com token temporario.
- Controlo basico de permissoes entre `admin` e `student`.

## Tecnologias

- PHP
- MySQL
- PDO
- HTML
- CSS
- JavaScript
- Apache/XAMPP

## Requisitos

- PHP 8 ou superior
- MySQL/MariaDB
- Apache com suporte a `.htaccess`
- XAMPP, Laragon, WAMP ou ambiente equivalente

## Como correr localmente

1. Coloca o projeto na pasta do servidor local:

```bash
C:\xampp\htdocs\turma-flow
```

2. Configura a ligacao a base de dados em `includes/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'turma_mvp');
define('DB_USER', 'root');
define('DB_PASS', '');
```

3. Com o MySQL ligado, executa o setup inicial:

```text
http://localhost/turma-flow/setup.php
```

O setup cria a base de dados `turma_mvp`, cria as tabelas e adiciona dados de exemplo.

4. Abre o projeto:

```text
http://localhost/turma-flow
```

5. Entra com uma conta de teste:

```text
Admin: maria@uni.pt / 123456
Aluno: joao@uni.pt / 123456
Codigo da turma: #ENG24
```

## Tabelas principais

A aplicacao usa as seguintes tabelas:

- `users`: contas dos utilizadores.
- `turmas`: turmas criadas na plataforma.
- `turma_users`: ligacao entre utilizadores e turmas, incluindo o papel de cada pessoa.
- `horario_aulas`: aulas do horario semanal.
- `tarefas`: tarefas, testes, exames e projetos.
- `materiais`: links e recursos de apoio.
- `password_resets`: tokens temporarios para recuperacao de password.

## Estrutura

```text
turma-flow/
|-- assets/
|   |-- css/
|   |   `-- style.css
|   `-- js/
|       `-- app.js
|-- includes/
|   |-- auth.php
|   |-- db.php
|   |-- layout_footer.php
|   `-- layout_header.php
|-- pages/
|   |-- dashboard.php
|   |-- gerir_horario.php
|   |-- gerir_tarefa.php
|   |-- horario.php
|   |-- login.php
|   |-- materiais.php
|   |-- nova_turma.php
|   |-- recuperar_senha.php
|   |-- register.php
|   `-- tarefas.php
|-- .htaccess
|-- index.php
`-- setup.php
```

## Nota sobre seguranca

Antes de publicar em producao:

- Altera as credenciais da base de dados em `includes/db.php`.
- Remove ou protege o ficheiro `setup.php`.
- Define `IS_DEV_MODE` como `false`.
- Nao publiques passwords reais, dumps da base de dados ou ficheiros `.env`.

## Comandos para enviar para o GitHub

```bash
git init
git add .
git commit -m "docs: add project README"
git branch -M main
git remote add origin https://github.com/SEU-USUARIO/turma-flow.git
git push -u origin main
```

Se o repositorio ja tiver um remote configurado:

```bash
git remote -v
git add .
git commit -m "docs: add project README"
git push
```

## Estado do projeto

Este e um MVP em desenvolvimento. A base ja cobre o fluxo principal de uma turma: criar conta, criar ou entrar numa turma, consultar horario, acompanhar tarefas e aceder a materiais.
