<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Os 5 ticks que o backend usou para decidir, como vieram da Deriv: [{epoch, quote}, ...].
            // Fica gravado para o usuário conferir o resultado depois (auditoria).
            $table->json('ticks')->nullable()->after('exit_epoch');
        });
    }

    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropColumn('ticks');
        });
    }
};
