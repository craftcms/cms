import {describe, expect, it} from 'vitest';
import {formChangeFromEvent, ignoreModelValueInitialization} from './runtime';
import type {FormChange} from './types';

describe('ignoreModelValueInitialization', () => {
  it('only forwards changes after initialization', () => {
    const changes: Event[] = [];
    const listener = ignoreModelValueInitialization((event) => {
      changes.push(event);
    });
    const change = new CustomEvent('model-value-changed');

    listener(
      new CustomEvent('model-value-changed', {detail: {initialize: true}})
    );
    listener(change);

    expect(changes).toEqual([change]);
  });
});

describe('formChangeFromEvent', () => {
  it('passes a FormChange through untouched', () => {
    const change: FormChange = {kind: 'discrete', path: ['settings', 'label']};

    expect(formChangeFromEvent(change)).toBe(change);
  });

  it('reads a change out of a Control CustomEvent', () => {
    const change = {kind: 'discrete', path: ['settings', 'label']};

    expect(
      formChangeFromEvent(new CustomEvent('change', {detail: change}))
    ).toBe(change);
  });

  it('ignores a CustomEvent carrying an unrelated detail', () => {
    // htmx puts its request context in `detail`; it is not a form change.
    const htmxish = new CustomEvent('change', {
      detail: {elt: document.createElement('div'), xhr: {}, requestConfig: {}},
    });

    expect(formChangeFromEvent(htmxish)).toBeNull();
    expect(formChangeFromEvent(new Event('change'))).toBeNull();
  });
});
