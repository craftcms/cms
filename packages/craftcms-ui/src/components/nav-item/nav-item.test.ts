import {afterEach, beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftPopover from '../popover/popover.js';
import CraftNavItem from './nav-item.js';
import navItemStyles from './nav-item.styles.js';
import popoverStyles from '../popover/popover.styles.js';
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
  href = true,
}: {
  iconOnly?: boolean;
  subnav?: boolean;
  group?: boolean;
  href?: boolean;
} = {}): Promise<CraftNavItem> {
  const item = document.createElement('craft-nav-item') as CraftNavItem;
  item.setAttribute('icon', 'gear');
  if (href) {
    item.setAttribute('href', '/admin/graphql');
  }
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

/** The flyout's content, whose position says how much room is left below. */
function flyoutContent(item: CraftNavItem): HTMLElement | null {
  return item.shadowRoot!.querySelector<HTMLElement>('.flyout');
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
const viewportHeight = window.innerHeight;

// The height tests write to it, and a zero viewport left behind would make
// every flyout after them measure as having no room.
afterEach(() => {
  window.innerHeight = viewportHeight;
});

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

  it('leaves the flyout shut when focus lands on the item', async () => {
    const item = await createFixture();

    item.dispatchEvent(new Event('focusin'));
    await item.updateComplete;

    // Opening here would put every child of every branch in the tab order, so
    // tabbing past a branch would mean tabbing through it.
    expect(item.flyoutOpen).toBe(false);
  });

  it('stays open while focus moves within it', async () => {
    const item = await createFixture();
    await hover(item, 'mouseenter');
    expect(item.flyoutOpen).toBe(true);

    // Tabbing from the item into its own subnav: `focusout` bubbles up from
    // inside the flyout, and closing on it would shut what's being tabbed into.
    const child = item.querySelector('craft-nav-item')!;
    item.dispatchEvent(new FocusEvent('focusout', {relatedTarget: child}));
    await afterCloseDelay();

    expect(item.flyoutOpen).toBe(true);
  });

  it('closes once focus actually leaves', async () => {
    const item = await createFixture();
    await hover(item, 'mouseenter');

    const elsewhere = document.createElement('button');
    document.body.append(elsewhere);
    item.dispatchEvent(new FocusEvent('focusout', {relatedTarget: elsewhere}));
    await afterCloseDelay();

    expect(item.flyoutOpen).toBe(false);
  });

  it('opens the flyout from its own disclosure instead', async () => {
    const item = await createFixture();
    const toggle = item.shadowRoot!.querySelector('.flyout-toggle')!;

    expect(toggle.getAttribute('aria-expanded')).toBe('false');
    expect(toggle.getAttribute('aria-controls')).toBe(`${item.id}-subnav`);
    // Named for what it opens: a row of bare chevrons says nothing about which
    // branch each one belongs to.
    expect(toggle.getAttribute('aria-label')).toContain('GraphQL');

    toggle.dispatchEvent(new MouseEvent('click', {bubbles: true}));
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
    const item = await createFixture({iconOnly: false, group: true});
    item.subnavDisplay = 'flyout';
    await item.updateComplete;

    expect(flyout(item)).not.toBeNull();
    expect(item.shadowRoot!.querySelector('.subnav-toggle')).toBeNull();
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
    // nothing left inline to collapse — so the collapse toggle gives way to
    // the one that opens the flyout.
    expect(flyout(item)).not.toBeNull();
    expect(item.shadowRoot!.querySelector('.subnav')).toBeNull();
    expect(item.shadowRoot!.querySelector('.subnav-toggle')).toBeNull();
    expect(item.shadowRoot!.querySelector('.flyout-toggle')).not.toBeNull();
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

  it('caps the flyout to the room left below it', async () => {
    const item = await createFixture({iconOnly: false});
    item.subnavDisplay = 'flyout';
    await item.updateComplete;

    // happy-dom reports no layout, so stand in for the measurement: a menu
    // whose top edge sits 700px down a 900px viewport has 184px left under it.
    flyoutContent(item)!.getBoundingClientRect = () => ({top: 700}) as DOMRect;
    window.innerHeight = 900;

    item.fitFlyout();

    // On the popover, whose pane is the scroller — so a subnav taller than
    // this scrolls inside it rather than running off the screen.
    expect(
      flyout(item)!.style.getPropertyValue('--popover-max-block-size')
    ).toBe(`${900 - 700 - CraftNavItem.flyoutViewportMargin}px`);
  });

  it('leaves the flyout with exactly one scroller', () => {
    // Asserted against the stylesheets rather than computed styles, which
    // happy-dom doesn't resolve through `var()`.
    const flyoutRule = /\.flyout \{([^}]*)\}/.exec(navItemStyles.cssText)?.[1];

    expect(flyoutRule).toBeDefined();
    // The popover's pane is the scroller. A second one in the content would
    // clip against it, each with its own idea of how tall it may be.
    expect(flyoutRule).not.toMatch(/overflow|max-block-size|max-height/);
    // And the pane's cap has to be the one the item can drive.
    expect(popoverStyles.cssText).toContain('var(--popover-max-block-size');
  });

  it('leaves the cap to the stylesheet when there is nothing to measure', async () => {
    const item = await createFixture({iconOnly: false});
    item.subnavDisplay = 'flyout';
    await item.updateComplete;

    const popover = flyout(item)!;
    popover.style.setProperty('--popover-max-block-size', '100px');
    flyoutContent(item)!.getBoundingClientRect = () => ({top: 0}) as DOMRect;
    window.innerHeight = 0;

    item.fitFlyout();

    // Pinning it to a measurement that isn't real would shut the menu.
    expect(popover.style.getPropertyValue('--popover-max-block-size')).toBe('');
  });

  it('marks an item whose subnav opens beside it', async () => {
    const item = await createFixture({iconOnly: false});
    item.subnavDisplay = 'flyout';
    await item.updateComplete;

    const indicator = item.shadowRoot!.querySelector('.flyout-indicator');

    // A flyout has no toggle, so without this there's nothing to tell it from
    // a leaf until you happen to hover it.
    expect(indicator).not.toBeNull();
    expect(indicator!.getAttribute('name')).toBe('chevron-right');
  });

  it('leaves a collapsible item to its toggle', async () => {
    const item = await createFixture({iconOnly: false});
    await item.updateComplete;

    // Its chevron is in the disclosure toggle; a second one would just be
    // two chevrons saying different things.
    expect(item.shadowRoot!.querySelector('.flyout-indicator')).toBeNull();
    expect(item.shadowRoot!.querySelector('craft-button')).not.toBeNull();
  });

  it('gives a childless item no indicator', async () => {
    const item = await createFixture({iconOnly: false, subnav: false});
    item.subnavDisplay = 'flyout';
    await item.updateComplete;

    expect(item.shadowRoot!.querySelector('.flyout-indicator')).toBeNull();
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

  it('gives both kinds of heading one rule, so they can’t drift', () => {
    // Against the stylesheet rather than computed styles, which happy-dom
    // doesn't resolve through `var()`.
    const hostRule = /:host\(\[group\]\) \{([^}]*)\}/.exec(
      navItemStyles.cssText
    )?.[1];
    const headingRule =
      /:host\(\[group\]\) \.nav-item,\s*\.flyout__label \{([^}]*)\}/.exec(
        navItemStyles.cssText
      )?.[1];

    // A `group` row in the list and the label heading a collapsed item's
    // flyout are the same thing drawn in two places.
    expect(headingRule).toMatch(/font-size/);
    expect(headingRule).toMatch(/font-weight/);

    // A group's subnav is nested inside the host, so type set there would be
    // inherited and every row beneath would read as a heading too.
    expect(hostRule).toBeDefined();
    expect(hostRule).not.toMatch(/font-size|font-weight/);
  });

  it('gives a heading more room above it than below', () => {
    const rowRule = /:host\(\[group\]\) \.nav-item \{([^}]*)\}/.exec(
      navItemStyles.cssText
    )?.[1];

    // It heads the rows under it, so it belongs with them: the gap above
    // separates it from what came before, the one below only sets it off.
    expect(navItemStyles.cssText).toContain(
      'margin-block-start: var(--c-spacing-sm)'
    );
    expect(rowRule).toContain(
      'padding-block: var(--_padding-block) var(--c-spacing-xs)'
    );
  });
});

