import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {playwright} from 'vite-plus/test/browser-playwright';

import {defineConfig} from 'vite-plus';

import {storybookTest} from '@storybook/addon-vitest/vitest-plugin';

const dirname =
  typeof __dirname !== 'undefined'
    ? __dirname
    : path.dirname(fileURLToPath(import.meta.url));

// More info at: https://storybook.js.org/docs/next/writing-tests/integrations/vitest-addon
export default defineConfig({
  test: {
    projects: [
      {
        resolve: {
          tsconfigPaths: true,
        },
        test: {
          name: 'utilities',
          root: './src/utilities',
          environment: 'happy-dom',
        },
      },
      {
        resolve: {
          tsconfigPaths: true,
        },
        test: {
          name: 'services',
          root: './src/services',
          environment: 'happy-dom',
        },
      },
      {
        resolve: {
          tsconfigPaths: true,
        },
        test: {
          name: 'components',
          root: './src/components',
          environment: 'happy-dom',
        },
      },
      {
        resolve: {
          tsconfigPaths: true,
        },
        test: {
          name: 'mixins',
          root: './src/mixins',
          environment: 'happy-dom',
        },
      },
      {
        resolve: {
          tsconfigPaths: true,
        },
        test: {
          name: 'controllers',
          root: './src/controllers',
          environment: 'happy-dom',
        },
      },
      {
        resolve: {
          tsconfigPaths: true,
        },
        test: {
          name: 'factory',
          root: './src/factory',
          environment: 'happy-dom',
        },
      },
      {
        resolve: {
          tsconfigPaths: true,
        },
        test: {
          name: 'styles',
          root: './src/styles',
          environment: 'happy-dom',
        },
      },
      {
        extends: true,
        plugins: [
          // The plugin will run tests for the stories defined in your Storybook config
          // See options at: https://storybook.js.org/docs/next/writing-tests/integrations/vitest-addon#storybooktest
          storybookTest({configDir: path.join(dirname, '.storybook')}),
        ],
        test: {
          name: 'storybook',
          browser: {
            enabled: true,
            headless: true,
            provider: playwright(),
            instances: [{browser: 'chromium'}],
          },
          setupFiles: ['.storybook/vitest.setup.ts'],
        },
      },
    ],
  },
});
