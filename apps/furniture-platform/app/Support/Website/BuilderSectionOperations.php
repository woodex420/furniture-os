<?php

declare(strict_types=1);

namespace App\Support\Website;

use Illuminate\Validation\ValidationException;

/** Pure V1 section operations. Callers must separately authorize and save with revisions. */
final class BuilderSectionOperations
{
    public function __construct(private readonly BuilderDocumentV1 $contract) {}

    public function addArticle(array $document, string $id, string $summary, string $body): array
    {
        $this->contract->encode($document);
        $document['sections'][] = ['id' => $id, 'type' => 'article_text', 'props' => ['summary' => $summary, 'body' => $body]];

        return $this->validated($document);
    }

    public function duplicate(array $document, string $sourceId, string $newId): array
    {
        $this->contract->encode($document);
        $index = $this->find($document, $sourceId);
        $copy = $document['sections'][$index];
        $copy['id'] = $newId;
        array_splice($document['sections'], $index + 1, 0, [$copy]);

        return $this->validated($document);
    }

    public function move(array $document, string $id, int $direction): array
    {
        $this->contract->encode($document);
        if ($direction !== -1 && $direction !== 1) {
            $this->invalid();
        }
        $index = $this->find($document, $id);
        $destination = $index + $direction;
        if ($destination < 0 || $destination >= count($document['sections'])) {
            $this->invalid();
        }
        [$document['sections'][$index], $document['sections'][$destination]] = [$document['sections'][$destination], $document['sections'][$index]];

        return $this->validated($document);
    }

    public function remove(array $document, string $id): array
    {
        $this->contract->encode($document);
        $index = $this->find($document, $id);
        array_splice($document['sections'], $index, 1);

        return $this->validated($document);
    }

    private function find(array $document, string $id): int
    {
        foreach ($document['sections'] as $index => $section) {
            if ($section['id'] === $id) {
                return $index;
            }
        }
        $this->invalid();
    }

    private function validated(array $document): array
    {
        $this->contract->encode($document);

        return $document;
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['section' => 'This section operation is invalid. No content was saved.']);
    }
}
