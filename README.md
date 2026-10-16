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

No `.env`, coloque o usuário e a senha do seu MySQL em `DB_USERNAME` e `DB_PASSWORD`. Depois:

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

**Ticks.** O navegador se conecta ao WebSocket da Deriv apenas para exibir o preço e o gráfico. Quem decide o resultado é o backend: ele busca na Deriv o tick de entrada e o 5º tick seguinte, compara os dois e grava os ticks usados na aposta. O endpoint indicado no enunciado está fora do ar, então o projeto usa o endpoint público atual, `wss://api.derivws.com/trading/v1/options/ws/public`.
