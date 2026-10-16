<?php

namespace Tests\Feature;

use App\Deriv\DerivClient;
use App\Deriv\Tick;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceBetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Nos testes a Deriv é substituída por um dublê que sempre responde o mesmo tick.
        $this->mock(DerivClient::class)
            ->shouldReceive('latestTick')
            ->andReturn(new Tick(quote: 1234.56, epoch: 1792063354));
    }

    public function test_bet_debits_wallet_and_is_saved_as_pending(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/bets', ['stake_cents' => 1000, 'direction' => 'up'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(99000, $user->wallet->fresh()->balance_cents);
        $this->assertDatabaseHas('bets', [
            'user_id' => $user->id,
            'stake_cents' => 1000,
            'direction' => 'up',
            'symbol' => '1HZ100V',
            'entry_quote' => 1234.56,
            'entry_epoch' => 1792063354,
            'status' => 'pending',
        ]);
    }

    public function test_bet_above_balance_is_rejected_and_nothing_changes(): void
    {
        $user = User::factory()->create();
        $user->wallet->update(['balance_cents' => 500]);

        $this->actingAs($user)
            ->post('/bets', ['stake_cents' => 1000, 'direction' => 'up'])
            ->assertSessionHasErrors('stake_cents');

        $this->assertSame(500, $user->wallet->fresh()->balance_cents);
        $this->assertDatabaseCount('bets', 0);
    }

    public function test_only_one_open_bet_at_a_time(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/bets', ['stake_cents' => 1000, 'direction' => 'up']);
        $this->actingAs($user)
            ->post('/bets', ['stake_cents' => 1000, 'direction' => 'down'])
            ->assertSessionHasErrors('stake_cents');

        $this->assertDatabaseCount('bets', 1);
        $this->assertSame(99000, $user->wallet->fresh()->balance_cents);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/bets', ['stake_cents' => 50, 'direction' => 'sideways'])
            ->assertSessionHasErrors(['stake_cents', 'direction']);

        $this->assertDatabaseCount('bets', 0);
    }

    public function test_guest_cannot_bet(): void
    {
        $this->post('/bets', ['stake_cents' => 1000, 'direction' => 'up'])
            ->assertRedirect('/login');
    }
}
