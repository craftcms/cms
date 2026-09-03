import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftSlideRule from './slide-rule.js';
import {expect} from 'storybook/test';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './slide-rule.js';

const {events, args, argTypes, template} =
  getStorybookHelpers('craft-slide-rule');

const meta: Meta<CraftSlideRule> = {
  title: 'Form Controls/Slide Rule',
  component: 'craft-slide-rule',
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
type Story = StoryObj<CraftSlideRule & typeof args>;

export const Default: Story = {
  args: {},
};

export const StartingAngle: Story = {
  args: {
    value: 15,
  },
};

/**
 * Give it a `name` and the angle posts with the form, like any other input.
 *
 * As with the slide picker, the assertions run in the browser because they
 * exercise the user agent's own side of form association, which neither
 * happy-dom nor the polyfill provides.
 */
export const InAForm: Story = {
  render: () => html`
    <form>
      <craft-slide-rule name="angle" value="15"></craft-slide-rule>
    </form>
  `,
  play: async ({canvasElement}) => {
    const form = canvasElement.querySelector('form')!;
    const rule = form.querySelector('craft-slide-rule')!;

    await expect(rule.form).toBe(form);
    await expect(new FormData(form).get('angle')).toBe('15');

    rule.disabled = true;
    await rule.updateComplete;
    await expect(new FormData(form).get('angle')).toBeNull();

    rule.disabled = false;
    await rule.updateComplete;

    rule.value = -30;
    await rule.updateComplete;
    await expect(new FormData(form).get('angle')).toBe('-30');

    form.reset();
    await rule.updateComplete;
    await expect(rule.value).toBe(15);
  },
};
