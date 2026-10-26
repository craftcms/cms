import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './resize-grips.js';
import type CraftResizeGrips from './resize-grips.js';
import type {ResizeGripsSize} from './resize-grips.js';

const {args, argTypes} =
  getStorybookHelpers<CraftResizeGrips>('craft-resize-grips');

type ResizeGripsArgs = CraftResizeGrips & typeof args;

const meta = {
  title: 'Components/Resize Grips',
  component: 'craft-resize-grips',
  args,
  argTypes,
} satisfies Meta<ResizeGripsArgs>;

export default meta;
type Story = StoryObj<ResizeGripsArgs>;

function applySize(event: CustomEvent<ResizeGripsSize>): void {
  const box = (event.target as HTMLElement).parentElement!;
  const {width, height} = event.detail;
  box.style.width = width === null ? '' : `${width}px`;
  box.style.height = height === null ? '' : `${height}px`;
}

/**
 * A box that the grips resize from its right and bottom edges, or both from
 * the corner, which also takes the keyboard. It's centered, so it grows and
 * shrinks from both sides.
 */
export const Default: Story = {
  render: () => html`
    <div style="display: grid; place-items: center; min-height: 32rem">
      <div
        style="position: relative; box-sizing: border-box; width: 24rem; height: 16rem; max-width: 100%; max-height: 30rem; padding: var(--c-spacing-lg); border: 1px solid var(--c-color-border-quiet); border-radius: var(--c-radius-lg); background: var(--c-surface-raised)"
      >
        Drag an edge or the corner.
        <craft-resize-grips @craft-resize=${applySize}></craft-resize-grips>
      </div>
    </div>
  `,
};
