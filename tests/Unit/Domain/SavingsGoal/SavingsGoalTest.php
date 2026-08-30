<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\SavingsGoal;

use App\Domain\SavingsGoal\Event\GoalCompleted;
use App\Domain\SavingsGoal\SavingsGoal;
use App\Domain\SavingsGoal\SavingsGoalStatus;
use App\Domain\Shared\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SavingsGoalTest extends TestCase
{
    private function goal(int $targetCents = 1_000_000): SavingsGoal
    {
        return SavingsGoal::create(
            id: 'goal-1',
            title: 'Fundo de imigracao',
            targetAmount: Money::fromCents($targetCents, 'EUR'),
        );
    }

    public function test_a_new_goal_starts_with_nothing_saved(): void
    {
        self::assertTrue(
            $this->goal()->currentAmount()->equals(Money::fromCents(0, 'EUR'))
        );
    }

    public function test_contributions_accumulate_in_the_current_amount(): void
    {
        $goal = $this->goal();

        $goal->addContribution(Money::fromCents(30_000, 'EUR'));
        $goal->addContribution(Money::fromCents(20_000, 'EUR'));

        self::assertTrue(
            $goal->currentAmount()->equals(Money::fromCents(50_000, 'EUR'))
        );
    }

    public function test_target_date_is_optional_and_kept_when_given(): void
    {
        self::assertNull($this->goal()->targetDate());

        $withDate = SavingsGoal::create(
            id: 'goal-2',
            title: 'Reserva',
            targetAmount: Money::fromCents(500_000, 'EUR'),
            targetDate: new DateTimeImmutable('2027-01-01'),
        );
        self::assertEquals(new DateTimeImmutable('2027-01-01'), $withDate->targetDate());
    }

    public function test_a_new_goal_is_active(): void
    {
        self::assertSame(SavingsGoalStatus::ACTIVE, $this->goal()->status());
    }

    public function test_reaching_the_target_completes_the_goal_and_records_an_event(): void
    {
        $goal = $this->goal(targetCents: 100_000);

        $goal->addContribution(Money::fromCents(100_000, 'EUR')); // bate exatamente

        self::assertSame(SavingsGoalStatus::COMPLETED, $goal->status());

        $events = $goal->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(GoalCompleted::class, $events[0]);
        self::assertSame('goal-1', $events[0]->savingsGoalId);
    }

    public function test_a_goal_is_only_completed_once(): void
    {
        $goal = $this->goal(targetCents: 100_000);

        $goal->addContribution(Money::fromCents(150_000, 'EUR')); // passa da meta
        $goal->releaseEvents(); // limpa o GoalCompleted da primeira vez

        $goal->addContribution(Money::fromCents(10_000, 'EUR')); // contribui de novo

        self::assertSame(SavingsGoalStatus::COMPLETED, $goal->status());
        self::assertCount(0, $goal->releaseEvents(), 'nao dispara GoalCompleted de novo');
    }

    public function test_releasing_events_empties_the_list(): void
    {
        $goal = $this->goal(targetCents: 100_000);
        $goal->addContribution(Money::fromCents(100_000, 'EUR'));

        self::assertCount(1, $goal->releaseEvents());
        self::assertCount(0, $goal->releaseEvents(), 'segunda chamada volta vazia');
    }
}
