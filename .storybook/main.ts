import type {StorybookConfig} from '@storybook/vue3-vite';
import {mergeConfig} from 'vite';
import {resolve} from 'path';
import remarkGfm from 'remark-gfm';

const config: StorybookConfig = {
  stories: [
    '../resources/js/components/**/*.mdx',
    '../resources/js/components/**/*.stories.@(js|jsx|mjs|ts|tsx|vue)',
  ],
  addons: [
    '@storybook/addon-links',
    '@storybook/addon-a11y',
    {
      name: '@storybook/addon-docs',
      options: {
        mdxPluginOptions: {
          mdxCompileOptions: {
            remarkPlugins: [remarkGfm],
          },
        },
      },
    },
  ],
  framework: {
    name: '@storybook/vue3-vite',
    options: {},
  },
  viteFinal(config) {
    return mergeConfig(config, {
      resolve: {
        alias: {
          '@': resolve(__dirname, '../resources/js'),
          '@actions': resolve(__dirname, '../resources/js/actions'),
        },
      },
    });
  },
};

export default config;
