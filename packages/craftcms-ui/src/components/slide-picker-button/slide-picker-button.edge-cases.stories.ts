import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import type CraftSlidePickerButton from './slide-picker-button.js';
import './slide-picker-button.js';
import {withAccessibilityTree} from '../../../test/slide-picker-button-accessibility-tree';

/**
 * Cases reviewers need to see that aren't part of the button's own API. The
 * API's examples are in `slide-picker-button.stories.ts`.
 */
const meta: Meta<CraftSlidePickerButton> = {
  title: 'Form Controls/Slide Picker Button/Edge Cases',
  component: 'craft-slide-picker-button',
  tags: ['!autodocs'],
  parameters: {
    controls: {disable: true},
  },
};

export default meta;
type Story = StoryObj<CraftSlidePickerButton>;

/**
 * A button in a field, as `Craft.ui.createSlidePickerField({button: true})`
 * builds it. The button's internals can't reference the field's label, so
 * the label is also the button's `item-label`, and its name holds both
 * visible labels: the tooltip's value, then the field's label, "50% Width".
 * The label also names the slider in the popover. A unit of `%` rather than
 * `% width` keeps the name from saying "width" twice.
 */
export const InAField: Story = {
  render: () =>
    withAccessibilityTree(html`
      <div style="display: grid; gap: var(--c-spacing-xs);">
        <div style="font-weight: bold;">Width</div>
        <craft-slide-picker-button
          slider-label="Width"
          item-label="Width"
          value-unit="%"
          min="25"
          max="100"
          step="25"
          value="50"
        ></craft-slide-picker-button>
      </div>
    `),
};
