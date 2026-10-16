<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Conta de demonstração (mesmos dados do config, que a tela de login oferece).
        // firstOrCreate: rodar o seeder de novo não duplica nem quebra.
        $demo = config('game.demo_user');

        User::firstOrCreate(
            ['email' => $demo['email']],
            ['name' => $demo['name'], 'password' => $demo['password']],
        );
    }
}
