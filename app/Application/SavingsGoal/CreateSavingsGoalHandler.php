<?php

declare(strict_types=1);

namespace App\Application\SavingsGoal;

use App\Domain\SavingsGoal\SavingsGoal;
use App\Domain\SavingsGoal\SavingsGoalRepository;

final class CreateSavingsGoalHandler
{
    public function __construct(
        private readonly SavingsGoalRepository $goals,
    ) {}

    public function handle(CreateSavingsGoalCommand $command): void
    {
        $goal = SavingsGoal::create(
            $command->id,
            $command->title,
            $command->targetAmount,
            $command->targetDate,
        );

        $this->goals->save($goal);
    }
}
