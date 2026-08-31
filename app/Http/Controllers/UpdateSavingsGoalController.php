<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\SavingsGoal\UpdateSavingsGoalCommand;
use App\Application\SavingsGoal\UpdateSavingsGoalHandler;
use App\Domain\Shared\Money;
use App\Http\Requests\UpdateSavingsGoalRequest;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;

final class UpdateSavingsGoalController extends Controller
{
    public function __construct(
        private readonly UpdateSavingsGoalHandler $handler,
    ) {}

    public function __invoke(UpdateSavingsGoalRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $this->handler->handle(new UpdateSavingsGoalCommand(
            id: $id,
            title: $data['title'] ?? null,
            targetAmount: array_key_exists('targetAmount', $data)
                ? Money::fromCents((int) $data['targetAmount'], 'EUR')
                : null,
            changesTargetDate: $request->has('targetDate'),
            targetDate: isset($data['targetDate'])
                ? new DateTimeImmutable($data['targetDate'])
                : null,
        ));

        return response()->json(status: 204);
    }
}
