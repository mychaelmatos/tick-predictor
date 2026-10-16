<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Services\SettleBet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(Request $request, SettleBet $settleBet): Response
    {
        $user = $request->user();

        // Aposta que ficou aberta (usuário fechou a aba) é decidida ao voltar.
        // rescue: se a Deriv estiver fora, a página abre mesmo assim e a aposta segue pendente.
        $user->bets()->where('status', Bet::PENDING)
            ->each(fn (Bet $bet) => rescue(fn () => $settleBet->handle($bet)));

        return Inertia::render('Game', [
            'balanceCents' => $user->wallet()->value('balance_cents'), // lê do banco, depois das liquidações acima
            'openBet' => $user->bets()->where('status', Bet::PENDING)->latest('id')->first(),
            'lastBet' => $user->bets()->where('status', '!=', Bet::PENDING)->latest('id')->first(),
            'game' => [
                'ticks' => config('game.ticks'),
                'payoutPercent' => config('game.payout_percent'),
                'minStakeCents' => config('game.min_stake_cents'),
                'maxStakeCents' => config('game.max_stake_cents'),
            ],
        ]);
    }
}
