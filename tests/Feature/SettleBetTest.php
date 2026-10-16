<?php

namespace Tests\Feature;

use App\Deriv\DerivClient;
use App\Deriv\Tick;
use App\Models\Bet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettleBetTest extends TestCase
{
    use RefreshDatabase;

    private function pendingBet(User $user, string $direction = 'up', int $stake = 1000): Bet
    {
        $user->wallet->decrement('balance_cents', $stake);

        return $user->bets()->create([
            'stake_cents' => $stake,
            'direction' => $direction,
            'symbol' => '1HZ100V',
            'entry_quote' => 1000.00,
            'entry_epoch' => 1792063354,
            'status' => Bet::PENDING,
        ]);
    }

    /** Dublê da Deriv: devolve ticks com as cotações informadas, 1 segundo após a entrada cada. */
    private function derivReturns(array $quotes): void
    {
        $ticks = [];
        foreach ($quotes as $i => $quote) {
            $ticks[] = new Tick($quote, 1792063354 + $i + 1);
        }

        $this->mock(DerivClient::class)->shouldReceive('ticksAfter')->andReturn($ticks);
    }

    public function test_up_bet_wins_when_fifth_tick_is_higher(): void
    {
        $user = User::factory()->create();
        $bet = $this->pendingBet($user, 'up', 1000);
        $this->derivReturns([999.0, 998.0, 997.0, 996.0, 1000.5]); // só o 5º importa

        $this->actingAs($user)->post("/bets/{$bet->id}/settle")->assertRedirect();

        $bet->refresh();
        $this->assertSame('won', $bet->status);
        $this->assertSame(950, $bet->profit_cents);
        $this->assertSame(1000.5, $bet->exit_quote);
        $this->assertSame(1792063359, $bet->exit_epoch);
        // Os 5 ticks usados ficam gravados para conferência; o último é o de saída.
        $this->assertCount(5, $bet->ticks);
        $this->assertSame(['epoch' => 1792063359, 'quote' => 1000.5], $bet->ticks[4]);
        // 100000 - 1000 (aposta) + 1000 (devolve) + 950 (lucro)
        $this->assertSame(100950, $user->wallet->fresh()->balance_cents);
    }

    public function test_down_bet_loses_when_fifth_tick_is_higher(): void
    {
        $user = User::factory()->create();
        $bet = $this->pendingBet($user, 'down', 1000);
        $this->derivReturns([1001.0, 1001.0, 1001.0, 1001.0, 1001.0]);

        $this->actingAs($user)->post("/bets/{$bet->id}/settle");

        $this->assertSame('lost', $bet->fresh()->status);
        $this->assertSame(-1000, $bet->fresh()->profit_cents);
        $this->assertSame(99000, $user->wallet->fresh()->balance_cents);
    }

    public function test_tie_counts_as_loss(): void
    {
        $user = User::factory()->create();
        $bet = $this->pendingBet($user, 'up');
        $this->derivReturns([1000.0, 1000.0, 1000.0, 1000.0, 1000.0]);

        $this->actingAs($user)->post("/bets/{$bet->id}/settle");

        $this->assertSame('lost', $bet->fresh()->status);
    }

    public function test_stays_pending_until_enough_ticks_exist(): void
    {
        $user = User::factory()->create();
        $bet = $this->pendingBet($user);
        $this->derivReturns([1001.0, 1002.0, 1003.0]); // só 3 ticks até agora

        $this->actingAs($user)->post("/bets/{$bet->id}/settle");

        $this->assertSame('pending', $bet->fresh()->status);
        $this->assertSame(99000, $user->wallet->fresh()->balance_cents);
    }

    public function test_ticks_before_entry_are_ignored(): void
    {
        $user = User::factory()->create();
        $bet = $this->pendingBet($user, 'up');

        // Deriv devolvendo ticks antigos (epoch anterior à entrada): não podem decidir nada.
        $old = [];
        for ($i = 1; $i <= 5; $i++) {
            $old[] = new Tick(1001.0, 1792063354 - 1000 + $i);
        }
        $this->mock(DerivClient::class)->shouldReceive('ticksAfter')->andReturn($old);

        $this->actingAs($user)->post("/bets/{$bet->id}/settle");

        $this->assertSame('pending', $bet->fresh()->status);
        $this->assertSame(99000, $user->wallet->fresh()->balance_cents);
    }

    public function test_settling_twice_pays_only_once(): void
    {
        $user = User::factory()->create();
        $bet = $this->pendingBet($user, 'up', 1000);
        $this->derivReturns([1001.0, 1001.0, 1001.0, 1001.0, 1001.0]);

        $this->actingAs($user)->post("/bets/{$bet->id}/settle");
        $this->actingAs($user)->post("/bets/{$bet->id}/settle");

        $this->assertSame(100950, $user->wallet->fresh()->balance_cents);
    }

    public function test_user_cannot_settle_someone_elses_bet(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $bet = $this->pendingBet($owner);
        $this->derivReturns([1001.0, 1001.0, 1001.0, 1001.0, 1001.0]);

        $this->actingAs($other)->post("/bets/{$bet->id}/settle")->assertForbidden();

        $this->assertSame('pending', $bet->fresh()->status);
    }
}
