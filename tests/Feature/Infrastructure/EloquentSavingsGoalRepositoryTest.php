<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Domain\SavingsGoal\SavingsGoal;
use App\Domain\SavingsGoal\SavingsGoalNotFound;
use App\Domain\SavingsGoal\SavingsGoalStatus;
use App\Domain\Shared\Money;
use App\Infrastructure\Persistence\Eloquent\EloquentSavingsGoalRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EloquentSavingsGoalRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentSavingsGoalRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new EloquentSavingsGoalRepository;
    }

    private function eur(int $cents): Money
    {
        return Money::fromCents($cents, 'EUR');
    }

    private function newGoal(): SavingsGoal
    {
        return SavingsGoal::create(
            id: '11111111-1111-1111-1111-111111111111',
            title: 'Fundo de imigracao',
            targetAmount: $this->eur(1_000_000),
            targetDate: new DateTimeImmutable('2027-01-01'),
        );
    }

    public function test_it_saves_a_new_goal_and_reads_it_back(): void
    {
        $this->repo->save($this->newGoal());

        $loaded = $this->repo->get('11111111-1111-1111-1111-111111111111');

        self::assertSame('Fundo de imigracao', $loaded->title());
        self::assertTrue($loaded->targetAmount()->equals($this->eur(1_000_000)));
        self::assertTrue($loaded->currentAmount()->equals($this->eur(0)));
        self::assertSame(SavingsGoalStatus::ACTIVE, $loaded->status());
        self::assertSame('2027-01-01', $loaded->targetDate()?->format('Y-m-d'));
    }

    public function test_it_round_trips_contributions(): void
    {
        $goal = $this->newGoal();
        $goal->addContribution('c-1', $this->eur(30_000), new DateTimeImmutable('2026-05-01'), 'bonus');
        $goal->addContribution('c-2', $this->eur(20_000), new DateTimeImmutable('2026-06-01'));
        $this->repo->save($goal);

        $loaded = $this->repo->get($goal->id());

        self::assertCount(2, $loaded->contributions());
        self::assertTrue($loaded->currentAmount()->equals($this->eur(50_000)));

        [$first, $second] = $loaded->contributions();
        self::assertTrue($first->amount()->equals($this->eur(30_000)));
        self::assertSame('bonus', $first->note());
        self::assertSame('2026-05-01', $first->date()->format('Y-m-d'));
        self::assertNull($second->note());
    }

    public function test_saving_again_updates_instead_of_duplicating(): void
    {
        $goal = $this->newGoal();
        $this->repo->save($goal);

        $goal->addContribution('c-1', $this->eur(40_000), new DateTimeImmutable('2026-05-01'));
        $this->repo->save($goal);

        $loaded = $this->repo->get($goal->id());
        self::assertCount(1, $loaded->contributions());
        self::assertTrue($loaded->currentAmount()->equals($this->eur(40_000)));
        self::assertDatabaseCount('savings_goals', 1);
    }

    public function test_it_persists_a_completed_status(): void
    {
        $goal = SavingsGoal::create('22222222-2222-2222-2222-222222222222', 'Reserva', $this->eur(100_000));
        $goal->addContribution('c-1', $this->eur(100_000), new DateTimeImmutable('2026-05-01'));
        $this->repo->save($goal);

        $loaded = $this->repo->get($goal->id());

        self::assertSame(SavingsGoalStatus::COMPLETED, $loaded->status());
    }

    public function test_get_throws_when_the_goal_is_missing(): void
    {
        $this->expectException(SavingsGoalNotFound::class);

        $this->repo->get('does-not-exist');
    }
}
