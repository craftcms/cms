import {beforeEach, expect, it} from 'vite-plus/test';
import '../icon/icon.js';
import '../thumbnail/thumbnail.js';
import '../truncate/truncate.js';
import './chip.js';
import {contrast} from '../../../test/contrast';

beforeEach(() => {
  document.body.innerHTML = '';
});

const image =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10" fill="#305ce7"/></svg>'
  );

function box(element: Element): {width: number; height: number} {
  const {width, height} = element.getBoundingClientRect();

  return {width: Math.round(width), height: Math.round(height)};
}

it('shows the thumbnail and status alongside custom prefix content', async () => {
  document.body.innerHTML = `
    <craft-chip show-thumb show-status>
      <span slot="prefix" id="prefix">★</span>
      <div slot="thumbnail"><craft-thumbnail src="${image}" alt="Thumbnail"></craft-thumbnail></div>
      <span slot="status" id="status" style="display: block; width: 10px; height: 10px"></span>
      Label
    </craft-chip>`;
  const chip = document.querySelector('craft-chip')!;
  await chip.updateComplete;

  const thumbnail = chip
    .querySelector('craft-thumbnail')!
    .shadowRoot!.querySelector('.thumbnail')!;

  expect(box(document.getElementById('prefix')!).width).toBeGreaterThan(0);
  expect(box(thumbnail).width).toBeGreaterThan(0);
  expect(box(document.getElementById('status')!).width).toBe(10);
});

/**
 * The radius a thumbnail needs to stay concentric with the chip's corner,
 * before any minimum.
 */
function concentricRadius(chip: Element): number {
  const style = getComputedStyle(chip.shadowRoot!.querySelector('.cp-chip')!);
  const padding = parseFloat(
    getComputedStyle(chip.shadowRoot!.querySelector('.cp-chip__thumbnail')!)
      .paddingTop
  );

  return (
    parseFloat(style.borderTopLeftRadius) -
    parseFloat(style.borderTopWidth) -
    padding
  );
}

it('sizes and rounds the thumbnail to suit the chip', async () => {
  document.body.innerHTML = `
    <style>:root { --c-radius-md: 12px; }</style>
    <craft-chip show-thumb size="large">
      <div slot="thumbnail"><craft-thumbnail src="${image}" alt="Thumbnail"></craft-thumbnail></div>
      Label
    </craft-chip>`;
  const chip = document.querySelector('craft-chip')!;
  const thumbnail = chip.querySelector('craft-thumbnail')!;
  await chip.updateComplete;
  await thumbnail.updateComplete;

  const rendered = thumbnail.shadowRoot!.querySelector('.thumbnail__image')!;
  await expect.poll(() => (rendered as HTMLImageElement).complete).toBe(true);

  expect(box(thumbnail.shadowRoot!.querySelector('.thumbnail')!)).toEqual({
    width: 40,
    height: 40,
  });
  expect(concentricRadius(chip)).toBeGreaterThan(0);
  expect(parseFloat(getComputedStyle(rendered).borderRadius)).toBe(
    concentricRadius(chip)
  );
});

it('keeps a thumbnail’s corners rounded when its padding outgrows the chip’s', async () => {
  document.body.innerHTML = `
    <style>:root { --c-radius-md: 4px; }</style>
    <craft-chip show-thumb size="large">
      <div slot="thumbnail"><craft-thumbnail src="${image}"></craft-thumbnail></div>
      Label
    </craft-chip>`;
  const chip = document.querySelector('craft-chip')!;
  const thumbnail = chip.querySelector('craft-thumbnail')!;
  await chip.updateComplete;
  await thumbnail.updateComplete;

  const rendered = thumbnail.shadowRoot!.querySelector('.thumbnail__image')!;

  expect(concentricRadius(chip)).toBeLessThanOrEqual(0);
  expect(parseFloat(getComputedStyle(rendered).borderRadius)).toBeGreaterThan(
    0
  );
});

it('centers a thumbnail that’s smaller than the chip’s thumbnail size', async () => {
  document.body.innerHTML = `
    <craft-chip show-thumb>
      <div slot="thumbnail"><div id="avatar" style="width: 24px; height: 24px"></div></div>
      Label
    </craft-chip>`;
  const chip = document.querySelector('craft-chip')!;
  await chip.updateComplete;

  const slot = chip.shadowRoot!.querySelector('.cp-chip__thumbnail')!;
  const styles = getComputedStyle(slot);
  const outer = slot.getBoundingClientRect();
  const inner = document.getElementById('avatar')!.getBoundingClientRect();

  expect(inner.top - (outer.top + parseFloat(styles.paddingTop))).toBeCloseTo(
    outer.bottom - parseFloat(styles.paddingBottom) - inner.bottom
  );
  expect(
    inner.left - (outer.left + parseFloat(styles.paddingLeft))
  ).toBeCloseTo(outer.right - parseFloat(styles.paddingRight) - inner.right);
});

