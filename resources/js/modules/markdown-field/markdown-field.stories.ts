import type {Meta, StoryObj} from '@storybook/vue3-vite';

import './markdown-field';

/**
 * The default toolbar of Markdown fields, without assets, which need Craft's
 * asset modal. As JSON, since Vue sets it as the `toolbar-buttons` attribute.
 */
const toolbarButtons = JSON.stringify([
  'bold',
  'italic',
  'code',
  'unordered-list',
  'ordered-list',
  'link',
  'preview',
]);

const text =
  'The first lighthouse on the coast was built in 1790, after a series of **shipwrecks**.';

/**
 * `<craft-markdown-field>`, the Markdown editor behind Markdown fields and
 * other Markdown inputs.
 */
const meta: Meta = {
  title: 'Fields/Markdown',
  parameters: {layout: 'padded'},
};

export default meta;
type Story = StoryObj;

/** The editor with its toolbar, as Markdown fields show it by default. */
export const Default: Story = {
  render: () => ({
    setup: () => ({text, toolbarButtons}),
    template: `
      <craft-markdown-field
        label="Body"
        rows="4"
        show-toolbar
        :toolbar-buttons="toolbarButtons"
      >{{ text }}</craft-markdown-field>
    `,
  }),
};

/** Without a toolbar, the text has even padding on every side. */
export const WithoutToolbar: Story = {
  render: () => ({
    setup: () => ({text}),
    template: `
      <craft-markdown-field
        label="Body"
        rows="4"
      >{{ text }}</craft-markdown-field>
    `,
  }),
};

/** `--markdown-field-font-size`, set on an ancestor, changes the text size. */
export const FontSize: Story = {
  render: () => ({
    setup: () => ({text}),
    template: `
      <div style="--markdown-field-font-size: var(--c-text-sm)">
        <craft-markdown-field
          label="Body"
          rows="4"
        >{{ text }}</craft-markdown-field>
      </div>
    `,
  }),
};
