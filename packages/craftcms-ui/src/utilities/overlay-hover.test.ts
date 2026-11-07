import {describe, expect, it} from 'vite-plus/test';
import {watchHover} from './overlay-hover.js';

function setup({delayIn = 0, delayOut = 0} = {}) {
  const invoker = document.createElement('button');
  const content = document.createElement('div');
  const calls: string[] = [];
  const watcher = watchHover({
    invoker,
    content,
    delayIn,
    delayOut,
    show: () => void calls.push('show'),
    hide: () => void calls.push('hide'),
  });

  return {invoker, content, calls, watcher};
}

const after = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

describe('watchHover', () => {
  it('shows after the in delay when the invoker is hovered', async () => {
    const {calls, invoker} = setup({delayIn: 20});

    invoker.dispatchEvent(new MouseEvent('mouseenter'));
    await after(50);

    expect(calls).toEqual(['show']);
  });

  it('stays shown while the pointer moves onto the content', async () => {
    const {calls, invoker, content} = setup({delayOut: 20});

    invoker.dispatchEvent(new MouseEvent('mouseenter'));
    await after(5);
    invoker.dispatchEvent(new MouseEvent('mouseleave'));
    content.dispatchEvent(new MouseEvent('mouseenter'));
    await after(50);

    expect(calls).toEqual(['show', 'show']);
  });

  it('cancels a pending show when torn down mid-hover', async () => {
    const {calls, invoker, watcher} = setup({delayIn: 20});

    invoker.dispatchEvent(new MouseEvent('mouseenter'));
    watcher.teardown();
    await after(50);

    expect(calls).toEqual([]);
  });

  it('cancels a pending hide when torn down mid-leave', async () => {
    const {calls, invoker, watcher} = setup({delayOut: 20});

    invoker.dispatchEvent(new MouseEvent('mouseleave'));
    watcher.teardown();
    await after(50);

    expect(calls).toEqual([]);
  });

  it('does not show while the invoker is disabled', async () => {
    const {calls, invoker} = setup({delayOut: 20});
    invoker.disabled = true;

    invoker.dispatchEvent(new MouseEvent('mouseenter'));
    await after(50);

    expect(calls).toEqual(['hide']);
  });

  it('shows while a disabled invoker stays focusable', async () => {
    const {calls, invoker} = setup({delayOut: 20});
    invoker.setAttribute('aria-disabled', 'true');
    Object.assign(invoker, {focusableWhenDisabled: true});

    invoker.dispatchEvent(new MouseEvent('mouseenter'));
    await after(50);

    expect(calls).toEqual(['show']);
  });
});
