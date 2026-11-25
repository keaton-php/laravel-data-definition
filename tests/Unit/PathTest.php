<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Keaton\DataDefinition\Exceptions\InvalidData;
use Keaton\DataDefinition\Path;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    #[Test]
    public function find_reports_a_present_value_and_a_present_null(): void
    {
        $data = ['a' => ['b' => 1, 'n' => null]];

        $this->assertSame([true, 1], Path::find($data, 'a.b'));
        $this->assertSame([true, null], Path::find($data, 'a.n'));
        $this->assertSame([true, ['b' => 1, 'n' => null]], Path::find($data, 'a'));
    }

    #[Test]
    public function find_reports_a_missing_value(): void
    {
        $data = ['a' => ['b' => 1], 'flat' => 'text'];

        $this->assertSame([false, null], Path::find($data, 'a.c'));
        $this->assertSame([false, null], Path::find($data, 'x.y'));
        $this->assertSame([false, null], Path::find($data, 'flat.deeper'));
    }

    #[Test]
    public function find_reads_integer_keys_by_their_string_form(): void
    {
        $this->assertSame([true, 'two'], Path::find(['items' => ['one', 'two']], 'items.1'));
    }

    #[Test]
    public function with_sets_a_value_and_creates_the_parents(): void
    {
        $this->assertSame(['a' => ['b' => ['c' => 1]]], Path::with('Thing', [], 'a.b.c', 1));
        $this->assertSame(['a' => ['b' => 2, 'c' => 3]], Path::with('Thing', ['a' => ['b' => 2]], 'a.c', 3));
    }

    #[Test]
    public function with_replaces_a_value_that_is_already_there(): void
    {
        $this->assertSame(['a' => ['b' => 9]], Path::with('Thing', ['a' => ['b' => 1]], 'a.b', 9));
        $this->assertSame(['a' => 9], Path::with('Thing', ['a' => ['b' => 1]], 'a', 9));
    }

    #[Test]
    public function with_does_not_change_the_array_it_was_given(): void
    {
        $data = ['a' => ['b' => 1]];

        Path::with('Thing', $data, 'a.b', 2);
        Path::with('Thing', $data, 'a.c.d', 2);

        $this->assertSame(['a' => ['b' => 1]], $data);
    }

    #[Test]
    public function with_refuses_to_replace_a_value_that_is_in_the_way(): void
    {
        $this->expectException(InvalidData::class);
        $this->expectExceptionMessage("'a.b' holds a value that is not an array");

        Path::with('Thing', ['a' => ['b' => 'text']], 'a.b.c', 1);
    }

    #[Test]
    public function with_treats_a_null_in_the_way_as_a_value_that_is_in_the_way(): void
    {
        $this->expectException(InvalidData::class);

        Path::with('Thing', ['a' => null], 'a.b', 1);
    }
}
