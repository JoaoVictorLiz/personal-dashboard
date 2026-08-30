<?php

declare(strict_types=1);

namespace App\Application\SavingsGoal;

use App\Domain\Shared\Money;
use DateTimeImmutable;

/**
 * Um COMANDO: objeto burro, so carrega a intencao "adicionar contribuicao X
 * a meta Y". Sem comportamento, sem regra. O controller monta isto a partir
 * do request HTTP (inclusive ja gerando o contributionId / UUID) e entrega
 * ao handler.
 */
final class AddContributionCommand
{
    public function __construct(
        public readonly string $savingsGoalId,
        public readonly string $contributionId,
        public readonly Money $amount,
        public readonly DateTimeImmutable $date,
        public readonly ?string $note = null,
    ) {}
}
