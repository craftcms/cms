import {describe, expect, it} from 'vite-plus/test';
import {dispatchNavigateEvent} from './navigate-event.js';

function createHost(): HTMLElement {
  const host = document.createElement('div');
  document.body.append(host);
  return host;
}

function createClick(overrides: MouseEventInit = {}): MouseEvent {
  return new MouseEvent('click', {
    cancelable: true,
    bubbles: true,
    button: 0,
    ...overrides,
  });
}

describe('dispatchNavigateEvent', () => {
  it('dispatches a craft-navigate event carrying the href', () => {
    const host = createHost();
    let detail: {href: string} | null = null;
    host.addEventListener('craft-navigate', (event) => {
      detail = event.detail;
    });

    dispatchNavigateEvent(host, '/admin/graphql', createClick());

    expect(detail).toEqual({href: '/admin/graphql'});
  });

  it('dispatches an event that bubbles and crosses shadow boundaries', () => {
    const host = createHost();
    let event: Event | null = null;
    document.body.addEventListener('craft-navigate', (e) => {
      event = e;
    });

    dispatchNavigateEvent(host, '/admin/graphql', createClick());

    expect(event).not.toBeNull();
    expect(event!.composed).toBe(true);
  });

  it('leaves the triggering click alone when nothing listens', () => {
    const host = createHost();
    const click = createClick();

    dispatchNavigateEvent(host, '/admin/graphql', click);

    expect(click.defaultPrevented).toBe(false);
  });

  it('prevents the triggering click when a listener cancels craft-navigate', () => {
    const host = createHost();
    host.addEventListener('craft-navigate', (event) => {
      event.preventDefault();
    });
    const click = createClick();

    dispatchNavigateEvent(host, '/admin/graphql', click);

    expect(click.defaultPrevented).toBe(true);
  });

  it('does not dispatch for a modifier-held click', () => {
    const host = createHost();
    let dispatched = false;
    host.addEventListener('craft-navigate', () => {
      dispatched = true;
    });

    dispatchNavigateEvent(host, '/admin/graphql', createClick({metaKey: true}));

    expect(dispatched).toBe(false);
  });

  it('does not dispatch for a non-primary button click', () => {
    const host = createHost();
    let dispatched = false;
    host.addEventListener('craft-navigate', () => {
      dispatched = true;
    });

    dispatchNavigateEvent(host, '/admin/graphql', createClick({button: 1}));

    expect(dispatched).toBe(false);
  });
});
