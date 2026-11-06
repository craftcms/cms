import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import * as focusReturn from '@src/utilities/focus-return';
import {runAction} from './index';

afterEach(() => {
  document.body.innerHTML = '';
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
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

describe('http actions that require an elevated session', () => {
  const action = {
    type: 'http',
    url: '/actions/auth/remove-method',
    requireElevatedSession: true,
  } as const;

  function hostAnswers(confirmed: boolean): void {
    window.addEventListener(
      'craft:require-elevated-session',
      (event) => {
        (event as CustomEvent).detail.confirmed = Promise.resolve(confirmed);
      },
      {once: true}
    );
  }

  it('send the request once the session is elevated', async () => {
    const fetch = vi.fn().mockResolvedValue(new Response('{}'));
    vi.stubGlobal('fetch', fetch);
    hostAnswers(true);

    await runAction(action);

    expect(fetch).toHaveBeenCalledOnce();
  });

  it('send nothing when the user cancels', async () => {
    const fetch = vi.fn();
    vi.stubGlobal('fetch', fetch);
    hostAnswers(false);

    await runAction(action);

    expect(fetch).not.toHaveBeenCalled();
  });
});
