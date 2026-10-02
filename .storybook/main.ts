import type {StorybookConfig} from '@storybook/vue3-vite';
import {createRequire} from 'module';
import {dirname, join} from 'path';
import {fileURLToPath} from 'url';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

const require = createRequire(import.meta.url);
const __dirname = dirname(fileURLToPath(import.meta.url));

/**
 * This function is used to resolve the absolute path of a package.
 * It is needed in projects that use Yarn PnP or are set up within a monorepo.
 */
function getAbsolutePath(value: string): string {
  return dirname(require.resolve(join(value, 'package.json')));
}

const config: StorybookConfig = {
  stories: [
    '../resources/js/**/*.mdx',
    '../resources/js/**/*.stories.@(js|jsx|mjs|ts|tsx)',
  ],
  // `craft-icon` fetches `/vendor/craft/icons/<family>/<name>.svg`, which in the
  // CP is a symlink to `cms-assets/resources`. Without this every icon in every
  // story 404s and renders nothing.
  staticDirs: [
    {from: '../cms-assets/resources/icons', to: '/vendor/craft/icons'},
  ],
  addons: [
    getAbsolutePath('@storybook/addon-themes'),
    getAbsolutePath('@storybook/addon-docs'),
    getAbsolutePath('@storybook/addon-a11y'),
  ],
  framework: {
    name: getAbsolutePath('@storybook/vue3-vite') as '@storybook/vue3-vite',
    options: {
      docgen: 'vue-component-meta',
    },
  },
  viteFinal(config) {
    // Storybook's vue3-vite framework adds its own Vue plugin with default options.
    // We need to configure `isCustomElement` so Vue treats `craft-*` tags as web
    // components (from @craftcms/ui) rather than trying to resolve them as Vue
    // components. Since Vite's mergeConfig doesn't deep-merge plugin options,
    // we remove Storybook's Vue plugin and add our own with the correct config.
    const filteredPlugins = (config.plugins || []).flat().filter((plugin) => {
      if (plugin && typeof plugin === 'object' && 'name' in plugin) {
        return plugin.name !== 'vite:vue';
      }
      return true;
    });

    return {
      ...config,
      plugins: [
        ...filteredPlugins,
        tailwindcss(),
        vue({
          template: {
            compilerOptions: {
              isCustomElement: (tag) => tag.startsWith('craft-'),
            },
          },
        }),
      ],
      resolve: {
        ...config.resolve,
        alias: {
          ...(config.resolve?.alias || {}),
          '@': join(__dirname, '../resources/js'),
          vue: 'vue/dist/vue.esm-bundler.js',
          // Mock Inertia for Storybook
          '@inertiajs/vue3': join(__dirname, 'inertia-mock.js'),
        },
      },
    };
  },
};

export default config;
