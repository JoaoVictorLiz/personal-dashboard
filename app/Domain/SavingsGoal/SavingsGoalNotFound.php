<?php

declare(strict_types=1);

namespace App\Domain\SavingsGoal;

use RuntimeException;

final class SavingsGoalNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self("No savings goal with id {$id}.");
    }
}
