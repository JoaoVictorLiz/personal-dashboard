<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Outra PORTA. O Domain junta eventos; alguem de fora precisa
 * entrega-los a quem reage (notificacao, dashboard, fila...).
 * O "como" (Laravel events, RabbitMQ) fica na Infrastructure.
 */
interface EventDispatcher
{
    public function dispatch(DomainEvent ...$events): void;
}
