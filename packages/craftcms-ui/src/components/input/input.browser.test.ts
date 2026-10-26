import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInput from './input.js';
import {contrast} from '../../../test/contrast';
import type CraftInputDateTime from '../input-date-time/input-date-time.js';
import './input.js';
import '../button/button.js';
import '../input-date-time/input-date-time.js';

beforeEach(() => {
  document.body.innerHTML = '';
  document.body.style.setProperty('--c-spacing-sm', '8px');
});

function inputOffset(element: HTMLElement): {top: number; bottom: number} {
  const host = element.getBoundingClientRect();
  const input = element.querySelector('input')!.getBoundingClientRect();

  return {top: input.top - host.top, bottom: host.bottom - input.bottom};
}

describe('craft-input label spacing', () => {
  it('spaces a label from the control', async () => {
    const element = document.createElement('craft-input') as CraftInput;
    element.label = 'Title';
    document.body.append(element);
    await element.updateComplete;

    const label = element.shadowRoot!.querySelector('.form-field__label')!;
    expect(getComputedStyle(label).marginBlockEnd).toBe('8px');
  });

  it('adds no space above the control when there is no label', async () => {
    const container = document.createElement('div');
    container.style.display = 'flow-root';
    const element = document.createElement('craft-input') as CraftInput;
    element.setAttribute('aria-label', 'Title');
    container.append(element);
    document.body.append(container);
    await element.updateComplete;

    expect(inputOffset(container)).toEqual({top: 0, bottom: 0});
  });

  it('adds no space above the date and time inputs of craft-input-date-time', async () => {
    const element = document.createElement(
      'craft-input-date-time'
    ) as CraftInputDateTime;
    element.name = 'postDate';
    document.body.append(element);
    await element.updateComplete;

    const parts = [
      ...element.querySelectorAll<CraftInput>(
        'craft-input-date, craft-input-time'
      ),
    ];
    await Promise.all(parts.map((part) => part.updateComplete));

    expect(parts).toHaveLength(2);
    for (const part of parts) {
      expect(inputOffset(part)).toEqual({top: 0, bottom: 0});
    }
  });
});

describe('craft-input maxlength sizing', () => {
  async function inputIn(
    width: number,
    maxlength: number
  ): Promise<HTMLInputElement> {
    document.body.innerHTML = `
      <div style="width: ${width}px">
        <craft-input label="Title" maxlength="${maxlength}"></craft-input>
      </div>`;
    const element = document.querySelector('craft-input') as CraftInput;
    await element.updateComplete;

    return element.querySelector('input')!;
  }

  it('shrinks to fit a short maxlength', async () => {
    const input = await inputIn(300, 3);

    expect(input.getBoundingClientRect().width).toBeLessThan(100);
  });

  it('doesn’t outgrow its container for a long maxlength', async () => {
    const input = await inputIn(300, 255);

    expect(input.getBoundingClientRect().width).toBe(300);
  });
});

describe('craft-input placeholder', () => {
  it.each(['light', 'dark'])(
    'sets the placeholder at 4.5:1 against the surface in the %s theme',
    async (theme) => {
      await import('../../styles/cp.css');
      document.body.innerHTML = `
        <div data-theme="${theme}" style="background-color: var(--c-surface-default)">
          <craft-input aria-label="Search" placeholder="Search"></craft-input>
        </div>`;
      const element = document.querySelector<CraftInput>('craft-input')!;
      await element.updateComplete;

      const placeholder = getComputedStyle(
        element.querySelector('input')!,
        '::placeholder'
      );
      const surface = getComputedStyle(
        document.querySelector('[data-theme]')!
      ).backgroundColor;

      expect(placeholder.opacity).toBe('1');
      expect(contrast(placeholder.color, surface)).toBeGreaterThanOrEqual(4.5);
    }
  );
});

describe('craft-input suffix buttons', () => {
  it.each([
    ['medium', 30],
    ['small', 24],
  ])(
    'fills the suffix with a square button at the %s size, no narrower than 24px',
    async (size, expectedWidth) => {
      await import('../../styles/cp.css');
      document.body.innerHTML = `
        <craft-input aria-label="Slug" size="${size}">
          <craft-button slot="suffix" size="small" variant="plain" icon="arrows-rotate" aria-label="Refresh"></craft-button>
        </craft-input>`;
      const element = document.querySelector<CraftInput>('craft-input')!;
      await element.updateComplete;

      const button = element
        .querySelector('craft-button')!
        .getBoundingClientRect();
      const suffix = element
        .shadowRoot!.querySelector('.input-group__suffix')!
        .getBoundingClientRect();

      expect(button.width).toBeCloseTo(expectedWidth, 0);
      expect(button.width).toBeGreaterThanOrEqual(button.height);
      expect(button.right).toBeCloseTo(suffix.right - 1, 0);
    }
  );

  it('draws an inline suffix inside the input’s border, at its own size, without a divider', async () => {
    await import('../../styles/cp.css');
    document.body.innerHTML = `
      <craft-input aria-label="Slug" inline-suffix>
        <craft-button slot="suffix" size="small" variant="plain" icon="arrows-rotate" aria-label="Refresh"></craft-button>
      </craft-input>`;
    const element = document.querySelector<CraftInput>('craft-input')!;
    await element.updateComplete;

    const input = element.querySelector('input')!;
    const button = element.querySelector('craft-button')!;
    const buttonBox = button.getBoundingClientRect();
    const suffixBox = element
      .shadowRoot!.querySelector('.input-group__suffix')!
      .getBoundingClientRect();

    expect(getComputedStyle(input).borderInlineEndWidth).toBe('0px');
    expect(getComputedStyle(input).boxShadow).toBe('none');
    expect(getComputedStyle(button).borderTopLeftRadius).not.toBe('0px');
    expect(buttonBox.top).toBeGreaterThan(suffixBox.top + 1);
    expect(buttonBox.right).toBeLessThan(suffixBox.right - 1);
    // Leaves room for the button's focus ring, which sits outside it.
    expect(
      getComputedStyle(
        element.shadowRoot!.querySelector('.input-group__suffix')!
      ).overflow
    ).toBe('visible');
  });

  it('draws an outer focus ring around the input and an inline suffix together', async () => {
    await import('../../styles/cp.css');
    document.body.innerHTML = `
      <craft-input aria-label="Slug" inline-suffix>
        <craft-button slot="suffix" size="small" variant="plain" icon="arrows-rotate" aria-label="Refresh"></craft-button>
      </craft-input>`;
    const element = document.querySelector<CraftInput>('craft-input')!;
    await element.updateComplete;
    const input = element.querySelector('input')!;
    input.focus();
    await element.updateComplete;

    const container = getComputedStyle(
      element.shadowRoot!.querySelector('.input-group__container')!
    );

    expect(getComputedStyle(input).outlineWidth).toBe('0px');
    expect(container.outlineStyle).toBe('solid');
    expect(parseFloat(container.outlineOffset)).toBeGreaterThan(0);

    element.querySelector<HTMLElement>('craft-button')!.focus();
    await element.updateComplete;

    expect(
      getComputedStyle(
        element.shadowRoot!.querySelector('.input-group__container')!
      ).outlineStyle
    ).toBe('none');
  });
});
