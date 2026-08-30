<?php

declare(strict_types=1);

namespace App\Application\SavingsGoal;

use App\Domain\SavingsGoal\SavingsGoalRepository;
use App\Domain\Shared\EventDispatcher;

/**
 * O CASO DE USO. Nao tem regra de negocio - so orquestra o passo-a-passo.
 * Depende das INTERFACES (portas), nunca de Eloquent. Por isso da pra
 * testar com um repositorio in-memory.
 */
final class AddContributionHandler
{
    public function __construct(
        private readonly SavingsGoalRepository $goals,
        private readonly EventDispatcher $events,
    ) {}

    public function handle(AddContributionCommand $command): void
    {
        // TODO (voce): 4 passos, nessa ordem
        $goal = $this->goals->get($command->savingsGoalId);

        $goal->addContribution(
            $command->contributionId,
            $command->amount,
            $command->date,
            $command->note,
        );

        $this->goals->save($goal);

        $this->events->dispatch(...$goal->releaseEvents());
    }
}
