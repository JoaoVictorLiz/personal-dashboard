<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PATCH parcial: "sometimes" = so valida o campo se ele veio no payload.
 * Enviar "targetDate": null e valido (remove o prazo).
 */
final class UpdateSavingsGoalRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'targetAmount' => ['sometimes', 'required', 'integer', 'min:1'],
            'targetDate' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
