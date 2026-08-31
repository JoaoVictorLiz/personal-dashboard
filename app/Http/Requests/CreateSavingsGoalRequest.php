<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * "targetAmount" em centavos (inteiro >= 1). "targetDate" opcional.
 * Validacao de formato, nao regra de dominio - o SavingsGoal::create()
 * ainda barra alvo <= 0 por conta propria (defesa em profundidade).
 */
final class CreateSavingsGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'targetAmount' => ['required', 'integer', 'min:1'],
            'targetDate' => ['nullable', 'date'],
        ];
    }
}
