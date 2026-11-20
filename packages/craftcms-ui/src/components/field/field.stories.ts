import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import {html} from 'lit';
import {ifDefined} from 'lit/directives/if-defined.js';

import './field.js';
import type CraftField from './field.js';
import '../input/input.js';
import '../badge/badge.js';
import '../button/button.js';
import '../icon/icon.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `field.ts` surfaces it here without touching this file.
 */
const {args, argTypes} = getStorybookHelpers<CraftField>('craft-field');

type CraftFieldArgs = CraftField & typeof args;

const meta = {
  title: 'Form Controls/Field',
  component: 'craft-field',
  args: {...args, label: 'Field label', helpText: ''},
  argTypes,
  render: ({
    label,
    helpText,
    required,
    translatable,
    fieldset,
    readOnly,
    disabled,
    status,
    statusLabel,
    orientation,
    instructionsPosition,
    instructionsSrOnly,
    labelSrOnly,
    width,
    layout,
  }) => {
    return html`<craft-field
      layout=${ifDefined(layout || undefined)}
      label=${label}
      help-text=${helpText}
      instructions-position=${ifDefined(instructionsPosition || undefined)}
      ?instructions-sr-only=${instructionsSrOnly}
      ?label-sr-only=${labelSrOnly}
      width=${ifDefined(width || undefined)}
      ?required=${required}
      ?translatable=${translatable}
      ?fieldset=${fieldset}
      ?readonly=${readOnly}
      ?disabled=${disabled}
      status=${ifDefined(status || undefined)}
      status-label=${ifDefined(statusLabel || undefined)}
      orientation=${ifDefined(orientation || undefined)}
    >
      <input slot="input" type="text" />
    </craft-field>`;
  },
} satisfies Meta<CraftFieldArgs>;

export default meta;
type Story = StoryObj<CraftFieldArgs>;

export const Default: Story = {
  args: {},
};

/**
 * Every part a field can show at once, to see where each one goes. The
 * controls switch the layout, the instructions' position and the label's and
 * instructions' visibility.
 */
export const EveryFeature: Story = {
  args: {
    label: 'Match authors by',
    helpText: 'Which of the authors’ values the incoming data holds.',
    required: true,
    translatable: true,
    readOnly: true,
    status: 'modified',
    statusLabel: 'This field has been modified.',
    instructionsPosition: 'before',
  },
  render: ({
    label,
    helpText,
    required,
    translatable,
    readOnly,
    status,
    statusLabel,
    instructionsPosition,
    instructionsSrOnly,
    labelSrOnly,
    layout,
  }) => html`
    <craft-field
      layout=${ifDefined(layout || undefined)}
      label=${label}
      help-text=${helpText}
      ?required=${required}
      ?translatable=${translatable}
      ?readonly=${readOnly}
      status=${ifDefined(status || undefined)}
      status-label=${ifDefined(statusLabel || undefined)}
      instructions-position=${ifDefined(instructionsPosition || undefined)}
      ?instructions-sr-only=${instructionsSrOnly}
      ?label-sr-only=${labelSrOnly}
    >
      <craft-icon slot="heading-prefix" name="user"></craft-icon>
      <craft-badge slot="label-badge" size="small" fill="blue" no-prefix
        >Changed</craft-badge
      >
      <craft-button
        slot="actions"
        icon="ellipsis"
        variant="plain"
        size="small"
        aria-label="Field actions"
      ></craft-button>
      <code slot="heading-suffix">authorIds</code>
      <craft-input
        slot="input"
        label=${label}
        label-sr-only
        value="ada@example"
      >
        <craft-icon slot="prefix" name="envelope"></craft-icon>
        <span slot="suffix">.com</span>
      </craft-input>
      <span slot="tip">Matched case-insensitively.</span>
      <span slot="warning"
        >Users who can’t author this section are skipped.</span
      >
      <ul slot="feedback" class="errors">
        <li>No user has the email “nobody@example.com”.</li>
      </ul>
    </craft-field>
  `,
};

