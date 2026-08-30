<?php

declare(strict_types=1);

namespace App\Domain\SavingsGoal\Event;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * "A meta X cruzou o marco de N%." N e sempre 25, 50 ou 75
 * (100% e concluir a meta -> GoalCompleted, evento separado).
 */
final class MilestoneReached implements DomainEvent
{
    public function __construct(
        public readonly string $savingsGoalId,
        public readonly int $percentage,
        public readonly DateTimeImmutable $occurredAt = new DateTimeImmutable,
    ) {}
}
