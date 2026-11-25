<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

class PlainObject
{
    /** @var array<string, string> */
    public array $profile = ['name' => 'Ana Costa'];

    protected string $hidden = 'protected';

    private string $secret = 'private';

    public function reveal(): string
    {
        return $this->hidden.$this->secret;
    }
}
