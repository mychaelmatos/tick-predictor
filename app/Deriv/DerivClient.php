<?php

namespace App\Deriv;

use RuntimeException;
use WebSocket\Client;

/**
 * Fala com o WebSocket público da Deriv. É a única classe do projeto que conhece a Deriv.
 * O resto do sistema só pede "me dá o último tick" ou "me dá os ticks depois de tal segundo".
 *
 * Cada pergunta abre a conexão, pergunta, lê a resposta e fecha. Nada fica aberto.
 */
class DerivClient
{
    public function __construct(private string $url) {}

    /** Último tick disponível do símbolo. */
    public function latestTick(string $symbol): Tick
    {
        $ticks = $this->ticksHistory([
            'ticks_history' => $symbol,
            'end' => 'latest',
            'count' => 1,
        ]);

        if ($ticks === []) {
            throw new RuntimeException("Deriv não devolveu nenhum tick para {$symbol}.");
        }

        return $ticks[0];
    }

    /**
     * Ticks estritamente depois de $epoch, em ordem, até $limit.
     * Janela fechada de $epoch+1 até $epoch+$windowSeconds: a Deriv devolve tudo dentro dela.
     * Pode vir menos que $limit se ainda não passou tempo suficiente.
     *
     * @return Tick[]
     */
    public function ticksAfter(string $symbol, int $epoch, int $limit, int $windowSeconds = 60): array
    {
        $ticks = $this->ticksHistory([
            'ticks_history' => $symbol,
            'start' => $epoch + 1,
            'end' => (string) ($epoch + $windowSeconds),
        ]);

        // Se o início da janela ainda está no futuro, a Deriv ignora o start e devolve
        // os últimos 1000 ticks. Por isso nunca confiamos na janela: filtramos pelo epoch.
        $after = array_values(array_filter($ticks, fn (Tick $t) => $t->epoch > $epoch));

        return array_slice($after, 0, $limit);
    }

    /** @return Tick[] */
    private function ticksHistory(array $params): array
    {
        $response = $this->request($params + ['style' => 'ticks']);

        $prices = $response['history']['prices'] ?? [];
        $times = $response['history']['times'] ?? [];

        return array_map(
            fn ($quote, $epoch) => new Tick((float) $quote, (int) $epoch),
            $prices,
            $times,
        );
    }

    /** Envia uma mensagem JSON e devolve a resposta decodificada. */
    private function request(array $payload): array
    {
        $client = new Client($this->url);
        $client->setTimeout(10);

        try {
            $client->text(json_encode($payload));
            $response = json_decode($client->receive()->getContent(), true);
        } finally {
            $client->close();
        }

        if (isset($response['error'])) {
            throw new RuntimeException('Deriv: '.$response['error']['message']);
        }

        return $response;
    }
}
