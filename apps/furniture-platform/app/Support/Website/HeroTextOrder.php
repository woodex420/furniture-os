<?php

declare(strict_types=1);

namespace App\Support\Website;

use LogicException;

/** Limited first-slide layout contract, not a general page document. */
final class HeroTextOrder
{
    public const TITLE_FIRST = 'title_first';

    public const SUBTITLE_FIRST = 'subtitle_first';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::TITLE_FIRST, self::SUBTITLE_FIRST];
    }

    public static function resolve(?string $value): string
    {
        if ($value === null) {
            return self::TITLE_FIRST;
        }
        if (! in_array($value, self::values(), true)) {
            throw new LogicException('Unrecognized saved home hero text order.');
        }

        return $value;
    }
}
