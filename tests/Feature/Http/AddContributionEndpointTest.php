<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Domain\SavingsGoal\SavingsGoal;
use App\Domain\SavingsGoal\SavingsGoalRepository;
use App\Domain\SavingsGoal\SavingsGoalStatus;
use App\Domain\Shared\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AddContributionEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const GOAL_ID = '11111111-1111-1111-1111-111111111111';

    private function seedGoal(int $targetCents = 1_000_000): void
    {
        $repo = $this->app->make(SavingsGoalRepository::class);
        $repo->save(SavingsGoal::create(self::GOAL_ID, 'Fundo', Money::fromCents($targetCents, 'EUR')));
    }

    public function test_it_adds_a_contribution_and_returns_201(): void
    {
        $this->seedGoal();

        $response = $this->postJson('/api/savings-goals/'.self::GOAL_ID.'/contributions', [
            'amount' => 30_000,
            'date' => '2026-05-01',
            'note' => 'bonus',
        ]);

        $response->assertCreated()->assertJsonStructure(['contributionId']);

        $this->assertDatabaseHas('contributions', [
            'savings_goal_id' => self::GOAL_ID,
            'amount_cents' => 30_000,
            'note' => 'bonus',
        ]);
        $this->assertDatabaseHas('savings_goals', [
            'id' => self::GOAL_ID,
            'current_amount_cents' => 30_000,
        ]);
    }

    public function test_completing_the_goal_persists_the_completed_status(): void
    {
        $this->seedGoal(targetCents: 50_000);

        $this->postJson('/api/savings-goals/'.self::GOAL_ID.'/contributions', [
            'amount' => 50_000,
            'date' => '2026-05-01',
        ])->assertCreated();

        $this->assertDatabaseHas('savings_goals', [
            'id' => self::GOAL_ID,
            'status' => SavingsGoalStatus::COMPLETED->value,
        ]);
    }

    public function test_it_returns_404_when_the_goal_does_not_exist(): void
    {
        $this->postJson('/api/savings-goals/ghost/contributions', [
            'amount' => 10_000,
            'date' => '2026-05-01',
        ])->assertNotFound();
    }

    public function test_it_returns_422_when_the_payload_is_invalid(): void
    {
        $this->seedGoal();

        $this->postJson('/api/savings-goals/'.self::GOAL_ID.'/contributions', [
            'amount' => 0,          // min:1
            'date' => 'not-a-date',
        ])->assertUnprocessable()->assertJsonValidationErrors(['amount', 'date']);
    }
}
