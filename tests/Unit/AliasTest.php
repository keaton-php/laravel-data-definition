<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Keaton\DataDefinition\Exceptions\InvalidDefinition;
use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use Keaton\DataDefinition\Tests\Fixtures\Person;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AliasTest extends TestCase
{
    #[Test]
    public function it_can_read_a_deep_path_through_an_alias(): void
    {
        $this->assertSame('ana@example.com', Person::make(Person::sample())->email);
    }

    #[Test]
    public function it_reads_the_same_value_by_alias_and_by_path(): void
    {
        $person = Person::make(Person::sample());

        $this->assertSame($person->get('email'), $person->get('contact.primary_email'));
    }

    #[Test]
    public function it_reads_a_key_that_is_not_an_alias_as_a_raw_path(): void
    {
        $this->assertSame('Ana Costa', Person::make(Person::sample())->get('profile.name'));
    }

    #[Test]
    public function it_can_read_list_positions_in_a_path(): void
    {
        $definition = Configurable::define(payload: ['items' => [['sku' => 'A'], ['sku' => 'B']]]);

        $this->assertSame('B', $definition->get('items.1.sku'));
    }

    #[Test]
    public function it_splits_every_dot_so_a_key_containing_a_dot_cannot_be_reached(): void
    {
        $definition = Configurable::define(payload: ['a.b' => 'literal', 'a' => ['b' => 'nested']]);

        $this->assertSame('nested', $definition->get('a.b'));
    }

    #[DataProvider('membersAnAliasMustNotHide')]
    #[Test]
    public function it_rejects_an_alias_that_hides_a_property_or_a_public_or_protected_method(string $alias): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->expectExceptionMessage("the alias '{$alias}' would shadow");

        Configurable::define(aliases: [$alias => 'some.path']);
    }

    /** @return array<string, array{string}> */
    public static function membersAnAliasMustNotHide(): array
    {
        return array_map(fn (string $alias) => [$alias], array_combine(
            $names = [
                'data', 'get', 'getOrFail', 'has', 'with', 'json', 'toJson', 'toArray', 'jsonSerialize',
                'make', 'makeMany', 'when', 'unless', 'boot', 'aliases', 'casts', 'collectionClass', 'offsetGet',
            ],
            $names,
        ));
    }

    #[DataProvider('namesOfPrivateHelpers')]
    #[Test]
    public function it_allows_an_alias_named_like_a_private_helper(string $alias): void
    {
        $definition = Configurable::define(aliases: [$alias => 'thing.value'], payload: ['thing' => ['value' => 'ok']]);

        $this->assertSame('ok', $definition->get($alias));
        $this->assertSame('ok', $definition->{$alias});
    }

    /** @return array<string, array{string}> */
    public static function namesOfPrivateHelpers(): array
    {
        return array_map(fn (string $alias) => [$alias], array_combine(
            $names = ['find', 'cast', 'validate', 'label', 'shadows', 'pathFor', 'checkKey', 'offsetKey'],
            $names,
        ));
    }

    /** @param  array<array-key, mixed>  $aliases */
    #[DataProvider('badAliasNames')]
    #[Test]
    public function it_rejects_an_alias_name_that_is_empty_numeric_or_contains_a_dot(array $aliases): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->expectExceptionMessage('alias names must be non-empty strings without a dot');

        Configurable::define(aliases: $aliases);
    }

    /** @return array<string, array{array<array-key, mixed>}> */
    public static function badAliasNames(): array
    {
        return [
            'a list instead of a map' => [['contact.email']],
            'an empty name' => [['' => 'contact.email']],
            'a dotted name' => [['contact.email' => 'contact.email']],
        ];
    }

    #[DataProvider('badAliasPaths')]
    #[Test]
    public function it_rejects_an_alias_path_that_is_not_a_clean_non_empty_string(mixed $path): void
    {
        $this->expectException(InvalidDefinition::class);
        $this->expectExceptionMessage("the path for alias 'email'");

        Configurable::define(aliases: ['email' => $path]);
    }

    /** @return array<string, array{mixed}> */
    public static function badAliasPaths(): array
    {
        return [
            'empty' => [''],
            'int' => [123],
            'null' => [null],
            'array' => [['contact', 'email']],
            'doubled dot' => ['contact..email'],
            'leading dot' => ['.contact'],
            'trailing dot' => ['contact.'],
        ];
    }

    #[Test]
    public function it_checks_the_aliases_after_boot_has_adjusted_them(): void
    {
        // Configurable sets its aliases inside boot(), so a bad one is only visible after it ran.
        $this->expectException(InvalidDefinition::class);

        Configurable::define(aliases: ['get' => 'x']);
    }
}
