import type {
  Preview,
  WebComponentsRenderer,
} from '@storybook/web-components-vite';
import '../src/styles/cp.css';
import './preview.css';
import {icons} from '@lion/ui/icon.js';
import {html} from 'lit';
import {withThemeByDataAttribute} from '@storybook/addon-themes';
import {setCustomElementsManifest} from '@storybook/web-components';
import manifest from '../dist/custom-elements.json' with {type: 'json'};
import {setStorybookHelpersConfig} from '@wc-toolkit/storybook-helpers';

setStorybookHelpersConfig({
  /** hides the `arg ref` label on each control */
  hideArgRef: false,
  /** sets the custom type reference in the Custom Elements Manifest */
  typeRef: 'parsedTypes',
  /** Adds a <script> tag where a `component` variable will reference the story's component */
  setComponentVariable: false,
  /** renders default values for attributes and CSS properties */
  renderDefaultValues: false,
});

setCustomElementsManifest(manifest);

icons.addIconResolver('craft', (iconSet: string, name: string) => {
  return html`<div>${iconSet} - ${name}</div>`;
});

const preview: Preview = {
  parameters: {
    controls: {
      expanded: true,
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
      test: 'error',
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
  tags: ['autodocs'],
};

export default preview;
