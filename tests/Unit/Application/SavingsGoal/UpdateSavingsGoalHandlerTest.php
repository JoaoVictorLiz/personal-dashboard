<?php

declare(strict_types=1);

namespace Tests\Unit\Application\SavingsGoal;

use App\Application\SavingsGoal\UpdateSavingsGoalCommand;
use App\Application\SavingsGoal\UpdateSavingsGoalHandler;
use App\Domain\SavingsGoal\Event\GoalCompleted;
use App\Domain\SavingsGoal\SavingsGoal;
use App\Domain\SavingsGoal\SavingsGoalNotFound;
use App\Domain\SavingsGoal\SavingsGoalStatus;
use App\Domain\Shared\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemorySavingsGoalRepository;
use Tests\Fakes\RecordingEventDispatcher;

final class UpdateSavingsGoalHandlerTest extends TestCase
{
    private InMemorySavingsGoalRepository $goals;

    private RecordingEventDispatcher $events;

    private UpdateSavingsGoalHandler $handler;

    protected function setUp(): void
    {
        $this->goals = new InMemorySavingsGoalRepository;
        $this->events = new RecordingEventDispatcher;
        $this->handler = new UpdateSavingsGoalHandler($this->goals, $this->events);

        $this->goals->save(SavingsGoal::create('goal-1', 'Antigo', Money::fromCents(1_000_000, 'EUR')));
    }

    private function eur(int $cents): Money
    {
        return Money::fromCents($cents, 'EUR');
    }

    public function test_it_updates_only_the_fields_that_were_sent(): void
    {
        $this->handler->handle(new UpdateSavingsGoalCommand(id: 'goal-1', title: 'Novo titulo'));

        $goal = $this->goals->get('goal-1');
        self::assertSame('Novo titulo', $goal->title());
        self::assertTrue($goal->targetAmount()->equals($this->eur(1_000_000)), 'target intacto');
    }

    public function test_it_can_set_the_target_date(): void
    {
        $this->handler->handle(new UpdateSavingsGoalCommand(
            id: 'goal-1',
            changesTargetDate: true,
            targetDate: new DateTimeImmutable('2028-01-01'),
        ));

        self::assertSame('2028-01-01', $this->goals->get('goal-1')->targetDate()?->format('Y-m-d'));
    }

    public function test_lowering_the_target_completes_the_goal_and_dispatches_the_event(): void
    {
        $goal = $this->goals->get('goal-1');
        $goal->addContribution('c-1', $this->eur(300_000), new DateTimeImmutable('2026-05-01'));
        $this->goals->save($goal);
        $this->events->dispatched = [];

        $this->handler->handle(new UpdateSavingsGoalCommand(id: 'goal-1', targetAmount: $this->eur(200_000)));

        self::assertSame(SavingsGoalStatus::COMPLETED, $this->goals->get('goal-1')->status());
        self::assertContains(GoalCompleted::class, array_map('get_class', $this->events->dispatched));
    }

    public function test_it_fails_when_the_goal_does_not_exist(): void
    {
        $this->expectException(SavingsGoalNotFound::class);

        $this->handler->handle(new UpdateSavingsGoalCommand(id: 'ghost', title: 'x'));
    }
}