/** The chip's edges inside its border. */
function chipEdges(chip: Element): {left: number; right: number} {
  const element = chip.shadowRoot!.querySelector('.cp-chip')!;
  const rect = element.getBoundingClientRect();
  const style = getComputedStyle(element);

  return {
    left: rect.left + parseFloat(style.borderLeftWidth),
    right: rect.right - parseFloat(style.borderRightWidth),
  };
}

/** Each kind of leading part, and how to find what it draws. */
const leadingParts = {
  status: {
    attrs: 'show-status',
    markup:
      '<span slot="status" style="display: block; width: 10px; height: 10px"></span>',
    part: (chip: Element) => chip.querySelector('[slot="status"]')!,
  },
  icon: {
    attrs: 'icon="star"',
    markup: '',
    part: (chip: Element) => chip.shadowRoot!.querySelector('craft-icon')!,
  },
  thumbnail: {
    attrs: 'show-thumb',
    markup: `<div slot="thumbnail"><craft-thumbnail src="${image}"></craft-thumbnail></div>`,
    part: (chip: Element) =>
      chip
        .querySelector('craft-thumbnail')!
        .shadowRoot!.querySelector('.thumbnail')!,
  },
};

const suffix =
  '<div slot="suffix"><div id="action" style="width: 16px; height: 16px"></div></div>';

/** A chip sized to its content, so its edges hug its parts. */
async function renderChip(attrs: string, content: string): Promise<Element> {
  document.body.innerHTML = `
    <style>:root { --c-chip-gap: 6px; }</style>
    <div style="display: flex">
      <craft-chip ${attrs}>${content}</craft-chip>
    </div>`;
  const chip = document.querySelector('craft-chip')!;
  await (chip as HTMLElement & {updateComplete: Promise<unknown>})
    .updateComplete;
  await (
    chip.querySelector('craft-thumbnail') as
      | (HTMLElement & {updateComplete: Promise<unknown>})
      | null
  )?.updateComplete;

  return chip;
}

/**
 * The chip's own spacing, measured from a chip that ends with the label and a
 * suffix: the gap between two parts, and the spacing at the trailing edge.
 */
function spacing(chip: Element): {gap: number; edge: number} {
  const label = chip.querySelector('#label')!.getBoundingClientRect();
  const action = chip.querySelector('#action')!.getBoundingClientRect();

  return {
    gap: action.left - label.right,
    edge: chipEdges(chip).right - action.right,
  };
}

/** How far a part sits in from the chip's leading edge. */
function inset(chip: Element, part: Element): number {
  return part.getBoundingClientRect().left - chipEdges(chip).left;
}

const ending = `<span id="label">Label</span>${suffix}`;

it('separates the selection checkbox from the next part by the gap alone', async () => {
  const {attrs, markup, part} = leadingParts.status;
  const chip = await renderChip(`selectable ${attrs}`, `${markup}${ending}`);
  const checkbox = chip.shadowRoot!.querySelector('input')!;

  expect(
    part(chip).getBoundingClientRect().left -
      checkbox.getBoundingClientRect().right
  ).toBeCloseTo(spacing(chip).gap, 0);
});

it('measures the gap after an icon from its glyph', async () => {
  // Narrower than the icon's usual box, as most glyphs are.
  const glyph =
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><rect width="384" height="512"/></svg>';
  const chip = await renderChip(
    '',
    `<craft-icon slot="icon">${glyph}</craft-icon>${ending}`
  );

  expect(
    chip.querySelector('#label')!.getBoundingClientRect().left -
      chip.querySelector('craft-icon svg')!.getBoundingClientRect().right
  ).toBeCloseTo(spacing(chip).gap, 0);
});

