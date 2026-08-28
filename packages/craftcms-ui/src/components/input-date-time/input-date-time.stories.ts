import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input-date-time.js';
import '../button/button.js';
import '../field/field.js';
import type CraftInputDateTime from './input-date-time.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `input-date-time.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} = getStorybookHelpers<CraftInputDateTime>(
  'craft-input-date-time'
);

type InputDateTimeArgs = CraftInputDateTime & typeof args;

/**
 * The component carries no label of its own, so every example sits in a
 * `craft-field` — which is how it is always used, and what gives the date and
 * time inputs an accessible name.
 */
const inField = (story: () => unknown) => html`
  <craft-field>
    <label slot="label">Post Date</label>
    ${story()}
  </craft-field>
`;

const meta = {
  title: 'Controls/Input Date Time',
  component: 'craft-input-date-time',
  args: {...args, name: 'postDate', 'date-value': '2026-08-28', slot: 'input'},
  argTypes,
  decorators: [inField],
  parameters: {
    // The date and time inputs have no accessible name of their own: each is a
    // Lion field whose label slot is empty, and a wrapping `craft-field` names
    // the pair rather than the parts. Surfaced rather than gated until the
    // component gives them sub-labels — see the docs page.
    a11y: {test: 'todo'},
  },
  // Render from args alone so every control drives the story. Stories below
  // vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<InputDateTimeArgs>;

export default meta;
type Story = StoryObj<InputDateTimeArgs>;

/** A date input and a time input, kept in step and submitted together. */
export const Default: Story = {};

/** Both parts can carry a value. */
export const WithValues: Story = {
  args: {'date-value': '2026-08-28', 'time-value': '09:30'},
};

/** `show-time="false"` drops the time input, leaving a date-only field. */
export const DateOnly: Story = {
  args: {'show-time': 'false'},
};

/** `show-date="false"` drops the date input, leaving a time-only field. */
export const TimeOnly: Story = {
  args: {'show-date': 'false', 'time-value': '09:30'},
};

/**
 * `show-timezone` makes the timezone editable. Without it the timezone still
 * travels with the value, in a hidden input.
 */
export const WithTimezone: Story = {
  args: {'show-timezone': true, timezone: 'America/Los_Angeles'},
};

/** `min` and `max` bound the date; `min-time` and `max-time` bound the time. */
export const Bounded: Story = {
  args: {
    min: '2026-08-01',
    max: '2026-08-31',
    'min-time': '09:00',
    'max-time': '17:00',
  },
};

/** `minute-increment` sets the spacing between the time input's suggestions. */
export const MinuteIncrement: Story = {
  args: {'minute-increment': 15, 'time-value': '09:15'},
};

export const Disabled: Story = {
  args: {disabled: true, 'time-value': '09:30'},
};

export const Readonly: Story = {
  args: {readonly: true, 'time-value': '09:30'},
};

/**
 * The component's own inputs always lead, so slotted content — a clear button,
 * say — stays after the inputs it acts on in reading and tab order.
 */
export const WithSlottedContent: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-input-date-time slot="input" name="postDate" date-value="2026-08-28">
      <craft-button
        size="small"
        variant="plain"
        @click="${(event: Event) => {
          const field = (event.currentTarget as HTMLElement)
            .parentElement as CraftInputDateTime;
          field.dateValue = '';
          field.timeValue = '';
        }}"
      >
        Clear
      </craft-button>
    </craft-input-date-time>
  `,
};

/**
 * Wrapping in `craft-field` is what supplies the label, help text, and error
 * handling. Every example on this page is wrapped the same way.
 */
export const InAField: Story = {
  parameters: {controls: {disable: true}},
  decorators: [(story) => story()],
  render: () => html`
    <craft-field>
      <label slot="label">Post Date</label>
      <span slot="help-text">When the entry should go live.</span>
      <craft-input-date-time
        slot="input"
        name="postDate"
        date-value="2026-08-28"
        time-value="09:30"
      ></craft-input-date-time>
    </craft-field>
  `,
};
