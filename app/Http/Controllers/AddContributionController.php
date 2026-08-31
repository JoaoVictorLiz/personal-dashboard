<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\SavingsGoal\AddContributionCommand;
use App\Application\SavingsGoal\AddContributionHandler;
use App\Domain\Shared\Money;
use App\Http\Requests\AddContributionRequest;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Controller FINO. Nao tem regra. So faz:
 *   request HTTP  ->  AddContributionCommand  ->  handler  ->  resposta HTTP
 */
final class AddContributionController extends Controller
{
    public function __construct(
        private readonly AddContributionHandler $handler,
    ) {}

    public function __invoke(AddContributionRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $contributionId = (string) Str::uuid();

        $this->handler->handle(new AddContributionCommand(
            savingsGoalId: $id,
            contributionId: $contributionId,
            amount: Money::fromCents((int) $data['amount'], 'EUR'),
            date: new DateTimeImmutable($data['date']),
            note: $data['note'] ?? null
        ));

        return response()->json(['contributionId' => $contributionId], 201);
    }
}
