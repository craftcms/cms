import type {Preview} from '@storybook/vue3';

// Import global styles
import '../resources/css/cp.css';

// Import craftcms-cp web components
import '@craftcms/cp';

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
      test: 'todo',
    },
  },
  tags: ['autodocs'],
};

export default preview;
