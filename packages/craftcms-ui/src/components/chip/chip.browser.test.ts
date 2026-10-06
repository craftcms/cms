import {beforeEach, expect, it} from 'vite-plus/test';
import '../thumbnail/thumbnail.js';
import '../truncate/truncate.js';
import './chip.js';

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

it('sizes and rounds the thumbnail to suit the chip', async () => {
  document.body.innerHTML = `
    <style>:root { --c-radius-sm: 3px; }</style>
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
  expect(getComputedStyle(rendered).borderRadius).toBe('3px');
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

it('drops the padding before whatever comes first in a plain chip', async () => {
  const cases = [
    {
      markup: `<div slot="thumbnail"><craft-thumbnail src="${image}"></craft-thumbnail></div>`,
      attrs: 'show-thumb',
      part: '.cp-chip__thumbnail',
    },
    {
      markup:
        '<span slot="status" style="display: block; width: 10px; height: 10px"></span>',
      attrs: 'show-status',
      part: '.cp-chip__status',
    },
    {markup: '', attrs: '', part: '.cp-chip__body'},
  ];

  for (const {markup, attrs, part} of cases) {
    document.body.innerHTML = `
      <craft-chip ${attrs} appearance="plain">${markup}Label</craft-chip>
      <craft-chip ${attrs}>${markup}Label</craft-chip>`;
    const [plain, outlined] = [...document.querySelectorAll('craft-chip')];
    await plain!.updateComplete;
    await outlined!.updateComplete;

    const padding = (chip: Element, side: 'Start' | 'End') =>
      parseFloat(
        getComputedStyle(chip.shadowRoot!.querySelector(part)!)[
          `paddingInline${side}`
        ]
      );

    expect(padding(plain!, 'Start'), part).toBe(0);
    expect(padding(outlined!, 'Start'), part).toBeGreaterThan(0);
  }

  document.body.innerHTML =
    '<craft-chip show-status appearance="plain"><span slot="status"></span>Label</craft-chip>';
  const chip = document.querySelector('craft-chip')!;
  await chip.updateComplete;

  expect(
    parseFloat(
      getComputedStyle(chip.shadowRoot!.querySelector('.cp-chip__body')!)
        .paddingInlineStart
    )
  ).toBeGreaterThan(0);
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
    <style>:root { --c-radius-sm: 3px; }</style>
    <craft-chip show-thumb>
      <img slot="thumbnail" src="${image}" alt="" />
      Label
    </craft-chip>`;
  const chip = document.querySelector('craft-chip')!;
  await chip.updateComplete;

  expect(getComputedStyle(chip.querySelector('img')!).borderRadius).toBe('3px');
  // The source image is 10px; it takes the chip's thumbnail size instead.
  expect(box(chip.querySelector('img')!)).toEqual({width: 30, height: 30});
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
