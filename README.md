# Tick Predictor

Mini-jogo de previsão de ticks: o usuário acompanha a cotação de um ativo sintético da Deriv em tempo real, aposta se ela vai **subir** ou **descer** nos próximos **5 ticks**, e ganha 95% de lucro se acertar. Saldo fictício, cotação real.

Desafio técnico para a vaga de Desenvolvedor(a) Full Stack PHP/Laravel Pleno.

**Stack:** PHP 8.2+ · Laravel 12 · Inertia.js · Vue 3 (`<script setup>`) · Tailwind CSS · MySQL 8 · WebSocket público da Deriv.

---

## Rodando no localhost

### Pré-requisitos

| Ferramenta | Versão mínima | Como conferir |
|---|---|---|
| PHP | 8.2 (desenvolvido com 8.5) com extensões `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `curl`, `dom`, `xml`, `zip` | `php -v` e `php -m` |
| Composer | 2.x | `composer --version` |
| Node.js + npm | Node 18+ (desenvolvido com 22) | `node -v` |
| MySQL | 8.x | `mysql --version` |

### 1. Clonar e instalar dependências

```bash
git clone https://github.com/mychaelmatos/tick-predictor.git
cd tick-predictor
composer install
npm install
```

### 2. Criar o banco MySQL

Entre no MySQL como root e crie o banco e um usuário para o projeto:

```sql
CREATE DATABASE tick_predictor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tick_predictor'@'localhost' IDENTIFIED BY 'escolha_uma_senha';
GRANT ALL PRIVILEGES ON tick_predictor.* TO 'tick_predictor'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Configurar o `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Abra o `.env` e preencha a senha do banco (o resto já vem pronto no `.env.example`):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tick_predictor
DB_USERNAME=tick_predictor
DB_PASSWORD=escolha_uma_senha
```

### 4. Criar as tabelas e a conta de teste

```bash
php artisan migrate --seed
```

Isso cria as tabelas `users`, `wallets` e `bets` (além das do Laravel) e a conta de demonstração abaixo.

### 5. Compilar o frontend e subir o servidor

```bash
npm run build
php artisan serve
```

Abra **http://localhost:8000**. Você cai na tela de login.

Durante o desenvolvimento, em vez de `npm run build`, use `npm run dev` em um segundo terminal: o Vite recompila ao salvar.

### 6. Entrar

Na tela de login há o botão **"Entrar com a conta de teste"**, que preenche e entra com:

| E-mail | Senha |
|---|---|
| `teste@exemplo.com` | `teste1234` |

Ou crie sua própria conta em "Criar conta". Toda conta nova começa com **R$ 1.000,00** fictícios.

### Rodando os testes

```bash
php artisan optimize:clear
php artisan test
```

Os testes usam SQLite em memória (configurado no `phpunit.xml`), então não tocam no MySQL e não precisam de `npm run build`. O `optimize:clear` antes é importante: com config em cache o `phpunit.xml` seria ignorado. Há uma trava em `tests/TestCase.php` que aborta a suíte se isso acontecer.

---

## Como jogar

1. O gráfico mostra os últimos 40 ticks do **1HZ100V** (Volatility 100 (1s) Index), um tick por segundo. Coluna verde subiu em relação ao tick anterior, vermelha caiu.
2. Informe o valor (R$ 1,00 a R$ 1.000,00) e clique **Subir** ou **Descer**. O valor é debitado na hora.
3. A aposta fica aberta por 5 ticks. A linha tracejada amarela é a cotação de entrada; a barra de progresso conta os ticks.
4. No 5º tick o resultado é decidido: se a cotação do 5º tick estiver **acima** da entrada e você apostou Subir (ou abaixo e apostou Descer), recebe o valor de volta mais 95% de lucro. Caso contrário perde o valor. Empate conta como perda.
5. Clique em "Última aposta" ou em qualquer linha do histórico para ver os 5 ticks exatos que decidiram, com horário e cotação.

---

## Decisões de arquitetura

### Integridade do saldo

