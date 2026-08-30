<?php

declare(strict_types=1);

namespace Tests\Unit\Application\SavingsGoal;

use App\Application\SavingsGoal\AddContributionCommand;
use App\Application\SavingsGoal\AddContributionHandler;
use App\Domain\SavingsGoal\Event\GoalCompleted;
use App\Domain\SavingsGoal\SavingsGoal;
use App\Domain\SavingsGoal\SavingsGoalNotFound;
use App\Domain\Shared\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemorySavingsGoalRepository;
use Tests\Fakes\RecordingEventDispatcher;

final class AddContributionHandlerTest extends TestCase
{
    private InMemorySavingsGoalRepository $goals;

    private RecordingEventDispatcher $events;

    private AddContributionHandler $handler;

    protected function setUp(): void
    {
        $this->goals = new InMemorySavingsGoalRepository;
        $this->events = new RecordingEventDispatcher;
        $this->handler = new AddContributionHandler($this->goals, $this->events);
    }

    private function eur(int $cents): Money
    {
        return Money::fromCents($cents, 'EUR');
    }

    private function command(string $goalId, int $cents): AddContributionCommand
    {
        return new AddContributionCommand(
            savingsGoalId: $goalId,
            contributionId: 'c-1',
            amount: $this->eur($cents),
            date: new DateTimeImmutable('2026-05-01'),
            note: null,
        );
    }

    public function test_it_applies_the_contribution_to_the_goal_and_saves_it(): void
    {
        $this->goals->save(SavingsGoal::create('goal-1', 'Reserva', $this->eur(100_000)));

        $this->handler->handle($this->command('goal-1', 30_000));

        $saved = $this->goals->get('goal-1');
        self::assertTrue($saved->currentAmount()->equals($this->eur(30_000)));
        self::assertCount(1, $saved->contributions());
    }

    public function test_it_dispatches_the_events_the_goal_collected(): void
    {
        $this->goals->save(SavingsGoal::create('goal-1', 'Reserva', $this->eur(100_000)));

        $this->handler->handle($this->command('goal-1', 100_000)); // conclui a meta

        self::assertNotEmpty($this->events->dispatched);
        $classes = array_map('get_class', $this->events->dispatched);
        self::assertContains(GoalCompleted::class, $classes);
    }

    public function test_the_goal_no_longer_holds_the_events_after_handling(): void
    {
        $this->goals->save(SavingsGoal::create('goal-1', 'Reserva', $this->eur(100_000)));

        $this->handler->handle($this->command('goal-1', 100_000));

        // handler ja chamou releaseEvents() -> a meta salva nao guarda mais nada
        self::assertCount(0, $this->goals->get('goal-1')->releaseEvents());
    }

    public function test_it_fails_when_the_goal_does_not_exist(): void
    {
        $this->expectException(SavingsGoalNotFound::class);

        $this->handler->handle($this->command('ghost', 10_000));
    }
}
