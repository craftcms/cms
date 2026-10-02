import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {emitChange, emitInput} from './form-events.js';

let host: HTMLElement;

beforeEach(() => {
  document.body.innerHTML = '';
  host = document.createElement('div');
  document.body.append(host);
});

describe('form events', () => {
  it.each([
    ['input', emitInput],
    ['change', emitChange],
  ])('emits a composed, bubbling %s from the host', (type, emit) => {
    const seen = vi.fn();
    document.body.addEventListener(type, seen);

    emit(host);

    expect(seen).toHaveBeenCalledTimes(1);
    const event = seen.mock.calls[0]![0] as Event;
    expect(event.target).toBe(host);
    expect(event.bubbles).toBe(true);
    expect(event.composed).toBe(true);
  });
});
