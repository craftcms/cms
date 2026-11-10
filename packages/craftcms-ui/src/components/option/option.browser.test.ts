import {
  afterEach,
  beforeAll,
  beforeEach,
  describe,
  expect,
  it,
} from 'vite-plus/test';
import {cdp, page, userEvent} from 'vite-plus/test/browser';
import {contrast, loadTokens} from '../../../test/contrast';
import type CraftCombobox from '../combobox/combobox.js';
import '../combobox/combobox.js';
import '../icon/icon.js';
import './option.js';
import '../../styles/cp.css';

beforeAll(loadTokens);

beforeEach(async () => {
  document.body.innerHTML = '';
  document.documentElement.style.fontSize = '';
  await page.viewport(1280, 720);
});

afterEach(async () => {
  document.documentElement.style.fontSize = '';
  await cdp().send('Emulation.setEmulatedMedia', {features: []});
});

async function settle() {
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await new Promise((resolve) => setTimeout(resolve));
}

async function openCombobox(
  configure: (control: CraftCombobox) => void = () => {},
  theme = 'light'
) {
  const surface = document.createElement('div');
  surface.dataset.theme = theme;
  surface.style.backgroundColor = 'var(--c-surface-default)';
  surface.style.padding = '1rem';
  const control = document.createElement('craft-combobox') as CraftCombobox;
  control.setAttribute('aria-label', 'Fruit');
  control.showAllOnEmpty = true;
  control.options = [
    {label: 'Apple', value: 'apple', data: {hint: 'Malus'}},
    {label: 'Banana', value: 'banana', disabled: true},
    {label: 'Cherry', value: 'cherry', data: {hint: 'Prunus'}},
  ];
  configure(control);
  surface.append(control);
  document.body.append(surface);
  await control.updateComplete;
  await settle();
  control.querySelector('input')!.focus();
  await userEvent.keyboard('{ArrowDown}');
  await settle();

  return {
    control,
    options: [...control.querySelectorAll<HTMLElement>('craft-option')],
    popup: control.shadowRoot!.querySelector<HTMLElement>(
      '#overlay-content-node-wrapper'
    )!,
  };
}

function background(element: HTMLElement, popup: HTMLElement): string {
  const own = getComputedStyle(element).backgroundColor;

  return own === 'rgba(0, 0, 0, 0)'
    ? getComputedStyle(popup).backgroundColor
    : own;
}

describe('semantics', () => {
  it('exposes each option with its selected state', async () => {
    await openCombobox((c) => (c.value = 'cherry'));

    await expect
      .element(page.getByRole('option', {name: /^Cherry/, selected: true}))
      .toBeInTheDocument();
    await expect
      .element(page.getByRole('option', {name: /^Apple/, selected: false}))
      .toBeInTheDocument();
  });

  it('exposes a disabled option as unavailable, and ignores clicks on it', async () => {
    const {control, options} = await openCombobox();

    await expect
      .element(page.getByRole('option', {name: 'Banana', disabled: true}))
      .toBeInTheDocument();
    options[1]!.click();
    await settle();

    expect(control.value).toBe('');
  });

  it('takes its name from its content, then its hint, then its suffix, leaving out an unlabeled icon', async () => {
    document.body.innerHTML = `
      <div role="listbox" aria-label="Units">
        <craft-option hint="kg">
          <craft-icon name="star"></craft-icon>Weight<span slot="suffix">Required</span>
        </craft-option>
      </div>`;
    const option = document.querySelector('craft-option')!;
    await (option as HTMLElement & {updateComplete: Promise<unknown>})
      .updateComplete;

    expect(option.querySelector('craft-icon')).toHaveAttribute(
      'aria-hidden',
      'true'
    );
    await expect
      .element(
        page.getByRole('option', {name: 'Weight kg Required', exact: true})
      )
      .toBeInTheDocument();
  });
});

