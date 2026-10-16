<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Saldo em centavos inteiros: R$ 1.000,00 = 100000. Sem decimal, sem arredondamento.
            // unsigned: o próprio MySQL recusa saldo negativo, é a última linha de defesa.
            $table->unsignedBigInteger('balance_cents')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
