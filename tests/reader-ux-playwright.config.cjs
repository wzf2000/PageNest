const { defineConfig } = require('@playwright/test');
const path = require('node:path');
const base = require('../playwright.config.cjs');
// Reuse the actual reader/admin regressions without multiplying the full browser suite.
module.exports = defineConfig({
  ...base,
  testDir: __dirname,
  testMatch: [
    'reader-preferences.spec.cjs',
    'appearance-discovery.spec.cjs',
    'presentation.spec.cjs',
    'login-layout.spec.cjs',
  ],
  testIgnore: [],
  outputDir: '../.runtime/reader-ux-webkit-results',
  forbidOnly: !!process.env.CI,
  workers: 1,
  projects: [{ name: 'webkit', use: { browserName: 'webkit' } }],
  webServer: { ...base.webServer, cwd: path.resolve(__dirname, '..') },
});
