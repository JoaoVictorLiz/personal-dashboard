<?php

use App\Http\Controllers\AddContributionController;
use Illuminate\Support\Facades\Route;

Route::post('/savings-goals/{id}/contributions', AddContributionController::class);
