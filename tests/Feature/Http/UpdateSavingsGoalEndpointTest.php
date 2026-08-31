<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdateSavingsGoalEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function createGoal(int $targetCents = 1_000_000, ?string $targetDate = '2027-01-01'): string
    {
        return $this->postJson('/api/savings-goals', array_filter([
            'title' => 'Original',
            'targetAmount' => $targetCents,
            'targetDate' => $targetDate,
        ]))->json('id');
    }

    public function test_it_updates_the_title(): void
    {
        $id = $this->createGoal();

        $this->patchJson("/api/savings-goals/{$id}", ['title' => 'Corrigido'])
            ->assertNoContent();

        $this->assertDatabaseHas('savings_goals', ['id' => $id, 'title' => 'Corrigido']);
    }

    public function test_lowering_the_target_below_saved_completes_the_goal(): void
    {
        $id = $this->createGoal(targetCents: 1_000_000);
        $this->postJson("/api/savings-goals/{$id}/contributions", ['amount' => 400_000, 'date' => '2026-05-01']);

        $this->patchJson("/api/savings-goals/{$id}", ['targetAmount' => 300_000])->assertNoContent();

        $this->assertDatabaseHas('savings_goals', [
            'id' => $id,
            'target_amount_cents' => 300_000,
            'status' => 'completed',
        ]);
    }

    public function test_it_can_clear_the_target_date(): void
    {
        $id = $this->createGoal(targetDate: '2027-01-01');

        $this->patchJson("/api/savings-goals/{$id}", ['targetDate' => null])->assertNoContent();

        $this->assertDatabaseHas('savings_goals', ['id' => $id, 'target_date' => null]);
    }

    public function test_it_returns_422_on_invalid_data(): void
    {
        $id = $this->createGoal();

        $this->patchJson("/api/savings-goals/{$id}", ['title' => '', 'targetAmount' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'targetAmount']);
    }

    public function test_it_returns_404_for_a_missing_goal(): void
    {
        $this->patchJson('/api/savings-goals/ghost', ['title' => 'x'])->assertNotFound();
    }
}
