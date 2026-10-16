<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceBetRequest;
use App\Models\Bet;
use App\Services\PlaceBet;
use App\Services\SettleBet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BetController extends Controller
{
    /** Histórico paginado do usuário logado, em JSON, para o modal. */
    public function index(Request $request): LengthAwarePaginator
    {
        return $request->user()->bets()->latest('id')->paginate(10);
    }

    public function store(PlaceBetRequest $request, PlaceBet $placeBet): RedirectResponse
    {
        $placeBet->handle(
            $request->user(),
            $request->integer('stake_cents'),
            $request->string('direction')->toString(),
        );

        return back();
    }

    public function settle(Request $request, Bet $bet, SettleBet $settleBet): RedirectResponse
    {
        abort_unless($bet->user_id === $request->user()->id, 403);

        $settleBet->handle($bet);

        return back();
    }
}
