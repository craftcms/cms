/// <reference types="vite-plus/test/browser/providers/playwright" />
import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {cdp} from 'vite-plus/test/browser';
import type CraftButton from './button.js';
import './button.js';
import {contrast, loadTokens} from '../../../test/contrast';

interface FrameTree {
  frame: {id: string};
  childFrames?: FrameTree[];
}

interface AXNode {
  ignored: boolean;
  role?: {value: string};
  name?: {value: unknown};
}

beforeEach(() => {
  document.body.innerHTML = '';
});

function isFlagged(button: CraftButton): boolean {
  return !!button.shadowRoot?.querySelector('.a11y-error');
}

/** The name the button last computed for itself, which it keeps internal. */
function computedName(button: CraftButton): string {
  return (button as unknown as {_accessibleName: string})._accessibleName;
}

/**
 * Mounts a button inside a container that starts hidden the way `craft-tabs`
 * hides a panel that isn't selected -- `display: none` and `visibility:
 * hidden` together. The inherited `visibility` is what empties the button's
 * name; `display: none` on an ancestor alone doesn't.
 */
async function mountHidden(label: string | null): Promise<{
  button: CraftButton;
  container: HTMLElement;
}> {
  const container = document.createElement('div');
  container.style.cssText = 'display: none; visibility: hidden;';

  const button = document.createElement('craft-button') as CraftButton;
  button.setAttribute('icon', 'image-landscape');

  if (label !== null) {
    button.textContent = label;
  }

  container.append(button);
  document.body.append(container);
  await button.updateComplete;

  // The name is computed asynchronously after the first render. An empty
  // string rather than undefined proves the check ran while still hidden.
  await vi.waitFor(() => expect(computedName(button)).toBe(''));

  return {button, container};
}

it('does not flag a labeled button that was hidden when it first rendered', async () => {
  const {button, container} = await mountHidden('Cropping Rectangle');

  expect(isFlagged(button)).toBe(false);

  container.style.cssText = '';

  await vi.waitFor(() =>
    expect(computedName(button)).toBe('Cropping Rectangle')
  );
  expect(isFlagged(button)).toBe(false);
});

it('still flags an unnamed button once it is shown', async () => {
  const {button, container} = await mountHidden(null);

  // Not judged while hidden: an empty name there says nothing either way.
  expect(isFlagged(button)).toBe(false);

  container.style.cssText = '';

  await vi.waitFor(() => expect(isFlagged(button)).toBe(true));
});

it('flags an unnamed button that is visible from the start', async () => {
  const button = document.createElement('craft-button') as CraftButton;
  button.setAttribute('icon', 'image-landscape');
  document.body.append(button);
  await button.updateComplete;

  await vi.waitFor(() => expect(isFlagged(button)).toBe(true));
});