it('insets whatever leads the chip by the gap, except a thumbnail or custom prefix', async () => {
  const leads = {
    status: leadingParts.status,
    icon: leadingParts.icon,
    checkbox: {
      attrs: 'selectable',
      markup: '',
      part: (chip: Element) => chip.shadowRoot!.querySelector('input')!,
    },
    label: {
      attrs: '',
      markup: '',
      part: (chip: Element) => chip.querySelector('#label')!,
    },
  };

  for (const size of ['small', 'medium', 'large']) {
    for (const [name, {attrs, markup, part}] of Object.entries(leads)) {
      const chip = await renderChip(
        `size="${size}" ${attrs}`,
        `${markup}${ending}`
      );

      expect(inset(chip, part(chip)), `${size} ${name}`).toBeCloseTo(
        spacing(chip).gap,
        0
      );
    }

    // A thumbnail sits as far in from the leading edge as from the top
    // (at 14px, where half the spacing isn't a whole pixel), and custom
    // content sits where a thumbnail would.
    const {attrs, markup, part} = leadingParts.thumbnail;
    const thumbnail = await renderChip(
      `size="${size}" ${attrs} style="font-size: 14px"`,
      `${markup}${ending}`
    );
    const thumbnailInset = inset(thumbnail, part(thumbnail));
    const chipTop = thumbnail
      .shadowRoot!.querySelector('.cp-chip')!
      .getBoundingClientRect().top;

    expect(thumbnailInset, `${size} thumbnail`).toBeCloseTo(
      part(thumbnail).getBoundingClientRect().top -
        chipTop -
        parseFloat(
          getComputedStyle(thumbnail.shadowRoot!.querySelector('.cp-chip')!)
            .borderTopWidth
        )
    );

    const custom = await renderChip(
      `size="${size}" style="font-size: 14px"`,
      `<span slot="prefix" id="prefix">★</span>${ending}`
    );

    expect(
      inset(custom, custom.querySelector('#prefix')!),
      `${size} prefix`
    ).toBeCloseTo(thumbnailInset);
  }
});

it('sits a plain chip’s parts flush with its edges', async () => {
  const cases = {
    ...leadingParts,
    label: {
      attrs: '',
      markup: '',
      part: (chip: Element) => chip.querySelector('#label')!,
    },
  };

  for (const [name, {attrs, markup, part}] of Object.entries(cases)) {
    for (const trailing of ['', suffix]) {
      const content = `${markup}<span id="label">Label</span>${trailing}`;
      const plain = await renderChip(`appearance="plain" ${attrs}`, content);
      const last = () =>
        document.getElementById(trailing ? 'action' : 'label')!;
      const insets = (chip: Element) => ({
        start: part(chip).getBoundingClientRect().left - chipEdges(chip).left,
        end: chipEdges(chip).right - last().getBoundingClientRect().right,
      });
      const label = `${name}${trailing ? ' with suffix' : ''}`;

      expect(insets(plain).start, label).toBeCloseTo(0);
      expect(insets(plain).end, label).toBeCloseTo(0);

      const outlined = await renderChip(attrs, content);

      expect(insets(outlined).start, label).toBeGreaterThan(0);
      expect(insets(outlined).end, label).toBeGreaterThan(0);
    }
  }
});

// A fraction of a pixel would round onto one side when drawn, leaving uneven
// space above and below the chip's parts.
it('keeps a chip with a thumbnail a whole number of pixels tall', async () => {
  for (const size of ['small', 'medium', 'large']) {
    const {attrs, markup} = leadingParts.thumbnail;
    const chip = await renderChip(
      `size="${size}" ${attrs} style="font-size: 14px"`,
      `${markup}Label`
    );
    const {height} = chip
      .shadowRoot!.querySelector('.cp-chip')!
      .getBoundingClientRect();

    expect(height % 1, size).toBe(0);
  }
});

it('keeps a small chip as tall as its suffix button, unless it is auto', async () => {
  const height = async (size: string, suffix: boolean) => {
    document.body.innerHTML = `
      <style>:root { --c-size-control-sm: 24px; }</style>
      <div style="display: flex">
        <craft-chip size="${size}">
          Label
          ${suffix ? '<div slot="suffix"><div style="width: 24px; height: 24px"></div></div>' : ''}
        </craft-chip>
      </div>`;
    const chip = document.querySelector('craft-chip')!;
    await chip.updateComplete;

    return box(chip.shadowRoot!.querySelector('.cp-chip')!).height;
  };

  expect(await height('small', false)).toBe(await height('small', true));
  expect(await height('auto', false)).toBeLessThan(
    await height('small', false)
  );
});

it('makes medium and large chips as tall as a button of the same size', async () => {
  const sizes = {medium: 34, large: 44};

  for (const [size, control] of Object.entries(sizes)) {
    for (const suffix of [false, true]) {
      document.body.innerHTML = `
        <style>:root { --c-size-control-md: 34px; --c-size-control-lg: 44px; }</style>
        <div style="display: flex">
          <craft-chip size="${size}">
            Label
            ${suffix ? '<div slot="suffix"><div style="width: 24px; height: 24px"></div></div>' : ''}
          </craft-chip>
        </div>`;
      const chip = document.querySelector('craft-chip')!;
      await chip.updateComplete;

      expect(
        box(chip.shadowRoot!.querySelector('.cp-chip')!).height,
        `${size}${suffix ? ' with suffix' : ''}`
      ).toBe(control);
    }
  }
});

