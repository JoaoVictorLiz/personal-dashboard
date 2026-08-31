<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateSavingsGoalEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_goal_and_returns_201_with_its_id(): void
    {
        $response = $this->postJson('/api/savings-goals', [
            'title' => 'Fundo de imigracao',
            'targetAmount' => 1_000_000,
            'targetDate' => '2027-01-01',
        ]);

        $response->assertCreated()->assertJsonStructure(['id']);

        $this->assertDatabaseHas('savings_goals', [
            'id' => $response->json('id'),
            'title' => 'Fundo de imigracao',
            'target_amount_cents' => 1_000_000,
            'current_amount_cents' => 0,
            'status' => 'active',
        ]);
    }

    public function test_target_date_is_optional(): void
    {
        $this->postJson('/api/savings-goals', [
            'title' => 'Reserva',
            'targetAmount' => 500_000,
        ])->assertCreated();
    }

    public function test_it_returns_422_on_invalid_payload(): void
    {
        $this->postJson('/api/savings-goals', [
            'title' => '',
            'targetAmount' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors(['title', 'targetAmount']);
    }

    public function test_a_created_goal_can_immediately_receive_contributions(): void
    {
        $id = $this->postJson('/api/savings-goals', [
            'title' => 'Reserva',
            'targetAmount' => 100_000,
        ])->json('id');

        $this->postJson("/api/savings-goals/{$id}/contributions", [
            'amount' => 40_000,
            'date' => '2026-05-01',
        ])->assertCreated();

        $this->assertDatabaseHas('savings_goals', [
            'id' => $id,
            'current_amount_cents' => 40_000,
        ]);
    }
}
