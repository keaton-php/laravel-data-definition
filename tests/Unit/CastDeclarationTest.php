<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Countable;
use DateTimeImmutable;
use Keaton\DataDefinition\Exceptions\InvalidCast;
use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class CastDeclarationTest extends TestCase
{
    #[DataProvider('unusableCasts')]
    #[Test]
    public function a_cast_nobody_can_use_is_rejected_when_the_definition_is_built(mixed $cast, string $messagePart): void
    {
        $this->expectException(InvalidCast::class);
        $this->expectExceptionMessage($messagePart);

        Configurable::define(casts: ['seats' => $cast]);
    }

    /** @return array<string, array{mixed, string}> */
    public static function unusableCasts(): array
    {
        return [
            'a typo in a built-in name' => ['inte', "the cast 'inte' for 'seats' is not a built-in cast"],
            'a date format after a colon' => ['date:Y-m-d', "the cast 'date:Y-m-d'"],
            'an empty string' => ['', "the cast '' for 'seats'"],
            'an interface name' => [Countable::class, 'is not a built-in cast'],
            'an arbitrary class' => [DateTimeImmutable::class, 'will not construct for you'],
            'a plain object' => [new stdClass, 'does not implement CastsAttributes'],
            'an int' => [5, 'must be a string'],
            'null' => [null, 'must be a string'],
            'a closure' => [fn ($value) => $value, 'does not implement CastsAttributes'],
        ];
    }

    /** @param  array<array-key, mixed>  $casts */
    #[DataProvider('badCastKeys')]
    #[Test]
    public function a_cast_key_must_be_a_non_empty_string(array $casts): void
    {
        $this->expectException(InvalidCast::class);
        $this->expectExceptionMessage('cast keys must be non-empty strings');

        Configurable::define(casts: $casts);
    }

    /** @return array<string, array{array<array-key, mixed>}> */
    public static function badCastKeys(): array
    {
        return [
            'a list' => [['int']],
            'an empty key' => [['' => 'int']],
            'a leading dot' => [['.age' => 'int']],
            'a trailing dot' => [['user.age.' => 'int']],
            'a doubled dot' => [['user..age' => 'int']],
        ];
    }

    #[Test]
    public function a_cast_for_a_path_that_matches_no_alias_is_accepted(): void
    {
        $definition = Configurable::define(casts: ['user.age' => 'int'], payload: ['user' => ['age' => '41']]);

        $this->assertSame(41, $definition->get('user.age'));
    }
}