it('sizes to its content, or fills its parent when full-width', async () => {
  for (const parent of ['', 'display: flex;']) {
    document.body.innerHTML = `
      <div style="${parent} width: 300px">
        <craft-chip>Label</craft-chip>
        <craft-chip full-width>Label</craft-chip>
      </div>`;
    const [content, full] = [...document.querySelectorAll('craft-chip')];
    await content!.updateComplete;
    await full!.updateComplete;

    const width = (chip: Element) =>
      box(chip.shadowRoot!.querySelector('.cp-chip')!).width;

    expect(width(content!), parent).toBeLessThan(150);
    expect(width(full!), parent).toBe(300 - (parent ? width(content!) : 0));
  }
});

it('truncates the label rather than outgrowing a narrow parent', async () => {
  const parents = {
    block: 'width: 160px',
    'flex row': 'display: flex; width: 160px',
  };

  for (const [parent, style] of Object.entries(parents)) {
    for (const attr of ['', 'full-width']) {
      const name = `${parent} ${attr}`;
      document.body.innerHTML = `
        <div style="${style}">
          <craft-chip show-status ${attr}>
            <span slot="status" id="status" style="display: block; width: 10px; height: 10px"></span>
            <craft-truncate>A label far too long to fit in the chip</craft-truncate>
            <div slot="suffix"><div id="action" style="width: 16px; height: 16px"></div></div>
          </craft-chip>
        </div>`;
      const chip = document.querySelector('craft-chip')!;
      await chip.updateComplete;

      const edges = chipEdges(chip);
      const text = chip
        .querySelector('craft-truncate')!
        .shadowRoot!.querySelector('.truncate')!;

      expect(box(chip.shadowRoot!.querySelector('.cp-chip')!).width, name).toBe(
        160
      );
      expect(text.scrollWidth, name).toBeGreaterThan(text.clientWidth);
      expect(box(document.getElementById('status')!).width, name).toBe(10);
      expect(
        document.getElementById('action')!.getBoundingClientRect().right,
        name
      ).toBeLessThanOrEqual(edges.right);
    }
  }
});

it('aligns the prefix and suffix against the first line when align-items is start', async () => {
  document.body.innerHTML = `
    <craft-chip icon="star">
      <div><div id="first">First</div><div>Second</div><div>Third</div></div>
      <div slot="suffix"><div id="action" style="width: 16px; height: 16px"></div></div>
    </craft-chip>`;
  const chip = document.querySelector('craft-chip')!;
  await chip.updateComplete;

  const centerY = (el: Element) => {
    const rect = el.getBoundingClientRect();
    return rect.top + rect.height / 2;
  };
  const body = chip.querySelector('#first')!.parentElement!;
  const first = chip.querySelector('#first')!;
  const action = chip.querySelector('#action')!;
  const icon = chip.shadowRoot!.querySelector('.cp-chip__icon')!;

  expect(centerY(action)).toBeCloseTo(centerY(body), 0);
  expect(centerY(icon)).toBeCloseTo(centerY(body), 0);

  chip.alignItems = 'start';
  await chip.updateComplete;

  expect(centerY(action)).toBeLessThan(centerY(body));
  expect(centerY(icon)).toBeCloseTo(centerY(first), 0);
});

it('sizes and rounds an image slotted straight into the thumbnail', async () => {
  document.body.innerHTML = `
    <style>:root { --c-radius-md: 12px; --c-size-control-md: 32px; }</style>
    <craft-chip show-thumb>
      <img slot="thumbnail" src="${image}" alt="" />
      Label
    </craft-chip>`;
  const chip = document.querySelector('craft-chip')!;
  await chip.updateComplete;

  expect(
    parseFloat(getComputedStyle(chip.querySelector('img')!).borderRadius)
  ).toBe(concentricRadius(chip));
  // The source image is 10px; it takes the chip's thumbnail size instead,
  // which fits a 32px chip within its border and padding.
  expect(box(chip.querySelector('img')!)).toEqual({width: 26, height: 26});
});

