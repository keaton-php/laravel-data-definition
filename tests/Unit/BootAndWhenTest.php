<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Unit;

use Keaton\DataDefinition\Tests\Fixtures\Configurable;
use Keaton\DataDefinition\Tests\Fixtures\Person;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BootAndWhenTest extends TestCase
{
    #[Test]
    public function boot_can_read_the_payload_and_decide_the_aliases(): void
    {
        $v2 = Configurable::define(aliases: ['email' => 'contact.email'], payload: ['contact' => ['email' => 'a@example.com']]);
        $v1 = Configurable::define(aliases: ['email' => 'email_address'], payload: ['email_address' => 'b@example.com']);

        $this->assertSame('a@example.com', $v2->email);
        $this->assertSame('b@example.com', $v1->email);
    }

    #[Test]
    public function when_runs_the_callback_for_a_truthy_condition_and_returns_its_result(): void
    {
        $person = Person::make(Person::sample());

        $changed = $person->when(true, fn (Person $person) => $person->with(['email' => 'new@example.com']));

        $this->assertSame('new@example.com', $changed->email);
        $this->assertSame('ana@example.com', $person->email);
    }

    #[Test]
    public function when_returns_the_same_instance_for_a_falsy_condition(): void
    {
        $person = Person::make(Person::sample());

        $this->assertSame($person, $person->when(false, fn (Person $person) => $person->with(['email' => 'x'])));
    }

    #[Test]
    public function unless_is_the_other_way_round(): void
    {
        $person = Person::make(Person::sample());

        $this->assertSame($person, $person->unless(true, fn (Person $person) => $person->with(['email' => 'x'])));
        $this->assertSame('x', $person->unless(false, fn (Person $person) => $person->with(['email' => 'x']))->email);
    }
}
