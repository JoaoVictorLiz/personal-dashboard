<?php

declare(strict_types=1);

namespace App\Domain\SavingsGoal\Event;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * "A meta X foi concluida." Objeto imutavel, so carrega os dados
 * de quem quiser reagir a isso (mandar notificacao, atualizar um
 * dashboard, etc.). Nao tem comportamento.
 */
final class GoalCompleted implements DomainEvent
{
    public function __construct(
        public readonly string $savingsGoalId,
        public readonly DateTimeImmutable $occurredAt = new DateTimeImmutable,
    ) {}
}
