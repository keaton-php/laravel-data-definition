<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Illuminate\Support\Collection;
use Keaton\DataDefinition\Exceptions\InvalidData;
use Keaton\DataDefinition\Exceptions\InvalidDefinition;
use Keaton\DataDefinition\Tests\Fixtures\AbstractCollectionDefinition;
use Keaton\DataDefinition\Tests\Fixtures\BadCollectionDefinition;
use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use Keaton\DataDefinition\Tests\Fixtures\Contact;
use Keaton\DataDefinition\Tests\Fixtures\Member;
use Keaton\DataDefinition\Tests\Fixtures\MemberCollection;
use Keaton\DataDefinition\Tests\Fixtures\MissingCollectionDefinition;
use Keaton\DataDefinition\Tests\Fixtures\Person;
use Keaton\DataDefinition\Tests\Fixtures\TwoArgumentCollectionDefinition;
use Keaton\DataDefinition\Tests\Fixtures\TypedConstructorCollectionDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MakeManyTest extends TestCase
{
    #[Test]
    public function it_wraps_every_item_in_a_plain_collection_by_default(): void
    {
        $contacts = Contact::makeMany([['full_name' => 'Ana'], ['full_name' => 'Ben']]);

        $this->assertSame(Collection::class, $contacts::class);
        $this->assertCount(2, $contacts);
        $this->assertContainsOnlyInstancesOf(Contact::class, $contacts);
        $this->assertSame('Ben', $contacts[1]->name);
    }

    #[Test]
    public function it_keeps_the_keys(): void
    {
        $contacts = Contact::makeMany(['ana' => ['full_name' => 'Ana'], 'ben' => ['full_name' => 'Ben']]);

        $this->assertSame(['ana', 'ben'], $contacts->keys()->all());
    }

    #[Test]
    public function it_takes_any_iterable_not_only_an_array(): void
    {
        $generator = (function () {
            yield 'first' => ['full_name' => 'Ana'];
            yield 'second' => ['full_name' => 'Ben'];
        })();

        $this->assertSame(['first', 'second'], Contact::makeMany($generator)->keys()->all());
    }

    #[Test]
    public function an_empty_input_is_an_empty_collection(): void
    {
        $this->assertCount(0, Contact::makeMany([]));
    }

    #[Test]
    public function it_returns_the_declared_collection_class(): void
    {
        $members = Member::makeMany([['full_name' => 'Ana', 'seats' => '4']]);

        $first = $members->first();

        $this->assertInstanceOf(MemberCollection::class, $members);
        $this->assertInstanceOf(Member::class, $first);
        $this->assertSame(4, $first->seats);
    }

    #[Test]
    public function the_collection_works_with_pluck_and_sum_on_alias_names(): void
    {
        $members = Member::makeMany([['full_name' => 'Ana', 'seats' => '4'], ['full_name' => 'Ben', 'seats' => '3']]);

        $this->assertSame(['Ana', 'Ben'], $members->pluck('name')->all());
        $this->assertSame(7, $members->sum('seats'));
    }

    #[Test]
    public function a_collection_class_that_is_not_a_collection_throws_even_for_empty_input(): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->expectExceptionMessage('must be Illuminate\Support\Collection or a class that extends it');

        BadCollectionDefinition::makeMany([]);
    }

    #[Test]
    public function an_abstract_collection_class_throws_the_package_exception(): void
    {
        $this->expectException(InvalidDefinition::class);

        AbstractCollectionDefinition::makeMany([['a' => 1]]);
    }

    #[Test]
    public function a_collection_class_that_cannot_be_built_from_one_array_throws_the_package_exception(): void
    {
        $this->expectException(InvalidDefinition::class);

        TwoArgumentCollectionDefinition::makeMany([['a' => 1]]);
    }

    #[Test]
    public function a_collection_class_whose_constructor_cannot_take_an_array_throws_the_package_exception(): void
    {
        $this->expectException(InvalidDefinition::class);

        TypedConstructorCollectionDefinition::makeMany([['a' => 1]]);
    }

    #[Test]
    public function a_list_cast_of_a_definition_with_a_bad_collection_class_throws_the_package_exception_on_read(): void
    {
        $definition = Configurable::define(
            aliases: ['items' => 'items'],
            casts: ['items' => [AbstractCollectionDefinition::class]],
            payload: ['items' => [['a' => 1]]],
        );

        $this->expectException(InvalidDefinition::class);

        $definition->get('items');
    }

    #[Test]
    public function a_collection_class_that_does_not_exist_throws(): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->expectExceptionMessage("'No\\Such\\Collection'");

        MissingCollectionDefinition::makeMany([]);
    }

    #[Test]
    public function an_item_that_cannot_be_wrapped_throws(): void
    {
        $this->expectException(InvalidData::class);

        Person::makeMany([Person::sample(), 'not a payload']);
    }
}