describe('flush', () => {
  /**
   * A button at the top-left corner of a container, with the tokens and
   * border-box sizing the CP normally supplies.
   */
  async function mountFlush(
    attrs: Record<string, string>,
    label: string | null = 'Edit'
  ): Promise<{button: CraftButton; container: HTMLElement}> {
    const container = document.createElement('div');
    container.style.cssText = [
      'position: absolute',
      // Out of a line box, whose baseline would nudge the button down.
      'display: flex',
      'align-items: flex-start',
      'inset-block-start: 100px',
      'inset-inline-start: 100px',
      'font: 16px/normal sans-serif',
      '--c-size-control-md: 34px',
      '--c-size-control-sm: 24px',
      '--c-form-control-spacing-inline: 12px',
      '--c-spacing-sm: 6px',
    ].join(';');

    const button = document.createElement('craft-button') as CraftButton;
    button.style.boxSizing = 'border-box';
    button.setAttribute('variant', 'plain');
    button.setAttribute('aria-label', 'Edit');
    for (const [name, value] of Object.entries(attrs)) {
      button.setAttribute(name, value);
    }
    if (label !== null) {
      button.textContent = label;
    }

    container.append(button);
    document.body.append(container);
    await button.updateComplete;

    return {button, container};
  }

  function textRect(element: Element): DOMRect {
    const range = document.createRange();
    range.selectNodeContents(element);
    return range.getBoundingClientRect();
  }

  function iconRect(button: CraftButton): DOMRect {
    return button
      .shadowRoot!.querySelector('craft-icon')!
      .getBoundingClientRect();
  }

  it('pulls the label out to the container edges on the inline axis', async () => {
    const {button, container} = await mountFlush({flush: ''});
    const box = container.getBoundingClientRect();
    const label = textRect(button);

    expect(label.left).toBeCloseTo(box.left, 0);
    expect(label.right).toBeCloseTo(box.right, 0);
  });

  it('lines the label up with the text beside it on the block axis', async () => {
    const {button, container} = await mountFlush({flush: ''});
    // Measured against text, not the container: a range rect is the font's
    // text box, which only matches the container's `1lh` in a gapless font.
    const reference = document.createElement('span');
    reference.textContent = 'Edit';
    container.prepend(reference);

    const label = textRect(button);
    const text = textRect(reference);

    expect(label.top).toBeCloseTo(text.top, 0);
    expect(label.bottom).toBeCloseTo(text.bottom, 0);
  });

  it('only pulls in the sides it names', async () => {
    const {button} = await mountFlush({flush: 'inline-end block-start'});
    const style = getComputedStyle(button);

    expect(style.marginInlineStart).toBe('0px');
    expect(style.marginInlineEnd).toBe('-13px');
    expect(parseFloat(style.marginBlockStart)).toBeLessThan(0);
    expect(style.marginBlockEnd).toBe('0px');
  });

  it('takes both sides of an axis from its name', async () => {
    const {button} = await mountFlush({flush: 'inline'});
    const style = getComputedStyle(button);

    expect(style.marginInlineStart).toBe('-13px');
    expect(style.marginInlineEnd).toBe('-13px');
    expect(style.marginBlockStart).toBe('0px');
  });

  it('pulls a danger-plain button out too', async () => {
    const {button} = await mountFlush({
      flush: 'inline-end',
      variant: 'danger-plain',
    });

    expect(getComputedStyle(button).marginInlineEnd).toBe('-13px');
  });

  it('follows the padding of a smaller button', async () => {
    const {button} = await mountFlush({flush: 'inline-start', size: 'small'});

    expect(getComputedStyle(button).marginInlineStart).toBe('-7px');
  });

  it('lines an icon-only button’s icon up instead', async () => {
    const {button, container} = await mountFlush(
      {flush: '', icon: 'pen'},
      null
    );
    const box = container.getBoundingClientRect();
    const icon = iconRect(button);

    expect(icon.left).toBeCloseTo(box.left, 0);
    expect(icon.top).toBeCloseTo(box.top, 0);
  });

  it('leaves a button without it where it is', async () => {
    const {button} = await mountFlush({});
    const style = getComputedStyle(button);

    expect(style.marginInlineStart).toBe('0px');
    expect(style.marginBlockStart).toBe('0px');
  });
});

describe('icon spacing', () => {
  async function mountLabeled(
    attrs: Record<string, string>,
    dir: 'ltr' | 'rtl' = 'ltr'
  ): Promise<{icon: DOMRect; label: DOMRect}> {
    const container = document.createElement('div');
    container.dir = dir;
    container.style.cssText = '--c-spacing-sm: 6px; font: 16px sans-serif';

    const button = document.createElement('craft-button') as CraftButton;
    for (const [name, value] of Object.entries(attrs)) {
      button.setAttribute(name, value);
    }
    button.textContent = 'Edit';
    container.append(button);
    document.body.append(container);
    await button.updateComplete;

    const range = document.createRange();
    range.selectNodeContents(button);

    return {
      icon: button
        .shadowRoot!.querySelector('craft-icon')!
        .getBoundingClientRect(),
      label: range.getBoundingClientRect(),
    };
  }

  it('leaves a gap after a prefix icon', async () => {
    const {icon, label} = await mountLabeled({icon: 'pen'});

    expect(label.left - icon.right).toBeCloseTo(6, 0);
  });

  it('leaves a gap before a suffix icon', async () => {
    const {icon, label} = await mountLabeled({
      icon: 'pen',
      'icon-position': 'suffix',
    });

    expect(icon.left - label.right).toBeCloseTo(6, 0);
  });

  it('keeps the gap between them right to left', async () => {
    const {icon, label} = await mountLabeled({icon: 'pen'}, 'rtl');

    expect(icon.left - label.right).toBeCloseTo(6, 0);
  });
});