- **Dinheiro em centavos inteiros** (`balance_cents`, `stake_cents`, `profit_cents`). Sem decimal, sem arredondamento. R$ 10,00 é `1000`.
- **Tabela `wallets` separada** (uma por usuário, criada automaticamente num evento `created` do model `User`, valendo para cadastro, seeder e factory). Separada da `users` para travar só a carteira durante a aposta.
- **`balance_cents` é `unsigned`**: o próprio MySQL recusa saldo negativo. É a última linha de defesa, caso algum bug passe pelo PHP.
- **Toda aposta roda em `DB::transaction` com `lockForUpdate()` na linha da carteira** (`app/Services/PlaceBet.php`). Dois cliques simultâneos entram um de cada vez: o segundo lê o saldo já debitado. Dentro da mesma trava, só é permitida **uma aposta aberta por vez**, o que também resolve o clique duplo sem precisar de chave de idempotência.
- **A chamada à Deriv fica fora da transação.** Nunca se segura uma trava de banco esperando a rede.
- **A liquidação relê a aposta com `lockForUpdate()`** e só credita se ela ainda estiver pendente (`app/Services/SettleBet.php`). Liquidar duas vezes paga uma.

### Ticks da Deriv

- **O endpoint citado no enunciado (`ws.derivws.com`, app_id 1089) está fora do ar** (responde HTTP 520). A Deriv migrou a API em 2026. O projeto usa o endpoint público atual, `wss://api.derivws.com/trading/v1/options/ws/public`, que não exige app_id nem login e fala o mesmo protocolo JSON. Detalhes e exemplos reais em [`docs/deriv-api.md`](docs/deriv-api.md).
- **O navegador conecta ao WebSocket só para exibir** (`resources/js/composables/useDerivTicks.js`): preço, gráfico e contagem dos ticks. Nada que vem do navegador decide uma aposta.
- **O backend decide sozinho.** Ao apostar, o Laravel pergunta à Deriv o último tick e grava cotação e `epoch` de entrada. Ao liquidar, pede à Deriv o histórico de ticks a partir desse `epoch`, pega o 5º tick seguinte, compara com a entrada e credita ou não. O navegador apenas avisa "pode conferir" quando contou 5 ticks; se mentir ou não avisar, nada muda.
- **Os 5 ticks usados ficam gravados na aposta** (coluna `ticks`, JSON). O usuário confere o resultado na tela, e há trilha de auditoria.
- **Sem processo em segundo plano.** Cada consulta à Deriv abre a conexão, pergunta, lê, fecha (`app/Deriv/DerivClient.php`, usando o pacote `phrity/websocket`). Apostas que ficaram abertas (aba fechada) são decididas quando o usuário volta à página. Se a Deriv estiver fora nesse momento, a página abre mesmo assim e a aposta segue pendente.
- **Janela de ticks por `epoch`, não por `count`.** A Deriv devolve os últimos N ticks quando se usa `count`, e ignora o `start` se ele estiver no futuro. Por isso o cliente pede uma janela fechada e filtra os ticks pelo `epoch` da entrada. Isso foi descoberto num teste real e está coberto por teste automatizado.

### Organização do código

| Onde | O quê |
|---|---|
| `config/game.php` | Regras do jogo num lugar só: saldo inicial, símbolo, 5 ticks, 95%, limites de aposta, conta de teste |
| `app/Services/PlaceBet.php` | Abrir aposta: tick de entrada, transação, trava, débito |
| `app/Services/SettleBet.php` | Decidir aposta: 5º tick, transação, trava, crédito |
| `app/Deriv/DerivClient.php` | Única classe que conhece a Deriv |
| `app/Http/Requests/PlaceBetRequest.php` | Validação da aposta (Form Request) |
| `app/Http/Controllers/BetController.php` | Apostar, liquidar, histórico paginado (JSON) |
| `app/Http/Controllers/GameController.php` | Tela do jogo |
| `resources/js/Pages/Game.vue` | Tela do jogo (Vue 3 + Inertia) |
| `resources/js/Components/TickChart.vue` | Gráfico de colunas, sem biblioteca |
| `tests/Feature/` | 35 testes: carteira, aposta, liquidação, página, login de teste |

### Escolhas e simplificações

- **Laravel 12, não 10/11.** O Composer bloqueia a linha 11.x por avisos de segurança abertos (o 11 saiu do suporte de segurança em março de 2026). A estrutura é a mesma.
- **Direção Subir/Descer** (o enunciado deixava escolher entre isso e Par/Ímpar).
- **Símbolo 1HZ100V** em vez de R_100: um tick por segundo, a rodada dura 5 segundos.
- **Autenticação básica com Breeze**, só cadastro, login e logout. Os fluxos de "esqueci a senha", confirmação de senha e verificação de e-mail foram removidos porque o projeto não envia e-mail.
- **Sem Docker e sem filas**, de propósito: o jogo não precisa de processo em segundo plano, e menos peças é menos para dar errado.
- **Tudo em português**, inclusive mensagens de validação do framework (`lang/pt_BR`) e páginas de erro.
