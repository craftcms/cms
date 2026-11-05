import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import './copy-button.js';
import type CraftCopyButton from './copy-button.js';
import type CraftButton from '../button/button.js';

async function createCopyButton(
  attrs: Record<string, string> = {},
  innerHTML = 'Copy'
): Promise<CraftCopyButton> {
  const element = document.createElement(
    'craft-copy-button'
  ) as CraftCopyButton;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function button(element: CraftCopyButton): CraftButton {
  return element.shadowRoot!.querySelector('craft-button')!;
}

/** happy-dom has no clipboard, so it is stubbed for what the component awaits. */
function stubClipboard(writeText = vi.fn().mockResolvedValue(undefined)) {
  Object.defineProperty(navigator, 'clipboard', {
    value: {writeText},
    configurable: true,
    writable: true,
  });
  return writeText;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-copy-button', () => {
  it('writes its value to the clipboard when pressed', async () => {
    const writeText = stubClipboard();
    const element = await createCopyButton({value: 'https://craftcms.com'});

    await element.copyValue();

    expect(writeText).toHaveBeenCalledWith('https://craftcms.com');
  });

  it('announces the copy so a page can react to it', async () => {
    stubClipboard();
    const element = await createCopyButton({value: 'handle'});
    const copied = vi.fn();
    element.addEventListener('craft-copy', copied);

    await element.copyValue();

    expect(copied).toHaveBeenCalled();
    const [event] = copied.mock.calls[0] as [CustomEvent<{value: string}>];
    expect(event.detail.value).toBe('handle');
  });

  it('announces a failure rather than swallowing it', async () => {
    stubClipboard(vi.fn().mockRejectedValue(new Error('denied')));
    const element = await createCopyButton({value: 'handle'});
    const failed = vi.fn();
    element.addEventListener('craft-error', failed);

    await element.copyValue();

    expect(failed).toHaveBeenCalled();
  });

  it('does nothing while disabled', async () => {
    const writeText = stubClipboard();
    const element = await createCopyButton({value: 'handle', disabled: ''});

    await element.copyValue();

    expect(writeText).not.toHaveBeenCalled();
    expect(button(element).disabled).toBe(true);
  });

  /** Being moved in the DOM must not leave the outcome as the resting label. */
  it('restores its tooltip label after showing the outcome', async () => {
    stubClipboard();
    const element = await createCopyButton({
      value: 'handle',
      'tooltip-label': 'Copy the handle',
      'feedback-duration': '10',
    });
    document.body.append(element);
    const tooltip = () =>
      element.shadowRoot!.querySelector('craft-tooltip')!.textContent!.trim();

    await element.copyValue();
    await element.updateComplete;
    expect(tooltip()).toBe('Copied');

    await new Promise((resolve) => setTimeout(resolve, 30));
    await element.updateComplete;
    expect(tooltip()).toBe('Copy the handle');
  });

  /** A second press mid-copy would double-fire the event and the feedback. */
  it('ignores a press while a copy is already running', async () => {
    const writeText = stubClipboard();
    const element = await createCopyButton({value: 'handle'});

    const first = element.copyValue();
    await element.copyValue();
    await first;

    expect(writeText).toHaveBeenCalledTimes(1);
  });
});
