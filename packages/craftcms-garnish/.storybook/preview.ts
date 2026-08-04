import type {HtmlRenderer, Preview} from '@storybook/html-vite';
import {withThemeByDataAttribute} from '@storybook/addon-themes';
import './preview.css';

/**
 * Global Storybook config for @craftcms/garnish.
 *
 * The global demo styles (modal/shade, drag arena/boxes, HUD tip, menu panel,
 * the event-log panel) are migrated from the old `playground/styles.css` into
 * `preview.css`, imported here so every story shares them.
 */
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
      // 'todo' surfaces a11y violations in the UI without failing the build —
      // these are imperative widget demos, not production-accessible markup.
      test: 'todo',
    },
  },
  decorators: [
    withThemeByDataAttribute<HtmlRenderer>({
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
