<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Repare: estende PHPUnit\Framework\TestCase direto, NAO Tests\TestCase.
 * Tests\TestCase sobe o Laravel inteiro. Se um teste de dominio precisar
 * do Laravel pra rodar, o dominio nao esta puro. Essa escolha de classe
 * base e o que forca a regra de arquitetura de forma mecanica.
 */
final class MoneyTest extends TestCase
{
    public function test_it_exposes_the_cents_and_currency_it_was_created_with(): void
    {
        // Guardamos centavos como int: aritmetica exata, sem arredondamento de float.
        // R$ 150,00 => 15000 centavos.
        $money = Money::fromCents(15000, 'EUR');

        self::assertSame(15000, $money->cents());
        self::assertSame('EUR', $money->currency());
    }

    public function test_two_amounts_with_the_same_cents_and_currency_are_equal(): void
    {
        // Value Object: identidade e o VALOR, nao a instancia.
        // Dois Money com mesmos centavos + moeda sao "o mesmo dinheiro".
        self::assertTrue(
            Money::fromCents(15000, 'EUR')->equals(Money::fromCents(15000, 'EUR'))
        );

        self::assertFalse(
            Money::fromCents(15000, 'EUR')->equals(Money::fromCents(15001, 'EUR'))
        );

        // Moeda faz parte da identidade: 150 EUR != 150 CAD.
        self::assertFalse(
            Money::fromCents(15000, 'EUR')->equals(Money::fromCents(15000, 'CAD'))
        );
    }

    public function test_adding_returns_a_new_amount_and_leaves_the_originals_untouched(): void
    {
        // Imutabilidade: operacoes retornam um Money NOVO, nunca mudam os operandos.
        $a = Money::fromCents(10000, 'EUR');
        $b = Money::fromCents(2550, 'EUR');

        $sum = $a->add($b);

        self::assertSame(12550, $sum->cents());
        self::assertSame(10000, $a->cents(), 'add() nao pode mutar o operando da esquerda');
        self::assertSame(2550, $b->cents(), 'add() nao pode mutar o operando da direita');
    }

    public function test_it_refuses_to_add_amounts_in_different_currencies(): void
    {
        // Sem cambio na v1. Somar moedas diferentes nao tem resposta correta,
        // entao o tipo se recusa a fazer em vez de devolver lixo.
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(10000, 'EUR')->add(Money::fromCents(10000, 'CAD'));
    }

    public function test_subtracting_returns_a_new_amount(): void
    {
        $a = Money::fromCents(10000, 'EUR');
        $b = Money::fromCents(2550, 'EUR');

        $rest = $a->subtract($b);

        self::assertSame(7450, $rest->cents());
        self::assertSame(10000, $a->cents(), 'subtract() nao pode mutar os operandos');
    }

    public function test_it_refuses_to_subtract_amounts_in_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(10000, 'EUR')->subtract(Money::fromCents(1, 'CAD'));
    }

    public function test_subtracting_more_than_you_have_is_not_allowed(): void
    {
        // Nao existe dinheiro negativo: o proprio fromCents() barra.
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(100, 'EUR')->subtract(Money::fromCents(101, 'EUR'));
    }

    public function test_it_compares_amounts_of_the_same_currency(): void
    {
        // Vai ser usado pra "currentAmount >= targetAmount" => meta batida.
        $target = Money::fromCents(100000, 'EUR');

        self::assertTrue(Money::fromCents(100000, 'EUR')->isGreaterThanOrEqualTo($target));
        self::assertTrue(Money::fromCents(100001, 'EUR')->isGreaterThanOrEqualTo($target));
        self::assertFalse(Money::fromCents(99999, 'EUR')->isGreaterThanOrEqualTo($target));
    }

    public function test_there_is_no_negative_money_in_this_system(): void
    {
        // Decisao de dominio: v1 nao tem saque nem transacao negativa (ver CLAUDE.md).
        // Colocamos essa invariante no tipo: um Money negativo nao pode nem existir.
        // Zero E permitido (currentAmount comeca em 0).
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(-1, 'EUR');
    }
}
