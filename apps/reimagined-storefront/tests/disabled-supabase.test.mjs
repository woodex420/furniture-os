import test from 'node:test';
import assert from 'node:assert/strict';
import { createDisabledSupabaseAdapter, selectAdminAdapter, UnsupportedSupabaseAdapterError } from '../src/api/disabled-supabase.mjs';

test('Supabase mode rejects reads and writes, including newly named operations, without demo fallback', async () => {
  let demoCalls = 0;
  const demo = { listProducts: async () => { demoCalls++; return ['demo']; } };
  const bagisto = {};
  const disabled = createDisabledSupabaseAdapter();
  const api = selectAdminAdapter('supabase', { demo, bagisto, disabledSupabase: disabled });
  for (const method of ['listProducts', 'createOrder', 'createLead', 'getPage', 'futureMethod']) {
    await assert.rejects(api[method]({}), (error) => error instanceof UnsupportedSupabaseAdapterError && error.message.includes(method));
  }
  assert.equal(demoCalls, 0);
  assert.equal(await Promise.resolve(api), api, 'adapter is not a thenable');
  assert.equal(api[Symbol.iterator], undefined);
  assert.equal(selectAdminAdapter('demo', { demo, bagisto, disabledSupabase: disabled }), demo);
  assert.equal(selectAdminAdapter('static', { demo, bagisto, disabledSupabase: disabled }), demo);
  assert.equal(selectAdminAdapter('bagisto', { demo, bagisto, disabledSupabase: disabled }), bagisto);
  assert.deepEqual(await demo.listProducts(), ['demo']);
});
