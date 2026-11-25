<?php

declare(strict_types=1);

namespace Keaton\DataDefinition;

use Keaton\DataDefinition\Exceptions\InvalidData;

/**
 * Dot-path reads and writes on a plain array.
 *
 * A path is split on every dot. A payload key that itself contains a dot cannot be reached;
 * that keeps get() and with() exact mirrors of each other.
 *
 * @internal
 */
class Path
{
    /**
     * @param  array<array-key, mixed>  $data
     * @return array{0: bool, 1: mixed} whether the path exists, and its value
     */
    public static function find(array $data, string $path): array
    {
        $current = $data;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return [false, null];
            }

            $current = $current[$segment];
        }

        return [true, $current];
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed> a copy of $data with $value set at $path
     */
    public static function with(string $definition, array $data, string $path, mixed $value): array
    {
        $segments = explode('.', $path);
        $last = array_pop($segments);

        $cursor = &$data;
        $walked = [];

        foreach ($segments as $segment) {
            $walked[] = $segment;

            if (! array_key_exists($segment, $cursor)) {
                $cursor[$segment] = [];
            } elseif (! is_array($cursor[$segment])) {
                throw InvalidData::blockedPath($definition, $path, implode('.', $walked));
            }

            $cursor = &$cursor[$segment];
        }

        $cursor[$last] = $value;
        unset($cursor);

        return $data;
    }
}
