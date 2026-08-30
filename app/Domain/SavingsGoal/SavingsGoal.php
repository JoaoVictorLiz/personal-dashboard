<?php

declare(strict_types=1);

namespace App\Domain\SavingsGoal;

use App\Domain\SavingsGoal\Event\GoalCompleted;
use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\Money;
use DateTimeImmutable;

final class SavingsGoal
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly Money $targetAmount,
        private Money $currentAmount,
        private SavingsGoalStatus $status,
        private readonly ?DateTimeImmutable $targetDate
    ) {}

    public static function create(
        string $id,
        string $title,
        Money $targetAmount,
        ?DateTimeImmutable $targetDate = null,
    ): self {
        $currentAmount = Money::fromCents(0, $targetAmount->currency());

        return new self($id, $title, $targetAmount, $currentAmount, SavingsGoalStatus::ACTIVE, $targetDate);

    }

    public function addContribution(Money $amount): void
    {
        $this->currentAmount = $this->currentAmount->add($amount);

        if ($this->status === SavingsGoalStatus::ACTIVE && $this->currentAmount->isGreaterThanOrEqualTo($this->targetAmount)) {
            $this->status = SavingsGoalStatus::COMPLETED;
            $this->recordEvent(new GoalCompleted($this->id));
        }

    }

    public function currentAmount(): Money
    {
        return $this->currentAmount;
    }

    public function targetDate(): ?DateTimeImmutable
    {
        return $this->targetDate;
    }

    public function status(): SavingsGoalStatus
    {
        return $this->status;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
