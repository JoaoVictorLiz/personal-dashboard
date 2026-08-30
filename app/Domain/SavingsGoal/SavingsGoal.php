<?php

declare(strict_types=1);

namespace App\Domain\SavingsGoal;

use App\Domain\Shared\Money;
use DateTimeImmutable;

final class SavingsGoal
{
    private function __construct(
        private readonly string $id,
        private readonly string $title,
        private readonly Money $targetAmount,
        private Money $currentAmount,
        private readonly ?DateTimeImmutable $targetDate
    ) {}

    public static function create(
        string $id,
        string $title,
        Money $targetAmount,
        ?DateTimeImmutable $targetDate = null,
    ): self {
        // currentAmount nasce zerado, na moeda da meta:
        $currentAmount = Money::fromCents(0, $targetAmount->currency());

        return new self($id, $title, $targetAmount, $currentAmount, $targetDate);

    }

    public function addContribution(Money $amount): void
    {
        $this->currentAmount = $this->currentAmount->add($amount);
    }

    public function currentAmount(): Money
    {
        return $this->currentAmount;
    }

    public function targetDate(): ?DateTimeImmutable
    {
        return $this->targetDate;
    }
}
