# TurmaApp — Guia Completo
## Deploy · Estratégia de Apresentação · Monetização

---

## PARTE 1 — DEPLOY NO INFINITYFREE

### O que é o InfinityFree?
Hosting gratuito com PHP + MySQL, sem cartão de crédito. Perfeito para um MVP.
**Limitações a saber:** 50k inodes (ficheiros), sem SSH, MySQL externo com host específico, pode ser lento em horas de pico.

---

### Passo a Passo: Do teu computador para a internet

#### 1. Criar conta
1. Vai a **infinityfree.com** → "Get Free Hosting"
2. Cria uma conta com o teu email
3. Cria um novo hosting → escolhe um subdomínio gratuito
   - Ex: `turmaapp.infinityfreeapp.com`
   - Ou usa um domínio próprio (`.pt` custa ~5€/ano no Namecheap)

#### 2. Criar a Base de Dados MySQL
1. No painel InfinityFree → **MySQL Databases**
2. Clica "Create Database"
3. **Guarda estes dados** — vais precisar deles:
   ```
   MySQL Host:     sql200.infinityfree.net  ← varia, vê no painel
   Database Name:  epiz_XXXXXXXX_turmaapp
   Username:       epiz_XXXXXXXX_turmaapp
   Password:       (a que definiste)
   ```

#### 3. Configurar o `config/db.php`
Abre o ficheiro e substitui os valores:
```php
define('DB_HOST', 'sql200.infinityfree.net'); // ← do painel
define('DB_NAME', 'epiz_XXXXXXXX_turmaapp');  // ← do painel
define('DB_USER', 'epiz_XXXXXXXX_turmaapp');  // ← do painel
define('DB_PASS', 'a-tua-password');          // ← o que escolheste
```

#### 4. Fazer Upload dos Ficheiros via FTP
**Programa recomendado: FileZilla** (gratuito, filezilla-project.org)

Credenciais FTP estão no painel InfinityFree → "FTP Details":
```
Host:     ftpupload.net
User:     epiz_XXXXXXXX
Password: (a password da tua conta InfinityFree)
Port:     21
```

Na janela do FileZilla:
- **Esquerda** = o teu computador → navega até à pasta `turmaapp/`
- **Direita** = o servidor → entra na pasta `htdocs/`
- Arrasta TODOS os ficheiros da pasta `turmaapp/` para dentro de `htdocs/`

> ⚠️ **Importante:** Os ficheiros devem ficar em `htdocs/` directamente (não numa sub-pasta), para que `turmaapp.infinityfreeapp.com` funcione.

#### 5. Criar as Tabelas com o phpMyAdmin
1. No painel InfinityFree → **phpMyAdmin**
2. Seleciona a tua base de dados à esquerda
3. Clica no separador **SQL**
4. Copia o conteúdo do ficheiro `schema.sql` e cola ali
5. Clica **Go / Executar**

#### 6. Correr o Setup
1. Vai a `https://turmaapp.infinityfreeapp.com/setup.php`
2. O script cria os dados de exemplo com hashes corretos
3. Testa o login: `maria@uni.pt` / `123456`
4. **Apaga o setup.php** pelo FileZilla depois de testar!

#### 7. Verificar se está tudo a funcionar
Checklist final:
- [ ] Login funciona
- [ ] Registo funciona (criar turma + entrar com código)
- [ ] Horário mostra a grade
- [ ] Tarefas listam com cores de urgência
- [ ] Materiais abrem os links
- [ ] Admin vê botões de editar/excluir
- [ ] Student NÃO vê botões de editar/excluir

---

### Quando migrares para o Hostinger (~3€/mês)

O processo é idêntico, mas com melhorias:
- Tens SSH (podes correr `mysql < schema.sql` diretamente)
- MySQL host é geralmente `127.0.0.1` (mais rápido)
- Certificado SSL automático (HTTPS) incluído
- Muito mais rápido e estável

No Hostinger, o `DB_HOST` típico é:
```php
define('DB_HOST', '127.0.0.1');
```

---

## PARTE 2 — ESTRATÉGIA DE APRESENTAÇÃO AOS COLEGAS

### O Problema que resolves (o teu "pitch de elevador")

> *"Quantas vezes já enviaste uma mensagem no grupo a perguntar 'qual é a data do teste?' ou 'quando é o prazo do TPC?'? O TurmaApp resolve isso: um lugar central onde o Chefe de Turma regista tudo uma vez, e os 40 alunos nunca mais precisam perguntar."*

---

### Como apresentar na faculdade

#### Fase 1 — Prova de conceito (Semana 1-2)
**Objetivo:** Convencer 1 turma a usar.

1. **Escolhe a tua própria turma** para o piloto — és o admin, conheces as pessoas.
2. **Apresenta numa aula** (pede 5 minutos ao professor, ou faz antes/depois da aula):
   - Mostra o horário visual da turma já preenchido
   - Mostra as tarefas com os prazos reais da turma
   - Mostra o repositório de materiais com links reais
   - Partilha o código de convite `#XXXXXW` e pede a toda a gente para entrar
3. **O argumento matador:** *"Já aqui estão o horário desta semana, os 3 TPCs próximos e os slides da última aula do Prof. Santos. Entrem com este código e nunca mais perguntem no grupo."*

#### Fase 2 — Expansão orgânica (Semana 3-8)
**Objetivo:** 5-10 turmas ativas.

