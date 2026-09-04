import type {
  Preview,
  WebComponentsRenderer,
} from '@storybook/web-components-vite';
import '../src/styles/cp.css';
import './preview.css';
import {withThemeByDataAttribute} from '@storybook/addon-themes';
import {setCustomElementsManifest} from '@storybook/web-components';
import manifest from '../dist/custom-elements.json' with {type: 'json'};
import {setStorybookHelpersConfig} from '@wc-toolkit/storybook-helpers';
import {
  createUrlIconResolver,
  getIconUrl,
  setIconResolver,
} from '../src/utilities/icons.js';

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

const FONT_AWESOME_CDN_URL =
  'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.0.0/svgs';

setIconResolver(
  createUrlIconResolver((name, family, variant) => {
    if (family === 'custom-icons' || variant === 'custom-icons') {
      return null;
    }

    return getIconUrl(name, family, variant, FONT_AWESOME_CDN_URL);
  })
);

const preview: Preview = {
  parameters: {
    controls: {
      expanded: true,
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },

    docs: {
      // Show each story's source in a "Code" panel alongside Controls, so the
      // markup is available from the story view and not only from the docs
      // page. Stories that pin `docs.source.code` supply that value here too.
      codePanel: true,
    },

    options: {
      storySort: {
        method: 'alphabetical',
        // Everything else sorts alphabetically beneath these. Getting Started
        // leads because a first-time reader lands on the sidebar, and Tokens
        // trails because it explains what the component pages reference.
        order: [
          'Getting Started',
          ['Introduction', 'Installation', 'Theming'],
          'Components',
          'Form Controls',
          ['Choice Controls', 'Select Controls', 'Text Controls'],
          'JavaScript API',
          ['Factory', 'Utilities', 'Services', 'Reactive Controllers'],
          'Tokens',
        ],
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