describe('[inherit]', () => {
  async function mountOnColoredSurface(
    attributes: string
  ): Promise<CraftButton> {
    const surface = document.createElement('div');
    // Text color and palette deliberately disagree, so the two paths differ.
    surface.style.color = 'rgb(255, 0, 0)';
    surface.style.setProperty('--c-color-neutral-on-quiet', 'rgb(0, 0, 255)');
    surface.innerHTML = `<craft-button variant="plain" icon="chevron-down" aria-label="Actions" ${attributes}></craft-button>`;

    document.body.append(surface);

    const button = surface.querySelector('craft-button') as CraftButton;
    await button.updateComplete;

    return button;
  }

  it('takes the surrounding text color', async () => {
    const button = await mountOnColoredSurface('inherit');

    expect(getComputedStyle(button).color).toBe('rgb(255, 0, 0)');
  });

  it('keeps it while hovered', async () => {
    const button = await mountOnColoredSurface('inherit');

    button.dispatchEvent(new MouseEvent('mouseover', {bubbles: true}));
    await button.updateComplete;

    expect(getComputedStyle(button).color).toBe('rgb(255, 0, 0)');
  });

  it('uses the variant’s own palette color without it', async () => {
    const button = await mountOnColoredSurface('');

    expect(getComputedStyle(button).color).toBe('rgb(0, 0, 255)');
  });
});

describe('outline and dashed borders', () => {
  async function mount(variant: string): Promise<CraftButton> {
    document.body.innerHTML = `
      <div style="--c-form-control-border-color: rgb(0, 128, 0)">
        <craft-button variant="${variant}">Add</craft-button>
      </div>`;
    const button = document.querySelector('craft-button') as CraftButton;
    await button.updateComplete;

    return button;
  }

  it.each(['outline', 'dashed'])(
    'gives a %s button the form control border color',
    async (variant) => {
      const button = await mount(variant);

      expect(getComputedStyle(button).borderTopColor).toBe('rgb(0, 128, 0)');
    }
  );

  it.each(['outline primary', 'dashed danger'])(
    'leaves a %s button its own border color',
    async (variant) => {
      const button = await mount(variant);

      expect(getComputedStyle(button).borderTopColor).not.toBe(
        'rgb(0, 128, 0)'
      );
    }
  );
});

// A transparent button's text sits on the page, not on its color's fill, and
// white's and black's fills are never the page's color (they swap with it).
describe.each(['light', 'dark'])('white and black on a %s page', (theme) => {
  it.each(
    ['outline', 'dashed', 'plain'].flatMap((variant) =>
      ['white', 'black'].map((color) => [variant, color])
    )
  )(
    'keeps the text of a %s button in %s readable against the page',
    async (variant, color) => {
      await import('../../styles/cp.css');
      await loadTokens();
      document.body.innerHTML = `
        <div data-theme="${theme}" style="background-color: var(--c-surface-default)">
          <craft-button variant="${variant}" data-color="${color}">Add</craft-button>
        </div>`;
      const button = document.querySelector('craft-button') as CraftButton;
      await button.updateComplete;

      expect(
        contrast(
          getComputedStyle(button).color,
          getComputedStyle(button.parentElement!).backgroundColor
        )
      ).toBeGreaterThanOrEqual(4.5);
    }
  );
});

describe('themed subtrees', () => {
  async function loadTokens(): Promise<void> {
    await import('../../styles/shared/color-palette.css');
    await import('../../styles/shared/colorable.css');
    await import('../../styles/shared/variables.css');
  }

  function resolved(element: Element, token: string): string {
    return getComputedStyle(element).getPropertyValue(token).trim();
  }

  it('re-resolves the generic color tokens against the subtree’s palette', async () => {
    await loadTokens();

    const dark = document.createElement('div');
    dark.setAttribute('data-theme', 'dark');
    document.body.append(dark);

    const neutral = resolved(dark, '--c-color-neutral-fill-quiet');

    expect(neutral).not.toBe('');
    expect(resolved(dark, '--c-color-fill-quiet')).toBe(neutral);
    expect(resolved(dark, '--c-color-on-quiet')).toBe(
      resolved(dark, '--c-color-neutral-on-quiet')
    );
  });

  it('leaves the root palette alone', async () => {
    await loadTokens();

    const root = document.documentElement;

    expect(resolved(root, '--c-color-fill-quiet')).toBe(
      resolved(root, '--c-color-neutral-fill-quiet')
    );
  });
});

