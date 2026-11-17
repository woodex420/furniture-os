<?php

declare(strict_types=1);

namespace App\Support\Customers;

/** A literal, bound LIKE pattern; %, _ and the escape character are not wildcards. */
final class SearchPattern
{
    public static function contains(string $text): string
    {
        $text = mb_strtolower(trim($text));

        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text).'%';
    }
}
