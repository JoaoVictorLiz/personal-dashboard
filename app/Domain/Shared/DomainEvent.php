<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Interface-marcador: nao tem metodos, so serve pra dizer
 * "esta classe e um evento de dominio". Permite tipar
 * releaseEvents(): array<DomainEvent> e restringir o que
 * o dispatcher aceita.
 */
interface DomainEvent {}