describe('[size=xsmall]', () => {
  async function mountExtraSmall(variant: string): Promise<CraftButton> {
    await import('../../styles/shared/color-palette.css');
    await import('../../styles/shared/colorable.css');
    await import('../../styles/shared/variables.css');
    await import('../../styles/shared/tokens.css');

    const holder = document.createElement('div');
    holder.style.padding = '40px';
    holder.innerHTML = `<craft-button variant="${variant}" size="xsmall" icon="chevron-down" aria-label="Actions"></craft-button>`;
    document.body.append(holder);

    const button = holder.querySelector('craft-button') as CraftButton;
    await button.updateComplete;

    return button;
  }

  it('draws smaller than the minimum target size', async () => {
    const button = await mountExtraSmall('plain');
    const {height} = button.getBoundingClientRect();

    expect(height).toBeGreaterThan(0);
    expect(height).toBeLessThan(24);
  });

  it('still answers a click across the full target area', async () => {
    // Plain switches the sizer off, so this is the variant that would lose its
    // hit area rather than the one that keeps it by default.
    const button = await mountExtraSmall('plain');
    const rect = button.getBoundingClientRect();
    const middle = rect.left + rect.width / 2;

    // Just outside the visible box, inside the 24px target.
    const above = document.elementFromPoint(middle, rect.top - 3);
    const below = document.elementFromPoint(middle, rect.bottom + 3);

    expect(above).toBe(button);
    expect(below).toBe(button);
  });
});

describe('[disabled]', () => {
  async function mountPair(variant: string): Promise<{
    enabled: CraftButton;
    disabled: CraftButton;
  }> {
    await import('../../styles/shared/color-palette.css');
    await import('../../styles/shared/colorable.css');
    await import('../../styles/shared/variables.css');
    await import('../../styles/shared/tokens.css');

    const holder = document.createElement('div');
    holder.innerHTML = `
      <craft-button variant="${variant}">On</craft-button>
      <craft-button variant="${variant}" disabled>Off</craft-button>`;
    document.body.append(holder);

    const [enabled, disabled] = [
      ...holder.querySelectorAll<CraftButton>('craft-button'),
    ];
    await enabled!.updateComplete;
    await disabled!.updateComplete;

    return {enabled: enabled!, disabled: disabled!};
  }

  it('keeps a focusable disabled button hoverable without letting clicks through', async () => {
    const button = document.createElement('craft-button') as CraftButton;
    button.setAttribute('disabled', '');
    button.setAttribute('focusable-when-disabled', '');
    button.textContent = 'Publish';
    const clicked = vi.fn();
    button.addEventListener('click', clicked);
    document.body.append(button);
    await button.updateComplete;

    button.click();

    expect(getComputedStyle(button).pointerEvents).toBe('auto');
    expect(clicked).not.toHaveBeenCalled();
  });

  it('mutes a disabled button', async () => {
    const {enabled, disabled} = await mountPair('fill');

    expect(getComputedStyle(enabled).opacity).toBe('1');
    expect(getComputedStyle(disabled).opacity).toBe('0.5');
  });

  it('keeps the variant’s own fill rather than repainting it', async () => {
    // Lion's own disabled rule paints a flat gray; the variants override it, so
    // muting is what has to carry the state.
    const {enabled, disabled} = await mountPair('fill');

    expect(getComputedStyle(disabled).backgroundColor).toBe(
      getComputedStyle(enabled).backgroundColor
    );
  });
});

describe('[loading]', () => {
  /**
   * The text Chromium exposes in the accessibility tree, across every frame.
   * Screen readers such as JAWS read a button's content from here, so text
   * that's missing from it is unreadable even if the button keeps its name.
   */
  async function accessibleTexts(): Promise<string[]> {
    const session = cdp();
    const {frameTree} = (await session.send('Page.getFrameTree')) as {
      frameTree: FrameTree;
    };
    const frameIds: string[] = [];
    const collect = (tree: FrameTree) => {
      frameIds.push(tree.frame.id);
      tree.childFrames?.forEach(collect);
    };
    collect(frameTree);

    const texts: string[] = [];
    for (const frameId of frameIds) {
      const {nodes} = (await session.send('Accessibility.getFullAXTree', {
        frameId,
      })) as {nodes: AXNode[]};
      for (const node of nodes) {
        if (!node.ignored && node.role?.value === 'StaticText') {
          texts.push(String(node.name?.value ?? ''));
        }
      }
    }

    return texts;
  }

  it('keeps the label in the accessibility tree while loading', async () => {
    const button = document.createElement('craft-button') as CraftButton;
    button.textContent = 'Reset password';
    document.body.append(button);
    await button.updateComplete;

    button.loading = true;
    await button.updateComplete;

    // Reading the whole tree is slow when the suite runs in parallel.
    await vi.waitFor(
      async () =>
        expect(await accessibleTexts()).toEqual(
          expect.arrayContaining(['Reset password', 'Loading'])
        ),
      {timeout: 5000}
    );
  });
});

