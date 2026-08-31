<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\SavingsGoal\Contribution;
use App\Domain\SavingsGoal\SavingsGoal;
use App\Domain\SavingsGoal\SavingsGoalNotFound;
use App\Domain\SavingsGoal\SavingsGoalRepository;
use App\Domain\SavingsGoal\SavingsGoalStatus;
use App\Domain\Shared\Money;
use DateTimeImmutable;

/**
 * O ADAPTADOR: implementa a porta SavingsGoalRepository usando Eloquent.
 * Toda a traducao Model <-> entidade de dominio mora aqui, isolada.
 * O resto do sistema so conhece a interface.
 */
final class EloquentSavingsGoalRepository implements SavingsGoalRepository
{
    public function get(string $id): SavingsGoal
    {
        $model = SavingsGoalModel::query()
            ->with(['contributions' => fn ($q) => $q->orderBy('created_at')->orderBy('id')])
            ->find($id);

        if ($model === null) {
            throw SavingsGoalNotFound::withId($id);
        }

        return $this->toDomain($model);
    }

    public function save(SavingsGoal $goal): void
    {
        SavingsGoalModel::query()->updateOrCreate(
            ['id' => $goal->id()],
            [
                'title' => $goal->title(),
                'target_amount_cents' => $goal->targetAmount()->cents(),
                'current_amount_cents' => $goal->currentAmount()->cents(),
                'currency' => $goal->targetAmount()->currency(),
                'status' => $goal->status()->value,
                'target_date' => $goal->targetDate(),
            ],
        );

        foreach ($goal->contributions() as $contribution) {
            ContributionModel::query()->updateOrCreate(
                ['id' => $contribution->id()],
                [
                    'savings_goal_id' => $goal->id(),
                    'amount_cents' => $contribution->amount()->cents(),
                    'currency' => $contribution->amount()->currency(),
                    'date' => $contribution->date(),
                    'note' => $contribution->note(),
                ],
            );
        }
    }

    private function toDomain(SavingsGoalModel $model): SavingsGoal
    {
        $contributions = $model->contributions
            ->map(fn (ContributionModel $c): Contribution => new Contribution(
                $c->id,
                $c->savings_goal_id,
                Money::fromCents($c->amount_cents, $c->currency),
                DateTimeImmutable::createFromInterface($c->date),
                $c->note,
            ))
            ->all();

        return SavingsGoal::reconstitute(
            $model->id,
            $model->title,
            Money::fromCents($model->target_amount_cents, $model->currency),
            Money::fromCents($model->current_amount_cents, $model->currency),
            SavingsGoalStatus::from($model->status),
            $model->target_date !== null
                ? DateTimeImmutable::createFromInterface($model->target_date)
                : null,
            ...$contributions,
        );
    }
}
