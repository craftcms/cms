import type {Meta, StoryObj} from '@storybook/vue3-vite';

import NavTree from './NavTree.vue';
import {navFixture} from './NavTree.fixture';

/**
 * PROTOTYPE. The deep, flyout-capable CP navigation, driven by a fixture.
 *
 * The server doesn't produce a tree this deep yet — `Navigation::getItems()`
 * builds one level plus whatever a plugin hands over, and element sources
 * never enter the nav at all — so these stories exist to settle the
 * interaction before the navigation map's shape is committed to.
 *
 * The question they're here to answer: at what depth does an indented nav stop
 * working, and does a hover flyout actually carry four levels?
 */
const meta: Meta<typeof NavTree> = {
  title: 'CP/NavTree',
  component: NavTree,
  argTypes: {
    flyoutFromDepth: {
      control: {type: 'range', min: 0, max: 3, step: 1},
      description:
        'The first depth whose children move into a flyout. 0 flyouts from ' +
        'the top level; 3 never flyouts within this fixture.',
    },
    iconOnly: {
      control: 'boolean',
      description: 'Collapses the root to a rail, which always flyouts.',
    },
  },
  args: {
    items: navFixture,
    flyoutFromDepth: 1,
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
 * The proposed default: the top level indents in place behind a disclosure
 * toggle, and everything below it opens in a flyout.
 *
 * Hover `Content` → `Entries` to reach the source list, where `Channels`,
 * `Structures` and `Heading` render as `group` headings inside the flyout
 * rather than opening flyouts of their own.
 */
export const FlyoutBelowTheFirstLevel: Story = {render};

/**
 * Every level flyouts, including the root. The sidebar stays one list tall,
 * but reaching a source is three hovers deep.
 */
export const FlyoutFromTheRoot: Story = {
  render,
  args: {flyoutFromDepth: 0},
};

/**
 * Nothing flyouts: four levels of indentation in a 220px column, which is the
 * thing worth looking at before committing to it. Expand `Content` → `Entries`
 * and watch the labels run out of room.
 */
export const FullyIndented: Story = {
  render,
  args: {flyoutFromDepth: 4},
};

/**
 * Collapsed to a rail. `icon-only` forces a flyout regardless of
 * `flyoutFromDepth`, since there's no room to indent to.
 */
export const CollapsedRail: Story = {
  render,
  args: {iconOnly: true},
};
