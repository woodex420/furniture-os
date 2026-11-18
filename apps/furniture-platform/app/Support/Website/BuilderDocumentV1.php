<?php

declare(strict_types=1);

namespace App\Support\Website;

use Illuminate\Validation\ValidationException;
use JsonException;
use stdClass;

/** Internal experimental document contract, not a route or publishing format. */
final class BuilderDocumentV1
{
    public const MAX_BYTES = 65536;

    /** Validate without silently dropping unknown properties or changing content. */
    public function encode(array $document): string
    {
        if (! $this->keysAre($document, ['schema_version', 'sections']) || $document['schema_version'] !== 1 || ! is_array($document['sections']) || ! array_is_list($document['sections']) || count($document['sections']) > 10) {
            $this->invalid();
        }
        $ids = [];
        foreach ($document['sections'] as $section) {
            if (! is_array($section) || ! $this->keysAre($section, ['id', 'type', 'props']) || ! $this->validId($section['id']) || isset($ids[$section['id']]) || $section['type'] !== 'article_text' || ! is_array($section['props']) || ! $this->keysAre($section['props'], ['summary', 'body'])) {
                $this->invalid();
            }
            $ids[$section['id']] = true;
            if (! $this->validText($section['props']['summary'], 300) || ! $this->validText($section['props']['body'], 12000)) {
                $this->invalid();
            }
        }
        try {
            $json = json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR, 32);
        } catch (JsonException) {
            $this->invalid();
        }
        if (strlen($json) > self::MAX_BYTES) {
            $this->invalid();
        }

        return $json;
    }

    public function decode(string $json): array
    {
        if (strlen($json) > self::MAX_BYTES) {
            $this->invalid();
        }
        try {
            // Preserve the JSON object/array distinction before associative decoding;
            // otherwise an empty object could be mistaken for an empty section list.
            $shape = json_decode($json, false, 32, JSON_THROW_ON_ERROR);
            if (! $shape instanceof stdClass || ! isset($shape->sections) || ! is_array($shape->sections)) {
                $this->invalid();
            }
            foreach ($shape->sections as $section) {
                if (! $section instanceof stdClass || ! isset($section->props) || ! $section->props instanceof stdClass) {
                    $this->invalid();
                }
            }
            $document = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->invalid();
        }
        if (! is_array($document)) {
            $this->invalid();
        }
        $this->encode($document);

        return $document;
    }

    public function validId(mixed $id): bool
    {
        return is_string($id) && preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', $id) === 1;
    }

    private function validText(mixed $value, int $max): bool
    {
        return is_string($value) && mb_check_encoding($value, 'UTF-8') && trim($value) !== '' && mb_strlen($value, 'UTF-8') <= $max;
    }

    private function keysAre(array $value, array $expected): bool
    {
        $keys = array_keys($value);
        sort($keys);
        sort($expected);

        return $keys === $expected;
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['document' => 'Unsupported or invalid page document. No content was saved.']);
    }
}
