<?php

declare(strict_types=1);

namespace App\Domain\SavingsGoal;

/**
 * Enum nativo do PHP (8.1+). "backed" por string: cada caso
 * tem um valor ('active' / 'completed') que e o que vai pro banco
 * mais tarde. No codigo voce usa SavingsGoalStatus::ACTIVE, nao a string.
 */
enum SavingsGoalStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
}
