import type {Decorator, Meta, StoryObj} from '@storybook/web-components';

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
 * `craft-field` — which is how it is always used, and what gives the pair a
 * name.
 *
 * A story that renders its own field sets `parameters.ownField` to opt out.
 * Story-level decorators are additive rather than overriding, so opting out
 * has to be a parameter the shared decorator checks; returning `story()` from
 * a story's own decorator would still leave this one wrapping it.
 */
const inField: Decorator = (story, context) => html`
  <craft-field>
    <label slot="label">Post Date</label>
    ${story()}
  </craft-field>
`;

/**
 * `show-date` and `show-time` cannot be driven from args.
 *
 * They are default-on string flags — only the literal `"false"` turns them
 * off, which is what the PHP builder emits. The manifest types them as
 * booleans, and the helpers map a `"false"` arg onto boolean `false`, which
 * *removes* the attribute; an absent attribute reads as on. So the stories
 * that turn them off write the attribute themselves, and the controls are
 * disabled rather than offering a knob that cannot work.
 */
const showFlagArgType = {control: {disable: true}} as const;

const meta = {
  title: 'Controls/Input Date Time',
  component: 'craft-input-date-time',
  args: {
    ...args,
    name: 'postDate',
    'date-value': '2026-08-28',
    slot: 'input',
  },
  argTypes: {
    ...argTypes,
    'show-date': showFlagArgType,
    'show-time': showFlagArgType,
  },
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
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-input-date-time
      slot="input"
      name="postDate"
      date-value="2026-08-28"
      show-time="false"
    ></craft-input-date-time>
  `,
};

/** `show-date="false"` drops the date input, leaving a time-only field. */
export const TimeOnly: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-input-date-time
      slot="input"
      name="postDate"
      time-value="09:30"
      show-date="false"
    ></craft-input-date-time>
  `,
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
