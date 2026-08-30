<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\EventDispatcher;

/**
 * Dispatcher "de mentira": nao entrega nada a lugar nenhum, so guarda
 * o que recebeu pra o teste inspecionar.
 */
final class RecordingEventDispatcher implements EventDispatcher
{
    /** @var list<DomainEvent> */
    public array $dispatched = [];

    public function dispatch(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->dispatched[] = $event;
        }
    }
}
