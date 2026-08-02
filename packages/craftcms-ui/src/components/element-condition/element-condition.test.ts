import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import './element-condition.js';

beforeEach(() => {
  document.body.innerHTML = '';
  (window as any).Craft = {initUiElements: vi.fn()};
  (window as any).htmx = {process: vi.fn()};
});

describe('craft-element-condition', () => {
  it('initializes slotted condition builder controls', async () => {
    const element = document.createElement('craft-element-condition');

    element.innerHTML = '<div class="condition-main"><input></div>';
    document.body.append(element);
    await element.updateComplete;

    expect(element.getAttribute('role')).toBe('group');
    expect((window as any).Craft.initUiElements).toHaveBeenCalledWith(element);
    expect((window as any).htmx.process).toHaveBeenCalledWith(element);
  });

  it('blocks read-only interaction and disables controls added later', async () => {
    const element = document.createElement('craft-element-condition');
    const input = document.createElement('input');
    const activate = vi.fn();

    element.readOnly = true;
    input.addEventListener('click', activate);
    element.append(input);
    document.body.append(element);
    await element.updateComplete;

    input.click();

    expect(element.getAttribute('aria-disabled')).toBe('true');
    expect(input.disabled).toBe(true);
    expect(activate).not.toHaveBeenCalled();

    const button = document.createElement('craft-button');
    element.append(button);

    await vi.waitFor(() => expect(button.disabled).toBe(true));

    element.readOnly = false;
    await element.updateComplete;

    expect(element.hasAttribute('aria-disabled')).toBe(false);
    expect(input.disabled).toBe(false);
    expect(button.disabled).toBe(false);
  });
});
