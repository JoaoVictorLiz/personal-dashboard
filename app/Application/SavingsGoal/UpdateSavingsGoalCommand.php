<?php

declare(strict_types=1);

namespace App\Application\SavingsGoal;

use App\Domain\Shared\Money;
use DateTimeImmutable;

/**
 * Edicao parcial de uma meta. Campos null = "nao mexer".
 *
 * targetDate tem o problema classico do PATCH: "nao enviado" e
 * "enviado como null" sao coisas diferentes. Por isso a flag
 * $changesTargetDate: so quando ela e true o targetDate (que pode
 * ser null = remover o prazo) e aplicado.
 */
final class UpdateSavingsGoalCommand
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $title = null,
        public readonly ?Money $targetAmount = null,
        public readonly bool $changesTargetDate = false,
        public readonly ?DateTimeImmutable $targetDate = null,
    ) {}
}
