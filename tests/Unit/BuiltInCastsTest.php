<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Keaton\DataDefinition\Exceptions\CastFailed;
use Keaton\DataDefinition\Tests\Fixtures\CastBench;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

class BuiltInCastsTest extends TestCase
{
    // ---- int

    #[DataProvider('integersThatCast')]
    #[Test]
    public function int_accepts_integers_integral_floats_and_plain_integer_strings(mixed $input, int $expected): void
    {
        $this->assertSame($expected, CastBench::make(['int' => $input])->asInt);
        $this->assertSame($expected, CastBench::make(['integer' => $input])->asInteger);
    }

    /** @return array<string, array{mixed, int}> */
    public static function integersThatCast(): array
    {
        return [
            'int' => [42, 42],
            'negative int' => [-7, -7],
            'zero' => [0, 0],
            'integral float' => [42.0, 42],
            'integer string' => ['42', 42],
            'negative integer string' => ['-7', -7],
            'zero string' => ['0', 0],
        ];
    }

    #[DataProvider('integersThatDoNotCast')]
    #[Test]
    public function int_rejects_everything_else_instead_of_turning_it_into_zero(mixed $input): void
    {
        $this->assertCastFails(fn () => CastBench::make(['int' => $input])->asInt, 'asInt', 'int');
    }

    /** @return array<string, array{mixed}> */
    public static function integersThatDoNotCast(): array
    {
        return [
            'words' => ['abc'],
            'the n/a of the README' => ['n/a'],
            'decimal string' => ['4.5'],
            'fractional float' => [4.5],
            'empty string' => [''],
            'bool' => [true],
            'array' => [[1]],
            'infinity' => [INF],
            'beyond the integer range' => [1e30],
        ];
    }

    // ---- float

    #[DataProvider('floatsThatCast')]
    #[Test]
    public function float_accepts_numbers_and_numeric_strings(mixed $input, float $expected): void
    {
        $this->assertSame($expected, CastBench::make(['float' => $input])->asFloat);
        $this->assertSame($expected, CastBench::make(['double' => $input])->asDouble);
    }

    /** @return array<string, array{mixed, float}> */
    public static function floatsThatCast(): array
    {
        return [
            'float' => [2.5, 2.5],
            'int' => [3, 3.0],
            'decimal string' => ['1.5', 1.5],
            'exponent string' => ['1e3', 1000.0],
            'integer string' => ['7', 7.0],
        ];
    }

    #[DataProvider('floatsThatDoNotCast')]
    #[Test]
    public function float_rejects_everything_else(mixed $input): void
    {
        $this->assertCastFails(fn () => CastBench::make(['float' => $input])->asFloat, 'asFloat', 'float');
    }

    /** @return array<string, array{mixed}> */
    public static function floatsThatDoNotCast(): array
    {
        return [
            'words' => ['abc'],
            'empty string' => [''],
            'bool' => [true],
            'array' => [[1.5]],
            'infinity word' => ['INF'],
            'overflowing exponent' => ['1e999'],
        ];
    }

    // ---- string

    #[Test]
    public function string_accepts_strings_numbers_and_stringables(): void
    {
        $stringable = new class implements Stringable
        {
            public function __toString(): string
            {
                return 'as text';
            }
        };

        $this->assertSame('a', CastBench::make(['string' => 'a'])->asString);
        $this->assertSame('5', CastBench::make(['string' => 5])->asString);
        $this->assertSame('1.5', CastBench::make(['string' => 1.5])->asString);
        $this->assertSame('as text', CastBench::make(['string' => $stringable])->asString);
    }

    #[DataProvider('stringsThatDoNotCast')]
    #[Test]
    public function string_rejects_bools_arrays_and_plain_objects(mixed $input): void
    {
        $this->assertCastFails(fn () => CastBench::make(['string' => $input])->asString, 'asString', 'string');
    }

    /** @return array<string, array{mixed}> */
    public static function stringsThatDoNotCast(): array
    {
        return [
            'true' => [true],
            'false' => [false],
            'array' => [['a']],
            'object' => [new stdClass],
        ];
    }

    // ---- bool

    #[DataProvider('boolsThatCast')]
    #[Test]
    public function bool_accepts_bools_zero_one_and_the_usual_words(mixed $input, bool $expected): void
    {
        $this->assertSame($expected, CastBench::make(['bool' => $input])->asBool);
        $this->assertSame($expected, CastBench::make(['boolean' => $input])->asBoolean);
    }

    /** @return array<string, array{mixed, bool}> */
    public static function boolsThatCast(): array
    {
        return [
            'true' => [true, true],
            'false' => [false, false],
            'one' => [1, true],
            'zero' => [0, false],
            'one string' => ['1', true],
            'zero string' => ['0', false],
            'true word' => ['true', true],
            'false word' => ['false', false],
            'yes' => ['yes', true],
            'no' => ['no', false],
            'on' => ['on', true],
            'off' => ['off', false],
            'upper case true' => ['TRUE', true],
            'mixed case off' => ['Off', false],
            'upper case yes' => ['YES', true],
        ];
    }

    #[DataProvider('boolsThatDoNotCast')]
    #[Test]
    public function bool_rejects_everything_else_instead_of_guessing(mixed $input): void
    {
        $this->assertCastFails(fn () => CastBench::make(['bool' => $input])->asBool, 'asBool', 'bool');
    }

    /** @return array<string, array{mixed}> */
    public static function boolsThatDoNotCast(): array
    {
        return [
            'maybe' => ['maybe'],
            'empty string' => [''],
            'two' => [2],
            'two string' => ['2'],
            'float one' => [1.0],
            'float zero' => [0.0],
            'padded word' => [' true'],
            'array' => [[true]],
            'object' => [new stdClass],
        ];
    }

