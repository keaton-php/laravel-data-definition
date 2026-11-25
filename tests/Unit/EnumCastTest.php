<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Keaton\DataDefinition\Exceptions\CastFailed;
use Keaton\DataDefinition\Exceptions\InvalidCast;
use Keaton\DataDefinition\Tests\Fixtures\Color;
use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use Keaton\DataDefinition\Tests\Fixtures\Level;
use Keaton\DataDefinition\Tests\Fixtures\Suit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnumCastTest extends TestCase
{
    #[Test]
    public function a_string_backed_enum_reads_its_case(): void
    {
        $this->assertSame(Color::Red, $this->read(Color::class, 'red'));
        $this->assertSame(Color::Blue, $this->read(Color::class, 'blue'));
    }

    #[Test]
    public function an_int_backed_enum_reads_its_case_from_an_int_or_an_integer_string(): void
    {
        $this->assertSame(Level::Low, $this->read(Level::class, 1));
        $this->assertSame(Level::High, $this->read(Level::class, '3'));
    }

    #[DataProvider('valuesThatAreNotCases')]
    #[Test]
    public function a_value_that_is_not_a_case_throws_and_names_the_value_and_the_enum(string $enum, mixed $value, string $shownValue): void
    {
        try {
            $this->read($enum, $value);
            $this->fail('The value was accepted as a case.');
        } catch (CastFailed $e) {
            $this->assertStringContainsString($shownValue, $e->getMessage());
            $this->assertStringContainsString($enum, $e->getMessage());
        }
    }

    /** @return array<string, array{class-string, mixed, string}> */
    public static function valuesThatAreNotCases(): array
    {
        return [
            'unknown string' => [Color::class, 'green', "'green'"],
            'wrong case' => [Color::class, 'RED', "'RED'"],
            'int for a string enum' => [Color::class, 5, '5'],
            'unknown int' => [Level::class, 2, '2'],
            'words for an int enum' => [Level::class, 'abc', "'abc'"],
            'decimal string for an int enum' => [Level::class, '1.5', "'1.5'"],
            'bool for an int enum' => [Level::class, true, 'true'],
        ];
    }

    #[Test]
    public function a_pure_enum_is_rejected_when_the_definition_is_built(): void
    {
        $this->expectException(InvalidCast::class);
        $this->expectExceptionMessage('pure enum');

        Configurable::define(casts: ['suit' => Suit::class]);
    }

    #[Test]
    public function an_enum_cast_does_not_take_constructor_style_arguments(): void
    {
        $this->expectException(InvalidCast::class);

        Configurable::define(casts: ['color' => Color::class.':x']);
    }

    private function read(string $enum, mixed $value): mixed
    {
        return Configurable::define(
            aliases: ['it' => 'value'],
            casts: ['it' => $enum],
            payload: ['value' => $value],
        )->it;
    }
}
