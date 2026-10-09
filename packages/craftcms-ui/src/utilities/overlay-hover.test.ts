import {describe, expect, it} from 'vite-plus/test';
import {withHoverInteraction} from './overlay-hover.js';

function createController(disabledInvoker = false) {
  const invokerNode = document.createElement('button');
  const contentNode = document.createElement('div');
  const calls: string[] = [];

  const controller = Object.assign(new EventTarget(), {
    invokerNode,
    contentNode,
    show: () => void calls.push('show'),
    hide: () => void calls.push('hide'),
    _hasDisabledInvoker: () => disabledInvoker,
  });

  return {controller, calls, invokerNode};
}

const after = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

describe('withHoverInteraction', () => {
  it('shows after the in delay when the invoker is hovered', async () => {
    const {controller, calls, invokerNode} = createController();
    const {init} = withHoverInteraction({
      delayIn: 20,
      delayOut: 0,
    }).visibilityTriggerFunction({controller});

    init();
    invokerNode.dispatchEvent(new MouseEvent('mouseenter'));
    await after(50);

    expect(calls).toEqual(['show']);
  });

  it('cancels a pending show when torn down mid-hover', async () => {
    const {controller, calls, invokerNode} = createController();
    const {init, teardown} = withHoverInteraction({
      delayIn: 20,
      delayOut: 0,
    }).visibilityTriggerFunction({controller});

    init();
    invokerNode.dispatchEvent(new MouseEvent('mouseenter'));
    teardown();
    await after(50);

    expect(calls).toEqual([]);
  });

  it('cancels a pending hide when torn down mid-leave', async () => {
    const {controller, calls, invokerNode} = createController();
    const {init, teardown} = withHoverInteraction({
      delayIn: 0,
      delayOut: 20,
    }).visibilityTriggerFunction({controller});

    init();
    invokerNode.dispatchEvent(new MouseEvent('mouseleave'));
    teardown();
    await after(50);

    expect(calls).toEqual([]);
  });

  it('does not show while the invoker is disabled', async () => {
    const {controller, calls, invokerNode} = createController(true);
    const {init} = withHoverInteraction({
      delayIn: 0,
      delayOut: 20,
    }).visibilityTriggerFunction({controller});

    init();
    invokerNode.dispatchEvent(new MouseEvent('mouseenter'));
    await after(50);

    expect(calls).toEqual(['hide']);
  });
});
