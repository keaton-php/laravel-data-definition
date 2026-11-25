<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Illuminate\Support\Carbon;
use JsonException;
use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use Keaton\DataDefinition\Tests\Fixtures\Contact;
use Keaton\DataDefinition\Tests\Fixtures\Group;
use Keaton\DataDefinition\Tests\Fixtures\Person;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OutputTest extends TestCase
{
    #[Test]
    public function to_array_resolves_and_casts_every_alias_in_declaration_order(): void
    {
        $array = Person::make(Person::sample())->toArray();

        $this->assertSame(['name', 'email', 'age', 'joined'], array_keys($array));
        $this->assertSame('Ana Costa', $array['name']);
        $this->assertSame(41, $array['age']);
        $this->assertInstanceOf(Carbon::class, $array['joined']);
    }

    #[Test]
    public function to_array_has_null_for_an_alias_whose_path_is_missing(): void
    {
        $this->assertSame(
            ['name' => null, 'email' => null, 'age' => null, 'joined' => null],
            Person::make([])->toArray(),
        );
    }

    #[Test]
    public function to_array_leaves_out_payload_keys_that_have_no_alias(): void
    {
        $array = Person::make(Person::sample() + ['secret' => 'hidden'])->toArray();

        $this->assertArrayNotHasKey('secret', $array);
        $this->assertArrayNotHasKey('profile', $array);
    }

    #[Test]
    public function to_array_turns_a_nested_definition_into_an_array(): void
    {
        $array = Group::make(['lead' => ['full_name' => 'Ana Costa', 'mail' => 'ana@example.com']])->toArray();

        $this->assertSame(['name' => 'Ana Costa', 'email' => 'ana@example.com'], $array['lead']);
    }

    #[Test]
    public function to_array_turns_a_list_of_definitions_into_a_list_of_arrays(): void
    {
        $array = Group::make(['people' => [
            ['full_name' => 'Ana Costa', 'mail' => 'ana@example.com'],
            ['full_name' => 'Ben Ito'],
        ]])->toArray();

        $this->assertSame([
            ['name' => 'Ana Costa', 'email' => 'ana@example.com'],
            ['name' => 'Ben Ito', 'email' => null],
        ], $array['contacts']);
    }

    #[Test]
    public function to_array_turns_any_arrayable_value_such_as_a_collection_cast_into_an_array(): void
    {
        $definition = Configurable::define(
            aliases: ['items' => 'items'],
            casts: ['items' => 'collection'],
            payload: ['items' => ['a', 'b']],
        );

        $this->assertSame(['items' => ['a', 'b']], $definition->toArray());
    }

    #[Test]
    public function data_returns_the_raw_payload_including_keys_with_no_alias(): void
    {
        $payload = Person::sample() + ['secret' => 'hidden'];

        $this->assertSame($payload, Person::make($payload)->data());
    }

    #[Test]
    public function json_serialize_is_to_array(): void
    {
        $contact = Contact::make(['full_name' => 'Ana Costa', 'mail' => 'ana@example.com']);

        $this->assertSame($contact->toArray(), $contact->jsonSerialize());
    }

    #[Test]
    public function json_to_json_and_json_encode_agree(): void
    {
        $contact = Contact::make(['full_name' => 'Ana Costa', 'mail' => 'ana@example.com']);

        $expected = '{"name":"Ana Costa","email":"ana@example.com"}';

        $this->assertSame($expected, $contact->json());
        $this->assertSame($expected, $contact->toJson());
        $this->assertSame($expected, json_encode($contact));
    }

    #[Test]
    public function json_writes_a_date_the_way_carbon_does(): void
    {
        $this->assertSame(
            '{"name":"Ana Costa","email":"ana@example.com","age":41,"joined":"2026-03-02T09:15:00.000000Z"}',
            Person::make(Person::sample())->json(),
        );
    }

    #[Test]
    public function json_takes_json_encode_flags(): void
    {
        $contact = Contact::make(['full_name' => 'Ana', 'mail' => 'a/b@example.com']);

        $this->assertStringContainsString("\n", $contact->json(JSON_PRETTY_PRINT));
        $this->assertStringContainsString('a/b@example.com', $contact->json(JSON_UNESCAPED_SLASHES));
        $this->assertStringContainsString('a\/b@example.com', $contact->json());
    }

    #[Test]
    public function json_throws_instead_of_returning_false_when_the_payload_cannot_be_encoded(): void
    {
        $definition = Configurable::define(aliases: ['broken' => 'x'], payload: ['x' => "\xB1\x31"]);

        $this->expectException(JsonException::class);

        $definition->json();
    }

    #[Test]
    public function a_collection_of_definitions_encodes_each_one(): void
    {
        $contacts = Contact::makeMany([['full_name' => 'Ana', 'mail' => 'a@example.com']]);

        $this->assertSame('[{"name":"Ana","email":"a@example.com"}]', $contacts->toJson());
    }
}
