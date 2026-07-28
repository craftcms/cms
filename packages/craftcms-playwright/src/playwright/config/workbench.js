/* jshint esversion: 9, strict: false */
const path = require('path');
const crypto = require('crypto');
const {devices} = require('@playwright/test');

/**
 * Playwright config for testing the CP against the Testbench workbench
 * install (`composer serve`) — no docker/ddev required. The ddev-based
 * config lives in ./config.js and is used by the `craft-playwright` CLI.
 *
 * Playwright boots `workbench:serve` itself (or reuses one already running
 * on the stable port) and authenticates via the workbench's
 * `/_workbench/login/{userId}` route (see tests-playwright/workbench.setup.ts).
 *
 * Env vars:
 * - E2E_PORT      override the serve port (default: same stable port that
 *                 `workbench:serve` derives from the repo path)
 * - E2E_BASE_URL  point at an already-running install instead
 * - E2E_FRESH     pass --fresh to workbench:serve (rebuild + reseed the db)
 * - CI            disables reuse of an already-running server
 */
module.exports = {
  getWorkbenchConfig: (config = {}) => {
    const repoRoot = config.repoRoot ?? process.cwd();
    const testDir = './tests-playwright';

    // Mirrors Workbench\App\Console\Commands\ServeCommand::port()
    const stablePort =
      8000 +
      (parseInt(
        crypto.createHash('sha256').update(repoRoot).digest('hex').slice(0, 4),
        16
      ) %
        1000);

    const port = process.env.E2E_PORT
      ? parseInt(process.env.E2E_PORT, 10)
      : stablePort;
    const origin = process.env.E2E_BASE_URL ?? `http://127.0.0.1:${port}`;

    // Tests use paths relative to the CP root (`./dashboard`), matching the
    // ddev config's convention. Absolute paths (`/admin/entries`) work too.
    const cpTrigger = process.env.CRAFT_CP_TRIGGER ?? 'admin';
    const baseURL = new URL(`./${cpTrigger}/`, origin).href;

    const storageState = path.join(testDir, '.auth/user.json');

    return {
      testDir,
      outputDir: path.join(testDir, 'test-results'),
      fullyParallel: false,
      // All tests share one sqlite install; keep them sequential.
      workers: 1,
      forbidOnly: !!process.env.CI,
      retries: process.env.CI ? 2 : 0,
      reporter: process.env.CI ? [['list'], ['github']] : [['list']],
      timeout: 30 * 1000,
      expect: {timeout: 10 * 1000},
      use: {
        baseURL,
        trace: 'on-first-retry',
      },
      projects: [
        {
          name: 'setup',
          testMatch: /.*\.setup\.ts/,
          use: {baseURL},
        },
        {
          name: 'chromium',
          use: {
            ...devices['Desktop Chrome'],
            storageState,
          },
          dependencies: ['setup'],
          // Suites not yet runnable against the workbench install. They were
          // written for the ddev harness (Codeception fixtures, empty
          // install) or are blocked by known CP bugs — see
          // tests-playwright/README.md before un-ignoring one.
          testIgnore: config.testIgnore ?? [
            '**/tests/elementindex/**', // needs Codeception fixtures (testSorting section)
            '**/tests/elements/**', // needs Codeception fixtures
            '**/tests/matrix/**', // needs Codeception fixtures (testMatrix section)
            '**/tests/pluginstore/**', // needs plugins + external network
            '**/tests/navigation/**', // blocked: /admin/dashboard 500s (Sites twig global)
            '**/tests/settings/**', // written against an empty install; workbench seeds content
          ],
        },
      ],
      webServer: {
        command: `php vendor/bin/testbench workbench:serve --port=${port}${
          process.env.E2E_FRESH ? ' --fresh' : ''
        }`,
        url: new URL('./login', baseURL).href,
        cwd: repoRoot,
        reuseExistingServer: !process.env.CI,
        // --fresh runs the full Craft install + seeders
        timeout: 180 * 1000,
      },
      ...config.overrides,
    };
  },
};
