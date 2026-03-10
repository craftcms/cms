import * as a11yAddonAnnotations from '@storybook/addon-a11y/preview';
import {setProjectAnnotations} from '@storybook/vue3-vite';
import * as projectAnnotations from './preview.js';

// Apply the right configuration when testing stories.
// More info at: https://storybook.js.org/docs/api/portable-stories/portable-stories-vitest#setprojectannotations
setProjectAnnotations([a11yAddonAnnotations, projectAnnotations]);
