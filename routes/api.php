<?php

use App\Http\Controllers\AddContributionController;
use App\Http\Controllers\CreateSavingsGoalController;
use Illuminate\Support\Facades\Route;

Route::post('/savings-goals', CreateSavingsGoalController::class);
Route::post('/savings-goals/{id}/contributions', AddContributionController::class);
