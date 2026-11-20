import * as a11yAddonAnnotations from '@storybook/addon-a11y/preview';
import {setProjectAnnotations} from '@storybook/web-components-vite';
import * as projectAnnotations from './preview.js';

// This is an important step to apply the right configuration when testing your stories.
// More info at: https://storybook.js.org/docs/api/portable-stories/portable-stories-vitest#setprojectannotations
setProjectAnnotations([
  a11yAddonAnnotations,
  projectAnnotations,
  // The tests run with the “Underline links” preference on, so
  // `link-in-text-block` checks the case where someone relies on it. Storybook
  // itself keeps the rule off, as it shows links without the preference.
  {
    beforeEach() {
      document.body.classList.add('underline-links');
    },
    parameters: {a11y: {config: {rules: []}}},
  },
]);
