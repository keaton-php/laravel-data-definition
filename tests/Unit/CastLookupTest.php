<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CastLookupTest extends TestCase
{
    #[Test]
    public function a_cast_keyed_by_the_alias_wins_over_one_keyed_by_the_path(): void
    {
        $definition = Configurable::define(
            aliases: ['age' => 'user.age'],
            casts: ['age' => 'string', 'user.age' => 'int'],
            payload: ['user' => ['age' => '41']],
        );

        $this->assertSame('41', $definition->age);
    }

    #[Test]
    public function reading_by_path_uses_the_path_cast_and_not_the_alias_cast(): void
    {
        $definition = Configurable::define(
            aliases: ['age' => 'user.age'],
            casts: ['age' => 'string', 'user.age' => 'int'],
            payload: ['user' => ['age' => '41']],
        );

        $this->assertSame(41, $definition->get('user.age'));
    }

    #[Test]
    public function a_cast_keyed_by_the_path_applies_when_reading_through_the_alias(): void
    {
        $definition = Configurable::define(
            aliases: ['age' => 'user.age'],
            casts: ['user.age' => 'int'],
            payload: ['user' => ['age' => '41']],
        );

        $this->assertSame(41, $definition->age);
        $this->assertSame(41, $definition->get('user.age'));
    }

    #[Test]
    public function a_cast_keyed_by_the_alias_does_not_apply_to_a_read_by_path(): void
    {
        $definition = Configurable::define(
            aliases: ['age' => 'user.age'],
            casts: ['age' => 'int'],
            payload: ['user' => ['age' => '41']],
        );

        $this->assertSame(41, $definition->age);
        $this->assertSame('41', $definition->get('user.age'));
    }

    #[Test]
    public function a_value_with_no_cast_is_returned_as_it_is_in_the_payload(): void
    {
        $definition = Configurable::define(
            aliases: ['age' => 'user.age'],
            payload: ['user' => ['age' => '41', 'tags' => ['a', 'b']]],
        );

        $this->assertSame('41', $definition->age);
        $this->assertSame(['a', 'b'], $definition->get('user.tags'));
    }
}
