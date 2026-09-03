import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftSlidePicker from './slide-picker.js';
import {expect} from 'storybook/test';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './slide-picker.js';

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
  args: {},
};

export const ReadOnly: Story = {
  args: {
    readonly: true,
  },
};

export const FiveSteps: Story = {
  args: {
    min: 0,
    max: 100,
    step: 20,
    value: 60,
    label: 'Completion',
  },
};

/**
 * The control is form-associated, so `name` is all it takes to post a value —
 * there is no hidden input behind it.
 *
 * The assertions run here rather than in a unit test because they exercise the
 * user agent's own side of form association: excluding a disabled control from
 * submission, and calling back on reset. happy-dom has no `ElementInternals` at
 * all, and the polyfill the unit tests use does not carry either behaviour.
 */
export const InAForm: Story = {
  render: () => html`
    <form>
      <craft-slide-picker
        name="columns"
        label="Number of columns"
        value-unit=" columns"
        min="1"
        max="6"
        step="1"
        value="3"
      ></craft-slide-picker>
    </form>
  `,
  play: async ({canvasElement}) => {
    const form = canvasElement.querySelector('form')!;
    const picker = form.querySelector('craft-slide-picker')!;

    // It belongs to the form, and posts under its name.
    await expect(picker.form).toBe(form);
    await expect(new FormData(form).get('columns')).toBe('3');

    // A disabled control is left out of submission entirely.
    picker.disabled = true;
    await picker.updateComplete;
    await expect(new FormData(form).get('columns')).toBeNull();

    picker.disabled = false;
    await picker.updateComplete;

    // And a reset puts back the value the markup started with.
    picker.value = 6;
    await picker.updateComplete;
    await expect(new FormData(form).get('columns')).toBe('6');

    form.reset();
    await picker.updateComplete;
    await expect(picker.value).toBe(3);
    await expect(new FormData(form).get('columns')).toBe('3');
  },
};
