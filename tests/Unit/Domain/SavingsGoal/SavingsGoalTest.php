<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\SavingsGoal;

use App\Domain\SavingsGoal\Contribution;
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
    private int $seq = 0;

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

    /** Helper: adiciona uma contribuicao gerando id e data automaticamente. */
    private function contribute(SavingsGoal $goal, int $cents, ?string $note = null): void
    {
        $goal->addContribution(
            contributionId: 'c-'.++$this->seq,
            amount: $this->eur($cents),
            date: new DateTimeImmutable('2026-01-01'),
            note: $note,
        );
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

        $this->contribute($goal, 30_000);
        $this->contribute($goal, 20_000);

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

    // --- contribuicoes registradas -------------------------------------

    public function test_a_contribution_is_recorded_with_its_details(): void
    {
        $goal = $this->goal();

        $goal->addContribution(
            contributionId: 'c-42',
            amount: $this->eur(30_000),
            date: new DateTimeImmutable('2026-03-10'),
            note: 'bonus anual',
        );

        $contributions = $goal->contributions();
        self::assertCount(1, $contributions);

        $c = $contributions[0];
        self::assertInstanceOf(Contribution::class, $c);
        self::assertSame('c-42', $c->id());
        self::assertSame('goal-1', $c->savingsGoalId());
        self::assertTrue($c->amount()->equals($this->eur(30_000)));
        self::assertEquals(new DateTimeImmutable('2026-03-10'), $c->date());
        self::assertSame('bonus anual', $c->note());
    }

    public function test_a_contribution_note_is_optional(): void
    {
        $goal = $this->goal();
        $this->contribute($goal, 10_000);

        self::assertNull($goal->contributions()[0]->note());
    }

    public function test_contributions_are_kept_in_the_order_they_were_added(): void
    {
        $goal = $this->goal();
        $this->contribute($goal, 10_000);
        $this->contribute($goal, 20_000);
        $this->contribute($goal, 30_000);

        $cents = array_map(static fn (Contribution $c) => $c->amount()->cents(), $goal->contributions());
        self::assertSame([10_000, 20_000, 30_000], $cents);
    }

    // --- conclusao ----------------------------------------------------

    public function test_reaching_the_target_completes_the_goal_and_records_an_event(): void
    {
        $goal = $this->goal(targetCents: 100_000);

        $this->contribute($goal, 100_000);

        self::assertSame(SavingsGoalStatus::COMPLETED, $goal->status());

        $completed = $this->only($goal->releaseEvents(), GoalCompleted::class);
        self::assertCount(1, $completed);
        self::assertSame('goal-1', $completed[0]->savingsGoalId);
    }

    public function test_a_goal_is_only_completed_once(): void
    {
        $goal = $this->goal(targetCents: 100_000);

        $this->contribute($goal, 150_000); // passa da meta
        $goal->releaseEvents();            // limpa eventos da 1a vez

        $this->contribute($goal, 10_000);  // contribui de novo

        self::assertSame(SavingsGoalStatus::COMPLETED, $goal->status());
        self::assertCount(0, $goal->releaseEvents(), 'nao dispara nada de novo');
    }

    // --- marcos (25 / 50 / 75) --------------------------------------

    public function test_crossing_a_milestone_records_a_milestone_reached_event(): void
    {
        $goal = $this->goal(targetCents: 1_000_000);

        $this->contribute($goal, 300_000); // 30% -> cruzou 25

        $milestones = $this->only($goal->releaseEvents(), MilestoneReached::class);
        self::assertCount(1, $milestones);
        self::assertSame(25, $milestones[0]->percentage);
        self::assertSame('goal-1', $milestones[0]->savingsGoalId);
    }

    public function test_a_milestone_is_only_reported_once(): void
    {
        $goal = $this->goal(targetCents: 1_000_000);

        $this->contribute($goal, 300_000); // 30% -> cruzou 25
        $goal->releaseEvents();

        $this->contribute($goal, 100_000); // 40% -> nao cruza nada

        self::assertCount(0, $this->only($goal->releaseEvents(), MilestoneReached::class));
    }

    public function test_one_contribution_reports_every_milestone_it_crosses(): void
    {
        $goal = $this->goal(targetCents: 1_000_000);

        $this->contribute($goal, 800_000); // 0% -> 80%

        $milestones = $this->only($goal->releaseEvents(), MilestoneReached::class);
        self::assertSame([25, 50, 75], array_map(static fn ($e) => $e->percentage, $milestones));
    }

    public function test_completing_the_goal_also_reports_the_milestones_crossed_on_the_way(): void
    {
        $goal = $this->goal(targetCents: 100_000);

        $this->contribute($goal, 100_000); // 0% -> 100%

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
        $this->contribute($goal, 300_000);

        self::assertCount(1, $goal->releaseEvents());
        self::assertCount(0, $goal->releaseEvents(), 'segunda chamada volta vazia');
    }
}
