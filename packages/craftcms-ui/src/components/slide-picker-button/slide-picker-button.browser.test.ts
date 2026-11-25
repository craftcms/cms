import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {cdp, page, userEvent} from 'vite-plus/test/browser';
import {contrast} from '../../../test/contrast';
import {outlineRows, pixels} from '../../../test/pixels';
import {movePointerAway} from '../../../test/pointer';
import type CraftSlidePickerButton from './slide-picker-button.js';
import './slide-picker-button.js';
import '../../styles/cp.css';

beforeEach(async () => {
  await page.viewport(414, 720);
  document.body.innerHTML = '';
});

afterEach(async () => {
  await cdp().send('Emulation.setEmulatedMedia', {features: []});
  await cdp().send('Emulation.clearDeviceMetricsOverride');
  await movePointerAway();
});

async function render(markup: string): Promise<CraftSlidePickerButton> {
  document.body.innerHTML = markup;
  const button = document.querySelector<CraftSlidePickerButton>(
    'craft-slide-picker-button'
  )!;
  await button.updateComplete;
  return button;
}

function invoker(button: CraftSlidePickerButton): HTMLElement {
  return button.shadowRoot!.querySelector<HTMLElement>('craft-button')!;
}

function track(button: CraftSlidePickerButton): HTMLElement {
  return button.shadowRoot!.querySelector<HTMLElement>('.segments')!;
}

function divider(segment: Element): string {
  return getComputedStyle(segment, '::after').backgroundColor;
}

function slider(button: CraftSlidePickerButton): HTMLElement {
  return button
    .shadowRoot!.querySelector('craft-slide-picker')!
    .querySelector<HTMLElement>('craft-slide-picker-slider')!;
}

async function open(button: CraftSlidePickerButton): Promise<void> {
  await userEvent.click(invoker(button));
  await expect.poll(() => slider(button).checkVisibility()).toBe(true);
}

it.each([
  {
    attributes:
      'slider-label="Columns" item-label="Recent Entries" value-unit="columns" value="3"',
    name: '3 columns',
    description: 'Recent Entries',
  },
  {
    attributes: 'slider-label="Width" value-unit="% width" value="50"',
    name: '50% width',
    description: '',
  },
])(
  'names the button by the value its tooltip shows, and describes it by the item it sizes: $name',
  async ({attributes, name, description}) => {
    await render(
      `<craft-slide-picker-button min="1" max="100" step="1" ${attributes}></craft-slide-picker-button>`
    );

    await expect.element(page.getByRole('button')).toHaveAccessibleName(name);
    await expect
      .element(page.getByRole('button'))
      .toHaveAccessibleDescription(description);
  }
);

it('names the button with valueLabel', async () => {
  const button = await render(
    `<craft-slide-picker-button slider-label="Columns" item-label="Recent Entries" min="1" max="3" step="1" value="1"></craft-slide-picker-button>`
  );
  button.valueLabel = (value) =>
    `${value} ${value === 1 ? 'column' : 'columns'}`;

  await expect
    .element(page.getByRole('button'))
    .toHaveAccessibleName('1 column');
});

it('names the button by exactly its tooltip’s text, without the tooltip also describing it', async () => {
  await render(
    `<craft-slide-picker-button slider-label="Columns" item-label="Recent Entries" value-unit="columns" min="1" max="4" step="1" value="3"></craft-slide-picker-button>`
  );

  await userEvent.tab();

  await expect
    .element(page.getByRole('tooltip'))
    .toMatchTextContent('3 columns');
  await expect
    .element(page.getByRole('button'))
    .toHaveAccessibleName('3 columns');
  await expect
    .element(page.getByRole('button'))
    .toHaveAccessibleDescription('Recent Entries');
});

it('names the popover’s slider by the label below it', async () => {
  const button = await render(
    `<craft-slide-picker-button slider-label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button>`
  );
  await open(button);

  const label = button.shadowRoot!.querySelector<HTMLElement>('.label')!;
  await expect.element(page.getByRole('slider')).toHaveAccessibleName('Width');
  expect(label.getBoundingClientRect().top).toBeGreaterThanOrEqual(
    slider(button).getBoundingClientRect().bottom
  );
});

it('opens with Enter, sets the value from the slider and returns focus on Escape', async () => {
  const button = await render(
    `<craft-slide-picker-button slider-label="Width" item-label="Body" value-unit="% width" min="25" max="100" step="25" value="50"></craft-slide-picker-button>`
  );
  const events: string[] = [];
  button.addEventListener('input', () => events.push(`input ${button.value}`));
  button.addEventListener('change', () =>
    events.push(`change ${button.value}`)
  );

  invoker(button).focus();
  await userEvent.keyboard('{Enter}');
  await expect.poll(() => slider(button).checkVisibility()).toBe(true);
  await userEvent.tab();
  expect(button.shadowRoot!.activeElement).toBe(slider(button));
  await userEvent.keyboard('{End}');

  expect(events).toEqual(['input 100', 'change 100']);
  await expect
    .element(page.getByRole('button'))
    .toHaveAccessibleName('100% width');

  await userEvent.keyboard('{Escape}');
  await expect.poll(() => slider(button).checkVisibility()).toBe(false);
  expect(button.shadowRoot!.activeElement).toBe(invoker(button));
});

