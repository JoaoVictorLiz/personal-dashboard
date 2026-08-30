<?php

declare(strict_types=1);

namespace App\Domain\SavingsGoal;

/**
 * A PORTA. Vive no Domain porque e o Domain/Application que PRECISA
 * disto. Quem IMPLEMENTA (Eloquent) vive na Infrastructure e depende
 * desta interface - nao o contrario. Essa e a "inversao de dependencia".
 *
 * Parece uma colecao de SavingsGoal; o banco fica escondido atras dela.
 */
interface SavingsGoalRepository
{
    /**
     * @throws SavingsGoalNotFound se nao existir meta com esse id
     */
    public function get(string $id): SavingsGoal;

    public function save(SavingsGoal $goal): void;
}
