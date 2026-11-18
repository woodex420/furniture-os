import { createClient } from '@supabase/supabase-js';
import type { Database } from './types';

// Compatibility for unreachable legacy UI code only. Never use configured
// provider credentials. Its HTTP transport rejects requests; no realtime client
// is used by this preview. Furniture uses Laravel; any future integration needs
// a new implementation and independent security review.
export const supabase = createClient<Database>('https://unconfigured.invalid', 'disabled', {
  global: {
    fetch: async () => { throw new Error('Supabase is disabled in the Furniture preview.'); },
  },
  auth: {
    persistSession: false,
    autoRefreshToken: false,
    detectSessionInUrl: false,
  },
});
