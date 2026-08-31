<?php

declare(strict_types=1);

namespace Tests\Unit\Application\SavingsGoal;

use App\Application\SavingsGoal\CreateSavingsGoalCommand;
use App\Application\SavingsGoal\CreateSavingsGoalHandler;
use App\Domain\SavingsGoal\SavingsGoalStatus;
use App\Domain\Shared\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemorySavingsGoalRepository;

final class CreateSavingsGoalHandlerTest extends TestCase
{
    public function test_it_creates_and_stores_an_active_zeroed_goal(): void
    {
        $goals = new InMemorySavingsGoalRepository;
        $handler = new CreateSavingsGoalHandler($goals);

        $handler->handle(new CreateSavingsGoalCommand(
            id: 'goal-1',
            title: 'Fundo de imigracao',
            targetAmount: Money::fromCents(1_000_000, 'EUR'),
            targetDate: new DateTimeImmutable('2027-01-01'),
        ));

        $goal = $goals->get('goal-1');
        self::assertSame('Fundo de imigracao', $goal->title());
        self::assertTrue($goal->currentAmount()->equals(Money::fromCents(0, 'EUR')));
        self::assertSame(SavingsGoalStatus::ACTIVE, $goal->status());
        self::assertSame('2027-01-01', $goal->targetDate()?->format('Y-m-d'));
    }
}
