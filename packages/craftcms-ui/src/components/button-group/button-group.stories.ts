import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './button-group.js';
import '../button/button.js';
import '../icon/icon.js';
import '../action-menu/action-menu.js';
import '../action-item/action-item.js';

const meta = {
  title: 'Components/Button Group',
  component: 'craft-button-group',
  parameters: {
    docs: {
      description: {
        component: `
Groups a set of buttons together visually and optionally as a radio input.

When a \`name\` attribute is set, the group enters **radio mode**: exactly one
button is selected at a time, the selection is reflected back via the \`value\`
property, and a \`change\` event fires with \`event.detail.value\` on each change.
The group is form-associated and submits its value with the surrounding form.
        `.trim(),
      },
    },
  },
  argTypes: {
    name: {
      description: 'Form field name. When set, enables radio mode.',
      control: {type: 'text'},
    },
    value: {
      description: 'The currently selected value (radio mode only).',
      control: {type: 'text'},
    },
  },
  args: {},
  render: () => html`
    <craft-button-group>
      <craft-button>One</craft-button>
      <craft-button>Two</craft-button>
      <craft-button>Three</craft-button>
    </craft-button-group>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

/** Standard button group with no selection behavior — buttons act independently. */
export const Default: Story = {
  name: 'Default',
};

/**
 * When `name` is set the group behaves as a radio input: clicking a button
 * selects it and deselects the previous selection. The active button receives
 * the `active` attribute and `aria-pressed="true"`.
 */
export const RadioMode: Story = {
  name: 'Radio mode',
  render: () => html`
    <craft-button-group name="view" value="list">
      <craft-button value="list">List</craft-button>
      <craft-button value="table">Table</craft-button>
      <craft-button value="thumbs">Thumbnails</craft-button>
    </craft-button-group>
  `,
};

/**
 * Radio mode works across all button variants. The selected button retains
 * its `active` styling regardless of the chosen variant.
 */
export const RadioModeVariants: Story = {
  name: 'Radio mode — variants',
  render: () => html`
    <div style="display:flex;flex-direction:column;gap:1rem">
      ${(['solid', 'fill', 'outline', 'dashed', 'plain'] as const).map(
        (variant) => html`
          <div style="display:flex;align-items:center;gap:1rem">
            <span style="width:4rem;font-size:0.75rem;color:var(--c-text-muted)"
              >${variant}</span
            >
            <craft-button-group name="view-${variant}" value="b">
              <craft-button variant="${variant}" value="a">A</craft-button>
              <craft-button variant="${variant}" value="b">B</craft-button>
              <craft-button variant="${variant}" value="c">C</craft-button>
            </craft-button-group>
          </div>
        `
      )}
    </div>
  `,
};

/** Icon-only buttons work in radio mode just like labeled buttons. */
export const RadioModeIcons: Story = {
  name: 'Radio mode — icon buttons',
  render: () => html`
    <craft-button-group name="layout" value="rows">
      <craft-button value="rows" aria-label="Row view">
        <craft-icon slot="prefix" name="list-unordered"></craft-icon>
      </craft-button>
      <craft-button value="table" aria-label="Table view">
        <craft-icon slot="prefix" name="table"></craft-icon>
      </craft-button>
      <craft-button value="thumbs" aria-label="Thumbnail view">
        <craft-icon slot="prefix" name="grid"></craft-icon>
      </craft-button>
    </craft-button-group>
  `,
};

/**
 * Because `craft-button-group` is form-associated, it participates in a form
 * submission and exposes its selected value via `FormData`. Click a button then
 * submit to see the value logged to the console.
 */
export const FormAssociated: Story = {
  name: 'Form-associated',
  render: () => html`
    <form
      @submit=${(e: SubmitEvent) => {
        e.preventDefault();
        const data = new FormData(e.target as HTMLFormElement);
        console.log('Submitted view:', data.get('view'));
        alert('Submitted: ' + data.get('view'));
      }}
      style="display:flex;align-items:center;gap:1rem"
    >
      <craft-button-group name="view" value="list">
        <craft-button value="list">List</craft-button>
        <craft-button value="table">Table</craft-button>
        <craft-button value="thumbs">Thumbnails</craft-button>
      </craft-button-group>
      <craft-button type="submit" variant="fill">Submit</craft-button>
    </form>
  `,
};

export const WithActions: Story = {
  name: 'With Actions',
  render: () => html`
    <craft-button-group name="view" value="list">
      <craft-button value="table">New Entry</craft-button>
      <craft-action-menu>
        <craft-button
          type="button"
          slot="invoker"
          icon="chevron-down"
          aria-label="Actions"
        ></craft-button>

        <div slot="content">
          <craft-action-item>Action Item 1</craft-action-item>
          <craft-action-item>Action Item 2</craft-action-item>
        </div>
      </craft-action-menu>
    </craft-button-group>
  `,
};
