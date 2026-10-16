<?php

namespace App\Services;

use App\Deriv\DerivClient;
use App\Models\Bet;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceBet
{
    public function __construct(private DerivClient $deriv) {}

    public function handle(User $user, int $stakeCents, string $direction): Bet
    {
        $symbol = config('game.symbol');

        // Rede fora da transação: nunca segurar uma trava de banco esperando a Deriv responder.
        $entry = $this->deriv->latestTick($symbol);

        return DB::transaction(function () use ($user, $stakeCents, $direction, $symbol, $entry) {
            // lockForUpdate: dois pedidos simultâneos do mesmo usuário entram aqui um de cada vez.
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($wallet->balance_cents < $stakeCents) {
                throw ValidationException::withMessages(['stake_cents' => 'Saldo insuficiente.']);
            }

            // Uma aposta aberta por vez. Como a carteira está travada, dois cliques
            // simultâneos passam aqui um de cada vez e o segundo é recusado.
            if ($user->bets()->where('status', Bet::PENDING)->exists()) {
                throw ValidationException::withMessages(['stake_cents' => 'Aguarde a aposta em andamento ser decidida.']);
            }

            $wallet->decrement('balance_cents', $stakeCents);

            return $user->bets()->create([
                'stake_cents' => $stakeCents,
                'direction' => $direction,
                'symbol' => $symbol,
                'entry_quote' => $entry->quote,
                'entry_epoch' => $entry->epoch,
                'status' => Bet::PENDING,
            ]);
        });
    }
}
