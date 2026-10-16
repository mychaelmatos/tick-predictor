<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_starts_with_initial_balance(): void
    {
        $user = User::factory()->create();

        $this->assertSame(100000, $user->wallet->balance_cents);
    }

    public function test_registration_creates_wallet(): void
    {
        $this->post('/register', [
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => User::where('email', 'maria@example.com')->value('id'),
            'balance_cents' => 100000,
        ]);
    }
}