    // ---- array and collection

    #[Test]
    public function array_returns_the_array(): void
    {
        $this->assertSame([1, 2], CastBench::make(['array' => [1, 2]])->asArray);
    }

    #[DataProvider('notArrays')]
    #[Test]
    public function array_and_collection_reject_anything_that_is_not_an_array(mixed $input): void
    {
        $this->assertCastFails(fn () => CastBench::make(['array' => $input])->asArray, 'asArray', 'array');
        $this->assertCastFails(fn () => CastBench::make(['collection' => $input])->asCollection, 'asCollection', 'collection');
    }

    /** @return array<string, array{mixed}> */
    public static function notArrays(): array
    {
        return [
            'string' => ['x'],
            'int' => [1],
            'json text' => ['[1,2]'],
        ];
    }

    #[Test]
    public function collection_returns_a_fresh_collection_each_read(): void
    {
        $bench = CastBench::make(['collection' => ['a' => 1, 'b' => 2]]);

        $this->assertInstanceOf(Collection::class, $bench->asCollection);
        $this->assertSame(['a' => 1, 'b' => 2], $bench->asCollection->all());
        $this->assertNotSame($bench->asCollection, $bench->asCollection);
    }

    // ---- json

    #[Test]
    public function json_decodes_a_string_into_an_array(): void
    {
        $this->assertSame(['a' => 1], CastBench::make(['json' => '{"a":1}'])->asJson);
        $this->assertSame([1, 2], CastBench::make(['json' => '[1,2]'])->asJson);
    }

    #[DataProvider('notJson')]
    #[Test]
    public function json_rejects_invalid_json_json_that_is_not_an_object_or_list_and_non_strings(mixed $input): void
    {
        $this->assertCastFails(fn () => CastBench::make(['json' => $input])->asJson, 'asJson', 'json');
    }

    /** @return array<string, array{mixed}> */
    public static function notJson(): array
    {
        return [
            'broken json' => ['{bad'],
            'json scalar' => ['5'],
            'empty string' => [''],
            'not a string' => [5],
            'already an array' => [['a' => 1]],
        ];
    }

    // ---- date and datetime

    #[Test]
    public function datetime_parses_a_string_and_keeps_the_time(): void
    {
        $date = CastBench::make(['datetime' => '2026-03-02T09:15:30Z'])->asDatetime;

        $this->assertInstanceOf(Carbon::class, $date);
        $this->assertSame('2026-03-02 09:15:30', $date->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function date_parses_a_string_and_drops_the_time(): void
    {
        $date = CastBench::make(['date' => '2026-03-02 17:45:10'])->asDate;

        $this->assertInstanceOf(Carbon::class, $date);
        $this->assertSame('2026-03-02 00:00:00', $date->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function a_unix_timestamp_is_read_as_a_utc_instant(): void
    {
        $date = CastBench::make(['datetime' => 1773133200])->asDatetime;

        $this->assertSame('2026-03-10 09:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame(0, $date->getOffset());
    }

    #[Test]
    public function a_date_time_object_in_the_payload_becomes_a_carbon_at_the_same_instant(): void
    {
        $original = new DateTimeImmutable('2026-03-02 10:00:00', new DateTimeZone('Europe/Lisbon'));

        $date = CastBench::make(['datetime' => $original])->asDatetime;

        $this->assertInstanceOf(Carbon::class, $date);
        $this->assertSame($original->getTimestamp(), $date->getTimestamp());
    }

    #[Test]
    public function casting_a_date_does_not_change_a_carbon_object_held_in_the_payload(): void
    {
        $held = Carbon::parse('2026-03-02 17:45:00');

        CastBench::make(['date' => $held])->asDate;

        $this->assertSame('17:45', $held->format('H:i'));
    }

    #[DataProvider('datesThatDoNotCast')]
    #[Test]
    public function date_and_datetime_reject_what_is_not_a_date_instead_of_returning_now(mixed $input): void
    {
        $this->assertCastFails(fn () => CastBench::make(['date' => $input])->asDate, 'asDate', 'date');
        $this->assertCastFails(fn () => CastBench::make(['datetime' => $input])->asDatetime, 'asDatetime', 'datetime');
    }

    /** @return array<string, array{mixed}> */
    public static function datesThatDoNotCast(): array
    {
        return [
            'words' => ['not a date'],
            'empty string' => [''],
            'blank string' => ['   '],
            'float' => [1.5],
            'bool' => [true],
            'array' => [['2026-03-02']],
        ];
    }

    // ---- shared

    #[Test]
    public function every_built_in_leaves_a_present_null_as_null(): void
    {
        $bench = CastBench::make(array_fill_keys(
            ['int', 'integer', 'float', 'double', 'string', 'bool', 'boolean', 'array', 'collection', 'date', 'datetime', 'json'],
            null,
        ));

        foreach (array_keys($bench->toArray()) as $alias) {
            $this->assertNull($bench->get($alias), "{$alias} should stay null");
        }
    }

    #[Test]
    public function a_failed_cast_names_the_key_and_shows_the_value(): void
    {
        try {
            CastBench::make(['int' => 'abc'])->asInt;
            $this->fail('The cast did not fail.');
        } catch (CastFailed $e) {
            $this->assertStringContainsString("cannot cast 'asInt' to int", $e->getMessage());
            $this->assertStringContainsString("'abc'", $e->getMessage());
        }
    }

    /** @param  callable(): mixed  $read */
    private function assertCastFails(callable $read, string $key, string $type): void
    {
        try {
            $read();
        } catch (CastFailed $e) {
            $this->assertStringContainsString("cannot cast '{$key}' to {$type}", $e->getMessage());

            return;
        }

        $this->fail("Reading {$key} should have thrown CastFailed.");
    }
}
