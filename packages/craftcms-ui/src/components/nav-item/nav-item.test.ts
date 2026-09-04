import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftPopover from '../popover/popover.js';
import CraftNavItem from './nav-item.js';
import './nav-item.js';
import '../nav-list/nav-list.js';
import {flyoutHoverIntent} from '@src/utilities/hover-intent.js';

/**
 * Builds a nav item, optionally with a subnav, and waits for the first render
 * plus the macrotask the overlay defers its controller setup to.
 */
async function createFixture({
  iconOnly = true,
  subnav = true,
  group = false,
}: {
  iconOnly?: boolean;
  subnav?: boolean;
  group?: boolean;
} = {}): Promise<CraftNavItem> {
  const item = document.createElement('craft-nav-item') as CraftNavItem;
  item.setAttribute('icon', 'gear');
  item.setAttribute('href', '/admin/graphql');
  if (iconOnly) {
    item.setAttribute('icon-only', '');
  }
  if (group) {
    item.setAttribute('group', '');
  }
  item.append(document.createTextNode('GraphQL'));

  if (subnav) {
    const list = document.createElement('craft-nav-list');
    list.slot = 'subnav';
    const child = document.createElement('craft-nav-item');
    child.setAttribute('href', '/admin/graphql/schemas');
    child.textContent = 'Schemas';
    list.append(child);
    item.append(list);
  }

  document.body.append(item);
  await item.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
  return item;
}

function flyout(item: CraftNavItem): CraftPopover | null {
  return item.shadowRoot!.querySelector<CraftPopover>('craft-popover');
}

async function hover(item: CraftNavItem, type: string) {
  item.dispatchEvent(new MouseEvent(type));
  await item.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));
}

/**
 * The group is shared across every item, so each test starts from cold. The
 * warm-up is off by default here — the tests that care about it turn it back
 * on — so the rest can assert on hover without waiting one out.
 */
beforeEach(() => {
  document.body.innerHTML = '';
  flyoutHoverIntent.reset();
  flyoutHoverIntent.options = {
    warmUpDelay: 0,
    closeDelay: 30,
    coolDownDelay: 1000,
  };
});

const afterCloseDelay = () =>
  new Promise((resolve) =>
    setTimeout(resolve, flyoutHoverIntent.options.closeDelay + 30)
  );

