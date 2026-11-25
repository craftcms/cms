import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import type CraftBreadcrumbs from './breadcrumbs.js';
import './breadcrumbs.js';
import '../breadcrumb-item/breadcrumb-item.js';
import '../button/button.js';

/**
 * Cases that nobody sets up on purpose, but that reviewers need to see: the
 * layouts the breadcrumbs' browser tests measure. The API's own examples are
 * in `breadcrumbs.stories.ts`.
 */
const meta: Meta<CraftBreadcrumbs> = {
  title: 'Components/Breadcrumbs/Edge Cases',
  component: 'craft-breadcrumbs',
  tags: ['!autodocs'],
  parameters: {controls: {disable: true}},
};

export default meta;
type Story = StoryObj<CraftBreadcrumbs>;

/**
 * In a row sized by its content, like a page header's, with a button after
 * the trail. Narrow the box until the trail collapses, then widen it: the
 * crumbs come back, and the button stays beside the trail.
 */
export const BesideOtherContent: Story = {
  name: 'Beside other content',
  render: () => html`
    <div class="resizable-container">
      <div style="display: flex; align-items: center; gap: 8px">
        <div style="flex: 0 1 auto; min-inline-size: 0">
          <craft-breadcrumbs>
            <craft-breadcrumb-item href="#">Site Name</craft-breadcrumb-item>
            <craft-breadcrumb-item href="#">Entries</craft-breadcrumb-item>
            <craft-breadcrumb-item href="#">Entry Type</craft-breadcrumb-item>
            <craft-breadcrumb-item href="#">Parent Entry</craft-breadcrumb-item>
            <craft-breadcrumb-item>Current</craft-breadcrumb-item>
          </craft-breadcrumbs>
        </div>
        <craft-button size="small">Actions</craft-button>
      </div>
    </div>
  `,
};