describe('craft-nav-item rail stand-ins', () => {
  it('stands the first letter in for a missing icon when collapsed', async () => {
    const item = await createFixture({subnav: false});
    item.removeAttribute('icon');
    await item.updateComplete;

    // Collapsed there's no label to read, so an icon-less row would be blank.
    const initial = item.shadowRoot!.querySelector('.nav-item__initial');
    expect(initial?.textContent?.trim()).toBe('G');
    // It's a picture of the label, which `aria-label` already carries.
    expect(initial?.getAttribute('aria-hidden')).toBe('true');
  });

  it('prefers a real icon over the stand-in', async () => {
    const item = await createFixture({subnav: false});

    expect(
      item.shadowRoot!.querySelector('craft-icon.nav-icon')
    ).not.toBeNull();
    expect(item.shadowRoot!.querySelector('.nav-item__initial')).toBeNull();
  });

  it('leaves an expanded item without one, since it shows its label', async () => {
    const item = await createFixture({iconOnly: false, subnav: false});
    item.removeAttribute('icon');
    await item.updateComplete;

    expect(item.shadowRoot!.querySelector('.nav-item__initial')).toBeNull();
  });

  it('indents a rail subnav in place when asked, rather than flying out', async () => {
    const item = await createFixture();
    item.subnavDisplay = 'inline';
    await item.updateComplete;

    // This is what a selected branch gets: its children as a column of
    // stand-ins under it, not a popover you have to hover to see.
    expect(item.shadowRoot!.querySelector('.subnav')).not.toBeNull();
    expect(flyout(item)).toBeNull();
    // And the item keeps the tooltip a childless rail item would have.
    expect(item.shadowRoot!.querySelector('craft-tooltip')).not.toBeNull();
  });

  it('lets you collapse the branch you are in without expanding the nav', async () => {
    const item = await createFixture();
    item.subnavDisplay = 'inline';
    item.active = true;
    await item.updateComplete;

    const toggle = item.shadowRoot!.querySelector('.rail-toggle craft-button')!;
    const subnav = item.shadowRoot!.querySelector<HTMLElement>('.subnav')!;

    // Selected, so it starts open — and it can be shut again from the rail.
    expect(subnav.style.display).toBe('block');
    expect(toggle.getAttribute('aria-expanded')).toBe('true');

    item.toggleSubnav(new Event('click'));
    await item.updateComplete;

    expect(
      item.shadowRoot!.querySelector<HTMLElement>('.subnav')!.style.display
    ).toBe('none');
  });

  it('names the rail toggle by something that is actually rendered', async () => {
    const item = await createFixture();
    item.subnavDisplay = 'inline';
    await item.updateComplete;

    // Expanded it also points at the item's label slot, which the rail has no
    // equivalent of — a dangling idref would leave the button unnamed.
    const toggle = item.shadowRoot!.querySelector('.rail-toggle craft-button')!;
    const target = toggle.getAttribute('aria-labelledby')!;

    expect(target).toBe(`${item.id}-toggle-icon`);
    expect(item.shadowRoot!.getElementById(target)).not.toBeNull();
  });

  it('turns a collapsed heading into the rule between the runs it separates', async () => {
    const item = await createFixture({group: true});

    // No room for the name, and nothing to head but icons.
    const separator = item.shadowRoot!.querySelector('hr.rail-separator');
    expect(separator).not.toBeNull();
    expect(item.shadowRoot!.querySelector('.nav-item')).toBeNull();
    // The name survives for anything reading the nav aloud.
    expect(separator!.getAttribute('aria-label')).toBe('GraphQL');
    // Its children carry on below it.
    expect(item.shadowRoot!.querySelector('.subnav')).not.toBeNull();
  });

  it('keeps a way into a collapsed flyout that isn’t the pointer', async () => {
    const item = await createFixture();
    const toggle = item.shadowRoot!.querySelector('.flyout-toggle')!;

    // Without it a rail flyout would be reachable by pointer alone, since
    // focus deliberately doesn't open one.
    expect(toggle).not.toBeNull();
    expect(toggle.closest('.rail-toggle')).not.toBeNull();

    // It sits over the icon and only shows once focused — by opacity, because
    // `visibility` or `display` would take it out of the tab order and then
    // nothing could ever focus it into view.
    const hidden = /&:not\(:focus-within\) \{([^}]*)\}/.exec(
      navItemStyles.cssText
    )?.[1];

    expect(hidden).toMatch(/opacity:\s*0/);
    expect(hidden).not.toMatch(/visibility|display/);
  });

  it('puts the disclosure ahead of what it opens', async () => {
    const item = await createFixture();
    const nodes = Array.from(item.shadowRoot!.querySelectorAll('*'));

    // Tab order follows the DOM, so a control that comes after the thing it
    // reveals would hand you the contents before the way in.
    expect(
      nodes.findIndex((node) => node.classList.contains('rail-toggle--flyout'))
    ).toBeLessThan(nodes.findIndex((node) => node.tagName === 'CRAFT-POPOVER'));
  });

  it('still flies out by default when collapsed', async () => {
    const item = await createFixture();

    // A rail has nowhere to indent to, so nothing said is still a flyout.
    expect(item.subnavDisplay).toBeUndefined();
    expect(flyout(item)).not.toBeNull();
    expect(item.shadowRoot!.querySelector('.subnav')).toBeNull();
  });
});

