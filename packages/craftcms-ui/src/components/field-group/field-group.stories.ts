import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './field-group.js';
import '../field/field.js';
import '../input/input.js';

const meta = {
  title: 'Form Controls/Field Group',
  component: 'craft-field-group',
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

const field = (label: string, widthClass = '') => html`
  <craft-field class="${widthClass}">
    <label slot="label">${label}</label>
    <craft-input slot="input"></craft-input>
  </craft-field>
`;

/** Fields take a full row unless told otherwise. */
export const Default: Story = {
  render: () => html`
    <craft-field-group> ${field('Title')} ${field('Slug')} </craft-field-group>
  `,
};

/** A `width-*` class gives a field a fraction of the row. */
export const Widths: Story = {
  render: () => html`
    <craft-field-group>
      ${field('Title', 'width-50')} ${field('Slug', 'width-50')}
      ${field('Author', 'width-33')} ${field('Post Date', 'width-33')}
      ${field('Expiry Date', 'width-33')}
    </craft-field-group>
  `,
};

/**
 * The breakpoints are container queries, so the same group stacks inside a
 * narrow container even on a wide screen.
 */
export const NarrowContainer: Story = {
  render: () => html`
    <div style="width: 22rem; outline: 1px dashed var(--c-color-border-quiet)">
      <craft-field-group>
        ${field('Title', 'width-50')} ${field('Slug', 'width-50')}
      </craft-field-group>
    </div>
  `,
};
