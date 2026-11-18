// Vercel ignores a build when this command exits 0. Only the isolated design
// preview is allowed; missing or unrecognized environments fail closed.
if (process.env.VERCEL_ENV === 'preview') {
  console.log('Building the synthetic dashboard preview, not the Laravel app.');
  process.exitCode = 1;
} else {
  console.log('Skipping Vercel deployment: this repository has no approved production release.');
  process.exitCode = 0;
}
