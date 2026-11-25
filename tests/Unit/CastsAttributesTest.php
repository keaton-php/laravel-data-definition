<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use DateTimeImmutable;
use Keaton\DataDefinition\Exceptions\InvalidCast;
use Keaton\DataDefinition\PayloadModel;
use Keaton\DataDefinition\Tests\Fixtures\AbstractCast;
use Keaton\DataDefinition\Tests\Fixtures\ArgsCast;
use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use Keaton\DataDefinition\Tests\Fixtures\ExplodingCast;
use Keaton\DataDefinition\Tests\Fixtures\NeedsTwoArguments;
use Keaton\DataDefinition\Tests\Fixtures\NotACast;
use Keaton\DataDefinition\Tests\Fixtures\PrefixCast;
use Keaton\DataDefinition\Tests\Fixtures\UpperCast;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class CastsAttributesTest extends TestCase
{
    #[Test]
    public function it_can_use_a_casts_attributes_class(): void
    {
        $definition = Configurable::define(
            aliases: ['shout' => 'a.b'],
            casts: ['shout' => UpperCast::class],
            payload: ['a' => ['b' => 'hi']],
        );

        $this->assertSame('HI', $definition->shout);
    }

    #[Test]
    public function the_cast_receives_a_stand_in_model_the_key_asked_for_the_raw_value_and_the_whole_payload(): void
    {
        $definition = Configurable::define(
            aliases: ['seen' => 'a.b'],
            casts: ['seen' => ArgsCast::class],
            payload: ['a' => ['b' => 'raw'], 'other' => 'sibling'],
        );

        [$modelClass, $key, $value, $attributes, $modelAttributes] = $definition->seen;

        $this->assertSame(PayloadModel::class, $modelClass);
        $this->assertSame('seen', $key);
        $this->assertSame('raw', $value);
        $this->assertSame($definition->data(), $attributes);
        $this->assertSame($definition->data(), $modelAttributes);
        $this->assertSame('sibling', $attributes['other']);
    }

    #[Test]
    public function the_stand_in_model_keeps_a_string_id_and_never_reaches_for_timestamps(): void
    {
        $model = (new PayloadModel)->setRawAttributes(['id' => 'SH-1042'], true);

        $this->assertSame('SH-1042', $model->getAttribute('id'));
        $this->assertFalse($model->usesTimestamps());
        $this->assertFalse($model->getIncrementing());
    }

    #[Test]
    public function the_key_is_the_one_that_was_asked_for_when_read_by_path(): void
    {
        $definition = Configurable::define(
            casts: ['a.b' => ArgsCast::class],
            payload: ['a' => ['b' => 'raw']],
        );

        $this->assertSame('a.b', $definition->get('a.b')[1]);
    }

    #[Test]
    public function the_cast_receives_a_nested_array_value_as_it_is(): void
    {
        $definition = Configurable::define(
            aliases: ['seen' => 'a'],
            casts: ['seen' => ArgsCast::class],
            payload: ['a' => ['b' => ['c' => 1]]],
        );

        $this->assertSame(['b' => ['c' => 1]], $definition->seen[2]);
    }

    #[Test]
    public function set_is_never_called_whatever_the_definition_does(): void
    {
        // ArgsCast::set() throws, so any call to it would fail this test.
        $definition = Configurable::define(
            aliases: ['seen' => 'a'],
            casts: ['seen' => ArgsCast::class],
            payload: ['a' => 'raw'],
        );

        $definition->get('seen');
        $definition->toArray();
        $definition->json();
        $changed = $definition->with(['seen' => 'new']);

        $this->assertSame('new', $changed->seen[2]);
    }

    #[Test]
    public function a_present_null_is_never_handed_to_the_cast(): void
    {
        $definition = Configurable::define(
            aliases: ['seen' => 'a'],
            casts: ['seen' => ArgsCast::class],
            payload: ['a' => null],
        );

        $this->assertNull($definition->seen);
    }

    #[Test]
    public function it_can_use_a_cast_object_with_its_own_constructor_arguments(): void
    {
        $definition = Configurable::define(
            aliases: ['tag' => 'a'],
            casts: ['tag' => new PrefixCast('<', '>')],
            payload: ['a' => 'hi'],
        );

        $this->assertSame('<hi>', $definition->tag);
    }

    #[Test]
    public function it_can_pass_constructor_arguments_in_the_class_string_the_way_eloquent_does(): void
    {
        $both = Configurable::define(
            aliases: ['tag' => 'a'],
            casts: ['tag' => PrefixCast::class.':<,>'],
            payload: ['a' => 'hi'],
        );
        $one = Configurable::define(
            aliases: ['tag' => 'a'],
            casts: ['tag' => PrefixCast::class.':<'],
            payload: ['a' => 'hi'],
        );

        $this->assertSame('<hi>', $both->tag);
        $this->assertSame('<hi', $one->tag);
    }

    #[DataProvider('wrongNumbersOfArguments')]
    #[Test]
    public function it_rejects_a_cast_string_with_the_wrong_number_of_constructor_arguments(string $cast): void
    {
        $this->expectException(InvalidCast::class);
        $this->expectExceptionMessage('constructor arguments');

        Configurable::define(casts: ['tag' => $cast]);
    }

    /** @return array<string, array{string}> */
    public static function wrongNumbersOfArguments(): array
    {
        return [
            'none given, two needed' => [NeedsTwoArguments::class],
            'one given, two needed' => [NeedsTwoArguments::class.':a'],
            'one given, no constructor' => [UpperCast::class.':a'],
            'three given, two accepted' => [PrefixCast::class.':a,b,c'],
        ];
    }

    #[DataProvider('castsThatAreNotUsable')]
    #[Test]
    public function it_rejects_a_cast_that_is_not_a_casts_attributes_object_or_class(mixed $cast): void
    {
        $this->expectException(InvalidCast::class);

        Configurable::define(casts: ['tag' => $cast]);
    }

    /** @return array<string, array{mixed}> */
    public static function castsThatAreNotUsable(): array
    {
        return [
            'a class that is not a cast' => [NotACast::class],
            'a built-in class' => [DateTimeImmutable::class],
            'an object that is not a cast' => [new stdClass],
            'an abstract cast class' => [AbstractCast::class],
        ];
    }

    #[Test]
    public function it_rejects_a_bad_cast_when_the_definition_is_built_not_when_the_value_is_read(): void
    {
        $this->expectException(InvalidCast::class);

        // No value is ever read here: the declaration alone is enough to fail.
        Configurable::define(casts: ['tag' => NotACast::class]);
    }

    #[Test]
    public function an_exception_thrown_inside_a_cast_comes_out_unchanged(): void
    {
        $definition = Configurable::define(
            aliases: ['seen' => 'a'],
            casts: ['seen' => ExplodingCast::class],
            payload: ['a' => 'x'],
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('the cast itself failed');

        $definition->get('seen');
    }
}