describe('craft-nav-item flyout accessibility', () => {
  it('tells a screen reader that a labelled item discloses a flyout', async () => {
    const item = await createFixture({iconOnly: false});
    item.subnavDisplay = 'flyout';
    await item.updateComplete;
    const action = item.shadowRoot!.querySelector('.nav-item__action-item')!;

    // Without these the flyout is invisible to anyone not using a pointer:
    // nothing says the item has more behind it, or whether it's showing.
    expect(action.getAttribute('aria-expanded')).toBe('false');
    expect(action.getAttribute('aria-controls')).toBe(`${item.id}-subnav`);
    expect(flyoutContent(item)!.id).toBe(`${item.id}-subnav`);

    await hover(item, 'mouseenter');

    expect(action.getAttribute('aria-expanded')).toBe('true');
  });

  it('points the rail item at the flyout it expands', async () => {
    const item = await createFixture();
    const action = item.shadowRoot!.querySelector('.nav-item--icon')!;

    expect(action.getAttribute('aria-expanded')).toBe('false');
    expect(action.getAttribute('aria-controls')).toBe(`${item.id}-subnav`);
    expect(flyoutContent(item)!.id).toBe(`${item.id}-subnav`);
  });

  it('names a rail item, whose label is projected away from it', async () => {
    const item = await createFixture();
    const action = item.shadowRoot!.querySelector('.nav-item--icon')!;

    // The label lives in the flyout or the tooltip, so without this the link
    // is an unnamed icon.
    expect(action.getAttribute('aria-label')).toBe('GraphQL');
  });

  it('keeps the anchor out of the flyout aria, which it cannot carry', async () => {
    const item = await createFixture({iconOnly: false});
    item.subnavDisplay = 'flyout';
    await item.updateComplete;
    await hover(item, 'mouseenter');

    // The popover anchors to the row for position; the row is a plain div, so
    // the item's own link is what says it expands.
    expect(
      item
        .shadowRoot!.querySelector(`#item-${item.id}`)!
        .hasAttribute('aria-expanded')
    ).toBe(false);
  });

  it('leaves a childless item without a disclosure to announce', async () => {
    const item = await createFixture({iconOnly: false, subnav: false});
    const action = item.shadowRoot!.querySelector('.nav-item__action-item')!;

    expect(action.getAttribute('aria-expanded')).toBeNull();
    expect(action.getAttribute('aria-controls')).toBeNull();
  });

  it('makes a hrefless item with a flyout a button, so it can be reached', async () => {
    const item = await createFixture({iconOnly: false, href: false});
    item.subnavDisplay = 'flyout';
    await item.updateComplete;
    const action = item.shadowRoot!.querySelector('.nav-item__action-item')!;

    // A span is neither focusable nor allowed to carry `aria-expanded`, which
    // would leave the subnav reachable by pointer only.
    expect(action.tagName).toBe('BUTTON');
    expect(action.getAttribute('type')).toBe('button');
    expect(action.getAttribute('aria-expanded')).toBe('false');
  });

  it('leaves a hrefless item with nothing to disclose as a label', async () => {
    const item = await createFixture({
      iconOnly: false,
      subnav: false,
      href: false,
    });

    expect(
      item.shadowRoot!.querySelector('.nav-item__action-item')!.tagName
    ).toBe('SPAN');
  });

  it('closes the flyout when the disclosure button is clicked', async () => {
    const item = await createFixture({iconOnly: false, href: false});
    item.subnavDisplay = 'flyout';
    await item.updateComplete;
    const action = item.shadowRoot!.querySelector('.nav-item__action-item')!;
    await hover(item, 'mouseenter');
    expect(item.flyoutOpen).toBe(true);

    action.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await item.updateComplete;

    // Hover and focus both open it, so a click on it can only mean dismiss.
    expect(item.flyoutOpen).toBe(false);
    expect(action.getAttribute('aria-expanded')).toBe('false');
  });
});

