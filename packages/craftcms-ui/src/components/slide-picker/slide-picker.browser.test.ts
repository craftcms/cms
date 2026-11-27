import {afterEach, beforeEach, expect, it} from 'vite-plus/test';
import {contrast} from '../../../test/contrast';
import {outlineRows, pixels} from '../../../test/pixels';
import {movePointerAway} from '../../../test/pointer';
import type CraftSlidePicker from './slide-picker.js';
import './slide-picker.js';

beforeEach(async () => {
  const {page} = await import('vite-plus/test/browser');
  await page.viewport(414, 720);
  // The test file before this one, in the same tab, may have left it on a segment.
  await movePointerAway();
  document.body.innerHTML = '';
});

afterEach(async () => {
  const {cdp} = await import('vite-plus/test/browser');
  await cdp().send('Emulation.setEmulatedMedia', {features: []});
  await cdp().send('Emulation.clearDeviceMetricsOverride');
  await movePointerAway();
});

async function render(
  attributes = '',
  value = 100,
  theme?: 'light' | 'dark'
): Promise<CraftSlidePicker> {
  await import('../../styles/cp.css');
  document.body.innerHTML = `<div class="surface" ${theme ? `data-theme="${theme}"` : ''} style="background-color: var(--c-surface-default); padding: 8px"><craft-slide-picker min="25" max="100" step="25" value="${value}" ${attributes}></craft-slide-picker><p class="elsewhere">Elsewhere</p></div>`;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  await picker.updateComplete;
  // A pointer resting where the picker renders previews a value once the
  // browser next updates hover, a frame after the picker is laid out.
  await new Promise(requestAnimationFrame);
  await new Promise(requestAnimationFrame);
  await slider(picker).updateComplete;
  expect(previewing(picker), 'previewing before any hover').toBe(false);
  return picker;
}

function slider(picker: CraftSlidePicker) {
  return picker.querySelector('craft-slide-picker-slider')!;
}

function previewing(picker: CraftSlidePicker): boolean {
  return !!slider(picker).shadowRoot!.querySelector('.is-previewing');
}

function segments(picker: CraftSlidePicker): HTMLElement[] {
  return [
    ...slider(picker).shadowRoot!.querySelectorAll<HTMLElement>(
      '.slide-picker__segment'
    ),
  ];
}

function surface(): string {
  return getComputedStyle(document.querySelector('.surface')!).backgroundColor;
}

function track(picker: CraftSlidePicker): HTMLElement {
  return slider(picker).shadowRoot!.querySelector<HTMLElement>(
    '.slide-picker__track'
  )!;
}

/** The color each segment is drawn in at its center. */
async function fills(picker: CraftSlidePicker): Promise<string[]> {
  const image = await pixels(document.querySelector('.surface')!);
  return segments(picker).map((segment) => {
    const box = segment.getBoundingClientRect();
    return image.atPoint(box.left + box.width / 2, box.top + box.height / 2);
  });
}

/** How each segment is drawn: its top two rows of pixels, and its center. */
async function looks(picker: CraftSlidePicker): Promise<string[]> {
  const image = await pixels(document.querySelector('.surface')!);
  return segments(picker).map((segment) => {
    const box = segment.getBoundingClientRect();
    const x = box.left + box.width / 2;
    return [box.top + 0.5, box.top + 1.5, box.top + box.height / 2]
      .map((y) => image.atPoint(x, y))
      .join(' ');
  });
}

/** Whether each segment's edge is drawn 2px thick, in other than its fill. */
async function previewed(picker: CraftSlidePicker): Promise<boolean[]> {
  return (await looks(picker)).map((look) => {
    const [, second, center] = look.split(/ (?=rgb)/);
    return second !== center;
  });
}

/** The surface's color, where nothing is drawn on it. */
async function bare(): Promise<string> {
  const surface = document.querySelector('.surface')!;
  return (await pixels(surface)).at(1, 1);
}

it('fills only the segments up to the hovered one', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render();
  const empty = await bare();
  const [selected] = await fills(picker);

  await userEvent.hover(segments(picker)[1]!);
  expect(previewing(picker)).toBe(true);
  const [first, second, third, fourth] = await fills(picker);

  expect(first).not.toBe(empty);
  expect(second).toBe(first);
  expect(first).not.toBe(selected);
  expect(third).toBe(empty);
  expect(fourth).toBe(empty);
});

