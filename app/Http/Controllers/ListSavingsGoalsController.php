<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\SavingsGoalQueries;
use Illuminate\Http\JsonResponse;

final class ListSavingsGoalsController extends Controller
{
    public function __construct(
        private readonly SavingsGoalQueries $queries,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json($this->queries->list());
    }
}
