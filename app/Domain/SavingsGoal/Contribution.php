<?php

declare(strict_types=1);

namespace App\Domain\SavingsGoal;

use App\Domain\Shared\Money;
use DateTimeImmutable;

/**
 * Entidade FILHA do agregado SavingsGoal: tem id (logo, identidade
 * propria), mas so existe dentro de uma meta e so o SavingsGoal a cria.
 *
 * E imutavel (tudo readonly) - uma contribuicao registrada nao muda.
 * "Entidade" nao quer dizer "mutavel", quer dizer "tem identidade":
 * duas contribuicoes de 100 EUR na mesma data sao contribuicoes
 * DIFERENTES se tem id diferente.
 */
final class Contribution
{
    public function __construct(
        private readonly string $id,
        private readonly string $savingsGoalId,
        private readonly Money $amount,
        private readonly DateTimeImmutable $date,
        private readonly ?string $note = null,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function savingsGoalId(): string
    {
        return $this->savingsGoalId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function note(): ?string
    {
        return $this->note;
    }
}
