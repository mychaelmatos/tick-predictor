<?php

namespace App\Http\Requests;

use App\Models\Bet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceBetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // a rota já exige login; qualquer usuário logado pode apostar
    }

    public function rules(): array
    {
        return [
            'stake_cents' => ['required', 'integer', 'min:'.config('game.min_stake_cents'), 'max:'.config('game.max_stake_cents')],
            'direction' => ['required', Rule::in([Bet::UP, Bet::DOWN])],
        ];
    }

    /** Mensagens em reais, não em centavos, porque é o que o usuário vê. */
    public function messages(): array
    {
        $reais = fn (int $cents) => 'R$ '.number_format($cents / 100, 2, ',', '.');

        return [
            'stake_cents.min' => 'A aposta mínima é '.$reais(config('game.min_stake_cents')).'.',
            'stake_cents.max' => 'A aposta máxima é '.$reais(config('game.max_stake_cents')).'.',
            'direction.in' => 'Escolha Subir ou Descer.',
        ];
    }
}
