<?php

declare(strict_types=1);

namespace App\Support\Website;

use LogicException;

/** Stable identifiers for the five images already bundled with the Reimagin hero. */
final class HeroImage
{
    public const DEFAULT = 'workspace';

    /** @return array<string, string> */
    public static function choices(): array
    {
        return [
            'workspace' => 'Workspace',
            'meeting' => 'Meeting room',
            'executive' => 'Executive office',
            'reception' => 'Reception',
            'manager' => 'Manager cabin',
        ];
    }

    public static function resolve(?string $value): string
    {
        $value ??= self::DEFAULT;
        if (! array_key_exists($value, self::choices())) {
            throw new LogicException('Unrecognized saved home hero image.');
        }

        return $value;
    }
}
