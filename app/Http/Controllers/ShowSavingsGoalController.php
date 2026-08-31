<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\SavingsGoal\Contribution;
use App\Domain\SavingsGoal\SavingsGoalRepository;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;

final class ShowSavingsGoalController extends Controller
{
    public function __construct(
        private readonly SavingsGoalRepository $goals,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $goal = $this->goals->get($id); // SavingsGoalNotFound -> 404 (mapeado no bootstrap/app.php)

        $payload = [
            'id' => $goal->id(),
            'title' => $goal->title(),
            'targetAmountCents' => $goal->targetAmount()->cents(),
            'currentAmountCents' => $goal->currentAmount()->cents(),
            'currency' => $goal->targetAmount()->currency(),
            'progressPercentage' => $goal->progressPercentage(),
            'status' => $goal->status()->value,
            'targetDate' => $goal->targetDate()?->format('Y-m-d'),
            'requiredDailyPaceCents' => $goal->requiredDailyPace(new DateTimeImmutable('today'))?->cents(),
            'contributions' => array_map(fn (Contribution $c) => [
                'id' => $c->id(),
                'amountCents' => $c->amount()->cents(),
                'date' => $c->date()->format('Y-m-d'),
                'note' => $c->note(),
            ], $goal->contributions()),
        ];

        return response()->json($payload);
    }
}