it('drops the preview when the popover closes under the pointer', async () => {
  const button = await render(
    `<craft-slide-picker-button slider-label="Columns" min="1" max="4" step="1" value="1"></craft-slide-picker-button><p>Elsewhere</p>`
  );
  const looks = () =>
    [
      ...slider(button).shadowRoot!.querySelectorAll('.slide-picker__segment'),
    ].map((segment) => {
      const style = getComputedStyle(segment);
      return `${style.borderTopWidth} ${style.borderTopColor} ${style.backgroundColor}`;
    });
  await open(button);
  const resting = looks();

  await userEvent.hover(
    slider(button).shadowRoot!.querySelectorAll('.slide-picker__segment')[2]!
  );
  slider(button).focus();
  await userEvent.keyboard('{Escape}');
  await expect.poll(() => slider(button).checkVisibility()).toBe(false);
  await userEvent.hover(page.getByText('Elsewhere'));
  invoker(button).focus();
  await userEvent.keyboard('{Enter}');
  await expect.poll(() => slider(button).checkVisibility()).toBe(true);

  expect(looks()).toEqual(resting);
});

it('keeps the popover’s slider from changing while read-only', async () => {
  const button = await render(
    `<craft-slide-picker-button slider-label="Width" min="25" max="100" step="25" value="50" readonly></craft-slide-picker-button>`
  );
  await open(button);

  await expect
    .element(page.getByRole('slider'))
    .toHaveAttribute('aria-readonly', 'true');
});

it('disables the button when disabled, or inside a disabled fieldset, and enables it again', async () => {
  await render(
    `<fieldset><craft-slide-picker-button slider-label="Width" value-unit="% width" min="25" max="100" step="25" value="50"></craft-slide-picker-button></fieldset><craft-slide-picker-button slider-label="Height" value-unit="% height" min="25" max="100" step="25" value="50" disabled></craft-slide-picker-button>`
  );
  const fieldset = document.querySelector('fieldset')!;

  await expect
    .element(page.getByRole('button', {name: '50% width'}))
    .toBeEnabled();
  await expect
    .element(page.getByRole('button', {name: '50% height'}))
    .toBeDisabled();

  fieldset.disabled = true;

  await expect
    .element(page.getByRole('button', {name: '50% width'}))
    .toBeDisabled();

  const height = document.querySelectorAll<CraftSlidePickerButton>(
    'craft-slide-picker-button'
  )[1]!;
  height.disabled = false;
  fieldset.disabled = false;

  await expect
    .element(page.getByRole('button', {name: '50% height'}))
    .toBeEnabled();
  await expect
    .element(page.getByRole('button', {name: '50% width'}))
    .toBeEnabled();
});

it.each(['light', 'dark'] as const)(
  'outlines the whole picker, and its filled run, at 3:1 in the %s theme',
  async (theme) => {
    const button = await render(
      `<div class="surface" data-theme="${theme}" style="background-color: var(--c-surface-default); padding: 8px"><craft-slide-picker-button slider-label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button></div>`
    );
    const surface = getComputedStyle(
      document.querySelector('.surface')!
    ).backgroundColor;
    const filled = getComputedStyle(track(button), '::before');
    const empty = getComputedStyle(track(button), '::after');
    const segments = [...button.shadowRoot!.querySelectorAll('.segment')];
    const edges: Record<string, [string, string]> = {
      'outline vs surface': [empty.borderTopColor, surface],
      'filled run outline vs surface': [filled.borderTopColor, surface],
      'filled run end vs empty fill': [
        divider(segments[1]!),
        empty.backgroundColor,
      ],
    };

    expect(
      Object.entries(edges)
        .map(
          ([edge, [color, against]]) =>
            [edge, contrast(color, against, surface)] as const
        )
        .filter(([, ratio]) => ratio < 3)
        .map(([edge, ratio]) => `${edge}: ${ratio.toFixed(2)}`)
    ).toEqual([]);
  }
);

it.each(['light', 'dark'] as const)(
  'shows a focus ring at 3:1 on a keyboard-focused button in the %s theme',
  async (theme) => {
    const button = await render(
      `<div class="surface" data-theme="${theme}" style="background-color: var(--c-surface-default); padding: 8px"><craft-slide-picker-button slider-label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button></div>`
    );
    const surface = getComputedStyle(
      document.querySelector('.surface')!
    ).backgroundColor;

    await userEvent.tab();
    const ring = getComputedStyle(invoker(button));

    expect(button.shadowRoot!.activeElement).toBe(invoker(button));
    expect(ring.outlineStyle).not.toBe('none');
    expect(parseFloat(ring.outlineWidth)).toBeGreaterThanOrEqual(2);
    expect(contrast(ring.outlineColor, surface)).toBeGreaterThanOrEqual(3);
    expect(
      contrast(ring.outlineColor, ring.backgroundColor, surface)
    ).toBeGreaterThanOrEqual(3);
  }
);

