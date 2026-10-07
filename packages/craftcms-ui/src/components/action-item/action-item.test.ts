import {expect, test} from 'vite-plus/test';
import {html, render} from 'lit';

import './action-item.js';
import '../button/button.js';

async function mount(template: unknown) {
  const host = document.createElement('div');
  document.body.append(host);
  render(template as never, host);
  const element = host.firstElementChild as HTMLElement & {
    updateComplete: Promise<unknown>;
  };
  await element.updateComplete;
  await new Promise((resolve) => setTimeout(resolve, 0));
  await element.updateComplete;
  return element;
}

function flagged(element: HTMLElement): boolean {
  return !!element.shadowRoot?.querySelector('.action-item__suffix.a11y-error');
}

test('a plain suffix is not flagged', async () => {
  const item = await mount(
    html`<craft-action-item>
      Duplicate <span slot="suffix">⌘D</span>
    </craft-action-item>`
  );

  expect(flagged(item)).toBe(false);
});

test('an empty suffix is not flagged', async () => {
  const item = await mount(
    html`<craft-action-item>Duplicate</craft-action-item>`
  );

  expect(flagged(item)).toBe(false);
});

test('a button in the suffix is flagged', async () => {
  const item = await mount(
    html`<craft-action-item>
      Duplicate
      <craft-button slot="suffix" aria-label="More">…</craft-button>
    </craft-action-item>`
  );

  expect(flagged(item)).toBe(true);
});

test('a native control in the suffix is flagged', async () => {
  const item = await mount(
    html`<craft-action-item>
      Duplicate <input slot="suffix" aria-label="Rename" />
    </craft-action-item>`
  );

  expect(flagged(item)).toBe(true);
});

test('a link in the suffix is flagged', async () => {
  const item = await mount(
    html`<craft-action-item>
      Duplicate <a slot="suffix" href="/help">Help</a>
    </craft-action-item>`
  );

  expect(flagged(item)).toBe(true);
});

test('marks its own button as current, not just the host', async () => {
  const item = document.createElement('craft-action-item');
  item.textContent = 'Blog';
  document.body.append(item);
  await item.updateComplete;

  const button = () => item.shadowRoot?.querySelector('button');
  expect(button()?.hasAttribute('aria-current')).toBe(false);

  item.current = true;
  await item.updateComplete;

  expect(button()?.getAttribute('aria-current')).toBe('true');
});

test('stops resetting its state once it is removed', async () => {
  const item = (await mount(
    html`<craft-action-item
      .action=${{type: 'event', name: 'action-item-test'}}
      .feedbackDuration=${10}
    >
      Duplicate
    </craft-action-item>`
  )) as HTMLElement & {feedbackDuration: number};
  const states: string[] = [];
  item.addEventListener('craft-state-change', (event) => {
    states.push((event as CustomEvent<{state: string}>).detail.state);
  });

  item.click();
  await new Promise((resolve) => setTimeout(resolve, 0));
  item.remove();
  await new Promise((resolve) =>
    setTimeout(resolve, item.feedbackDuration * 3)
  );

  expect(states).toEqual(['success']);
});
