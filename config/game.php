<?php

// Regras do jogo em um só lugar. Nada disso fica espalhado pelo código.
return [
    // Saldo fictício com que todo usuário novo começa (em centavos). R$ 1.000,00.
    'initial_balance_cents' => 100000,

    // Ativo sintético da Deriv acompanhado no jogo. 1HZ100V = Volatility 100 (1s), um tick por segundo.
    'symbol' => '1HZ100V',

    // Quantos ticks depois da entrada a aposta é decidida.
    'ticks' => 5,

    // Lucro sobre o valor apostado quando acerta. 95 = ganha R$ 9,50 numa aposta de R$ 10,00.
    'payout_percent' => 95,

    // Limites por aposta (em centavos): R$ 1,00 a R$ 1.000,00.
    'min_stake_cents' => 100,
    'max_stake_cents' => 100000,

    // Conta de demonstração: criada pelo seeder e oferecida na tela de login com um clique.
    'demo_user' => [
        'name' => 'Conta de teste',
        'email' => 'teste@exemplo.com',
        'password' => 'teste1234',
    ],
];