describe('visual', () => {
  describe.each(['light', 'dark'])('in the %s theme', (theme) => {
    it('draws the label at 4.5:1 and the hint at 4.5:1, at rest, hovered, highlighted and selected', async () => {
      const {options, popup} = await openCombobox(
        (c) => (c.value = 'cherry'),
        theme
      );
      const [apple, , cherry] = options;
      const measure = (option: HTMLElement, state: string) => {
        const hint = option.shadowRoot!.querySelector<HTMLElement>('.hint')!;
        const fill = background(option, popup);
        expect
          .soft(
            contrast(getComputedStyle(option).color, fill),
            `${state} label`
          )
          .toBeGreaterThanOrEqual(4.5);
        expect
          .soft(contrast(getComputedStyle(hint).color, fill), `${state} hint`)
          .toBeGreaterThanOrEqual(4.5);
      };

      measure(apple!, 'at rest');
      measure(cherry!, 'selected');
      await userEvent.hover(apple!);
      await settle();
      measure(apple!, 'hovered');
      await userEvent.unhover(apple!);
      await userEvent.keyboard('{ArrowDown}');
      await settle();
      measure(
        options.find((option) => option.hasAttribute('active'))!,
        'highlighted'
      );
    });
  });

  it('marks only the selected option with a check mark, hidden from assistive technology', async () => {
    const {control, options} = await openCombobox((c) => (c.value = 'cherry'));
    const [apple, , cherry] = options;
    const check = cherry!.shadowRoot!.querySelector<HTMLElement>('.check');

    expect(check).not.toBeNull();
    expect(check!.getBoundingClientRect().width).toBeGreaterThan(0);
    await expect.element(cherry!).toHaveAccessibleName('Cherry Prunus');
    await expect
      .element(
        page
          .getByRole('option', {name: 'Cherry Prunus', exact: true})
          .getByRole('img')
      )
      .not.toBeInTheDocument();
    expect(apple!.shadowRoot!.querySelector('.check')).toBeNull();

    control.value = 'apple';
    await settle();

    expect(apple!.shadowRoot!.querySelector('.check')).not.toBeNull();
    expect(cherry!.shadowRoot!.querySelector('.check')).toBeNull();
  });

  describe.each(['light', 'dark'])('in the %s theme', (theme) => {
    it('fills the highlighted option at 3:1 against the rest, but not the selected one, whose check mark reaches 3:1', async () => {
      const {options, popup} = await openCombobox(
        (c) => (c.value = 'cherry'),
        theme
      );
      const [apple, , cherry] = options;
      await userEvent.keyboard('{ArrowDown}');
      await settle();
      expect(apple).toHaveAttribute('active');

      expect(background(cherry!, popup)).toBe(
        getComputedStyle(popup).backgroundColor
      );
      expect(
        contrast(background(apple!, popup), background(cherry!, popup))
      ).toBeGreaterThanOrEqual(3);

      const check = cherry!.shadowRoot!.querySelector<HTMLElement>('.check')!;
      expect(
        contrast(getComputedStyle(check).color, background(cherry!, popup))
      ).toBeGreaterThanOrEqual(3);

      await userEvent.hover(cherry!);
      await settle();
      expect(
        contrast(getComputedStyle(check).color, background(cherry!, popup))
      ).toBeGreaterThanOrEqual(3);
      await userEvent.unhover(cherry!);

      while (!cherry!.hasAttribute('active')) {
        await userEvent.keyboard('{ArrowDown}');
        await settle();
      }
      expect(
        contrast(getComputedStyle(check).color, background(cherry!, popup))
      ).toBeGreaterThanOrEqual(3);
    });
  });

  it('keeps the highlighted and selected options visible in forced colors mode', async () => {
    await cdp().send('Emulation.setEmulatedMedia', {
      features: [{name: 'forced-colors', value: 'active'}],
    });
    const {options, popup} = await openCombobox((c) => (c.value = 'cherry'));
    const [apple, , cherry] = options;
    await userEvent.keyboard('{ArrowDown}');
    await settle();
    expect(apple).toHaveAttribute('active');
    const outline = getComputedStyle(apple!);
    const check = cherry!.shadowRoot!.querySelector<HTMLElement>('.check')!;

    expect(outline.outlineStyle).not.toBe('none');
    expect(parseFloat(outline.outlineWidth)).toBeGreaterThanOrEqual(2);
    expect(
      contrast(outline.outlineColor, getComputedStyle(popup).backgroundColor)
    ).toBeGreaterThanOrEqual(3);
    expect(check.getBoundingClientRect().width).toBeGreaterThan(0);
    expect(
      contrast(
        getComputedStyle(check).color,
        getComputedStyle(popup).backgroundColor
      )
    ).toBeGreaterThanOrEqual(3);
  });

  it('spaces an icon from the label beside it', async () => {
    document.body.innerHTML = `
      <div role="listbox" aria-label="Status" style="width: 20rem">
        <craft-option><craft-icon name="star"></craft-icon>Starred</craft-option>
      </div>`;
    const option = document.querySelector('craft-option')!;
    await (option as HTMLElement & {updateComplete: Promise<unknown>})
      .updateComplete;
    await settle();
    const spacing = document.createElement('div');
    spacing.style.width = 'var(--c-spacing-sm)';
    document.body.append(spacing);
    const label = document.createRange();
    label.selectNodeContents(option.lastChild!);

    const icon = option.querySelector('craft-icon')!.getBoundingClientRect();
    const text = label.getBoundingClientRect();

    expect(text.top).toBeLessThan(icon.bottom);
    expect(text.left - icon.right).toBeGreaterThanOrEqual(
      spacing.getBoundingClientRect().width
    );
    await expect
      .element(page.getByRole('option', {name: 'Starred', exact: true}))
      .toBeInTheDocument();
  });

  it('makes every option at least 24 pixels tall', async () => {
    const {options} = await openCombobox();

    for (const option of options) {
      expect(option.getBoundingClientRect().height).toBeGreaterThanOrEqual(24);
    }
  });
});

describe('reflow and zoom', () => {
  it.each([
    ['a 320 pixel viewport', 320, ''],
    ['200% text size', 1280, '200%'],
  ])(
    'wraps a long label rather than overflowing at %s',
    async (_, width, size) => {
      await page.viewport(width, 640);
      document.documentElement.style.fontSize = size;
      const long =
        'A long option label with plenty of words that cannot fit on one line of a narrow list';
      const {options} = await openCombobox(
        (c) => (c.options = [{label: long, value: 'long'}])
      );
      const option = options[0]!;

      expect(option.scrollWidth).toBeLessThanOrEqual(option.clientWidth);
      expect(option.getBoundingClientRect().right).toBeLessThanOrEqual(width);
      expect(option.getBoundingClientRect().height).toBeGreaterThan(
        parseFloat(getComputedStyle(option).lineHeight) * 1.5
      );
    }
  );
});