describe('craft-button link click area', () => {
  async function linkArea(markup: string): Promise<string> {
    document.body.innerHTML = markup;
    const button = document.querySelector('craft-button')!;
    await button.updateComplete;

    return getComputedStyle(
      button.shadowRoot!.querySelector('.link')!,
      '::before'
    ).minWidth;
  }

  it('defaults to a 44px click area', async () => {
    expect(
      await linkArea('<craft-button href="/" size="small">Go</craft-button>')
    ).toBe('44px');
  });

  it('takes its click area from an ancestor', async () => {
    expect(
      await linkArea(
        '<div style="--_link-min-width: 28px"><craft-button href="/" size="small">Go</craft-button></div>'
      )
    ).toBe('28px');
  });

  it('keeps the smaller touch target for extra-small links', async () => {
    expect(
      await linkArea(
        '<div style="--_link-min-width: 28px; --c-size-touch-target-sm: 24px"><craft-button href="/" size="xsmall">Go</craft-button></div>'
      )
    ).toBe('24px');
  });
});

describe('craft-button link padding', () => {
  async function linkPadding(markup: string): Promise<string> {
    document.body.innerHTML = `<div style="--c-button-spacing-inline: 12px">${markup}</div>`;
    const button = document.querySelector('craft-button')!;
    await button.updateComplete;

    return getComputedStyle(button.shadowRoot!.querySelector('.link')!)
      .paddingInlineStart;
  }

  it('pads a link with an icon and a label like one without an icon', async () => {
    const plain = await linkPadding('<craft-button href="/">Go</craft-button>');

    expect(plain).toBe('12px');
    expect(
      await linkPadding(
        '<craft-button href="/" icon="arrow-right" icon-position="suffix">Go</craft-button>'
      )
    ).toBe(plain);
  });

  it('leaves an icon-only link unpadded', async () => {
    expect(
      await linkPadding(
        '<craft-button href="/" icon="arrow-right" aria-label="Go"></craft-button>'
      )
    ).toBe('0px');
  });
});

describe('craft-button link keyboard focus', () => {
  it('is a single tab stop on its anchor', async () => {
    const {userEvent} = await import('vite-plus/test/browser');
    document.body.innerHTML =
      '<button>Before</button><craft-button href="/">Go</craft-button><button>After</button>';
    const [before, after] = document.querySelectorAll('button');
    const button = document.querySelector('craft-button')!;
    await button.updateComplete;

    before!.focus();
    await userEvent.tab();
    expect(button.shadowRoot!.activeElement).toBe(
      button.shadowRoot!.querySelector('a.link')
    );

    await userEvent.tab();
    expect(document.activeElement).toBe(after);
  });

  it('draws the same ring as a button around the same box', async () => {
    const {userEvent} = await import('vite-plus/test/browser');
    await import('../../styles/cp.css');
    document.body.innerHTML =
      '<craft-button variant="link">Button</craft-button> <craft-button variant="link" href="/">Link</craft-button>';
    const [button, link] = document.querySelectorAll('craft-button');
    await Promise.all([button!.updateComplete, link!.updateComplete]);
    const anchor = link!.shadowRoot!.querySelector<HTMLElement>('a.link')!;

    await userEvent.tab();
    const {outlineColor, outlineWidth} = getComputedStyle(button!);
    await userEvent.tab();
    const anchorRing = getComputedStyle(anchor);

    expect(anchorRing.outlineStyle).toBe('solid');
    expect(anchorRing.outlineColor).toBe(outlineColor);
    expect(anchorRing.outlineWidth).toBe(outlineWidth);
    expect(anchor.getBoundingClientRect().height).toBeCloseTo(
      link!.getBoundingClientRect().height -
        2 * parseFloat(getComputedStyle(link!).borderBlockStartWidth),
      0
    );
  });
});

