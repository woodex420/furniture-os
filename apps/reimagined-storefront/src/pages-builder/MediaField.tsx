import { useEffect, useState } from "react";
import { getAdminApi } from "@/api";
import type { AdminMediaAsset } from "@/api/types";

/** Select an existing local demo asset or enter a URL. No file is uploaded here. */
export function MediaField({ value, onChange, readOnly }: { value: unknown; onChange: (value: string) => void; readOnly?: boolean }) {
  const [assets, setAssets] = useState<AdminMediaAsset[]>([]);
  const [open, setOpen] = useState(false);
  const [error, setError] = useState(false);
  useEffect(() => {
    if (!open) return;
    let active = true;
    setError(false);
    getAdminApi().listMediaAssets().then((items) => { if (active) setAssets(items.filter((item) => item.mime.startsWith("image/"))); })
      .catch(() => { if (active) setError(true); });
    return () => { active = false; };
  }, [open]);
  const url = typeof value === "string" ? value : "";
  return <div className="space-y-2">
    <input aria-label="Image URL" className="w-full rounded border border-border bg-background px-2 py-2 text-sm" value={url} disabled={readOnly} onChange={(e) => onChange(e.target.value)} placeholder="/assets/image.webp or https://…" />
    <button type="button" disabled={readOnly} onClick={() => setOpen(!open)} aria-expanded={open} className="rounded border border-border bg-background px-3 py-1.5 text-xs font-semibold hover:bg-muted">{open ? "Close media library" : "Choose from media library"}</button>
    {open && <div className="max-h-56 overflow-y-auto rounded border border-border p-2">
      <p className="mb-2 text-xs text-muted-foreground">Choose an existing image. Add uploads in the Media page.</p>
      {error ? <p className="text-xs text-red-700">Could not load media. You can still enter an image URL.</p> : assets.length === 0 ? <p className="text-xs text-muted-foreground">No images available.</p> : <div className="grid grid-cols-3 gap-2">{assets.map((asset) => <button type="button" key={asset.id} title={asset.name} aria-label={`Use image ${asset.name}`} className="overflow-hidden rounded border border-border hover:ring-2 hover:ring-accent" onClick={() => { onChange(asset.url); setOpen(false); }}><img src={asset.url} alt="" className="h-16 w-full object-cover" /><span className="block truncate p-1 text-left text-[10px]">{asset.name}</span></button>)}</div>}
    </div>}
  </div>;
}
