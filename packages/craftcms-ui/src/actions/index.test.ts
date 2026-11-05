import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import * as focusReturn from '@src/utilities/focus-return';
import {runAction} from './index';

afterEach(() => {
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

function listen(name: string): Promise<CustomEvent> {
  return new Promise((resolve) =>
    window.addEventListener(name, (event) => resolve(event as CustomEvent), {
      once: true,
    })
  );
}

describe('event actions', () => {
  it('pass the trigger and a returnFocus resolver for it', async () => {
    document.body.innerHTML = `
      <craft-action-menu>
        <button slot="invoker">Actions</button>
        <div slot="content" hidden><button>Copy</button></div>
      </craft-action-menu>
    `;
    const invoker = document.querySelector('[slot="invoker"]');
    const trigger = document.querySelector('[slot="content"] button')!;
    const received = listen('test:action');

    await runAction(
      {type: 'event', name: 'test:action', detail: {value: 1}},
      {trigger}
    );
    const {detail} = await received;

    expect(detail.value).toBe(1);
    expect(detail.trigger).toBe(trigger);
    expect(detail.returnFocus()).toBe(invoker);
  });

  it('only work out returnFocus when a listener reads it', async () => {
    const focusReturnFor = vi.spyOn(focusReturn, 'focusReturnFor');
    const trigger = document.createElement('button');
    document.body.append(trigger);
    const received = listen('test:action');

    await runAction({type: 'event', name: 'test:action'}, {trigger});
    const {detail} = await received;

    expect(focusReturnFor).not.toHaveBeenCalled();
    void detail.returnFocus;
    void detail.returnFocus;
    expect(focusReturnFor).toHaveBeenCalledTimes(1);
  });
});
