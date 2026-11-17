import { useMemo } from "react";
import type { AdminService } from "@/api/types";
import { services } from "@/data/services";
import { useServiceCopy, type ServiceCopy } from "@/lib/useServiceCopy";

// This preview uses the original bundled service definitions plus the narrow
// Furniture copy bridge. It does not read or modify the legacy demo database.
const staticServices: AdminService[] = services.map((s, i) => ({
  ...s,
  active: true,
  sortOrder: (i + 1) * 10,
  createdAt: "",
  updatedAt: "",
}));

export function useServices(): { services: AdminService[]; ready: boolean; copy: ServiceCopy } {
  const copy = useServiceCopy();
  const withCopy = useMemo(() => staticServices.map((service) => copy[service.slug] ? { ...service, ...copy[service.slug] } : service), [copy]);
  return { services: withCopy, ready: true, copy };
}
