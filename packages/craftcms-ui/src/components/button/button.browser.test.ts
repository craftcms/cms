import {beforeEach, expect, it, vi} from 'vite-plus/test';
import type CraftButton from './button.js';
import './button.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

function isFlagged(button: CraftButton): boolean {
  return !!button.shadowRoot?.querySelector('.a11y-error');
}

/**
 * Mounts a button inside a container that starts hidden the way `craft-tabs`
 * hides a panel that isn't selected -- `display: none` and `visibility:
 * hidden` together. The inherited `visibility` is what empties the button's
 * name; `display: none` on an ancestor alone doesn't.
 */
async function mountHidden(label: string | null): Promise<{
  button: CraftButton;
  container: HTMLElement;
}> {
  const container = document.createElement('div');
  container.style.cssText = 'display: none; visibility: hidden;';

  const button = document.createElement('craft-button') as CraftButton;
  button.setAttribute('icon', 'image-landscape');

  if (label !== null) {
    button.textContent = label;
  }

  container.append(button);
  document.body.append(container);
  await button.updateComplete;

  // The name is computed asynchronously after the first render. An empty
  // string rather than undefined proves the check ran while still hidden.
  await vi.waitFor(() => expect(button.accessibleName).toBe(''));

  return {button, container};
}

it('does not flag a labelled button that was hidden when it first rendered', async () => {
  const {button, container} = await mountHidden('Cropping Rectangle');

  expect(isFlagged(button)).toBe(false);

  container.style.cssText = '';

  await vi.waitFor(() =>
    expect(button.accessibleName).toBe('Cropping Rectangle')
  );
  expect(isFlagged(button)).toBe(false);
});

it('still flags an unnamed button once it is shown', async () => {
  const {button, container} = await mountHidden(null);

  // Not judged while hidden: an empty name there says nothing either way.
  expect(isFlagged(button)).toBe(false);

  container.style.cssText = '';

  await vi.waitFor(() => expect(isFlagged(button)).toBe(true));
});

it('flags an unnamed button that is visible from the start', async () => {
  const button = document.createElement('craft-button') as CraftButton;
  button.setAttribute('icon', 'image-landscape');
  document.body.append(button);
  await button.updateComplete;

  await vi.waitFor(() => expect(isFlagged(button)).toBe(true));
});
