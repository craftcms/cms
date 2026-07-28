import craftPlaywright from '@craftcms/playwright';

/**
 * Default Playwright config: runs tests-playwright/ against the Testbench
 * workbench install (`composer serve`). Run with `npm run test:e2e`.
 *
 * The ddev-based harness uses playwright.ddev.config.cjs via the
 * `craft-playwright` CLI.
 */
export default craftPlaywright.getWorkbenchConfig();
