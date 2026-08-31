<?php

use App\Http\Controllers\AddContributionController;
use App\Http\Controllers\CreateSavingsGoalController;
use App\Http\Controllers\ListSavingsGoalsController;
use App\Http\Controllers\ShowSavingsGoalController;
use Illuminate\Support\Facades\Route;

Route::get('/savings-goals', ListSavingsGoalsController::class);
Route::get('/savings-goals/{id}', ShowSavingsGoalController::class);
Route::post('/savings-goals', CreateSavingsGoalController::class);
Route::post('/savings-goals/{id}/contributions', AddContributionController::class);
