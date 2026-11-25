<?php

/*
 * Small classes the documentation pages use without showing: a sample payload and a value object.
 * The docs tests (tests/Feature/DocsExamplesTest.php) load this file before running the page examples.
 * They live in the global namespace because the pages show them that way.
 */

class ExamplePayloads
{
    /** A customer as a billing service might send it. */
    public static function customer(): array
    {
        return [
            'data' => [
                'contact' => ['primary_email' => 'ana@example.com', 'phone' => null],
                'created' => '2026-03-02T09:15:00Z',
                'seats' => '5',
            ],
        ];
    }
}

class Weight
{
    public function __construct(
        public readonly float $value,
        public readonly string $unit,
    ) {}

    public function toKilograms(): float
    {
        return round(match ($this->unit) {
            'kg' => $this->value,
            'lb' => $this->value * 0.45359237,
        }, 2);
    }

    public function __toString(): string
    {
        return "{$this->value} {$this->unit}";
    }
}
