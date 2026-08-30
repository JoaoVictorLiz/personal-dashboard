<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\SavingsGoal\SavingsGoal;
use App\Domain\SavingsGoal\SavingsGoalNotFound;
use App\Domain\SavingsGoal\SavingsGoalRepository;

/**
 * Implementacao "de mentira" do repositorio: um array em memoria.
 * Mesma interface que o Eloquent vai implementar depois. O handler
 * nao percebe a diferenca - e esse o ponto da porta/interface.
 */
final class InMemorySavingsGoalRepository implements SavingsGoalRepository
{
    /** @var array<string, SavingsGoal> */
    private array $goals = [];

    public function get(string $id): SavingsGoal
    {
        return $this->goals[$id] ?? throw SavingsGoalNotFound::withId($id);
    }

    public function save(SavingsGoal $goal): void
    {
        $this->goals[$goal->id()] = $goal;
    }
}
