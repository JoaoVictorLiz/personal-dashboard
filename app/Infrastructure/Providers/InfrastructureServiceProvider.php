<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Domain\SavingsGoal\SavingsGoalRepository;
use App\Domain\Shared\EventDispatcher;
use App\Infrastructure\Events\LaravelEventDispatcher;
use App\Infrastructure\Persistence\Eloquent\EloquentSavingsGoalRepository;
use Illuminate\Support\ServiceProvider;

/**
 * O UNICO lugar onde porta (interface, no Domain) encontra
 * adaptador (implementacao, na Infrastructure).
 *
 * Handlers pedem a interface no construtor; o container injeta
 * a implementacao registrada aqui. Trocar Eloquent por outra
 * coisa = mudar so estas linhas.
 */
final class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SavingsGoalRepository::class, EloquentSavingsGoalRepository::class);
        $this->app->bind(EventDispatcher::class, LaravelEventDispatcher::class);
    }
}
