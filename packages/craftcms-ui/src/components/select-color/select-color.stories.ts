import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {expect} from 'storybook/test';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './select-color.js';
import type CraftSelectColor from './select-color.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `select-color.ts` surfaces it here without touching this file.
 *
 * The template is written out rather than generated: the value is bound as the
 * `modelValue` property so an empty selection can be `null`, which an attribute
 * cannot express.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftSelectColor>('craft-select-color');

type SelectColorArgs = CraftSelectColor & typeof args;

const meta = {
  title: 'Form Controls/Select Controls/Select Color',
  component: 'craft-select-color',
  args: {...args, label: 'Color', name: 'color', 'model-value': 'red'},
  argTypes,
  render: (args) => html`
    <craft-select-color
      label="${args.label ?? ''}"
      name="${args.name ?? 'color'}"
      ?allow-transparent="${args['allow-transparent']}"
      .modelValue="${args['model-value'] ?? null}"
    ></craft-select-color>
  `,
} satisfies Meta<SelectColorArgs>;

export default meta;
type Story = StoryObj<SelectColorArgs>;

/**
 * A colour is selected on load, so the invoker shows its swatch.
 *
 * The assertions live here rather than in a unit test: the component renders a
 * `craft-select-rich`, whose Lion internals need layout that happy-dom does not
 * provide, so this is the only place the rendered control can be checked.
 */
export const Default: Story = {
  play: async ({canvasElement}) => {
    const host = canvasElement.querySelector('craft-select-color')!;

    // It forwards its label and name onto the select it wraps.
    const select = host.shadowRoot!.querySelector('craft-select-rich')!;
    await expect(select).toBeTruthy();
    await expect(select.getAttribute('label')).toBe('Color');
    await expect(select.getAttribute('name')).toBe('color');

    // And the selection reaches it as a model value.
    await expect((host as {modelValue?: unknown}).modelValue).toBe('red');
  },
};

/**
 * Picking a colour is a commit, so the control emits native `input` and
 * `change` together — the events a consumer should bind, rather than Lion's
 * internal `model-value-changed`.
 *
 * Asserted here rather than in a unit test for the same reason as the rest of
 * this file: the inner `craft-select-rich` needs layout that happy-dom does
 * not provide.
 */
export const NativeEvents: Story = {
  play: async ({canvasElement}) => {
    const host = canvasElement.querySelector('craft-select-color')!;
    const order: string[] = [];
    host.addEventListener('input', (event) => {
      // Dispatched from the host, so `event.target` is the component.
      if (event.target === host) order.push('input');
    });
    host.addEventListener('change', (event) => {
      if (event.target === host) order.push('change');
    });

    const inner = host.shadowRoot!.querySelector('craft-select-rich')!;
    inner.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );

    await expect(order).toEqual(['input', 'change']);
  },
};

/** Nothing selected — the invoker shows the placeholder with no swatch. */
export const Empty: Story = {
  args: {'model-value': null},
  play: async ({canvasElement}) => {
    const host = canvasElement.querySelector('craft-select-color')!;

    await expect((host as {modelValue?: unknown}).modelValue).toBeNull();
  },
};

/** Preselected blue, so the blue swatch appears in the invoker. */
export const Preselected: Story = {
  args: {'model-value': 'blue'},
};

/**
 * `allow-transparent` adds a "transparent" option, whose swatch is the
 * chequerboard.
 */
export const AllowTransparent: Story = {
  args: {'allow-transparent': true, 'model-value': '__blank__'},
  play: async ({canvasElement}) => {
    const host = canvasElement.querySelector('craft-select-color')!;

    // The transparent option is opt-in, and carries its own sentinel value.
    await expect((host as {allowTransparent?: boolean}).allowTransparent).toBe(
      true
    );
    await expect((host as {modelValue?: unknown}).modelValue).toBe('__blank__');
  },
};

/** The same option offered but nothing selected yet. */
export const AllowTransparentEmpty: Story = {
  args: {'allow-transparent': true, 'model-value': null},
};