it('draws filled segments in Highlight and empty ones in Canvas with a ButtonBorder edge, with lines between them, in forced colors mode', async () => {
  await cdp().send('Emulation.setEmulatedMedia', {
    features: [{name: 'forced-colors', value: 'active'}],
  });
  const button = await render(
    `<craft-slide-picker-button slider-label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button>`
  );
  const filled = getComputedStyle(track(button), '::before');
  const empty = getComputedStyle(track(button), '::after');
  const segments = [...button.shadowRoot!.querySelectorAll('.segment')];
  const probe = document.createElement('span');
  probe.style.forcedColorAdjust = 'none';
  document.body.append(probe);
  const system = (color: string) => {
    probe.style.color = color;
    return getComputedStyle(probe).color;
  };

  expect(filled.backgroundColor).toBe(system('Highlight'));
  expect(empty.backgroundColor).toBe(system('Canvas'));
  expect(empty.borderTopColor).toBe(system('ButtonBorder'));
  expect(segments.slice(0, -1).map(divider)).toEqual([
    system('Canvas'),
    system('Highlight'),
    system('ButtonBorder'),
  ]);
});

it.each([1.25, 1.5])(
  'keeps its outline even, with the lines between segments inside it, at a device pixel ratio of %s',
  async (ratio) => {
    await cdp().send('Emulation.setDeviceMetricsOverride', {
      width: window.innerWidth,
      height: window.innerHeight,
      deviceScaleFactor: ratio,
      mobile: false,
    });
    const uneven: number[] = [];

    for (const offset of [0, 0.2, 0.4, 0.6, 0.8]) {
      const button = await render(
        `<div class="surface" style="background-color: var(--c-surface-default); padding: 8px; padding-block-start: ${8 + offset}px; display: inline-block"><craft-slide-picker-button slider-label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button></div>`
      );
      const surface = document.querySelector('.surface')!;
      const image = await pixels(surface);
      const rows = outlineRows(
        image,
        surface.getBoundingClientRect(),
        track(button).getBoundingClientRect(),
        image.at(1, 1)
      );
      if (rows.size !== 1) {
        uneven.push(offset);
      }
    }

    expect(window.devicePixelRatio).toBe(ratio);
    expect(uneven).toEqual([]);
  }
);

it('is at least 24×24 CSS px, and so is each segment in the popover', async () => {
  const button = await render(
    `<craft-slide-picker-button slider-label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button>`
  );
  const box = invoker(button).getBoundingClientRect();
  expect(box.width).toBeGreaterThanOrEqual(24);
  expect(box.height).toBeGreaterThanOrEqual(24);

  await open(button);
  const segments = [
    ...slider(button).shadowRoot!.querySelectorAll('.slide-picker__segment'),
  ];
  expect(segments).toHaveLength(4);
  for (const segment of segments) {
    const {width, height} = segment.getBoundingClientRect();
    expect(width).toBeGreaterThanOrEqual(24);
    expect(height).toBeGreaterThanOrEqual(24);
  }
});

it('wraps a long label to the slider’s width', async () => {
  const button = await render(
    `<craft-slide-picker-button slider-label="Number of columns the widget spans on the dashboard" min="1" max="3" step="1" value="1"></craft-slide-picker-button>`
  );
  await open(button);

  const label = button.shadowRoot!.querySelector<HTMLElement>('.label')!;
  const sliderBox = slider(button).getBoundingClientRect();
  const labelBox = label.getBoundingClientRect();
  expect(labelBox.width).toBeLessThanOrEqual(sliderBox.width);
  expect(labelBox.height).toBeGreaterThan(
    2 * parseFloat(getComputedStyle(label).lineHeight)
  );
});

it('posts its value under its name, and puts it back on reset', async () => {
  const button = await render(
    `<form><craft-slide-picker-button name="width" slider-label="Width" min="25" max="100" step="25" value="50"></craft-slide-picker-button></form>`
  );
  const form = document.querySelector('form')!;
  expect(new FormData(form).get('width')).toBe('50');

  await open(button);
  slider(button).focus();
  await userEvent.keyboard('{End}');
  expect(new FormData(form).get('width')).toBe('100');

  form.reset();
  await button.updateComplete;
  expect(new FormData(form).get('width')).toBe('50');

  button.disabled = true;
  await button.updateComplete;
  expect(new FormData(form).has('width')).toBe(false);
});
