<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Tests\Fixtures;

use Keaton\DataDefinition\DataDefinition;

/** A definition with methods that try to retarget an alias or swap a cast after it was built. */
class SelfEditing extends DataDefinition
{
    protected array $aliases = ['email' => 'contact.email', 'age' => 'age'];

    protected array $casts = ['age' => 'int'];

    public function retarget(): void
    {
        $this->aliases['email'] = 'contact.other';
        $this->aliases['added'] = 'contact.email';
    }

    public function recast(): void
    {
        $this->casts['age'] = 'string';
    }
}
