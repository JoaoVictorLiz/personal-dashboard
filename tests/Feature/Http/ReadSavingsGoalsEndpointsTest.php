<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReadSavingsGoalsEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function createGoal(string $title, int $targetCents, ?string $targetDate = null): string
    {
        return $this->postJson('/api/savings-goals', array_filter([
            'title' => $title,
            'targetAmount' => $targetCents,
            'targetDate' => $targetDate,
        ]))->json('id');
    }

    private function contribute(string $goalId, int $cents, string $date): void
    {
        $this->postJson("/api/savings-goals/{$goalId}/contributions", [
            'amount' => $cents,
            'date' => $date,
        ])->assertCreated();
    }

    public function test_the_list_returns_every_goal_with_its_progress(): void
    {
        $a = $this->createGoal('Reserva', 100_000);
        $this->createGoal('Viagem', 200_000);
        $this->contribute($a, 25_000, '2026-05-01');

        $response = $this->getJson('/api/savings-goals');

        $response->assertOk()->assertJsonCount(2);
        $response->assertJsonFragment(['id' => $a, 'currentAmountCents' => 25_000, 'progressPercentage' => 25]);
    }

    public function test_the_detail_shows_pace_and_contributions(): void
    {
        $id = $this->createGoal('Fundo', 1_000_000, '2026-01-11');
        $this->contribute($id, 40_000, '2026-01-01');

        $response = $this->getJson("/api/savings-goals/{$id}");

        $response->assertOk()->assertJson([
            'id' => $id,
            'title' => 'Fundo',
            'currentAmountCents' => 40_000,
            'progressPercentage' => 4,
            'status' => 'active',
            'targetDate' => '2026-01-11',
        ]);
        $response->assertJsonCount(1, 'contributions');
        $response->assertJsonPath('contributions.0.amountCents', 40_000);

        // ritmo: falta 960_000, e ha dias ate 11-jan -> valor positivo
        self::assertIsInt($response->json('requiredDailyPaceCents'));
        self::assertGreaterThan(0, $response->json('requiredDailyPaceCents'));
    }

    public function test_a_completed_goal_has_null_pace(): void
    {
        $id = $this->createGoal('Reserva', 50_000, '2026-12-31');
        $this->contribute($id, 50_000, '2026-05-01');

        $this->getJson("/api/savings-goals/{$id}")
            ->assertOk()
            ->assertJson(['status' => 'completed', 'requiredDailyPaceCents' => null]);
    }

    public function test_detail_of_a_missing_goal_is_404(): void
    {
        $this->getJson('/api/savings-goals/ghost')->assertNotFound();
    }
}
