import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftPopover from '../popover/popover.js';
import CraftNavItem from './nav-item.js';
import './nav-item.js';
import '../nav-list/nav-list.js';

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

beforeEach(() => {
  document.body.innerHTML = '';
});

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

    await new Promise((resolve) =>
      setTimeout(resolve, CraftNavItem.flyoutCloseDelay + 50)
    );
    await item.updateComplete;

    expect(item.flyoutOpen).toBe(false);
    expect(flyout(item)!.opened).toBe(false);
  });

  it('cancels a pending close when the pointer comes back', async () => {
    const item = await createFixture();
    await hover(item, 'mouseenter');
    item.dispatchEvent(new MouseEvent('mouseleave'));
    await hover(item, 'mouseenter');

    await new Promise((resolve) =>
      setTimeout(resolve, CraftNavItem.flyoutCloseDelay + 50)
    );

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
});
