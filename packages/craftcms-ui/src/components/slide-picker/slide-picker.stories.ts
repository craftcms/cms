import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftSlidePicker from './slide-picker.js';
import {expect} from 'storybook/test';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './slide-picker.js';
import '../field/field.js';

/**
 * Lion's own `label` and `help-text` are slots here, so they are set through
 * the `label-slot` and `help-text-slot` args rather than as attributes.
 */
const {events, args, argTypes, template} =
  getStorybookHelpers('craft-slide-picker');

const meta: Meta<CraftSlidePicker> = {
  title: 'Form Controls/Slide Picker',
  component: 'craft-slide-picker',
  args,
  argTypes,
  render: (args) => template(args),
  parameters: {
    actions: {
      handles: events,
    },
  },
};

export default meta;
type Story = StoryObj<CraftSlidePicker & typeof args>;

export const Default: Story = {
  args: {
    'label-slot': 'Completion',
  },
};

/** A read-only picker can still be focused, so its value can be read. */
export const ReadOnly: Story = {
  render: () => html`
    <craft-slide-picker
      label="Completion"
      readonly
      model-value="60"
    ></craft-slide-picker>
  `,
};

export const FiveSteps: Story = {
  args: {
    min: 0,
    max: 100,
    step: 20,
    'model-value': 60,
    'label-slot': 'Completion',
  },
};

/**
 * Inside `craft-field`, the field's label names the slider and its
 * instructions and errors describe it, so the picker needs no `label` of its
 * own.
 */
export const InField: Story = {
  render: () => html`
    <craft-field
      label="Card width"
      help-text="How many columns the card spans."
    >
      <craft-slide-picker
        slot="input"
        value-unit="columns"
        min="1"
        max="4"
        step="1"
        model-value="2"
      ></craft-slide-picker>
    </craft-field>
  `,
};

/**
 * A picker with a `name` posts its value like any other field, and a form
 * reset puts back the value it started with.
 *
 * The assertions run here to check a real form in a real browser: what is
 * posted, what a disabled picker leaves out, and what a reset restores.
 */
export const InAForm: Story = {
  render: () => html`
    <form>
      <craft-slide-picker
        name="columns"
        label="Number of columns"
        value-unit="columns"
        min="1"
        max="6"
        step="1"
        model-value="3"
      ></craft-slide-picker>
    </form>
  `,
  play: async ({canvasElement}) => {
    const form = canvasElement.querySelector('form')!;
    const picker = form.querySelector('craft-slide-picker')!;
    await picker.updateComplete;

    await expect(new FormData(form).get('columns')).toBe('3');

    // A disabled picker is left out of submission entirely.
    picker.disabled = true;
    await picker.updateComplete;
    await expect(new FormData(form).get('columns')).toBeNull();

    picker.disabled = false;
    await picker.updateComplete;

    picker.modelValue = 6;
    await picker.updateComplete;
    await expect(new FormData(form).get('columns')).toBe('6');

    form.reset();
    await picker.updateComplete;
    await expect(picker.modelValue).toBe(3);
    await expect(new FormData(form).get('columns')).toBe('3');
  },
};
