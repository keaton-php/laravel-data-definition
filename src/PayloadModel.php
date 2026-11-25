<?php

declare(strict_types=1);

namespace Keaton\DataDefinition;

use Illuminate\Database\Eloquent\Model;

/**
 * The $model a CastsAttributes cast receives.
 *
 * The CastsAttributes contract wants an Eloquent model as its first argument, and a definition
 * has none. This is a throwaway stand-in that carries the raw payload as its attributes. It is
 * never saved, never connected to a database and never shared between reads.
 *
 * @internal
 */
class PayloadModel extends Model
{
    /** There is no table: a cast reading created_at or updated_at must not reach for a connection. */
    public $timestamps = false;

    /** Keeps an 'id' from the payload as it was given, instead of casting it to an integer. */
    public $incrementing = false;
}
