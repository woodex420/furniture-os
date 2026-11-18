import type { AdminApi } from './types';
import type { ApiMode } from '../config/env';

export class UnsupportedSupabaseAdapterError extends Error {
  constructor(method: string);
}
/** Every string-named operation returns a rejected promise; no data is returned. */
export function createDisabledSupabaseAdapter(): AdminApi;
export function selectAdminAdapter(mode: ApiMode, adapters: {
  demo: AdminApi;
  bagisto: AdminApi;
  disabledSupabase: AdminApi;
}): AdminApi;
