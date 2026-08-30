<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\SavingsGoal;

use App\Domain\SavingsGoal\Event\GoalCompleted;
use App\Domain\SavingsGoal\Event\MilestoneReached;
use App\Domain\SavingsGoal\SavingsGoal;
use App\Domain\SavingsGoal\SavingsGoalStatus;
use App\Domain\Shared\Money;
use DateTimeImmutable;
use InvalidArgumentException;
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

    private function eur(int $cents): Money
    {
        return Money::fromCents($cents, 'EUR');
    }

    /**
     * @param  list<object>  $events
     * @return list<object>
     */
    private function only(array $events, string $class): array
    {
        return array_values(array_filter($events, static fn ($e) => $e instanceof $class));
    }

    public function test_a_new_goal_starts_with_nothing_saved(): void
    {
        self::assertTrue($this->goal()->currentAmount()->equals($this->eur(0)));
    }

    public function test_a_goal_needs_a_positive_target(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SavingsGoal::create(id: 'x', title: 'x', targetAmount: $this->eur(0));
    }

    public function test_contributions_accumulate_in_the_current_amount(): void
    {
        $goal = $this->goal();

        $goal->addContribution($this->eur(30_000));
        $goal->addContribution($this->eur(20_000));

        self::assertTrue($goal->currentAmount()->equals($this->eur(50_000)));
    }

    public function test_target_date_is_optional_and_kept_when_given(): void
    {
        self::assertNull($this->goal()->targetDate());

        $withDate = SavingsGoal::create(
            id: 'goal-2',
            title: 'Reserva',
            targetAmount: $this->eur(500_000),
            targetDate: new DateTimeImmutable('2027-01-01'),
        );
        self::assertEquals(new DateTimeImmutable('2027-01-01'), $withDate->targetDate());
    }

    public function test_a_new_goal_is_active(): void
    {
        self::assertSame(SavingsGoalStatus::ACTIVE, $this->goal()->status());
    }

    // --- conclusao -------------------------------------------------------

    public function test_reaching_the_target_completes_the_goal_and_records_an_event(): void
    {
        $goal = $this->goal(targetCents: 100_000);

        $goal->addContribution($this->eur(100_000));

        self::assertSame(SavingsGoalStatus::COMPLETED, $goal->status());

        $completed = $this->only($goal->releaseEvents(), GoalCompleted::class);
        self::assertCount(1, $completed);
        self::assertSame('goal-1', $completed[0]->savingsGoalId);
    }

    public function test_a_goal_is_only_completed_once(): void
    {
        $goal = $this->goal(targetCents: 100_000);

        $goal->addContribution($this->eur(150_000)); // passa da meta
        $goal->releaseEvents();                      // limpa eventos da 1a vez

        $goal->addContribution($this->eur(10_000));  // contribui de novo

        self::assertSame(SavingsGoalStatus::COMPLETED, $goal->status());
        self::assertCount(0, $goal->releaseEvents(), 'nao dispara nada de novo');
    }

    // --- marcos (25 / 50 / 75) ----------------------------------------

    public function test_crossing_a_milestone_records_a_milestone_reached_event(): void
    {
        $goal = $this->goal(targetCents: 1_000_000);

        $goal->addContribution($this->eur(300_000)); // 30% -> cruzou 25

        $milestones = $this->only($goal->releaseEvents(), MilestoneReached::class);
        self::assertCount(1, $milestones);
        self::assertSame(25, $milestones[0]->percentage);
        self::assertSame('goal-1', $milestones[0]->savingsGoalId);
    }

    public function test_a_milestone_is_only_reported_once(): void
    {
        $goal = $this->goal(targetCents: 1_000_000);

        $goal->addContribution($this->eur(300_000)); // 30% -> cruzou 25
        $goal->releaseEvents();

        $goal->addContribution($this->eur(100_000)); // 40% -> nao cruza nada

        self::assertCount(0, $this->only($goal->releaseEvents(), MilestoneReached::class));
    }

    public function test_one_contribution_reports_every_milestone_it_crosses(): void
    {
        $goal = $this->goal(targetCents: 1_000_000);

        $goal->addContribution($this->eur(800_000)); // 0% -> 80%

        $milestones = $this->only($goal->releaseEvents(), MilestoneReached::class);
        self::assertSame([25, 50, 75], array_map(static fn ($e) => $e->percentage, $milestones));
    }

    public function test_completing_the_goal_also_reports_the_milestones_crossed_on_the_way(): void
    {
        $goal = $this->goal(targetCents: 100_000);

        $goal->addContribution($this->eur(100_000)); // 0% -> 100%

        $events = $goal->releaseEvents();
        self::assertSame(
            [25, 50, 75],
            array_map(static fn ($e) => $e->percentage, $this->only($events, MilestoneReached::class)),
        );
        self::assertCount(1, $this->only($events, GoalCompleted::class));
    }

    public function test_releasing_events_empties_the_list(): void
    {
        $goal = $this->goal(targetCents: 1_000_000);
        $goal->addContribution($this->eur(300_000));

        self::assertCount(1, $goal->releaseEvents());
        self::assertCount(0, $goal->releaseEvents(), 'segunda chamada volta vazia');
    }
}
