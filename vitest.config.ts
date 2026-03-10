import path from 'node:path';
import {fileURLToPath} from 'node:url';

import vue from '@vitejs/plugin-vue';
import {defineConfig} from 'vitest/config';

import {storybookTest} from '@storybook/addon-vitest/vitest-plugin';

const dirname =
  typeof __dirname !== 'undefined'
    ? __dirname
    : path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.join(dirname, 'resources/js'),
      '@actions': path.join(dirname, 'resources/js/actions'),
    },
  },
  test: {
    projects: [
      {
        extends: true,
        plugins: [storybookTest({configDir: path.join(dirname, '.storybook')})],
        test: {
          name: 'storybook',
          browser: {
            enabled: true,
            headless: true,
            provider: 'playwright',
            instances: [{browser: 'chromium'}],
          },
          setupFiles: ['.storybook/vitest.setup.ts'],
        },
      },
    ],
  },
});
