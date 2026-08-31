<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Domain\SavingsGoal\SavingsGoalRepository;
use App\Domain\Shared\EventDispatcher;
use App\Infrastructure\Events\LaravelEventDispatcher;
use App\Infrastructure\Persistence\Eloquent\EloquentSavingsGoalRepository;
use Tests\TestCase;

final class ContainerBindingsTest extends TestCase
{
    public function test_the_repository_port_resolves_to_the_eloquent_adapter(): void
    {
        self::assertInstanceOf(
            EloquentSavingsGoalRepository::class,
            $this->app->make(SavingsGoalRepository::class),
        );
    }

    public function test_the_dispatcher_port_resolves_to_the_laravel_adapter(): void
    {
        self::assertInstanceOf(
            LaravelEventDispatcher::class,
            $this->app->make(EventDispatcher::class),
        );
    }
}
