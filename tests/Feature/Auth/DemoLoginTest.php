<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_offers_the_demo_account(): void
    {
        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('demo.email', config('game.demo_user.email'))
            ->where('demo.password', config('game.demo_user.password'))
        );
    }

    public function test_demo_account_from_seeder_can_log_in(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post('/login', [
            'email' => config('game.demo_user.email'),
            'password' => config('game.demo_user.password'),
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_seeder_can_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
    }
}