- Cada colega que usar vai partilhar naturalmente com outros cursos
- Contacta diretamente os **delegados/chefes de turma** de outros cursos — eles têm o poder de adotar a ferramenta
- Cria uma conta de Instagram simples: `@turmaapp.pt` com prints do horário e depoimentos
- Pede feedback real e implementa melhorias visíveis — as pessoas ficam mais leais quando veem que as sugestões são implementadas

#### Fase 3 — Credibilidade académica (Mês 2-3)
- Apresenta o projeto no teu curso como projeto pessoal/portfólio
- Vê se podes integrar numa UC de projetos ou estágio
- Testemunhos reais de alunos = melhor marketing

---

### O que diferencia o TurmaApp das alternativas

| Funcionalidade | Grupo WhatsApp | Google Calendar | TurmaApp |
|---|---|---|---|
| Horário visual por turma | ❌ | Parcial | ✅ |
| Um admin, todos consomem | ❌ | ❌ | ✅ |
| Materiais centralizados | ❌ | ❌ | ✅ |
| Alerta visual de urgência | ❌ | ✅ | ✅ |
| Código de convite simples | ❌ | ❌ | ✅ |
| Sem instalação | ✅ | ✅ | ✅ |

---

## PARTE 3 — MONETIZAÇÃO

### A realidade honesta
O InfinityFree é gratuito mas lento e instável para uso sério.
O Hostinger custa ~3€/mês = **36€/ano.**
Com 50 turmas ativas a pagar 1€/mês, o projeto paga-se e ainda sobra.

Aqui está o caminho realista:

---

### Modelo Freemium (recomendado para começar)

#### Plano Gratuito (Forever Free)
- ✅ 1 turma por conta
- ✅ Até 30 membros
- ✅ Horário, tarefas e materiais completos
- ✅ Código de convite

#### Plano Turma Pro — 2€/mês ou 15€/ano
- ✅ Tudo do gratuito
- ✅ Turmas ilimitadas
- ✅ Até 80 membros por turma
- ✅ Histórico de tarefas passadas
- ✅ Exportar horário como imagem/PDF
- ✅ Sem publicidade (futura)

#### Plano Faculdade — 20€/ano (por departamento)
- ✅ Todas as turmas do departamento geridas centralmente
- ✅ Logo da faculdade
- ✅ Acesso administrativo para coordenadores
- ✅ Suporte prioritário

---

### Como implementar o pagamento (sem complicar)

**Fase inicial (0-100 utilizadores):** Nem implementes pagamento ainda. Valida primeiro.

**Fase de monetização (100+ utilizadores ativos):**

1. **MBWay/Transferência Bancária manual** — incrível mas funciona para as primeiras dezenas de clientes. Envias um email, recebes o pagamento, ativas manualmente uma flag `is_pro = 1` na tabela `users`.

2. **Stripe** (quando tiveres mais de 20 clientes pagantes) — aceita cartões, débito direto, MBWay em Portugal. Taxa: 1.4% + 0.25€ por transação em cartões europeus. A integração com PHP é direta.

3. **Ko-fi ou Buy Me a Coffee** — alternativa simples para começar, sem necessidade de integração. Crias um link de doação e pedes às turmas que "apoiem o projeto".

---

### Matemática simples para pagar o Hostinger

| Cenário | Turmas pagas | Preço/ano | Receita/ano | Lucro |
|---|---|---|---|---|
| Mínimo | 5 turmas | 15€ | 75€ | +39€ |
| Razoável | 20 turmas | 15€ | 300€ | +264€ |
| Bom | 50 turmas | 15€ | 750€ | +714€ |
| Excelente | 200 turmas | 15€ | 3.000€ | +2.964€ |

*Custo base: Hostinger Web Starter (~36€/ano)*

---

### Funcionalidades futuras que aumentam o valor percebido

Estes são os próximos passos técnicos que justificam o plano pago:

1. **Notificações por email** — "Tens um teste amanhã" (PHP `mail()` + cron job)
2. **App móvel progressiva (PWA)** — o site já funciona no telemóvel, mas podes adicionar "Instalar no ecrã inicial" gratuitamente
3. **Exportar horário como imagem** — os alunos adoram partilhar no Instagram Stories
4. **Integração com Google Calendar** — um clique para adicionar as tarefas ao calendário pessoal
5. **Dark mode** — simples com CSS, mas percebido como feature premium

---

### O teu pitch de 30 segundos para investimento/parceria

> *"O TurmaApp é uma plataforma web que resolve a desorganização de turmas universitárias. Em vez de 40 alunos gerirem cada um o seu calendário, um Chefe de Turma centraliza tudo — horário, prazos, materiais — e todos os colegas acedem em tempo real com um código de convite. Já temos [X] turmas ativas em [Y] faculdades. O modelo é freemium: gratuito para uma turma, 15€/ano para turmas ilimitadas. O objetivo é cobrir os custos de hosting e, a médio prazo, tornar-se a plataforma de referência para organização académica em Portugal."*

---

## RESUMO EXECUTIVO

```
Agora:          Deploy no InfinityFree (gratuito) → valida com a tua turma
Mês 1-2:        Expande para 10 turmas → recolhe feedback
Mês 3:          Migra para Hostinger (3€/mês) → mais velocidade e HTTPS
Mês 4-6:        Lança plano Pro (15€/ano) → 5 clientes pagam o hosting
Ano 1:          50 turmas pagas → 750€/ano → 714€ de lucro líquido
Ano 2+:         Escala para outras faculdades e países lusófonos (Brasil 🇧🇷)
```
