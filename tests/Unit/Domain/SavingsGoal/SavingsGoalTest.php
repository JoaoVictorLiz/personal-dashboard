<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\SavingsGoal;

use App\Domain\SavingsGoal\SavingsGoal;
use App\Domain\Shared\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SavingsGoalTest extends TestCase
{
    public function test_a_new_goal_starts_with_nothing_saved(): void
    {
        $goal = SavingsGoal::create(
            id: 'goal-1',
            title: 'Fundo de imigracao',
            targetAmount: Money::fromCents(1_000_000, 'EUR'), // 10.000,00 EUR
        );

        // currentAmount comeca em zero, na MESMA moeda da meta.
        self::assertTrue(
            $goal->currentAmount()->equals(Money::fromCents(0, 'EUR'))
        );
    }

    public function test_contributions_accumulate_in_the_current_amount(): void
    {
        $goal = SavingsGoal::create(
            id: 'goal-1',
            title: 'Fundo de imigracao',
            targetAmount: Money::fromCents(1_000_000, 'EUR'),
        );

        $goal->addContribution(Money::fromCents(30_000, 'EUR')); // +300,00
        $goal->addContribution(Money::fromCents(20_000, 'EUR')); // +200,00

        self::assertTrue(
            $goal->currentAmount()->equals(Money::fromCents(50_000, 'EUR'))
        );
    }

    public function test_target_date_is_optional_and_kept_when_given(): void
    {
        $withoutDate = SavingsGoal::create(
            id: 'goal-1',
            title: 'Reserva',
            targetAmount: Money::fromCents(500_000, 'EUR'),
        );
        self::assertNull($withoutDate->targetDate());

        $withDate = SavingsGoal::create(
            id: 'goal-2',
            title: 'Reserva',
            targetAmount: Money::fromCents(500_000, 'EUR'),
            targetDate: new DateTimeImmutable('2027-01-01'),
        );
        self::assertEquals(new DateTimeImmutable('2027-01-01'), $withDate->targetDate());
    }
}
