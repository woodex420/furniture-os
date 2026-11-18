<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Support\Website\BuilderDocumentRevisions;
use App\Support\Website\BuilderDocumentV1;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

        return view('workspace.builder-html-proof', ['revision' => $row->revision, 'props' => $document['sections'][0]['props']]);
    }

    public function update(Request $request, BuilderDocumentV1 $contract, BuilderDocumentRevisions $revisions): RedirectResponse
    {
        $this->requireProof();
        // An application-level bound supplements, but cannot replace, a web-server body limit.
        abort_if(strlen($request->getContent()) > 98304, 413);
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:1', 'max:4294967294'],
            'summary' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:12000'],
        ]);
        [, $document] = $this->fixture($contract);
        $document['sections'][0]['props'] = ['summary' => $data['summary'], 'body' => $data['body']];
        $revisions->edit((int) $request->user()->id, self::DOCUMENT_ID, (int) $data['revision'], $document);

        return redirect()->route('admin.builder-html-proof.edit')->with('status', 'Synthetic section saved. The next HTML request reads the new revision.');
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
        // Never expose an arbitrary multi-page document through this experimental URL.
        abort_unless(count($document['sections']) === 1 && $document['sections'][0]['id'] === self::SECTION_ID && $document['sections'][0]['type'] === 'article_text', 503, 'Synthetic proof fixture has an unexpected shape.');

        return [$row, $document];
    }
}