it('draws every segment at one height, with the selected ones filled alike', async () => {
  const picker = await render('', 50);
  const heights = segments(picker).map(
    (segment) => segment.getBoundingClientRect().height
  );
  const [first, second, third] = await fills(picker);

  expect(new Set(heights).size).toBe(1);
  expect(second).toBe(first);
  expect(third).not.toBe(first);
});

it.each(['light', 'dark'] as const)(
  'edges empty segments, and fills, outlines and divides selected ones, at 3:1 in the %s theme',
  async (theme) => {
    const picker = await render('', 50, theme);
    const back = surface();
    const first = getComputedStyle(track(picker), '::before');
    const empty = getComputedStyle(track(picker), '::after');
    const divider = getComputedStyle(segments(picker)[0]!, '::after');
    const ratios: Record<string, number> = {
      'empty edge vs surface': contrast(empty!.borderTopColor, back),
      'empty edge vs its fill': contrast(
        empty!.borderTopColor,
        empty!.backgroundColor,
        back
      ),
      'selected fill vs surface': contrast(first!.backgroundColor, back),
      'selected outline vs surface': contrast(first!.borderTopColor, back),
      'selected outline vs empty fill': contrast(
        first!.borderTopColor,
        empty!.backgroundColor,
        back
      ),
      'divider vs selected fill': contrast(
        divider.backgroundColor,
        first!.backgroundColor,
        back
      ),
    };

    expect(
      Object.entries(ratios)
        .filter(([, ratio]) => ratio < 3)
        .map(([part, ratio]) => `${part}: ${ratio.toFixed(2)}`)
    ).toEqual([]);
  }
);

it.each(['light', 'dark'] as const)(
  'outlines the previewed run 2px thick at 3:1, unlike the selected run, in the %s theme',
  async (theme) => {
    const {userEvent} = await import('vite-plus/test/browser');
    const picker = await render('', 25, theme);
    const back = surface();
    const resting = await previewed(picker);

    await userEvent.hover(segments(picker)[2]!);
    const run = getComputedStyle(track(picker), '::before');

    expect(resting).toEqual([false, false, false, false]);
    expect(await previewed(picker)).toEqual([true, true, true, false]);
    expect(run.borderTopWidth).toBe('2px');
    expect(contrast(run.borderTopColor, back)).toBeGreaterThanOrEqual(3);
    expect(
      contrast(run.borderTopColor, run.backgroundColor, back)
    ).toBeGreaterThanOrEqual(3);
    expect(segments(picker)[0]!.getBoundingClientRect().height).toBe(
      segments(picker)[3]!.getBoundingClientRect().height
    );
  }
);

it('outlines the previewed run 2px thick in forced colors mode', async () => {
  const {cdp, userEvent} = await import('vite-plus/test/browser');
  await cdp().send('Emulation.setEmulatedMedia', {
    features: [{name: 'forced-colors', value: 'active'}],
  });
  const picker = await render('', 25);

  await userEvent.hover(segments(picker)[2]!);
  const run = getComputedStyle(track(picker), '::before');
  const rest = getComputedStyle(track(picker), '::after');

  expect(await previewed(picker)).toEqual([true, true, true, false]);
  expect(run.borderTopWidth).toBe('2px');
  expect(run.borderTopColor).not.toBe(rest.borderTopColor);
  expect(
    contrast(run.borderTopColor, run.backgroundColor)
  ).toBeGreaterThanOrEqual(3);
});

it('draws read-only segments at full strength', async () => {
  const picker = await render('readonly', 50);

  expect(
    segments(picker).map((segment) => getComputedStyle(segment).opacity)
  ).toEqual(['1', '1', '1', '1']);
});

it('leaves a read-only picker’s fills alone while hovered', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render('readonly');
  const before = await fills(picker);

  await userEvent.hover(segments(picker)[1]!);

  expect(await fills(picker)).toEqual(before);
});

it('previews the current value like any other', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render('', 50);
  const [selected] = await fills(picker);

  await userEvent.hover(segments(picker)[1]!);
  const [first, second, third] = await fills(picker);

  expect(first).not.toBe(selected);
  expect(second).toBe(first);
  expect(third).toBe(await bare());
  expect(getComputedStyle(segments(picker)[1]!).cursor).toBe('pointer');
});

