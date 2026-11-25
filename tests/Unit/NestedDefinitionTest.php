<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Illuminate\Support\Collection;
use Keaton\DataDefinition\DataDefinition;
use Keaton\DataDefinition\Exceptions\CastFailed;
use Keaton\DataDefinition\Exceptions\InvalidCast;
use Keaton\DataDefinition\Tests\Fixtures\AbstractShape;
use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use Keaton\DataDefinition\Tests\Fixtures\Contact;
use Keaton\DataDefinition\Tests\Fixtures\Group;
use Keaton\DataDefinition\Tests\Fixtures\Member;
use Keaton\DataDefinition\Tests\Fixtures\MemberCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class NestedDefinitionTest extends TestCase
{
    #[Test]
    public function a_definition_cast_wraps_the_sub_object_in_that_definition(): void
    {
        $group = Group::make(['lead' => ['full_name' => 'Ana Costa', 'mail' => 'ana@example.com']]);

        $this->assertInstanceOf(Contact::class, $group->lead);
        $this->assertSame('Ana Costa', $group->lead->name);
        $this->assertSame('ana@example.com', $group->lead->email);
    }

    #[Test]
    public function a_definition_cast_can_wrap_an_object_in_the_payload(): void
    {
        $lead = new stdClass;
        $lead->full_name = 'Ana Costa';

        $this->assertSame('Ana Costa', Group::make(['lead' => $lead])->lead->name);
    }

    #[Test]
    public function a_null_sub_object_and_a_missing_one_are_both_null(): void
    {
        $this->assertNull(Group::make(['lead' => null])->lead);
        $this->assertNull(Group::make([])->lead);
    }

    #[Test]
    public function a_list_cast_wraps_every_item_and_keeps_the_keys(): void
    {
        $group = Group::make(['people' => [
            'ana' => ['full_name' => 'Ana Costa'],
            'ben' => ['full_name' => 'Ben Ito'],
        ]]);

        $this->assertInstanceOf(Collection::class, $group->contacts);
        $this->assertSame(['ana', 'ben'], $group->contacts->keys()->all());
        $this->assertInstanceOf(Contact::class, $group->contacts->get('ben'));
        $this->assertSame('Ben Ito', $group->contacts->get('ben')->name);
    }

    #[Test]
    public function a_list_cast_returns_the_collection_class_of_the_item_definition(): void
    {
        $group = Group::make(['staff' => [['full_name' => 'Ana Costa', 'seats' => '4']]]);

        $first = $group->members->first();

        $this->assertInstanceOf(MemberCollection::class, $group->members);
        $this->assertInstanceOf(Member::class, $first);
        $this->assertSame(4, $first->seats);
    }

    #[Test]
    public function an_empty_list_is_an_empty_collection_and_a_null_list_is_null(): void
    {
        $this->assertCount(0, Group::make(['people' => []])->contacts);
        $this->assertNull(Group::make(['people' => null])->contacts);
    }

    #[DataProvider('subObjectsThatAreNotArraysOrObjects')]
    #[Test]
    public function a_definition_cast_rejects_a_value_that_is_not_an_array_or_object(mixed $value): void
    {
        $this->expectException(CastFailed::class);
        $this->expectExceptionMessage("cannot cast 'lead'");

        Group::make(['lead' => $value])->lead;
    }

    /** @return array<string, array{mixed}> */
    public static function subObjectsThatAreNotArraysOrObjects(): array
    {
        return [
            'string' => ['Ana Costa'],
            'int' => [5],
            'bool' => [true],
        ];
    }

    #[Test]
    public function a_list_cast_rejects_a_value_that_is_not_an_array(): void
    {
        $this->expectException(CastFailed::class);
        $this->expectExceptionMessage("cannot cast 'contacts'");

        Group::make(['people' => 'nobody'])->contacts;
    }

    #[Test]
    public function a_list_cast_names_the_position_of_an_item_that_cannot_be_wrapped(): void
    {
        try {
            Group::make(['people' => [['full_name' => 'Ana Costa'], 'Ben Ito']])->contacts;
            $this->fail('The bad item was accepted.');
        } catch (CastFailed $e) {
            $this->assertStringContainsString("'contacts.1'", $e->getMessage());
            $this->assertStringContainsString("'Ben Ito'", $e->getMessage());
        }
    }

    #[DataProvider('badListDeclarations')]
    #[Test]
    public function a_list_cast_must_be_exactly_one_concrete_definition_class_in_an_array(mixed $declaration): void
    {
        $this->expectException(InvalidCast::class);
        $this->expectExceptionMessage('[SomeDefinition::class]');

        Configurable::define(casts: ['items' => $declaration]);
    }

    /** @return array<string, array{mixed}> */
    public static function badListDeclarations(): array
    {
        return [
            'empty' => [[]],
            'two classes' => [[Contact::class, Member::class]],
            'a keyed entry' => [['contact' => Contact::class]],
            'not a definition' => [[stdClass::class]],
            'not a class' => [['No\\Such\\Class']],
            'an abstract definition' => [[AbstractShape::class]],
            'not a string' => [[5]],
        ];
    }

    #[DataProvider('definitionClassesThatCannotBeBuilt')]
    #[Test]
    public function a_definition_cast_must_name_a_concrete_definition_subclass(string $class): void
    {
        $this->expectException(InvalidCast::class);

        Configurable::define(casts: ['sub' => $class]);
    }

    /** @return array<string, array{class-string}> */
    public static function definitionClassesThatCannotBeBuilt(): array
    {
        return [
            'the abstract base itself' => [DataDefinition::class],
            'an abstract subclass' => [AbstractShape::class],
        ];
    }
}
