import type {
  Preview,
  WebComponentsRenderer,
} from '@storybook/web-components-vite';
import '../src/styles/shared/preflight.css';
import '../src/styles/cp.css';
import './preview.css';

import {icons} from '@lion/ui/icon.js';
import {html} from 'lit';
import {withThemeByDataAttribute} from '@storybook/addon-themes';
icons.addIconResolver('craft', (iconSet: string, name: string) => {
  console.log(iconSet, name);
  return html`<div>${iconSet} - ${name}</div>`;
});

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },

    options: {
      storySort: {
        method: 'alphabetical',
      },
    },

    a11y: {
      // 'todo' - show a11y violations in the test UI only
      // 'error' - fail CI on a11y violations
      // 'off' - skip a11y checks entirely
      test: 'todo',
    },
  },
  decorators: [
    withThemeByDataAttribute<WebComponentsRenderer>({
      themes: {
        light: 'light',
        dark: 'dark',
      },
      defaultTheme: 'light',
      attributeName: 'data-theme',
    }),
  ],
};

export default preview;
