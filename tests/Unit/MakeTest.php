<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Illuminate\Support\Collection;
use Keaton\DataDefinition\Exceptions\InvalidData;
use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use Keaton\DataDefinition\Tests\Fixtures\Person;
use Keaton\DataDefinition\Tests\Fixtures\PlainObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class MakeTest extends TestCase
{
    #[Test]
    public function it_can_wrap_an_array(): void
    {
        $person = Person::make(Person::sample());

        $this->assertSame(Person::sample(), $person->data());
        $this->assertSame('Ana Costa', $person->name);
    }

    #[Test]
    public function it_can_be_built_with_new_as_well_as_make(): void
    {
        $this->assertSame('Ana Costa', (new Person(Person::sample()))->name);
    }

    #[Test]
    public function it_can_wrap_an_arrayable_through_its_to_array(): void
    {
        $person = Person::make(new Collection(['profile' => ['name' => 'Ben Ito']]));

        $this->assertSame('Ben Ito', $person->name);
        $this->assertSame(['profile' => ['name' => 'Ben Ito']], $person->data());
    }

    #[Test]
    public function it_can_wrap_another_definition_through_its_to_array(): void
    {
        $copy = Configurable::make(Person::make(['profile' => ['name' => 'Ben Ito']]));

        $this->assertSame('Ben Ito', $copy->get('name'));
        $this->assertSame(['name' => 'Ben Ito', 'email' => null, 'age' => null, 'joined' => null], $copy->data());
    }

    #[Test]
    public function it_can_wrap_an_object_by_its_public_properties_only(): void
    {
        $person = Person::make(new PlainObject);

        $this->assertSame(['profile' => ['name' => 'Ana Costa']], $person->data());
        $this->assertSame('Ana Costa', $person->name);
    }

    #[Test]
    public function it_can_wrap_a_standard_object_with_array_properties(): void
    {
        $payload = new stdClass;
        $payload->profile = ['name' => 'Ana Costa'];

        $this->assertSame('Ana Costa', Person::make($payload)->name);
    }

    #[DataProvider('notPayloads')]
    #[Test]
    public function it_rejects_anything_that_is_not_a_payload(mixed $notAPayload, string $typeInMessage): void
    {
        try {
            Person::make($notAPayload);
            $this->fail('make() accepted a value it should have rejected.');
        } catch (InvalidData $e) {
            $this->assertStringContainsString("got {$typeInMessage}.", $e->getMessage());
            $this->assertStringContainsString('Person::make()', $e->getMessage());
        }
    }

    /** @return array<string, array{mixed, string}> */
    public static function notPayloads(): array
    {
        return [
            'string' => ['a string', 'string'],
            'int' => [5, 'int'],
            'float' => [1.5, 'float'],
            'bool' => [true, 'bool'],
            'null' => [null, 'null'],
        ];
    }
}
