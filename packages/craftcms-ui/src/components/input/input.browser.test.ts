import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftInput from './input.js';
import type CraftInputDateTime from '../input-date-time/input-date-time.js';
import './input.js';
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