/**
 * `layout="inline"` puts the heading and instructions in a column beside the
 * control. Everything about the value stays in the control's column.
 */
export const InlineLayout: Story = {
  ...EveryFeature,
  args: {...EveryFeature.args, layout: 'inline'},
};

/**
 * Stacked inline fields line their controls up once the heading column has a
 * fixed width.
 */
export const InlineLayoutStacked: Story = {
  render: () => html`
    <div style="display: grid; gap: 16px; --c-field-inline-label-size: 10rem;">
      <craft-field layout="inline" label="Site name" required>
        <craft-input slot="input" label="Site name" label-sr-only></craft-input>
      </craft-field>
      <craft-field
        layout="inline"
        label="Base URL"
        help-text="Where the site lives."
      >
        <craft-input slot="input" label="Base URL" label-sr-only></craft-input>
      </craft-field>
      <craft-field layout="inline" label="Language">
        <craft-input slot="input" label="Language" label-sr-only></craft-input>
        <ul slot="feedback" class="errors">
          <li>Choose a language.</li>
        </ul>
      </craft-field>
    </div>
  `,
};

export const RequiredWithInstructions: Story = {
  args: {
    required: true,
    helpText: 'Enter a value. This one is required.',
  },
};

/**
 * `instructions-sr-only` visually hides the instructions, keeping them in the
 * input's accessible description, such as when its placeholder repeats them.
 */
export const InstructionsSrOnly: Story = {
  render: () => html`
    <craft-field
      label="Prompt"
      help-text="Type { to insert a value."
      instructions-sr-only
    >
      <input slot="input" type="text" placeholder="Type { to insert a value." />
    </craft-field>
  `,
};

export const TipAndWarning: Story = {
  render: () => html`
    <craft-field label="Field label">
      <input slot="input" type="text" />
      <span slot="tip">You can use environment variables here.</span>
      <span slot="warning">Changing this may break existing content.</span>
    </craft-field>
  `,
};

export const Errors: Story = {
  render: () => html`
    <craft-field label="Field label">
      <input slot="input" type="text" />
      <ul slot="feedback" class="errors">
        <li>This field is required.</li>
        <li>That doesn't look right.</li>
      </ul>
    </craft-field>
  `,
};

export const StatusBadge: Story = {
  args: {
    status: 'modified',
    statusLabel: 'This field has been modified.',
  },
};

export const FieldsetMode: Story = {
  render: () => html`
    <craft-field label="Choose one" fieldset>
      <div slot="input" style="display: flex; gap: 12px;">
        <label><input type="radio" name="choice" value="a" /> A</label>
        <label><input type="radio" name="choice" value="b" /> B</label>
      </div>
    </craft-field>
  `,
};

export const Translatable: Story = {
  args: {
    translatable: true,
  },
};

export const ReadOnly: Story = {
  args: {
    readOnly: true,
  },
};

export const Rtl: Story = {
  args: {
    orientation: 'rtl',
    label: 'RTL field',
  },
};

export const LabelExtra: Story = {
  render: () => html`
    <craft-field label="Field label">
      <input slot="input" type="text" />
      <code slot="label-extra">myFieldHandle</code>
    </craft-field>
  `,
};

export const Actions: Story = {
  render: () => html`
    <craft-field label="Field label">
      <input slot="input" type="text" />
      <craft-checkbox slot="actions" label="Hide"></craft-checkbox>
      <craft-button slot="actions" icon="clipboard" variant="subtle">
        Copy value
      </craft-button>
    </craft-field>
  `,
};

export const WithCraftInput: Story = {
  render: () => html`
    <craft-field
      label="Site name"
      help-text="What this site will be called in the control panel."
      required
    >
      <craft-input slot="input" label-sr-only label="Site name"></craft-input>
    </craft-field>
  `,
};
