<?php

namespace Tests\Feature;

use App\Deriv\DerivClient;
use App\Deriv\Tick;
use App\Models\Bet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_page_shows_balance_bets_and_rules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('Game')
            ->where('balanceCents', 100000)
            ->where('openBet', null)
            ->where('lastBet', null)
            ->where('deriv.symbol', '1HZ100V')
            ->where('game.ticks', 5)
        );
    }

    public function test_root_goes_to_the_game(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_history_is_paginated_json_with_only_own_bets(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach (range(1, 12) as $i) {
            $user->bets()->create([
                'stake_cents' => 100 * $i, 'direction' => 'up', 'symbol' => '1HZ100V',
                'entry_quote' => 1000.0, 'entry_epoch' => 1792063354, 'status' => Bet::LOST, 'profit_cents' => -100 * $i,
            ]);
        }
        $other->bets()->create([
            'stake_cents' => 999, 'direction' => 'up', 'symbol' => '1HZ100V',
            'entry_quote' => 1000.0, 'entry_epoch' => 1792063354, 'status' => Bet::LOST, 'profit_cents' => -999,
        ]);

        $this->actingAs($user)->getJson('/bets?page=2')
            ->assertOk()
            ->assertJsonPath('total', 12)
            ->assertJsonPath('last_page', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['stake_cents' => 999]);
    }

    public function test_pending_bet_is_settled_when_page_opens(): void
    {
        $user = User::factory()->create();
        $user->wallet->decrement('balance_cents', 1000);
        $bet = $user->bets()->create([
            'stake_cents' => 1000, 'direction' => 'up', 'symbol' => '1HZ100V',
            'entry_quote' => 1000.0, 'entry_epoch' => 1792063354, 'status' => Bet::PENDING,
        ]);

        $ticks = [];
        for ($i = 1; $i <= 5; $i++) {
            $ticks[] = new Tick(1001.0, 1792063354 + $i);
        }
        $this->mock(DerivClient::class)->shouldReceive('ticksAfter')->andReturn($ticks);

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('balanceCents', 100950)
            ->where('openBet', null)
            ->where('lastBet.status', 'won')
        );
    }

    public function test_page_opens_even_if_deriv_is_down(): void
    {
        $user = User::factory()->create();
        $user->bets()->create([
            'stake_cents' => 1000, 'direction' => 'up', 'symbol' => '1HZ100V',
            'entry_quote' => 1000.0, 'entry_epoch' => 1792063354, 'status' => Bet::PENDING,
        ]);

        $this->mock(DerivClient::class)->shouldReceive('ticksAfter')->andThrow(new \RuntimeException('Deriv fora'));

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('openBet.status', 'pending'));
    }
}