it.each([
  {path: 'from a middle segment', from: 1, to: 'elsewhere'},
  {path: 'from the last segment', from: 3, to: 'elsewhere'},
  {path: 'onto its label', from: 1, to: 'label'},
] as const)(
  'goes back to the selected value when the pointer leaves $path',
  async ({from, to}) => {
    const {page, userEvent} = await import('vite-plus/test/browser');
    const picker = await render('label="Columns"', 50);
    const resting = await looks(picker);

    await userEvent.hover(segments(picker)[from]!);
    expect(await looks(picker)).not.toEqual(resting);
    await userEvent.hover(
      to === 'elsewhere'
        ? page.getByText('Elsewhere')
        : page.getByText('Columns', {exact: true})
    );

    expect(await looks(picker)).toEqual(resting);
  }
);

it.each([
  {value: 25, enter: 3, preview: [true, true, true, true]},
  {value: 25, enter: 1, preview: [true, true, false, false]},
  {value: 100, enter: 2, preview: [true, true, true, false]},
])(
  'previews every segment up to the pointer when it enters on segment $enter with $value selected',
  async ({value, enter, preview}) => {
    const {userEvent} = await import('vite-plus/test/browser');
    const picker = await render('', value);

    await userEvent.hover(segments(picker)[enter]!);

    const empty = await bare();

    expect(await previewed(picker)).toEqual(preview);
    expect((await fills(picker)).filter((_, index) => !preview[index])).toEqual(
      preview.filter((on) => !on).map(() => empty)
    );
  }
);

it('shrinks the preview when the pointer moves back', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  const picker = await render('', 100);

  await userEvent.hover(segments(picker)[3]!);
  await userEvent.hover(segments(picker)[1]!);

  const empty = await bare();

  expect(await previewed(picker)).toEqual([true, true, false, false]);
  expect((await fills(picker)).slice(2)).toEqual([empty, empty]);
});

it('shows a clicked value as selected while the pointer stays on it, and previews again on the next segment', async () => {
  const {userEvent} = await import('vite-plus/test/browser');
  await import('../../styles/cp.css');
  document.body.innerHTML = `<div class="surface" style="background-color: var(--c-surface-default); padding: 8px"><craft-slide-picker id="clicked" min="25" max="100" step="25" value="25"></craft-slide-picker><craft-slide-picker id="reference" min="25" max="100" step="25" value="75"></craft-slide-picker></div>`;
  const [picker, reference] = [
    ...document.querySelectorAll<CraftSlidePicker>('craft-slide-picker'),
  ] as [CraftSlidePicker, CraftSlidePicker];
  for (const element of [picker, reference]) {
    await element.updateComplete;
    await slider(element).updateComplete;
  }

  await userEvent.click(segments(picker)[2]!);
  await slider(picker).updateComplete;

  expect(picker.value).toBe(75);
  expect(segments(picker)[2]!.matches(':hover')).toBe(true);
  expect(await looks(picker)).toEqual(await looks(reference));

  await userEvent.hover(segments(picker)[3]!);

  expect(await previewed(picker)).toEqual([true, true, true, true]);
});

it('is one Tab stop, on the slider', async () => {
  const {page, userEvent} = await import('vite-plus/test/browser');
  await render();

  await userEvent.tab();

  await expect.element(page.getByRole('slider')).toHaveFocus();
});

it.each(['light', 'dark'] as const)(
  'draws one focus ring, around the current segment, at 3:1 in the %s theme',
  async (theme) => {
    const {userEvent} = await import('vite-plus/test/browser');
    const picker = await render('', 50, theme);

    await userEvent.tab();

    const current =
      slider(picker).shadowRoot!.querySelector('.is-last-active')!;
    const ring = getComputedStyle(current);
    expect(getComputedStyle(slider(picker)).outlineStyle).toBe('none');
    expect(ring.outlineStyle).not.toBe('none');
    expect(parseFloat(ring.outlineOffset)).toBeGreaterThan(0);
    expect(contrast(ring.outlineColor, surface())).toBeGreaterThanOrEqual(3);
  }
);

it('tells selected and empty segments apart, divides selected ones, and shows the focus ring, in forced colors mode', async () => {
  const {cdp, userEvent} = await import('vite-plus/test/browser');
  await cdp().send('Emulation.setEmulatedMedia', {
    features: [{name: 'forced-colors', value: 'active'}],
  });
  const picker = await render('', 50);

  await userEvent.tab();
  const selected = getComputedStyle(track(picker), '::before');
  const empty = getComputedStyle(track(picker), '::after');
  const dividers = segments(picker)
    .slice(0, -1)
    .map((segment) => getComputedStyle(segment, '::after').backgroundColor);
  const ring = getComputedStyle(segments(picker)[1]!);
  const probe = document.createElement('span');
  probe.style.forcedColorAdjust = 'none';
  document.body.append(probe);
  const system = (color: string) => {
    probe.style.color = color;
    return getComputedStyle(probe).color;
  };

  expect(selected!.backgroundColor).toBe(system('Highlight'));
  expect(empty!.backgroundColor).toBe(system('Canvas'));
  expect(empty!.borderTopColor).toBe(system('ButtonBorder'));
  expect(dividers).toEqual([
    system('Canvas'),
    system('Highlight'),
    system('ButtonBorder'),
  ]);
  expect(ring.outlineStyle).not.toBe('none');
  expect(ring.outlineColor).not.toBe(empty!.backgroundColor);
});

