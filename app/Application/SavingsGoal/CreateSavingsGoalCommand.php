<?php

declare(strict_types=1);

namespace App\Application\SavingsGoal;

use App\Domain\Shared\Money;
use DateTimeImmutable;

/**
 * Intencao "criar uma meta de poupanca". O id (UUID) e gerado no
 * controller e entra pronto - mesma decisao do contributionId.
 */
final class CreateSavingsGoalCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly Money $targetAmount,
        public readonly ?DateTimeImmutable $targetDate = null,
    ) {}
}
