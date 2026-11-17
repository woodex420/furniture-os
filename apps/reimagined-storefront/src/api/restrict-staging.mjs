// Defense in depth for the protected staging preview. This browser-side gate is
// not an authorization boundary; Laravel still enforces server-side permissions.
const restrictedMethods = new Set(['createOrder', 'createLead', 'captureChatOffline', 'postChatVisitorMessage']);

export function restrictStagingSubmissions(api, restricted) {
  if (!restricted) return api;
  return new Proxy(api, {
    get(target, key, receiver) {
      if (restrictedMethods.has(key)) {
        return async () => { throw new Error('Submissions are unavailable in the staging preview.'); };
      }
      return Reflect.get(target, key, receiver);
    },
  });
}
