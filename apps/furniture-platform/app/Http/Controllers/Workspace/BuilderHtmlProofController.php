<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Support\Website\BuilderDocumentRevisions;
use App\Support\Website\BuilderDocumentV1;
use App\Support\Website\BuilderSectionOperations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Fixed synthetic fixture only; not a page registry or production publisher. */
final class BuilderHtmlProofController
{
    public const DOCUMENT_ID = 'b78e0df1-6890-4844-8b53-1a21ebc49433';

    public const SECTION_ID = '06516020-34a2-4615-9b8f-d1c6fa5fb077';

    public function show(BuilderDocumentV1 $contract): Response
    {
        $this->requireProof();
        [$row, $document] = $this->fixture($contract);

        return response()->view('proof.builder-html', ['document' => $document, 'revision' => $row->revision])
            ->header('Cache-Control', 'no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function edit(BuilderDocumentV1 $contract): View
    {
        $this->requireProof();
        [$row, $document] = $this->fixture($contract);

        return view('workspace.builder-html-proof', ['revision' => $row->revision, 'sections' => $document['sections'], 'anchorId' => self::SECTION_ID]);
    }

    public function update(Request $request, BuilderDocumentV1 $contract, BuilderDocumentRevisions $revisions): RedirectResponse
    {
        $this->requireProof();
        // An application-level bound supplements, but cannot replace, a web-server body limit.
        abort_if(strlen($request->getContent()) > 98304, 413);
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:1', 'max:4294967294'],
            'section_id' => ['required', 'string', 'uuid'],
            'summary' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:12000'],
        ]);
        [, $document] = $this->fixture($contract);
        $index = $this->sectionIndex($document, $data['section_id']);
        $document['sections'][$index]['props'] = ['summary' => $data['summary'], 'body' => $data['body']];
        $revisions->edit((int) $request->user()->id, self::DOCUMENT_ID, (int) $data['revision'], $document);

        return redirect()->route('admin.builder-html-proof.edit')->with('status', 'Synthetic section saved. The next HTML request reads the new revision.');
    }

    public function sections(Request $request, BuilderDocumentV1 $contract, BuilderDocumentRevisions $revisions, BuilderSectionOperations $operations): RedirectResponse
    {
        $this->requireProof();
        abort_if(strlen($request->getContent()) > 4096, 413);
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:1', 'max:4294967294'],
            'action' => ['required', 'string', Rule::in(['add', 'duplicate', 'up', 'down', 'remove'])],
            'section_id' => ['required_unless:action,add', 'nullable', 'string', 'uuid'],
        ]);
        [, $document] = $this->fixture($contract);
        $id = $data['section_id'] ?? '';
        if ($data['action'] === 'remove' && $id === self::SECTION_ID) {
            throw ValidationException::withMessages(['section' => 'The required synthetic section cannot be removed.']);
        }
        $updated = match ($data['action']) {
            'add' => $operations->addArticle($document, (string) Str::uuid(), 'Synthetic summary', 'Synthetic article text.'),
            'duplicate' => $operations->duplicate($document, $id, (string) Str::uuid()),
            'up' => $operations->move($document, $id, -1),
            'down' => $operations->move($document, $id, 1),
            'remove' => $operations->remove($document, $id),
        };
        $revisions->edit((int) $request->user()->id, self::DOCUMENT_ID, (int) $data['revision'], $updated);

        return redirect()->to(route('admin.builder-html-proof.edit', absolute: false))->with('status', 'Synthetic section structure saved.');
    }

    private function sectionIndex(array $document, string $id): int
    {
        foreach ($document['sections'] as $index => $section) {
            if ($section['id'] === $id) {
                return $index;
            }
        }
        throw ValidationException::withMessages(['section' => 'The selected section no longer exists. Reload the editor.']);
    }

    private function requireProof(): void
    {
        abort_unless(app()->environment(['local', 'testing']) && config('furniture.builder_html_proof_enabled'), 404);
    }

    private function fixture(BuilderDocumentV1 $contract): array
    {
        $row = DB::table('furniture_builder_documents')->where('id', self::DOCUMENT_ID)->first();
        abort_if($row === null, 404);
        try {
            $document = $contract->decode($row->document);
        } catch (ValidationException) {
            abort(503, 'Synthetic proof document is invalid or unsupported.');
        }
        // Only this fixed test document containing the required synthetic anchor is exposed.
        $ids = array_column($document['sections'], 'id');
        abort_unless(in_array(self::SECTION_ID, $ids, true), 503, 'Synthetic proof fixture has an unexpected shape.');

        return [$row, $document];
    }
}
