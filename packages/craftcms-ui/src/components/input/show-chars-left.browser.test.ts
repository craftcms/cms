import {beforeAll, beforeEach, describe, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import {loadTokens} from '../../../test/contrast';
import './input.js';
import '../textarea/textarea.js';

beforeAll(loadTokens);

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(tag: 'craft-input' | 'craft-textarea', attrs: string) {
  document.body.innerHTML = `<${tag} label="Title" ${attrs}></${tag}>`;
  const element = document.querySelector(tag)!;
  await element.updateComplete;

  return {
    element,
    control: element.querySelector<HTMLInputElement | HTMLTextAreaElement>(
      'input, textarea'
    )!,
    counter: () =>
      element.shadowRoot!.querySelector<HTMLElement>('.chars-left'),
  };
}

describe.each(['craft-input', 'craft-textarea'] as const)(
  '%s show-chars-left',
  (tag) => {
    it('counts down the characters left as text is typed', async () => {
      const {element, control, counter} = await render(
        tag,
        'maxlength="12" show-chars-left'
      );

      expect(counter()!.textContent).toContain('12');

      await userEvent.type(control, 'Hello');
      await element.updateComplete;

      expect(counter()!.querySelector('[aria-hidden]')!.textContent).toBe('7');
      expect(counter()!.getAttribute('role')).toBe('status');
      expect(counter()!.textContent).toContain('Characters left: 7');
    });

    it('keeps typed text clear of the counter', async () => {
      const {element, control, counter} = await render(
        tag,
        'maxlength="1000" show-chars-left'
      );
      element.style.width = '300px';
      await element.updateComplete;

      const text = control.getBoundingClientRect();
      const padding = parseFloat(getComputedStyle(control).paddingInlineEnd);
      const number = counter()!
        .querySelector('[aria-hidden]')!
        .getBoundingClientRect();

      expect(number.width).toBeGreaterThan(0);
      expect(text.right - padding).toBeLessThanOrEqual(number.left);
    });

    it('shows no counter without a maxlength', async () => {
      const {counter} = await render(tag, 'show-chars-left');

      expect(counter()).toBeNull();
    });

    it('shows no counter unless show-chars-left is set', async () => {
      const {counter} = await render(tag, 'maxlength="12"');

      expect(counter()).toBeNull();
    });
  }
);
