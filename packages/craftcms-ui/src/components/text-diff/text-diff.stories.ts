import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './text-diff.js';
import type CraftTextDiff from './text-diff.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftTextDiff>('craft-text-diff');

type TextDiffArgs = CraftTextDiff & typeof args;

const filler =
  'Keepers lived at the lighthouse, often with their families, for years at a time. The work was repetitive: cleaning the lens, trimming the wick, and winding the clockwork that turned the light. ';

const meta = {
  title: 'Components/Text Diff',
  component: 'craft-text-diff',
  args: {
    ...args,
    current:
      'The first lighthouse on the coast was build in 1790, after a series of shipwrecks that cost hundreds of lifes.',
    proposed:
      'The first lighthouse on the coast was built in 1790, after a series of shipwrecks that cost hundreds of lives.',
  },
  argTypes,
  render: (args) => template(args),
} satisfies Meta<TextDiffArgs>;

export default meta;
type Story = StoryObj<TextDiffArgs>;

export const Default: Story = {};

/** Each rewritten phrase is one deletion and one insertion. */
export const RewrittenPhrase: Story = {
  args: {
    current:
      'It was a simple stone tower, on top, that had to be kept burning all night by the keeper and his family.',
    proposed:
      'It was a simple stone tower topped with a coal fire, which the keeper and his family had to keep burning all night.',
  },
};

/**
 * `collapse` collapses long unchanged stretches, with a button that shows all
 * of the text. `label` ends the button's accessible name.
 */
export const Collapse: Story = {
  args: {
    current: `${filler.repeat(3)}The stairs is steep and narrow.`,
    proposed: `${filler.repeat(3)}The stairs are steep and narrow.`,
    collapse: true,
    label: 'Body',
  },
};

/** `highlights` marks passages of the text before the changes. */
export const Highlights: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-text-diff
      current="In 2015 the main tower was restored, using original materials were possible."
      proposed="In 2015 the main tower was restored, using original materials where possible."
      .highlights=${['were possible']}
    ></craft-text-diff>
  `,
};

/** With no text before the changes, the text shows as it is. */
export const NewText: Story = {
  args: {current: '', proposed: 'Bring a jacket — it’s windy at the top.'},
};
