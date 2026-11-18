// Compile-only reference contracts, derived from the upstream snapshot's SQL.
// These do not verify any connected Supabase project or the rest of its schema.
import { supabase } from '../src/integrations/supabase/client';

void supabase.rpc('has_cap', { need: 'page.manage' });
void supabase.rpc('has_role', { need: 'owner' });
void supabase.rpc('storefront_page_snapshot', { p_slug: 'sample' });
// @ts-expect-error missing required permission argument
void supabase.rpc('has_cap', {});
// @ts-expect-error slug is text, not a number
void supabase.rpc('storefront_page_snapshot', { p_slug: 12 });
// @ts-expect-error schema beyond the reviewed reference signatures is unknown
void supabase.rpc('unverified_function', {});
