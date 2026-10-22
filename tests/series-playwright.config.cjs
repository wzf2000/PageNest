const { defineConfig } = require('@playwright/test');
const path = require('node:path');
const base = require('../playwright.config.cjs');
module.exports = defineConfig({
  ...base,
  testDir: __dirname,
  testMatch: 'series.spec.cjs',
  outputDir: '../.runtime/series-browser-results',
  workers: 1,
  projects: ['chromium', 'firefox', 'webkit'].map((browserName) => ({
    name: browserName,
    use: { browserName },
  })),
  webServer: { ...base.webServer, cwd: path.resolve(__dirname, '..') },
});
