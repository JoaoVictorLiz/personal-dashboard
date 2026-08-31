<?php

declare(strict_types=1);

namespace App\Domain\SavingsGoal;

use App\Domain\SavingsGoal\Event\GoalCompleted;
use App\Domain\SavingsGoal\Event\MilestoneReached;
use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\Money;
use DateTimeImmutable;
use InvalidArgumentException;

final class SavingsGoal
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    /** @var list<Contribution> */
    private array $contributions = [];

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
        if ($targetAmount->cents() <= 0) {
            throw new InvalidArgumentException('Target amount must be positive.');
        }

        $currentAmount = Money::fromCents(0, $targetAmount->currency());

        return new self($id, $title, $targetAmount, $currentAmount, SavingsGoalStatus::ACTIVE, $targetDate);

    }

    public static function reconstitute(
        string $id,
        string $title,
        Money $targetAmount,
        Money $currentAmount,
        SavingsGoalStatus $status,
        ?DateTimeImmutable $targetDate,
        Contribution ...$contributions,
    ): self {
        $goal = new self($id, $title, $targetAmount, $currentAmount, $status, $targetDate);
        $goal->contributions = $contributions;

        return $goal;
    }

    private function percentageOf(Money $amount): int
    {
        return intdiv($amount->cents() * 100, $this->targetAmount->cents());
    }

    public function addContribution(
        string $contributionId,
        Money $amount,
        DateTimeImmutable $date,
        ?string $note = null,
    ): void {
        $this->contributions[] = new Contribution($contributionId, $this->id, $amount, $date, $note);

        $before = $this->percentageOf($this->currentAmount);
        $this->currentAmount = $this->currentAmount->add($amount);
        $after = $this->percentageOf($this->currentAmount);

        foreach ([25, 50, 75] as $milestone) {
            if ($before < $milestone && $after >= $milestone) {
                $this->recordEvent(new MilestoneReached($this->id, $milestone));
            }
        }

        if ($this->status === SavingsGoalStatus::ACTIVE && $this->currentAmount->isGreaterThanOrEqualTo($this->targetAmount)) {
            $this->status = SavingsGoalStatus::COMPLETED;
            $this->recordEvent(new GoalCompleted($this->id));
        }

    }

    public function requiredDailyPace(DateTimeImmutable $today): ?Money
    {
        if ($this->targetDate === null || $this->status === SavingsGoalStatus::COMPLETED) {
            return null;
        }

        $remaining = $this->targetAmount->subtract($this->currentAmount);

        $daysLeft = max(1, (int) $today->diff($this->targetDate)->format('%r%a'));

        $paceCents = (int) ceil($remaining->cents() / $daysLeft);

        return Money::fromCents($paceCents, $this->targetAmount->currency());
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

    public function contributions(): array
    {
        return $this->contributions;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function targetAmount(): Money
    {
        return $this->targetAmount;
    }
}
