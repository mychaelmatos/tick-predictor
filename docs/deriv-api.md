# Deriv API — o que o projeto usa

Levantamento feito em 15/10/2026, com testes reais de conexão.

## Endpoint

| Endereço | Situação |
|---|---|
| `wss://ws.derivws.com/websockets/v3?app_id=1089` (o do PDF) | **Morto.** Responde HTTP 520, handshake falha. |
| `wss://red.binaryws.com/websockets/v3?app_id=1089` | Legado, parcialmente quebrado (`ticks_history` funciona, `ticks` com subscribe dá `InvalidSymbol`). Não usar. |
| `wss://api.derivws.com/trading/v1/options/ws/public` | **Atual.** Público, sem `app_id`, sem autenticação. Só dados de mercado. É o que usamos. |

Fonte: https://developers.deriv.com/docs/options/ws-public/

## Protocolo

Mensagens JSON em ambos os sentidos. Toda resposta traz `msg_type`, `echo_req` (cópia do pedido) e, se enviado, `req_id`. Se algo deu errado, vem um campo `error` com `code` e `message`; checar antes de usar os dados.

### Assinar ticks (`ticks`)

Pedido:
```json
{ "ticks": "R_100", "subscribe": 1, "req_id": 1 }
```

Cada tick que chega (1 por segundo no 1HZ100V, 1 a cada 2 s no R_100):
```json
{
  "msg_type": "tick",
  "req_id": 1,
  "subscription": { "id": "41623f3f-d762-4f1e-bca6-c4cc831bbc11" },
  "tick": {
    "symbol": "R_100",
    "quote": 656.26,
    "epoch": 1792063280,
    "ask": 656.36,
    "bid": 656.16,
    "pip_size": 2,
    "id": "41623f3f-d762-4f1e-bca6-c4cc831bbc11"
  }
}
```

Campos que importam: `quote` (preço), `epoch` (segundo Unix do tick), `pip_size` (casas decimais para exibir), `subscription.id` (para cancelar).

### Histórico (`ticks_history`)

Serve para preencher o gráfico com os últimos 20 ticks logo ao abrir a tela.

Pedido:
```json
{ "ticks_history": "R_100", "end": "latest", "count": 20, "style": "ticks", "req_id": 2 }
```

Resposta:
```json
{
  "msg_type": "history",
  "history": { "prices": [655.07, 655.33, "..."], "times": [1792063316, 1792063318, "..."] },
  "pip_size": 2
}
```

`prices[i]` e `times[i]` são pares. Aceita `subscribe: 1` para já receber os ticks seguintes na mesma chamada.

### Cancelar (`forget`)

```json
{ "forget": "41623f3f-d762-4f1e-bca6-c4cc831bbc11", "req_id": 3 }
```
Resposta `"forget": 1` cancelou, `0` não achou o id. `{ "forget_all": "ticks" }` cancela todas de ticks.

### Manter viva (`ping`)

```json
{ "ping": 1 }
```
Resposta `"ping": "pong"`. A Deriv recomenda enviar a cada 30 s. Até 10 por segundo, fora da cota.

## Limites

- 220 chamadas por minuto e 14.400 por hora por conexão para chamadas de dados (ticks, history, forget).
- Ping não conta.

Fonte: https://developers.deriv.com/docs/limits/

## Boas práticas recomendadas pela Deriv

- Uma única conexão por cliente, reaproveitada para tudo.
- `req_id` em cada pedido para casar resposta com pedido.
- Reconectar com espera crescente (backoff) e um pouco de aleatoriedade, nunca em loop apertado.
- Dar `forget` em assinaturas que não usa mais.
- Checar `error` em toda resposta.

Fonte: https://developers.deriv.com/docs/best-practices/

## Símbolos

- `R_100`: Volatility 100 Index, 1 tick a cada 2 segundos.
- `1HZ100V`: Volatility 100 (1s) Index, 1 tick por segundo.

Ambos existem 24/7 porque são sintéticos. Para o jogo de 5 ticks, o 1HZ100V deixa a rodada mais rápida (5 s); o R_100 dura 10 s.