describe('craft-nav-item flyout', () => {
  it('moves the subnav into a flyout when collapsed to an icon', async () => {
    const item = await createFixture();

    const popover = flyout(item);
    expect(popover).not.toBeNull();

    // The subnav slot belongs to the flyout, and only to the flyout — a slot
    // can only project in one place, so the inline subnav must be gone.
    const slot = popover!.querySelector<HTMLSlotElement>(
      '.flyout slot[name="subnav"]'
    );
    expect(slot).not.toBeNull();
    expect(slot!.assignedElements()).toEqual([
      item.querySelector('[slot="subnav"]'),
    ]);
    expect(item.shadowRoot!.querySelector('.subnav')).toBeNull();
  });

  it('labels the flyout instead of showing the tooltip', async () => {
    const item = await createFixture();

    expect(item.shadowRoot!.querySelector('craft-tooltip')).toBeNull();
    const label = flyout(item)!.querySelector('.flyout__label slot');
    expect(label).not.toBeNull();
    expect((label as HTMLSlotElement).assignedNodes()[0]!.textContent).toBe(
      'GraphQL'
    );
  });

  it('opens the flyout on hover and reports it on the item', async () => {
    const item = await createFixture();
    expect(item.flyoutOpen).toBe(false);

    await hover(item, 'mouseenter');

    expect(item.flyoutOpen).toBe(true);
    expect(flyout(item)!.opened).toBe(true);
    expect(
      item.shadowRoot!.querySelector('.nav-item')!.getAttribute('aria-expanded')
    ).toBe('true');
  });

  it('opens the flyout on focus', async () => {
    const item = await createFixture();

    item.dispatchEvent(new Event('focusin'));
    await item.updateComplete;

    expect(item.flyoutOpen).toBe(true);
  });

  it('stays open across the gap, then closes after the delay', async () => {
    const item = await createFixture();
    await hover(item, 'mouseenter');

    await hover(item, 'mouseleave');
    // Still open: the pointer needs time to travel into the flyout.
    expect(item.flyoutOpen).toBe(true);

    await afterCloseDelay();
    await item.updateComplete;

    expect(item.flyoutOpen).toBe(false);
    expect(flyout(item)!.opened).toBe(false);
  });

  it('cancels a pending close when the pointer comes back', async () => {
    const item = await createFixture();
    await hover(item, 'mouseenter');
    item.dispatchEvent(new MouseEvent('mouseleave'));
    await hover(item, 'mouseenter');

    await afterCloseDelay();

    expect(item.flyoutOpen).toBe(true);
  });

  it('follows the overlay when it closes itself', async () => {
    const item = await createFixture();
    await hover(item, 'mouseenter');

    // Escape and outside clicks are handled by the overlay, not by us.
    const popover = flyout(item)!;
    await popover.hide();
    await item.updateComplete;

    expect(item.flyoutOpen).toBe(false);
  });

  it('flies out for a group item, which never shows a disclosure toggle', async () => {
    // The flyout keys off having a subnav, not off the toggle: a `group` item
    // has a subnav and no toggle, and still needs somewhere to put it.
    const item = await createFixture({group: true});

    expect(flyout(item)).not.toBeNull();
    expect(item.shadowRoot!.querySelector('craft-tooltip')).toBeNull();
  });

  it('keeps the label tooltip when there is no subnav', async () => {
    const item = await createFixture({subnav: false});

    expect(flyout(item)).toBeNull();
    const tooltip = item.shadowRoot!.querySelector('craft-tooltip');
    expect(tooltip).not.toBeNull();
    expect(tooltip!.getAttribute('for')).toBe(`item-${item.id}`);
    expect(
      item.shadowRoot!.querySelector('.nav-item')!.hasAttribute('aria-expanded')
    ).toBe(false);
  });

  it('leaves the subnav inline when the item is not collapsed', async () => {
    const item = await createFixture({iconOnly: false});

    expect(flyout(item)).toBeNull();
    const slot = item.shadowRoot!.querySelector<HTMLSlotElement>(
      '.subnav slot[name="subnav"]'
    );
    expect(slot).not.toBeNull();
    expect(slot!.assignedElements()).toEqual([
      item.querySelector('[slot="subnav"]'),
    ]);
  });

  it("renders a labelled item's subnav in a flyout on request", async () => {
    const item = await createFixture({iconOnly: false});
    item.subnavDisplay = 'flyout';
    await item.updateComplete;

    // The subnav moves out of the indent and into the popover, and there's
    // nothing left inline to collapse, so the toggle goes with it.
    expect(flyout(item)).not.toBeNull();
    expect(item.shadowRoot!.querySelector('.subnav')).toBeNull();
    expect(item.shadowRoot!.querySelector('craft-button')).toBeNull();
  });

  it('reopens the subnav when the selection moves onto it', async () => {
    const item = await createFixture({iconOnly: false});

    expect(item.subnavState).toBe('closed');

    // An Inertia visit patches these elements rather than recreating them, so
    // `connectedCallback` never runs again — without the update hook the
    // branch you navigated into would stay shut.
    item.active = true;
    await item.updateComplete;
    expect(item.subnavState).toBe('open');

    item.active = false;
    await item.updateComplete;
    expect(item.subnavState).toBe('closed');
  });

  it('closes a sibling flyout the moment another one opens', async () => {
    const first = await createFixture();
    const second = await createFixture();

    await hover(first, 'mouseenter');
    expect(first.flyoutOpen).toBe(true);

    // Sweeping down a list: the pointer leaves one item and lands on the next.
    first.dispatchEvent(new MouseEvent('mouseleave'));
    await hover(second, 'mouseenter');

    // Without the handover the first would linger for its whole close delay
    // and the two would overlap.
    expect(first.flyoutOpen).toBe(false);
    expect(second.flyoutOpen).toBe(true);
  });

  it('keeps a parent open when the pointer moves into its flyout', async () => {
    const parent = await createFixture();
    const child = parent.querySelector('craft-nav-item') as CraftNavItem;

    await hover(parent, 'mouseenter');
    // The child's trigger is a descendant of the parent's, so reaching it
    // means travelling through the parent's flyout. Closing "everything else"
    // here would take the flyout out from under the pointer.
    await hover(child, 'mouseenter');

    expect(parent.flyoutOpen).toBe(true);
  });

  it('makes the first flyout wait, then opens the rest immediately', async () => {
    flyoutHoverIntent.options.warmUpDelay = 40;

    const first = await createFixture();
    const second = await createFixture();

    await hover(first, 'mouseenter');
    // Cold: brushing past something shouldn't flash it open.
    expect(first.flyoutOpen).toBe(false);

    await new Promise((resolve) => setTimeout(resolve, 60));
    await first.updateComplete;
    expect(first.flyoutOpen).toBe(true);
    expect(flyoutHoverIntent.warm).toBe(true);

    // Warm: having decided you're reading the nav, waiting again at every
    // item would be worse than useless.
    first.dispatchEvent(new MouseEvent('mouseleave'));
    await hover(second, 'mouseenter');
    expect(second.flyoutOpen).toBe(true);
  });

  it('abandons the warm-up if the pointer leaves before it elapses', async () => {
    flyoutHoverIntent.options.warmUpDelay = 40;

    const item = await createFixture();

    await hover(item, 'mouseenter');
    item.dispatchEvent(new MouseEvent('mouseleave'));

    await new Promise((resolve) => setTimeout(resolve, 60));
    await item.updateComplete;

    expect(item.flyoutOpen).toBe(false);
    expect(flyoutHoverIntent.warm).toBe(false);
  });

  it('goes cold again once the last flyout has closed', async () => {
    flyoutHoverIntent.options.coolDownDelay = 30;

    const item = await createFixture();

    await hover(item, 'mouseenter');
    expect(flyoutHoverIntent.warm).toBe(true);

    await hover(item, 'mouseleave');
    await afterCloseDelay();
    await new Promise((resolve) => setTimeout(resolve, 50));

    // Warmth shouldn't outlive the interaction that earned it.
    expect(flyoutHoverIntent.warm).toBe(false);
  });

  it('leaves a manual toggle alone across unrelated renders', async () => {
    const item = await createFixture({iconOnly: false});

    item.toggleSubnav(new Event('click'));
    await item.updateComplete;
    expect(item.subnavState).toBe('open');

    item.icon = 'wrench';
    await item.updateComplete;
    expect(item.subnavState).toBe('open');
  });
});