it.each([1.25, 1.5])(
  'keeps its outline even, with the lines between segments inside it, at a device pixel ratio of %s',
  async (ratio) => {
    const {cdp, userEvent} = await import('vite-plus/test/browser');
    await cdp().send('Emulation.setDeviceMetricsOverride', {
      width: window.innerWidth,
      height: window.innerHeight,
      deviceScaleFactor: ratio,
      mobile: false,
    });
    const uneven: string[] = [];

    for (const offset of [0, 0.2, 0.4, 0.6, 0.8]) {
      const picker = await render('', 50);
      const surface = document.querySelector<HTMLElement>('.surface')!;
      surface.style.paddingBlockStart = `${8 + offset}px`;
      const rows = async () =>
        outlineRows(
          await pixels(surface),
          surface.getBoundingClientRect(),
          track(picker).getBoundingClientRect(),
          await bare()
        );

      if ((await rows()).size !== 1) {
        uneven.push(`${offset}`);
      }
      await userEvent.hover(segments(picker)[2]!);
      expect(
        slider(picker).shadowRoot!.querySelector('.is-previewing')
      ).not.toBeNull();
      if ((await rows()).size !== 1) {
        uneven.push(`${offset} previewing`);
      }
      await movePointerAway();
    }

    expect(window.devicePixelRatio).toBe(ratio);
    expect(uneven).toEqual([]);
  }
);

it('scales its segments with the text size', async () => {
  const picker = await render('', 50);
  // The first segment adds a fixed 1px border on each side, which doesn't scale.
  const border = 2;
  const before = segments(picker)[0]!.getBoundingClientRect();

  document.documentElement.style.fontSize = '200%';
  try {
    const after = segments(picker)[0]!.getBoundingClientRect();

    expect(after.width - border).toBeCloseTo((before.width - border) * 2, 0);
    expect(after.height - border).toBeCloseTo((before.height - border) * 2, 0);
  } finally {
    document.documentElement.style.fontSize = '';
  }
});

it('fits a 320px-wide viewport without scrolling sideways', async () => {
  const {page} = await import('vite-plus/test/browser');
  const [width, height] = [window.innerWidth, window.innerHeight];
  await import('../../styles/cp.css');
  document.body.innerHTML = `<craft-slide-picker min="0" max="100" step="10" value="50"></craft-slide-picker>`;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  await picker.updateComplete;
  await slider(picker).updateComplete;

  await page.viewport(320, 640);
  try {
    expect(document.documentElement.scrollWidth).toBeLessThanOrEqual(320);
    expect(
      segments(picker).at(-1)!.getBoundingClientRect().right
    ).toBeLessThanOrEqual(320);
  } finally {
    await page.viewport(width, height);
  }
});

it('is named by its label, and focused when the label is clicked', async () => {
  const {page} = await import('vite-plus/test/browser');
  await render('label="Columns"');

  await page.getByText('Columns', {exact: true}).click();

  const slider = page.getByRole('slider');
  await expect.element(slider).toHaveAccessibleName('Columns');
  await expect.element(slider).toHaveFocus();
});

it('takes its name and description from a surrounding field', async () => {
  const {page} = await import('vite-plus/test/browser');
  await import('../field/field.js');
  await import('../../styles/cp.css');
  const label = 'Card width';
  const helpText = 'How many columns the card spans.';
  const error = 'Card width must be at least 2 columns.';
  document.body.innerHTML = `<craft-field label="${label}" help-text="${helpText}"><craft-slide-picker slot="input" min="1" max="4" step="1" value="1"></craft-slide-picker><ul slot="feedback" class="errors"><li>${error}</li></ul></craft-field>`;

  const slider = page.getByRole('slider');

  await expect.element(slider).toHaveAccessibleName(label);
  await expect
    .element(slider)
    .toHaveAccessibleDescription(`${helpText} ${error}`);
});

