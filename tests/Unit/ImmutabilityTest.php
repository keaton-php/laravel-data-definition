<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Keaton\DataDefinition\Exceptions\ImmutableDefinition;
use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use Keaton\DataDefinition\Tests\Fixtures\Person;
use Keaton\DataDefinition\Tests\Fixtures\SelfEditing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ImmutabilityTest extends TestCase
{
    #[Test]
    public function setting_a_property_throws(): void
    {
        $person = Person::make(Person::sample());

        $this->expectException(ImmutableDefinition::class);
        $this->expectExceptionMessage("setting 'email' is not allowed");

        $person->email = 'x@example.com';
    }

    #[Test]
    public function setting_a_property_that_does_not_exist_throws_too(): void
    {
        $person = Person::make(Person::sample());

        $this->expectException(ImmutableDefinition::class);

        $person->brandNew = 'x';
    }

    #[Test]
    public function unsetting_a_property_throws(): void
    {
        $person = Person::make(Person::sample());

        $this->expectException(ImmutableDefinition::class);
        $this->expectExceptionMessage("unsetting 'email' is not allowed");

        unset($person->email);
    }

    #[Test]
    public function setting_an_offset_throws(): void
    {
        $person = Person::make(Person::sample());

        $this->expectException(ImmutableDefinition::class);
        $this->expectExceptionMessage('assigning to an offset is not allowed');

        $person['email'] = 'x@example.com';
    }

    #[Test]
    public function appending_with_empty_brackets_throws(): void
    {
        $person = Person::make(Person::sample());

        $this->expectException(ImmutableDefinition::class);

        $person[] = 'x';
    }

    #[Test]
    public function unsetting_an_offset_throws(): void
    {
        $person = Person::make(Person::sample());

        $this->expectException(ImmutableDefinition::class);
        $this->expectExceptionMessage('unsetting an offset is not allowed');

        unset($person['email']);
    }

    #[Test]
    public function the_message_points_at_with(): void
    {
        try {
            Person::make(Person::sample())->email = 'x';
            $this->fail('The assignment did not throw.');
        } catch (ImmutableDefinition $e) {
            $this->assertStringContainsString('Use with()', $e->getMessage());
        }
    }

    #[Test]
    public function a_refused_write_leaves_the_payload_untouched(): void
    {
        $person = Person::make(Person::sample());

        foreach ([
            function () use ($person): void {
                $person->email = 'x';
            },
            function () use ($person): void {
                $person['email'] = 'x';
            },
            function () use ($person): void {
                unset($person->email);
            },
            function () use ($person): void {
                unset($person['email']);
            },
        ] as $attempt) {
            try {
                $attempt();
            } catch (ImmutableDefinition) {
                // expected
            }
        }

        $this->assertSame(Person::sample(), $person->data());
        $this->assertSame('ana@example.com', $person->email);
    }

    #[Test]
    public function a_cast_date_that_is_changed_does_not_change_the_next_read(): void
    {
        $person = Person::make(Person::sample());

        $date = $person->joined;
        $date->addYear();

        $this->assertSame('2026-03-02', $person->joined->format('Y-m-d'));
    }

    #[Test]
    public function every_read_of_a_date_is_a_new_object(): void
    {
        $person = Person::make(Person::sample());

        $this->assertNotSame($person->joined, $person->joined);
    }

    #[Test]
    public function a_collection_that_is_changed_does_not_change_the_next_read(): void
    {
        $definition = Configurable::define(
            aliases: ['items' => 'items'],
            casts: ['items' => 'collection'],
            payload: ['items' => ['a', 'b']],
        );

        $definition->items->push('c');

        $this->assertCount(2, $definition->items);
    }

    #[Test]
    public function an_array_that_is_changed_does_not_change_the_next_read(): void
    {
        $definition = Configurable::define(
            aliases: ['items' => 'items'],
            casts: ['items' => 'array'],
            payload: ['items' => ['a', 'b']],
        );

        $items = $definition->items;
        $items[] = 'c';

        $this->assertSame(['a', 'b'], $definition->items);
    }

    #[Test]
    public function a_method_that_edits_the_aliases_after_construction_changes_nothing(): void
    {
        $definition = SelfEditing::make(['contact' => ['email' => 'a@example.com', 'other' => 'b@example.com'], 'age' => '41']);

        $definition->retarget();

        $this->assertSame('a@example.com', $definition->email);
        $this->assertNull($definition->added);
        $this->assertSame(['email' => 'a@example.com', 'age' => 41], $definition->toArray());
    }

    #[Test]
    public function a_method_that_edits_the_casts_after_construction_changes_nothing(): void
    {
        $definition = SelfEditing::make(['age' => '41']);

        $definition->recast();

        $this->assertSame(41, $definition->age);
    }

    #[Test]
    public function the_raw_data_returned_is_a_copy(): void
    {
        $person = Person::make(Person::sample());

        $data = $person->data();
        $data['profile']['name'] = 'Someone Else';

        $this->assertSame('Ana Costa', $person->name);
    }
}
