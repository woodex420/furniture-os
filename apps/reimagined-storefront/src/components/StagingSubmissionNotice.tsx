import { config } from "@/config/env";

/** Describes disabled test actions; protection and authorization remain server-side. */
export function StagingSubmissionNotice() {
  if (!config.restrictedStaging) return null;
  return (
    <p role="status" className="rounded-sm border border-amber-500 bg-amber-50 p-3 text-sm text-amber-950">
      Staging preview: orders and enquiries are disabled. Do not enter real customer information.
    </p>
  );
}
