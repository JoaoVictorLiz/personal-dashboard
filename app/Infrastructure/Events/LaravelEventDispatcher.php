<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\EventDispatcher;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Adaptador: implementa a porta EventDispatcher jogando cada evento
 * de dominio no event bus do Laravel. Listeners/subscribers do Laravel
 * reagem a partir dai (mandar notificacao, atualizar leitura, etc.).
 */
final class LaravelEventDispatcher implements EventDispatcher
{
    public function __construct(private readonly Dispatcher $bus) {}

    public function dispatch(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->bus->dispatch($event);
        }
    }
}
