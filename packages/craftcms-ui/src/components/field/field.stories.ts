import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {ifDefined} from 'lit/directives/if-defined.js';

import './field.js';
import '../input/input.js';

const meta = {
  title: 'Form Controls/Field',
  component: 'craft-field',
  args: {
    label: 'Field label',
    helpText: '',
    required: false,
    translatable: false,
    fieldset: false,
    readOnly: false,
    disabled: false,
    status: undefined,
    statusLabel: undefined,
    orientation: undefined,
  },
  argTypes: {
    orientation: {
      control: 'select',
      options: ['ltr', 'rtl'],
    },
    status: {
      control: 'select',
      options: ['modified', 'outdated'],
    },
  },
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
  }) => {
    return html`<craft-field
      label=${label}
      help-text=${helpText}
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
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {
  args: {},
};

export const RequiredWithInstructions: Story = {
  args: {
    required: true,
    helpText: 'Enter a value. This one is required.',
  },
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
