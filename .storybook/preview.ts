import type {Preview} from '@storybook/vue3';
import {setup} from '@storybook/vue3';
import {withThemeByDataAttribute} from '@storybook/addon-themes';
import '@craftcms/ui';
import '../resources/css/cp.css';
import './preview.css';
import {installInertiaMock, setPageProps} from './inertia-mock';

// Install the Inertia mock globally
setup((app) => {
  installInertiaMock(app);
});

// Declare module augmentation for Storybook parameters
declare module '@storybook/vue3' {
  interface Parameters {
    inertia?: Partial<Record<string, unknown>>;
  }
}

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
      test: 'todo',
    },
  },
  decorators: [
    // Inertia page props decorator - must come before theme decorator
    (story, context) => {
      // Reset and apply any story-specific page props
      const inertiaProps = context.parameters.inertia || {};
      setPageProps(inertiaProps);
      return story();
    },
    withThemeByDataAttribute({
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
