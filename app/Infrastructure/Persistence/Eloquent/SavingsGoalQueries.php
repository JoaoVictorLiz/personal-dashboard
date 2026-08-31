<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

/**
 * Lado de LEITURA. Nao reconstroi o agregado - le a tabela e devolve
 * arrays ja no formato da resposta. Rapido e independente do dominio.
 */
final class SavingsGoalQueries
{
    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return SavingsGoalModel::query()
            ->orderBy('created_at')
            ->get()
            ->map(fn (SavingsGoalModel $g): array => [
                'id' => $g->id,
                'title' => $g->title,
                'targetAmountCents' => $g->target_amount_cents,
                'currentAmountCents' => $g->current_amount_cents,
                'currency' => $g->currency,
                'progressPercentage' => $g->target_amount_cents > 0
                    ? intdiv($g->current_amount_cents * 100, $g->target_amount_cents)
                    : 0,
                'status' => $g->status,
                'targetDate' => $g->target_date?->format('Y-m-d'),
            ])
            ->all();
    }
}
