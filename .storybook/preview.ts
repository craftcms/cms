import type { Preview } from '@storybook/vue3-vite'
import '@craftcms/cp';
import '../resources/css/cp.css';

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
       color: /(background|color)$/i,
       date: /Date$/i,
      },
    },
  },
};

export default preview;
