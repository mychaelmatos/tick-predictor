<?php

namespace App\Deriv;

// Um tick da Deriv: a cotação e o segundo em que ela valia. Só isso interessa ao jogo.
final readonly class Tick
{
    public function __construct(
        public float $quote,
        public int $epoch,
    ) {}
}
