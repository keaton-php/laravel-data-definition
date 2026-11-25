<?php

declare(strict_types=1);

namespace Keaton\DataDefinition\Exceptions;

use RuntimeException;

/** Base class for the package's own exceptions. A JsonException from json(), or an exception thrown inside your own cast, passes through unchanged. */
class DataDefinitionException extends RuntimeException {}
