import type {Meta, StoryObj} from '@storybook/vue3-vite';

import NavTree from './NavTree.vue';
import {navFixture, selectFixtureItem} from './NavTree.fixture';

/**
 * PROTOTYPE. The deep, flyout-capable CP navigation, driven by a fixture.
 *
 * The server doesn't produce a tree this deep yet — `Navigation::getItems()`
 * builds one level plus whatever a plugin hands over, and element sources
 * never enter the nav at all — so these stories exist to settle the
 * interaction before the navigation map's shape is committed to.
 *
 * `Trail` is the proposed behaviour. `AllFlyouts` and `AllInline` are the two
 * halves it's built from, kept so they can be compared against it.
 */
const meta: Meta<typeof NavTree> = {
  title: 'CP/NavTree',
  component: NavTree,
  argTypes: {
    mode: {
      control: 'inline-radio',
      options: ['trail', 'flyout', 'inline'],
    },
    iconOnly: {
      control: 'boolean',
      description: 'Collapses the root to a rail, which always flyouts.',
    },
  },
  args: {
    items: navFixture,
    mode: 'trail',
    iconOnly: false,
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

const railWidth = 'calc(var(--c-size-touch-target) + var(--c-spacing-md))';

const render = (args: Record<string, unknown>) => ({
  components: {NavTree},
  setup: () => ({args}),
  template: `
    <div :style="{
      width: args.iconOnly ? '${railWidth}' : '220px',
      padding: 'var(--c-spacing-md)',
      background: 'var(--c-color-neutral-fill-quiet)',
      minHeight: '100vh',
    }">
      <craft-nav-list>
        <NavTree v-bind="args" />
      </craft-nav-list>
    </div>
  `,
});

/**
 * The proposed behaviour: you're on `Blog`, so `Content › Entries › Channels`
 * is expanded down the sidebar and your place in it is visible without
 * hovering anything. Every other branch — `Administration`, `Commerce`,
 * `Settings` — flyouts on hover, so getting anywhere else is one gesture and
 * doesn't disturb the column.
 */
export const Trail: Story = {render};

/**
 * The same tree with nothing selected, which is what a flyout-only nav looks
 * like. Nothing is expanded, so the sidebar stays one list tall and reaching a
 * source is three hovers deep.
 */
export const AllFlyouts: Story = {
  render,
  args: {mode: 'flyout'},
};

/**
 * Nothing flyouts: every branch indents, which is four levels in a 220px
 * column. The reason `trail` expands one branch rather than all of them.
 */
export const AllInline: Story = {
  render,
  args: {mode: 'inline'},
};

/**
 * The selection somewhere shallower — `Utilities`, under `Administration`.
 * Only that branch expands; `Content` closes back up into a flyout.
 */
export const TrailToAnotherBranch: Story = {
  render,
  args: {items: selectFixtureItem('Utilities')},
};

/**
 * Collapsed to a rail. `icon-only` forces a flyout regardless of `mode`, since
 * there's no room to indent to.
 */
export const CollapsedRail: Story = {
  render,
  args: {iconOnly: true},
};
