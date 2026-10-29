import {
  afterEach,
  beforeAll,
  beforeEach,
  describe,
  expect,
  it,
} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser';
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

afterEach(() => {
  document.documentElement.style.fontSize = '';
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
    popup: control.querySelector<HTMLElement>('[slot=listbox]')!,
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
    await openCombobox((c) => (c.modelValue = 'cherry'));

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

    expect(control.modelValue).toBe('');
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
        (c) => (c.modelValue = 'cherry'),
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
