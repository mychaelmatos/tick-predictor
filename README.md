# Tick Predictor

Jogo de previsão de ticks com cotações reais da Deriv. Você aposta se o preço vai subir ou descer nos próximos 5 ticks. Se acertar, ganha 95% sobre o valor apostado.

Laravel 12 · Inertia · Vue 3 · Tailwind · MySQL

## Como rodar

Requisitos: PHP 8.2+, Composer, Node 18+ e MySQL.

```bash
git clone https://github.com/mychaelmatos/tick-predictor.git
cd tick-predictor
cp .env.example .env
```

Abra o arquivo `.env` e encontre este bloco:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tick_predictor
DB_USERNAME=root
DB_PASSWORD=
```

Altere só o que for diferente no seu MySQL:

| Linha | O que colocar |
|---|---|
| `DB_USERNAME` | Usuário do seu MySQL. Já vem `root`. |
| `DB_PASSWORD` | Senha desse usuário. Se o seu root não tem senha, deixe vazio. |
| `DB_HOST` e `DB_PORT` | Só mude se o MySQL não estiver nesta máquina ou não usar a porta 3306. |
| `DB_DATABASE` | Nome do banco. Não precisa criar antes: o setup cria. |

O resto do `.env` não precisa mudar. Depois:

```bash
composer run setup
php artisan serve
```

O `setup` instala as dependências, cria o banco `tick_predictor`, as tabelas e a conta de teste, e compila o frontend.

Acesse http://localhost:8000 e clique em **Entrar com a conta de teste**. A conta é `teste@exemplo.com`, senha `teste1234`. Toda conta começa com R$ 1.000,00 fictícios.

Para rodar os testes:

```bash
composer run test
```

## Decisões

**Saldo.** Os valores ficam em centavos inteiros. A aposta debita o saldo dentro de uma transação com a carteira travada (`lockForUpdate`), então dois cliques ao mesmo tempo não deixam o saldo negativo. Só é permitida uma aposta aberta por vez. O prêmio é creditado da mesma forma, e uma aposta nunca é paga duas vezes.

**Ticks.** O navegador se conecta ao WebSocket da Deriv apenas para exibir o preço e o gráfico. Quem decide o resultado é o backend: ele busca na Deriv o tick de entrada e o 5º tick seguinte, compara os dois e grava os ticks usados na aposta. O endpoint usado: `wss://api.derivws.com/trading/v1/options/ws/public`.
