import base from '../reimagined-storefront/tailwind.config.ts';
export default {
  ...base,
  content: [
    '../reimagined-storefront/src/**/*.{ts,tsx}',
    './src/**/*.{astro,ts,tsx}',
  ],
};
