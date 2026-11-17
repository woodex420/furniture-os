import test from 'node:test';
import assert from 'node:assert/strict';
import { restrictStagingSubmissions } from '../src/api/restrict-staging.mjs';

test('restricted staging blocks orders, leads and chat writes without calling the adapter', async () => {
  let calls = 0;
  const api = {
    createOrder: async () => { calls++; return 'order'; },
    createLead: async () => { calls++; return 'lead'; },
    captureChatOffline: async () => { calls++; return 'capture'; },
    postChatVisitorMessage: async () => { calls++; return 'message'; },
    listShowrooms: async () => ['synthetic'],
  };
  const gated = restrictStagingSubmissions(api, true);
  for (const method of ['createOrder', 'createLead', 'captureChatOffline', 'postChatVisitorMessage']) {
    await assert.rejects(gated[method]({}), /unavailable in the staging preview/);
  }
  assert.equal(calls, 0);
  assert.deepEqual(await gated.listShowrooms(), ['synthetic']);
  assert.equal(restrictStagingSubmissions(api, false), api);
  assert.equal(await restrictStagingSubmissions(api, false).createLead({}), 'lead');
  assert.equal(calls, 1);
});
