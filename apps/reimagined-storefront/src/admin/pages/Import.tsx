import { FileSpreadsheet } from "lucide-react";

/** The legacy demo admin is not mounted in the Furniture storefront. */
export default function AdminImport() {
  return (
    <section className="mx-auto max-w-5xl space-y-4 p-4 sm:p-6" aria-labelledby="import-title">
      <div className="flex items-center gap-3">
        <FileSpreadsheet className="h-6 w-6 text-muted-foreground" aria-hidden="true" />
        <h1 id="import-title" className="text-lg font-bold text-foreground">Import data</h1>
      </div>
      <div role="status" className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        <strong>Import is unavailable.</strong> The inherited demo spreadsheet parser was removed for safety.
        No files can be uploaded or imported here. A secure Furniture import workflow is still to be built.
      </div>
    </section>
  );
}
