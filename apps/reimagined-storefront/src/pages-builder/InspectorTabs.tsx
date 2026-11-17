import { createContext, useState, type ReactNode } from "react";

export const InspectorTabContext = createContext<"style" | "advanced" | null>(null);

/** Puck supplies fully rendered fields; keep them mounted so their local UI state survives tab changes. */
export function InspectorTabs({ children, fields, itemSelector }: { children: ReactNode; fields: Partial<Record<string, ReactNode>>; itemSelector?: unknown }) {
  const [tab, setTab] = useState<"content" | "style" | "advanced">("content");
  if (!itemSelector || !("_inspector" in fields)) return <>{children}</>;
  return <div className="builder-inspector-tabs">
    <div role="tablist" aria-label="Block settings" className="builder-inspector-nav">
      <button type="button" role="tab" aria-selected={tab === "content"} onClick={() => setTab("content")}>Content</button>
      <button type="button" role="tab" aria-selected={tab === "style"} onClick={() => setTab("style")}>Style</button>
      <button type="button" role="tab" aria-selected={tab === "advanced"} onClick={() => setTab("advanced")}>Advanced</button>
    </div>
    <div hidden={tab !== "content"}>{Object.entries(fields).filter(([key]) => key !== "_inspector").map(([key, node]) => <div key={key} className="builder-field-row">{node}</div>)}</div>
    <div hidden={tab === "content"} className="builder-field-row"><InspectorTabContext.Provider value={tab === "advanced" ? "advanced" : "style"}>{fields._inspector}</InspectorTabContext.Provider></div>
  </div>;
}
