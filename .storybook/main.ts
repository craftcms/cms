import type {StorybookConfig} from '@storybook/vue3-vite';
import {dirname, join} from 'path';
import {mergeConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

/**
 * This function is used to resolve the absolute path of a package.
 * It is needed in projects that use Yarn PnP or are set up within a monorepo.
 */
function getAbsolutePath(value: string): string {
  return dirname(require.resolve(join(value, 'package.json')));
}

const config: StorybookConfig = {
  stories: ['../resources/js/**/*.mdx', '../resources/js/**/*.stories.@(js|jsx|mjs|ts|tsx)'],
  addons: [
    getAbsolutePath('@storybook/addon-themes'),
    getAbsolutePath('@storybook/addon-docs'),
    getAbsolutePath('@storybook/addon-a11y'),
  ],
  framework: {
    name: getAbsolutePath('@storybook/vue3-vite') as '@storybook/vue3-vite',
    options: {},
  },
  viteFinal(config) {
    return mergeConfig(config, {
      plugins: [
        tailwindcss(),
        vue({
          template: {
            compilerOptions: {
              // Treat craft-* tags as custom elements (web components from @craftcms/cp)
              isCustomElement: (tag) => tag.startsWith('craft-'),
            },
          },
        }),
      ],
      resolve: {
        alias: {
          '@': join(__dirname, '../resources/js'),
          vue: 'vue/dist/vue.esm-bundler.js',
        },
      },
    });
  },
};

export default config;