describe('craft-nav-item navigation', () => {
  it('dispatches craft-navigate with the href when the expanded link is clicked', async () => {
    const item = await createFixture({iconOnly: false, subnav: false});
    const actionItem = item.shadowRoot!.querySelector(
      '.nav-item__action-item'
    )!;
    let detail: {href: string} | null = null;
    item.addEventListener('craft-navigate', (event) => {
      detail = (event as CustomEvent<{href: string}>).detail;
    });

    actionItem.dispatchEvent(new MouseEvent('click', {bubbles: true}));

    expect(detail).toEqual({href: '/admin/graphql'});
  });

  it('dispatches craft-navigate with the href when the collapsed icon is clicked', async () => {
    const item = await createFixture({subnav: false});
    const iconAnchor = item.shadowRoot!.querySelector('.nav-item--icon')!;
    let detail: {href: string} | null = null;
    item.addEventListener('craft-navigate', (event) => {
      detail = (event as CustomEvent<{href: string}>).detail;
    });

    iconAnchor.dispatchEvent(new MouseEvent('click', {bubbles: true}));

    expect(detail).toEqual({href: '/admin/graphql'});
  });

  it('prevents the click when a listener handles craft-navigate itself', async () => {
    const item = await createFixture({iconOnly: false, subnav: false});
    const actionItem = item.shadowRoot!.querySelector(
      '.nav-item__action-item'
    )!;
    item.addEventListener('craft-navigate', (event) => event.preventDefault());
    const click = new MouseEvent('click', {bubbles: true, cancelable: true});

    actionItem.dispatchEvent(click);

    expect(click.defaultPrevented).toBe(true);
  });

  it("doesn't dispatch craft-navigate for a hrefless item", async () => {
    const item = await createFixture({
      iconOnly: false,
      subnav: false,
      href: false,
    });
    const actionItem = item.shadowRoot!.querySelector(
      '.nav-item__action-item'
    )!;
    let dispatched = false;
    item.addEventListener('craft-navigate', () => {
      dispatched = true;
    });

    actionItem.dispatchEvent(new MouseEvent('click', {bubbles: true}));

    expect(dispatched).toBe(false);
  });
});
