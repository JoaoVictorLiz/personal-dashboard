<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use InvalidArgumentException;

final class Money
{
    private function __construct(
        private readonly int $cents,
        private readonly string $currency,
    ) {}

    public static function fromCents(int $cents, string $currency): self
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }

        return new self($cents, $currency);
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot operate on different currencies.');
        }
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents && $this->currency === $other->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return self::fromCents($this->cents + $other->cents, $this->currency);
    }

    public function isGreaterThanOrEqualTo(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->cents >= $other->cents;
    }
}