describe('text box', () => {
  async function mount(content: string): Promise<CraftButton> {
    document.body.innerHTML = `<craft-button>${content}</craft-button>`;
    const button = document.querySelector('craft-button')!;
    await button.updateComplete;

    return button;
  }

  it('centers a text label on its capitals without changing the button’s height', async () => {
    const button = await mount('Save');
    const label = button.shadowRoot!.querySelector<HTMLElement>('.label')!;
    const style = getComputedStyle(label);
    const box = button.getBoundingClientRect();
    const rect = label.getBoundingClientRect();
    const capTop = rect.top + parseFloat(style.paddingTop);
    const capBottom = rect.bottom - parseFloat(style.paddingBottom);
    const context = document.createElement('canvas').getContext('2d')!;
    context.font = style.font;
    const capHeight = context.measureText('H').actualBoundingBoxAscent;

    expect(Math.abs(capBottom - capTop - capHeight)).toBeLessThan(1.5);
    expect(capTop - box.top).toBeCloseTo(box.bottom - capBottom, 0);
    expect(box.height).toBeCloseTo(
      parseFloat(getComputedStyle(button).minHeight),
      0
    );
  });

  it('still trims a text label that carries screen-reader-only text', async () => {
    const button = await mount(
      'View <span class="sr-only">Opens in a new window</span>'
    );
    const label = button.shadowRoot!.querySelector<HTMLElement>('.label')!;

    expect(getComputedStyle(label).textBoxTrim).toBe('trim-both');
  });

  it('leaves a label holding elements laid out as before', async () => {
    const button = await mount('<span>Save</span> <span>all</span>');
    const label = button.shadowRoot!.querySelector<HTMLElement>('.label')!;

    expect(getComputedStyle(label).display).toBe('contents');
  });
});

describe('small icon buttons', () => {
  it('sizes an icon the same whether it is an attribute or slotted', async () => {
    document.body.innerHTML = `
      <craft-button size="small" icon="plus"></craft-button>
      <craft-button size="small">
        <craft-icon slot="prefix" name="plus"></craft-icon>
      </craft-button>
      <craft-button size="small" icon="plus">Add</craft-button>
      <craft-button size="small">Add</craft-button>`;
    const [attribute, slotted, labeledIcon, labeled] = Array.from(
      document.querySelectorAll<CraftButton>('craft-button')
    );
    await Promise.all(
      [attribute, slotted, labeledIcon, labeled].map(
        (button) => button!.updateComplete
      )
    );

    expect(getComputedStyle(attribute!).fontSize).toBe(
      getComputedStyle(slotted!).fontSize
    );
    expect(getComputedStyle(labeledIcon!).fontSize).toBe(
      getComputedStyle(labeled!).fontSize
    );
  });
});

describe('craft-button selected state', () => {
  it.each(['outline', 'fill', 'plain'])(
    'keeps a selected %s button’s look while hovered',
    async (variant) => {
      const {userEvent} = await import('vite-plus/test/browser');
      await import('../../styles/cp.css');
      document.body.innerHTML = `<craft-button variant="${variant}" active>Selected</craft-button>`;
      const button = document.querySelector('craft-button')!;
      await button.updateComplete;
      const before = getComputedStyle(button).backgroundColor;

      await userEvent.hover(button);

      expect(button.matches(':hover')).toBe(true);
      expect(getComputedStyle(button).backgroundColor).toBe(before);
    }
  );
});

it('shows an inset shadow while pressed', async () => {
  await loadTokens();
  document.body.innerHTML = `<craft-button class="is-active">Add</craft-button>`;
  const button = document.querySelector('craft-button') as CraftButton;
  await button.updateComplete;

  expect(getComputedStyle(button).boxShadow).toContain('inset');
});

it('draws plain buttons’ focus rings just inside their edges, and others’ outside them', async () => {
  await loadTokens();
  const variants = ['plain', 'danger-plain', 'outline', 'primary'];
  document.body.innerHTML = variants
    .map((variant) => `<craft-button variant="${variant}">Label</craft-button>`)
    .join('');
  const buttons = [...document.querySelectorAll<CraftButton>('craft-button')];
  await Promise.all(buttons.map((button) => button.updateComplete));

  const offsets = buttons.map((button) => {
    button.focus({focusVisible: true});

    return getComputedStyle(button).outlineOffset;
  });

  expect(offsets).toEqual(['-2px', '-2px', '2px', '2px']);
});