it('takes the theme’s text color when it has no fill of its own', async () => {
  await import('../../styles/shared/color-palette.css');
  await import('../../styles/shared/colorable.css');
  await import('../../styles/shared/variables.css');
  await import('../../styles/shared/tokens.css');

  document.body.innerHTML = `
    <div data-theme="dark">
      <span class="text-default" style="color: var(--c-text-default)"></span>
      <craft-chip appearance="plain">Label</craft-chip>
      <craft-chip appearance="outline">Label</craft-chip>
    </div>`;
  const expected = getComputedStyle(
    document.querySelector('.text-default')!
  ).color;

  for (const chip of document.querySelectorAll('craft-chip')) {
    await chip.updateComplete;

    expect(
      getComputedStyle(chip.shadowRoot!.querySelector('.cp-chip')!).color,
      chip.getAttribute('appearance')!
    ).toBe(expected);
  }
});

function decoration(element: Element): string {
  return getComputedStyle(element).textDecorationLine;
}

/**
 * Element chips nest their label link in a craft-truncate, out of reach of
 * ::slotted(), so the chip hands the decoration down instead.
 */
it('draws a default chip’s border lighter than its fill in the dark theme', async () => {
  await import('../../styles/shared/color-palette.css');
  await import('../../styles/shared/colorable.css');
  await import('../../styles/shared/variables.css');
  await import('../../styles/shared/tokens.css');

  document.body.innerHTML = `
    <div data-theme="dark">
      <craft-chip appearance="outline-fill">Label</craft-chip>
    </div>`;
  const chip = document.querySelector('craft-chip')!;
  await chip.updateComplete;

  const style = getComputedStyle(chip.shadowRoot!.querySelector('.cp-chip')!);
  const luminance = (color: string, over: string): number => {
    const parse = (value: string) => {
      const numbers = value.match(/[\d.]+/g)!.map(Number);
      const scale = value.startsWith('color(') ? 255 : 1;
      return {
        rgb: numbers.slice(0, 3).map((n) => n * scale),
        alpha: numbers[3] ?? 1,
      };
    };
    const {rgb, alpha} = parse(color);
    const base = parse(over).rgb;
    const [r, g, b] = rgb.map((value, i) => {
      const channel = (value * alpha + base[i]! * (1 - alpha)) / 255;
      return channel <= 0.03928
        ? channel / 12.92
        : ((channel + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * r! + 0.7152 * g! + 0.0722 * b!;
  };

  expect(
    luminance(style.borderTopColor, style.backgroundColor)
  ).toBeGreaterThan(luminance(style.backgroundColor, style.backgroundColor));
});

it('does not underline a label link, nested or slotted directly', async () => {
  document.body.innerHTML = `
    <craft-chip id="nested">
      <craft-truncate class="label"><a href="#">Nested</a></craft-truncate>
    </craft-chip>
    <craft-chip id="direct"><a href="#">Direct</a></craft-chip>`;
  for (const chip of document.querySelectorAll('craft-chip')) {
    await (chip as HTMLElement & {updateComplete: Promise<unknown>})
      .updateComplete;
  }

  expect(decoration(document.querySelector('#nested a')!)).toBe('none');
  expect(decoration(document.querySelector('#direct a')!)).toBe('none');
});

it('leaves a link in a truncate outside a chip as it is', async () => {
  document.body.innerHTML = `<craft-truncate><a href="#">Link</a></craft-truncate>`;
  await (
    document.querySelector('craft-truncate') as HTMLElement & {
      updateComplete: Promise<unknown>;
    }
  ).updateComplete;

  expect(decoration(document.querySelector('a')!)).toBe('underline');
});

it('centers a truncated label on its capitals without clipping its descenders', async () => {
  document.body.innerHTML = `
    <div style="width: 120px; font: 14px / 1.5 system-ui, sans-serif">
      <craft-chip>
        <craft-truncate><span id="text">Typography gyp jumpy quaggy</span></craft-truncate>
      </craft-chip>
    </div>`;
  const chip = document.querySelector('craft-chip')!;
  const truncate = document.querySelector('craft-truncate')!;
  await Promise.all([chip.updateComplete, truncate.updateComplete]);

  const clip = truncate.shadowRoot!.querySelector<HTMLElement>('.truncate')!;
  const style = getComputedStyle(clip);
  const rect = clip.getBoundingClientRect();
  const capTop = rect.top + parseFloat(style.paddingTop);
  const capBottom = rect.bottom - parseFloat(style.paddingBottom);
  const body = chip
    .shadowRoot!.querySelector('.cp-chip')!
    .getBoundingClientRect();
  const context = document.createElement('canvas').getContext('2d')!;
  context.font = style.font;
  const capHeight = context.measureText('H').actualBoundingBoxAscent;
  const range = document.createRange();
  range.selectNodeContents(document.getElementById('text')!);

  expect(Math.abs(capBottom - capTop - capHeight)).toBeLessThan(1.5);
  expect(capTop - body.top).toBeCloseTo(body.bottom - capBottom, 0);
  expect(range.getBoundingClientRect().bottom).toBeLessThanOrEqual(rect.bottom);
});

it('draws a selected chip with the accent’s normal border', async () => {
  document.body.innerHTML = `
    <span class="probe" style="border: 1px solid var(--c-color-accent-border-normal)"></span>
    <craft-chip selectable selected>Label</craft-chip>
    <craft-chip selectable selected appearance="plain">Label</craft-chip>`;
  const [chip, plain] = Array.from(document.querySelectorAll('craft-chip'));
  await Promise.all([chip!.updateComplete, plain!.updateComplete]);

  const expected = getComputedStyle(
    document.querySelector('.probe')!
  ).borderTopColor;

  expect(
    getComputedStyle(chip!.shadowRoot!.querySelector('.cp-chip')!)
      .borderTopColor
  ).toBe(expected);
  expect(
    getComputedStyle(plain!.shadowRoot!.querySelector('.cp-chip')!).outlineColor
  ).toBe(expected);
});

// Inheriting controls, like the action menu's button, tint their hover from
// --c-color-fill-quiet. The stamped white palette's is static white, which
// left a white hover on a dark chip.
it.each([
  ['default', ''],
  ['selected', 'selected'],
])(
  'hands its own fill to slotted controls in the dark theme (%s)',
  async (_, attrs) => {
    await import('../../styles/shared/color-palette.css');
    await import('../../styles/shared/colorable.css');
    await import('../../styles/shared/variables.css');
    await import('../../styles/shared/tokens.css');

    document.body.innerHTML = `
      <div data-theme="dark">
        <craft-chip appearance="outline-fill" ${attrs}>
          Label
          <div slot="suffix" id="action" style="background-color: var(--c-color-fill-quiet)">…</div>
        </craft-chip>
      </div>`;
    const chip = document.querySelector('craft-chip')!;
    await chip.updateComplete;

    expect(
      getComputedStyle(document.querySelector('#action')!).backgroundColor
    ).toBe(
      getComputedStyle(chip.shadowRoot!.querySelector('.cp-chip')!)
        .backgroundColor
    );
  }
);

it.each(['plain', 'outline', 'fill'])(
  'colors only the chip’s own icon with its color, not a %s chip’s status',
  async (appearance) => {
    await import('../../styles/cp.css');
    document.body.innerHTML = `
      <craft-chip data-color="amber" appearance="${appearance}" icon="building-columns">
        <craft-icon slot="status" name="scribble" label="Status: Draft"></craft-icon>
        Northern Light
      </craft-chip>`;
    const chip = document.querySelector<
      HTMLElement & {updateComplete: Promise<unknown>}
    >('craft-chip')!;
    await chip.updateComplete;

    const ownIcon = chip.shadowRoot!.querySelector(
      '.cp-chip__icon craft-icon'
    )!;
    const status = chip.querySelector('[slot="status"]')!;
    const reference = document.createElement('span');
    reference.style.color = 'var(--c-text-default)';
    document.body.append(reference);

    expect(getComputedStyle(status).color).toBe(
      getComputedStyle(reference).color
    );
    expect(getComputedStyle(ownIcon).color).not.toBe(
      getComputedStyle(reference).color
    );
  }
);

it('draws a colored chip’s selection checkbox in its text color, with the mark cut out once checked', async () => {
  await import('../../styles/cp.css');
  document.body.innerHTML = `
    <craft-chip data-color="violet" appearance="outline-fill" selectable select-label="Select">Work</craft-chip>`;
  const chip = document.querySelector<
    HTMLElement & {updateComplete: Promise<unknown>}
  >('craft-chip')!;
  await chip.updateComplete;
  const checkbox =
    chip.shadowRoot!.querySelector<HTMLInputElement>('.cp-chip__select')!;
  const text = getComputedStyle(
    chip.shadowRoot!.querySelector('.cp-chip')!
  ).color;

  expect(getComputedStyle(checkbox).appearance).toBe('none');
  expect(getComputedStyle(checkbox).borderTopColor).toBe(text);

  checkbox.checked = true;

  expect(getComputedStyle(checkbox).backgroundColor).toBe(text);
  expect(getComputedStyle(checkbox).maskImage).toContain('svg');
});

it('keeps the standard checkbox colors on a white chip', async () => {
  await import('../../styles/cp.css');
  document.body.innerHTML = `
    <craft-chip selectable select-label="Select">Default</craft-chip>`;
  const chip = document.querySelector<
    HTMLElement & {updateComplete: Promise<unknown>}
  >('craft-chip')!;
  await chip.updateComplete;
  const checkbox =
    chip.shadowRoot!.querySelector<HTMLInputElement>('.cp-chip__select')!;
  const reference = document.createElement('span');
  reference.style.borderColor = 'var(--c-form-control-border-color)';
  reference.style.color = 'var(--c-color-accent-fill-loud)';
  document.body.append(reference);

  expect(chip.getAttribute('data-color')).toBe('white');
  expect(getComputedStyle(checkbox).borderTopColor).toBe(
    getComputedStyle(reference).borderTopColor
  );

  checkbox.checked = true;

  expect(getComputedStyle(checkbox).backgroundColor).toBe(
    getComputedStyle(reference).color
  );
});

it('sizes the selection checkbox at 14px, with 24px of clear space around it', async () => {
  await import('../../styles/cp.css');
  document.body.innerHTML = `
    <craft-chip data-color="violet" size="small" selectable select-label="Select"><a href="#">Work</a></craft-chip>`;
  const chip = document.querySelector<
    HTMLElement & {updateComplete: Promise<unknown>}
  >('craft-chip')!;
  await chip.updateComplete;
  const box = chip
    .shadowRoot!.querySelector('.cp-chip__select')!
    .getBoundingClientRect();
  const link = chip.querySelector('a')!.getBoundingClientRect();
  const centerX = box.left + box.width / 2;
  const centerY = box.top + box.height / 2;
  const nearestX = Math.max(link.left, Math.min(centerX, link.right));
  const nearestY = Math.max(link.top, Math.min(centerY, link.bottom));

  expect(box.width).toBeCloseTo(14, 0);
  expect(box.height).toBeCloseTo(14, 0);
  expect(
    Math.hypot(centerX - nearestX, centerY - nearestY)
  ).toBeGreaterThanOrEqual(12);
});

it('gives a colored chip a card body’s background, and its color’s normal fill when selected', async () => {
  await import('../../styles/cp.css');
  await import('../card/card.js');
  document.body.innerHTML = `
    <craft-chip data-color="violet" appearance="outline-fill">Work</craft-chip>
    <craft-card data-color="violet">Body</craft-card>`;
  const chip = document.querySelector<
    HTMLElement & {updateComplete: Promise<unknown>}
  >('craft-chip')!;
  const card = document.querySelector<
    HTMLElement & {updateComplete: Promise<unknown>}
  >('craft-card')!;
  await Promise.all([chip, card].map((el) => el.updateComplete));
  const chipStyle = () =>
    getComputedStyle(chip.shadowRoot!.querySelector('.cp-chip')!);
  const body = getComputedStyle(card.shadowRoot!.querySelector('.card')!);
  const normal = document.createElement('span');
  normal.dataset.color = 'violet';
  normal.style.backgroundColor = 'var(--c-color-fill-normal)';
  normal.style.color = 'var(--c-color-on-normal)';
  document.body.append(normal);

  expect(chipStyle().backgroundColor).toBe(body.backgroundColor);
  expect(chipStyle().color).toBe(body.color);

  chip.setAttribute('selected', '');
  await chip.updateComplete;

  expect(chipStyle().backgroundColor).toBe(
    getComputedStyle(normal).backgroundColor
  );
  expect(chipStyle().color).toBe(getComputedStyle(normal).color);
});

it.each(['light', 'dark'])(
  'keeps colored chips’ text at 4.5:1, selected or not, in the %s theme',
  async (theme) => {
    await import('../../styles/cp.css');
    const {Color} = await import('../../constants/colors');
    const colors = Object.values(Color).filter(
      (color) => color !== 'white' && color !== 'black'
    );
    document.body.innerHTML = `
      <div data-theme="${theme}" style="background-color: var(--c-surface-default)">
        ${colors
          .flatMap((color) => [
            `<craft-chip data-color="${color}" appearance="outline-fill">${color}</craft-chip>`,
            `<craft-chip data-color="${color}" appearance="outline-fill" selected>${color}</craft-chip>`,
          ])
          .join('')}
      </div>`;
    const surface = getComputedStyle(
      document.querySelector('[data-theme]')!
    ).backgroundColor;
    const failures: string[] = [];

    for (const chip of document.querySelectorAll<
      HTMLElement & {updateComplete: Promise<unknown>}
    >('craft-chip')) {
      await chip.updateComplete;
      const style = getComputedStyle(
        chip.shadowRoot!.querySelector('.cp-chip')!
      );
      const ratio = contrast(style.color, style.backgroundColor, surface);
      if (ratio < 4.5) {
        failures.push(
          `${chip.dataset.color}${chip.hasAttribute('selected') ? ' (selected)' : ''}: ${ratio.toFixed(2)}`
        );
      }
    }

    expect(failures).toEqual([]);
  }
);

it('tints a selected colored chip’s controls from its own fill, not the accent color', async () => {
  await import('../../styles/cp.css');
  document.body.innerHTML = `
    <craft-chip data-color="violet" appearance="outline-fill" selected>
      Work
      <span slot="suffix" id="control" style="background-color: var(--c-color-fill-quiet)"></span>
    </craft-chip>`;
  const chip = document.querySelector<
    HTMLElement & {updateComplete: Promise<unknown>}
  >('craft-chip')!;
  await chip.updateComplete;
  const own = document.createElement('span');
  own.dataset.color = 'violet';
  own.style.backgroundColor = 'var(--c-color-fill-normal)';
  document.body.append(own);

  expect(
    getComputedStyle(document.getElementById('control')!).backgroundColor
  ).toBe(getComputedStyle(own).backgroundColor);
});

it('makes non-plain chips as tall as a medium button, thumbnail included', async () => {
  await import('../../styles/cp.css');
  document.body.innerHTML = `
    <div><craft-chip>Label</craft-chip></div>
    <div><craft-chip show-thumb>
      <div slot="thumbnail"><craft-thumbnail src="${image}"></craft-thumbnail></div>
      Label
    </craft-chip></div>
    <div><craft-chip size="medium" show-thumb>
      <div slot="thumbnail"><craft-thumbnail src="${image}"></craft-thumbnail></div>
      Label
    </craft-chip></div>
    <div><craft-chip appearance="plain">Label</craft-chip></div>
  `;
  const chips = [...document.querySelectorAll('craft-chip')];
  for (const chip of chips) {
    await chip.updateComplete;
  }
  const heights = chips.map(
    (chip) => box(chip.shadowRoot!.querySelector('.cp-chip')!).height
  );

  expect(heights.slice(0, 3)).toEqual([32, 32, 32]);
  expect(heights[3]).toBeLessThan(32);
});

it('styles the badges in a filled black chip as in the dark theme', async () => {
  await import('../../styles/cp.css');
  await import('../badge/badge.js');
  const badge = `<craft-badge>Badge</craft-badge>`;
  document.body.innerHTML = `
    <div id="black"><craft-chip data-color="black">${badge}</craft-chip></div>
    <div id="plain"><craft-chip data-color="black" appearance="plain">${badge}</craft-chip></div>
    <div id="dark" data-theme="dark">${badge}</div>
    <div id="light">${badge}</div>`;
  for (const element of document.querySelectorAll<
    HTMLElement & {updateComplete: Promise<unknown>}
  >('craft-chip, craft-badge')) {
    await element.updateComplete;
  }
  const look = (id: string) => {
    const element = document.querySelector(`#${id} craft-badge`)!;
    const inner = element.shadowRoot!.firstElementChild!;
    const style = getComputedStyle(
      inner instanceof HTMLStyleElement ? inner.nextElementSibling! : inner
    );

    return [
      getComputedStyle(element).colorScheme,
      style.backgroundColor,
      style.borderTopColor,
    ];
  };

  expect(look('black')).toEqual(look('dark'));
  expect(look('plain')).toEqual(look('light'));
});

it('colors only the icon of a plain colored chip, not its label', async () => {
  await import('../../styles/cp.css');
  document.body.innerHTML = `
    <craft-chip data-color="amber" appearance="plain" icon="building-columns"><span id="plain-label">Exhibit</span></craft-chip>
    <craft-chip data-color="amber" appearance="outline"><span id="outline-label">Exhibit</span></craft-chip>
    <span id="text">Text</span>`;
  for (const chip of document.querySelectorAll('craft-chip')) {
    await (chip as HTMLElement & {updateComplete: Promise<unknown>})
      .updateComplete;
  }
  const color = (element: Element) => getComputedStyle(element).color;
  const plain = document.querySelector('craft-chip')!;

  expect(color(document.getElementById('plain-label')!)).toBe(
    color(document.getElementById('text')!)
  );
  expect(color(plain.shadowRoot!.querySelector('.cp-chip__icon')!)).toBe(
    color(document.getElementById('outline-label')!)
  );
  expect(color(document.getElementById('outline-label')!)).not.toBe(
    color(document.getElementById('text')!)
  );
});
