// The inherited Supabase database schema is unavailable and is not the selected
// Furniture backend. Explicit failure prevents silent fallback to demo records.
export class UnsupportedSupabaseAdapterError extends Error {
  constructor(method) {
    super(`Supabase adapter is disabled for Furniture: ${method} is unavailable.`);
    this.name = 'UnsupportedSupabaseAdapterError';
  }
}

export function createDisabledSupabaseAdapter() {
  return new Proxy(Object.create(null), {
    get(_target, property) {
      // A proxy is not a promise, and should not pretend to implement symbols.
      if (property === 'then' || typeof property !== 'string') return undefined;
      return async () => { throw new UnsupportedSupabaseAdapterError(property); };
    },
  });
}

export function selectAdminAdapter(mode, { demo, bagisto, disabledSupabase }) {
  if (mode === 'supabase') return disabledSupabase;
  if (mode === 'bagisto') return bagisto;
  return demo;
}
