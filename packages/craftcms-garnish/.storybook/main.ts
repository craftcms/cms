import type {StorybookConfig} from '@storybook/html-vite';

import {dirname} from 'path';
import {fileURLToPath} from 'url';

/**
 * Resolve the absolute path of a package. Needed in monorepos so Storybook finds
 * the hoisted addons/framework regardless of where node_modules ends up.
 */
function getAbsolutePath(value: string): string {
  return dirname(fileURLToPath(import.meta.resolve(`${value}/package.json`)));
}

/**
 * Storybook config for @craftcms/garnish.
 *
 * Garnish widgets are imperative (`new Modal(container)`, `new DisclosureMenu(trigger)`)
 * operating on plain DOM — NOT web components — so we use the `html-vite` renderer
 * (cp uses `web-components-vite`). Each story's `render()` returns an `HTMLElement`
 * in which the Garnish class is instantiated against the REAL source under `../src`.
 */
const config: StorybookConfig = {
  stories: ['../stories/**/*.stories.@(ts|mdx)'],
  addons: [
    getAbsolutePath('@storybook/addon-docs'),
    getAbsolutePath('@storybook/addon-a11y'),
    getAbsolutePath('@storybook/addon-themes'),
  ],
  framework: {
    name: getAbsolutePath('@storybook/html-vite'),
    options: {},
  },
};
export default config;
