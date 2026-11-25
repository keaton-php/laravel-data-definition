<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Keaton\DataDefinition\Exceptions\InvalidData;
use Keaton\DataDefinition\Tests\Fixtures\Person;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WithTest extends TestCase
{
    #[Test]
    public function it_returns_a_new_instance_of_the_same_class(): void
    {
        $person = Person::make(Person::sample());

        $changed = $person->with(['email' => 'new@example.com']);

        $this->assertNotSame($person, $changed);
        $this->assertInstanceOf(Person::class, $changed);
    }

    #[Test]
    public function the_original_is_untouched(): void
    {
        $person = Person::make(Person::sample());

        $person->with(['email' => 'new@example.com', 'profile.name' => 'Someone Else']);

        $this->assertSame(Person::sample(), $person->data());
        $this->assertSame('ana@example.com', $person->email);
    }

    #[Test]
    public function it_applies_changes_given_by_alias_and_by_path(): void
    {
        $changed = Person::make(Person::sample())->with([
            'email' => 'new@example.com',
            'profile.name' => 'Ben Ito',
        ]);

        $this->assertSame('new@example.com', $changed->email);
        $this->assertSame('Ben Ito', $changed->name);
        $this->assertSame('new@example.com', $changed->data()['contact']['primary_email']);
    }

    #[Test]
    public function it_creates_the_parents_of_a_new_path(): void
    {
        $changed = Person::make([])->with(['email' => 'new@example.com']);

        $this->assertSame(['contact' => ['primary_email' => 'new@example.com']], $changed->data());
    }

    #[Test]
    public function it_can_set_a_value_to_null_and_the_path_then_exists(): void
    {
        $changed = Person::make(Person::sample())->with(['email' => null]);

        $this->assertTrue($changed->has('email'));
        $this->assertNull($changed->email);
    }

    #[Test]
    public function a_changed_value_goes_through_the_casts_on_the_next_read(): void
    {
        $changed = Person::make(Person::sample())->with(['age' => '42']);

        $this->assertSame(42, $changed->age);
        $this->assertSame('42', $changed->data()['profile']['age']);
    }

    #[Test]
    public function changes_are_applied_in_the_order_given(): void
    {
        $changed = Person::make([])->with(['email' => 'first@example.com', 'contact.primary_email' => 'second@example.com']);

        $this->assertSame('second@example.com', $changed->email);
    }

    #[Test]
    public function no_changes_returns_an_equal_but_separate_copy(): void
    {
        $person = Person::make(Person::sample());
        $copy = $person->with([]);

        $this->assertNotSame($person, $copy);
        $this->assertSame($person->data(), $copy->data());
    }

    #[Test]
    public function it_refuses_to_write_through_a_value_that_is_not_an_array(): void
    {
        $person = Person::make(Person::sample());

        try {
            $person->with(['profile.name.first' => 'Ana']);
            $this->fail('with() wrote through a string.');
        } catch (InvalidData $e) {
            $this->assertStringContainsString("cannot set 'profile.name.first'", $e->getMessage());
            $this->assertStringContainsString("'profile.name'", $e->getMessage());
        }

        $this->assertSame('Ana Costa', $person->name);
    }

    /** @param  array<array-key, mixed>  $changes */
    #[DataProvider('badChangeKeys')]
    #[Test]
    public function it_rejects_a_change_that_is_not_keyed_by_a_non_empty_string(array $changes): void
    {
        $this->expectException(InvalidData::class);
        $this->expectExceptionMessage('a key must be a non-empty string');

        Person::make(Person::sample())->with($changes);
    }

    /** @return array<string, array{array<array-key, mixed>}> */
    public static function badChangeKeys(): array
    {
        return [
            'a list' => [['new@example.com']],
            'an empty key' => [['' => 'x']],
        ];
    }
}
