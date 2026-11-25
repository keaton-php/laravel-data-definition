<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Keaton\DataDefinition\Exceptions\InvalidData;
use Keaton\DataDefinition\Exceptions\MissingValue;
use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use Keaton\DataDefinition\Tests\Fixtures\Person;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GetTest extends TestCase
{
    #[Test]
    public function it_returns_a_cast_value_as_a_property_an_offset_and_through_get(): void
    {
        $person = Person::make(Person::sample());

        $this->assertSame(41, $person->age);
        $this->assertSame(41, $person['age']);
        $this->assertSame(41, $person->get('age'));
    }

    #[Test]
    public function it_returns_the_default_for_a_missing_path(): void
    {
        $person = Person::make(Person::sample());

        $this->assertNull($person->get('profile.missing'));
        $this->assertSame('fallback', $person->get('profile.missing', 'fallback'));
        $this->assertSame('fallback', $person->get('missing', 'fallback'));
    }

    #[Test]
    public function it_returns_a_present_null_and_not_the_default(): void
    {
        $person = Person::make(Person::sample());

        $this->assertNull($person->get('profile.nickname', 'fallback'));
    }

    #[Test]
    public function it_does_not_cast_the_default(): void
    {
        // 'unknown' could never be cast to an int, so this proves the cast is skipped.
        $this->assertSame('unknown', Person::make([])->get('age', 'unknown'));
    }

    #[Test]
    public function it_returns_null_for_a_missing_value_without_casting_it(): void
    {
        $this->assertNull(Person::make([])->age);
        $this->assertNull(Person::make([])->joined);
    }

    #[Test]
    public function it_does_not_cast_a_present_null(): void
    {
        $this->assertNull(Person::make(['profile' => ['age' => null]])->age);
        $this->assertNull(Person::make(['created' => null])->joined);
    }

    #[Test]
    public function has_tells_a_present_null_from_a_missing_path(): void
    {
        $person = Person::make(Person::sample());

        $this->assertTrue($person->has('profile.nickname'));
        $this->assertFalse($person->has('profile.missing'));
        $this->assertTrue($person->has('email'));
        $this->assertFalse(Person::make([])->has('email'));
    }

    #[Test]
    public function has_is_false_when_a_parent_on_the_path_is_not_an_array(): void
    {
        $this->assertFalse(Person::make(['profile' => 'flat'])->has('profile.name'));
    }

    #[Test]
    public function get_or_fail_returns_the_cast_value_when_the_path_exists(): void
    {
        $this->assertSame(41, Person::make(Person::sample())->getOrFail('age'));
    }

    #[Test]
    public function get_or_fail_returns_a_present_null(): void
    {
        $this->assertNull(Person::make(Person::sample())->getOrFail('profile.nickname'));
    }

    #[Test]
    public function get_or_fail_throws_for_a_missing_path_and_names_the_path_and_the_alias(): void
    {
        try {
            Person::make([])->getOrFail('email');
            $this->fail('getOrFail() did not throw for a missing path.');
        } catch (MissingValue $e) {
            $this->assertStringContainsString("'contact.primary_email'", $e->getMessage());
            $this->assertStringContainsString("alias 'email'", $e->getMessage());
        }
    }

    #[Test]
    public function get_or_fail_names_only_the_path_when_it_was_asked_for_by_path(): void
    {
        try {
            Person::make([])->getOrFail('contact.primary_mail');
            $this->fail('getOrFail() did not throw for a missing path.');
        } catch (MissingValue $e) {
            $this->assertStringContainsString("'contact.primary_mail'", $e->getMessage());
            $this->assertStringNotContainsString('alias', $e->getMessage());
        }
    }

    #[Test]
    public function a_misspelled_alias_reads_as_a_missing_path(): void
    {
        $this->assertNull(Person::make(Person::sample())->emial);
    }

    #[Test]
    public function isset_follows_php_and_is_false_for_null(): void
    {
        $person = Person::make(Person::sample());

        $this->assertTrue(isset($person->email));
        $this->assertTrue(isset($person['email']));
        $this->assertTrue(isset($person['profile.name']));
        $this->assertFalse(isset($person['profile.nickname']));
        $this->assertFalse(isset($person->missing));
        $this->assertFalse(empty($person->email));
    }

    #[Test]
    public function isset_does_not_cast_so_it_cannot_throw(): void
    {
        $person = Person::make(['profile' => ['age' => 'not a number']]);

        $this->assertTrue(isset($person->age));
    }

    #[Test]
    public function an_integer_offset_reads_a_list_position(): void
    {
        $list = Configurable::make(['first', 'second']);

        $this->assertSame('second', $list[1]);
        $this->assertTrue(isset($list[0]));
        $this->assertFalse(isset($list[7]));
    }

    #[Test]
    public function an_empty_key_is_rejected_everywhere(): void
    {
        $person = Person::make(Person::sample());

        foreach ([
            fn () => $person->get(''),
            fn () => $person->has(''),
            fn () => $person->getOrFail(''),
            fn () => $person->with(['' => 'x']),
            fn () => $person[''],
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('An empty key was accepted.');
            } catch (InvalidData $e) {
                $this->assertStringContainsString('a key must be a non-empty string', $e->getMessage());
            }
        }
    }

    #[DataProvider('keysWithEmptySegments')]
    #[Test]
    public function a_key_with_an_empty_dot_segment_is_rejected_everywhere(string $key): void
    {
        $person = Person::make(Person::sample());

        foreach ([
            fn () => $person->get($key),
            fn () => $person->has($key),
            fn () => $person->getOrFail($key),
            fn () => $person->with([$key => 'x']),
            fn () => $person[$key],
        ] as $attempt) {
            try {
                $attempt();
                $this->fail("The key '{$key}' was accepted.");
            } catch (InvalidData $e) {
                $this->assertStringContainsString('a key must be a non-empty string with no empty dot segment', $e->getMessage());
            }
        }
    }

    /** @return array<string, array{string}> */
    public static function keysWithEmptySegments(): array
    {
        return [
            'leading dot' => ['.profile'],
            'trailing dot' => ['profile.'],
            'doubled dot' => ['profile..name'],
        ];
    }

    #[DataProvider('offsetsThatAreNotStringsOrIntegers')]
    #[Test]
    public function an_offset_that_is_not_a_string_or_integer_is_rejected(mixed $offset): void
    {
        $this->expectException(InvalidData::class);

        Person::make(Person::sample())->offsetGet($offset);
    }

    /** @return array<string, array{mixed}> */
    public static function offsetsThatAreNotStringsOrIntegers(): array
    {
        return [
            'null' => [null],
            'float' => [1.5],
            'array' => [['a']],
            'bool' => [true],
        ];
    }
}
