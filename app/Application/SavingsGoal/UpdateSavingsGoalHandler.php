<?php

declare(strict_types=1);

namespace App\Application\SavingsGoal;

use App\Domain\SavingsGoal\SavingsGoalRepository;
use App\Domain\Shared\EventDispatcher;

final class UpdateSavingsGoalHandler
{
    public function __construct(
        private readonly SavingsGoalRepository $goals,
        private readonly EventDispatcher $events,
    ) {}

    public function handle(UpdateSavingsGoalCommand $command): void
    {
        $goal = $this->goals->get($command->id);

        if ($command->title !== null) {
            $goal->rename($command->title);
        }

        if ($command->targetAmount !== null) {
            $goal->changeTarget($command->targetAmount);
        }

        if ($command->changesTargetDate) {
            $goal->changeTargetDate($command->targetDate);
        }

        $this->goals->save($goal);
        $this->events->dispatch(...$goal->releaseEvents());
    }
}
