<?php

namespace App\Services;

use App\Deriv\DerivClient;
use App\Models\Bet;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * Decide uma aposta pendente olhando o N-ésimo tick depois da entrada, direto na Deriv.
 * O navegador nunca manda cotação nenhuma; ele só avisa "pode conferir".
 */
class SettleBet
{
    public function __construct(private DerivClient $deriv) {}

    public function handle(Bet $bet): Bet
    {
        if (! $bet->isPending()) {
            return $bet;
        }

        $ticksNeeded = config('game.ticks');

        // Rede fora da transação, pelo mesmo motivo do PlaceBet.
        $ticks = $this->deriv->ticksAfter($bet->symbol, $bet->entry_epoch, $ticksNeeded);

        // Só conta tick posterior à entrada. Nunca decidir uma aposta com cotação do passado.
        $ticks = array_values(array_filter($ticks, fn ($t) => $t->epoch > $bet->entry_epoch));

        if (count($ticks) < $ticksNeeded) {
            return $bet; // ainda não passaram os 5 ticks; continua pendente
        }

        $exit = $ticks[$ticksNeeded - 1];

        $won = $bet->direction === Bet::UP
            ? $exit->quote > $bet->entry_quote
            : $exit->quote < $bet->entry_quote;
        // Empate (saiu no mesmo preço) conta como perda: é a regra usual em opções binárias.

        return DB::transaction(function () use ($bet, $exit, $won, $ticks) {
            // Relê a aposta com trava: se dois pedidos de liquidar chegarem juntos, o segundo vê "já resolvida".
            $bet = Bet::whereKey($bet->id)->lockForUpdate()->first();

            if (! $bet->isPending()) {
                return $bet;
            }

            if ($won) {
                $profit = intdiv($bet->stake_cents * config('game.payout_percent'), 100);

                Wallet::where('user_id', $bet->user_id)
                    ->lockForUpdate()
                    ->first()
                    ->increment('balance_cents', $bet->stake_cents + $profit);
            } else {
                $profit = -$bet->stake_cents;
            }

            $bet->update([
                'exit_quote' => $exit->quote,
                'exit_epoch' => $exit->epoch,
                'ticks' => array_map(fn ($t) => ['epoch' => $t->epoch, 'quote' => $t->quote], $ticks),
                'status' => $won ? Bet::WON : Bet::LOST,
                'profit_cents' => $profit,
            ]);

            return $bet;
        });
    }
}
