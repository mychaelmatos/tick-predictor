<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('stake_cents');          // valor apostado
            $table->string('direction', 4);                  // up | down
            $table->string('symbol', 30);                    // ex: 1HZ100V
            $table->decimal('entry_quote', 15, 5);           // cotação no tick de entrada
            $table->unsignedInteger('entry_epoch');          // segundo Unix do tick de entrada (vem da Deriv)
            $table->decimal('exit_quote', 15, 5)->nullable();// cotação no tick de saída (5 ticks depois)
            $table->unsignedInteger('exit_epoch')->nullable();
            $table->string('status', 7)->default('pending'); // pending | won | lost
            $table->integer('profit_cents')->nullable();     // +950 se ganhou R$ 9,50; -1000 se perdeu R$ 10,00
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
