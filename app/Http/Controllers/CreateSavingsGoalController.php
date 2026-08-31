<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\SavingsGoal\CreateSavingsGoalCommand;
use App\Application\SavingsGoal\CreateSavingsGoalHandler;
use App\Domain\Shared\Money;
use App\Http\Requests\CreateSavingsGoalRequest;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class CreateSavingsGoalController extends Controller
{
    public function __construct(
        private readonly CreateSavingsGoalHandler $handler,
    ) {}

    public function __invoke(CreateSavingsGoalRequest $request): JsonResponse
    {
        $data = $request->validated();

        $id = (string) Str::uuid();

        $this->handler->handle(new CreateSavingsGoalCommand(
            id: $id,
            title: $data['title'],
            targetAmount: Money::fromCents((int) $data['targetAmount'], 'EUR'),
            targetDate: isset($data['targetDate']) ? new DateTimeImmutable($data['targetDate']) : null
        ));

        return response()->json(['id' => $id], 201);
    }
}
