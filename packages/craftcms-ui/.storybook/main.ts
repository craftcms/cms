import type {StorybookConfig} from '@storybook/web-components-vite';

import {dirname} from 'path';
import {fileURLToPath} from 'url';
import {mergeConfig} from 'vite';
import remarkGfm from 'remark-gfm';

/**
 * This function is used to resolve the absolute path of a package.
 * It is needed in projects that use Yarn PnP or are set up within a monorepo.
 */
function getAbsolutePath(value: string): string {
  return dirname(fileURLToPath(import.meta.resolve(`${value}/package.json`)));
}

const config: StorybookConfig = {
  stories: ['../src/**/*.mdx', '../src/**/*.stories.@(js|jsx|mjs|ts|tsx)'],
  addons: [
    getAbsolutePath('@chromatic-com/storybook'),
    getAbsolutePath('@storybook/addon-themes'),
    {
      name: getAbsolutePath('@storybook/addon-docs'),
      // GitHub-flavored Markdown, so docs can use tables.
      options: {
        mdxPluginOptions: {mdxCompileOptions: {remarkPlugins: [remarkGfm]}},
      },
    },
    getAbsolutePath('@storybook/addon-a11y'),
    getAbsolutePath('@storybook/addon-vitest'),
  ],
  framework: {
    name: getAbsolutePath('@storybook/web-components-vite'),
    options: {},
  },

  viteFinal(config, {configType}) {
    return mergeConfig(config, {
      resolve: {
        tsconfigPaths: true,
      },
    });
  },
};
export default config;