it('has no name of its own until one is given', async () => {
  const {page} = await import('vite-plus/test/browser');
  await render();

  await expect.element(page.getByRole('slider')).toHaveAccessibleName('');
});

it('is named and described by the elements its host references', async () => {
  const {page} = await import('vite-plus/test/browser');
  await import('../../styles/cp.css');
  document.body.innerHTML = `<table><thead><tr><th id="width-heading">Width</th></tr></thead><tbody><tr><td><craft-slide-picker min="1" max="4" step="1" value="2" labelled-by="width-heading" described-by="width-help"></craft-slide-picker></td></tr></tbody></table><p id="width-help">How many columns the field spans.</p>`;

  const slider = page.getByRole('slider');

  await expect.element(slider).toHaveAccessibleName('Width');
  await expect
    .element(slider)
    .toHaveAccessibleDescription('How many columns the field spans.');
});

it('follows changes to the references on its host', async () => {
  const {page} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<span id="first">Width</span><span id="second">Columns</span><craft-slide-picker min="1" max="4" step="1" labelled-by="first"></craft-slide-picker>`;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  const slider = page.getByRole('slider');
  await expect.element(slider).toHaveAccessibleName('Width');

  picker.setAttribute('labelled-by', 'second');
  await expect.element(slider).toHaveAccessibleName('Columns');

  picker.removeAttribute('labelled-by');
  await expect.element(slider).toHaveAccessibleName('');
});

it('comes back from a disabled fieldset, and stays disabled if it was set so', async () => {
  document.body.innerHTML = `
    <form>
      <fieldset>
        <craft-slide-picker name="width" min="1" max="4" step="1" value="2"></craft-slide-picker>
        <craft-slide-picker name="height" min="1" max="4" step="1" value="3" disabled></craft-slide-picker>
      </fieldset>
    </form>`;
  const form = document.querySelector('form')!;
  const fieldset = form.querySelector('fieldset')!;
  const [picker, locked] = [
    ...form.querySelectorAll<CraftSlidePicker>('craft-slide-picker'),
  ] as [CraftSlidePicker, CraftSlidePicker];
  const settle = async () => {
    for (const element of [picker, locked]) {
      await element.updateComplete;
      await slider(element).updateComplete;
    }
  };
  await settle();

  fieldset.disabled = true;
  await settle();
  expect(slider(picker).getAttribute('aria-disabled')).toBe('true');
  expect(slider(picker).tabIndex).toBe(-1);
  expect(getComputedStyle(picker).pointerEvents).toBe('none');
  expect(new FormData(form).has('width')).toBe(false);

  fieldset.disabled = false;
  await settle();
  expect(slider(picker).getAttribute('aria-disabled')).toBe('false');
  expect(slider(picker).tabIndex).toBe(0);
  expect(getComputedStyle(picker).pointerEvents).toBe('auto');
  expect(new FormData(form).getAll('width')).toEqual(['2']);
  expect(locked.disabled).toBe(true);
  expect(slider(locked).getAttribute('aria-disabled')).toBe('true');
  expect(new FormData(form).has('height')).toBe(false);
});

it('keeps its value when the form reset is canceled', async () => {
  document.body.innerHTML = `<form><craft-slide-picker name="columns" min="1" max="4" step="1" value="2"></craft-slide-picker><input name="title" value="Original"></form>`;
  const form = document.querySelector('form')!;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  const input = form.querySelector<HTMLInputElement>('input[name="title"]')!;
  await picker.updateComplete;
  form.addEventListener('reset', (event) => event.preventDefault());

  picker.value = 4;
  input.value = 'Changed';
  await picker.updateComplete;
  form.reset();
  await picker.updateComplete;

  expect(input.value).toBe('Changed');
  expect(picker.value).toBe(4);
  expect(new FormData(form).get('columns')).toBe('4');
});

it('picks up references changed while it was off the page once it is put back', async () => {
  const {page} = await import('vite-plus/test/browser');
  document.body.innerHTML = `<span id="width">Width</span><craft-slide-picker min="1" max="4" step="1"></craft-slide-picker>`;
  const picker =
    document.querySelector<CraftSlidePicker>('craft-slide-picker')!;
  await picker.updateComplete;

  picker.remove();
  picker.setAttribute('labelled-by', 'width');
  await expect(picker.updateComplete).resolves.toBeTypeOf('boolean');

  document.body.append(picker);
  await expect.element(page.getByRole('slider')).toHaveAccessibleName('Width');
});
