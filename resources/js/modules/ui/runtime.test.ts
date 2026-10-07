import {describe, expect, it} from 'vitest';
import {
  uiChangeFromEvent,
  ignoreModelValueInitialization,
  valueAt,
  setValue,
  unsetValue,
} from './runtime';
import type {UiChange} from './types';

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

describe('uiChangeFromEvent', () => {
  it('passes a UiChange through untouched', () => {
    const change: UiChange = {kind: 'discrete', path: ['settings', 'label']};

    expect(uiChangeFromEvent(change)).toBe(change);
  });

  it('reads a change out of a Control CustomEvent', () => {
    const change = {kind: 'discrete', path: ['settings', 'label']};

    expect(uiChangeFromEvent(new CustomEvent('change', {detail: change}))).toBe(
      change
    );
  });

  it('ignores a CustomEvent carrying an unrelated detail', () => {
    // Request context in `detail` is not a form change.
    const requestEvent = new CustomEvent('change', {
      detail: {elt: document.createElement('div'), xhr: {}, requestConfig: {}},
    });

    expect(uiChangeFromEvent(requestEvent)).toBeNull();
    expect(uiChangeFromEvent(new Event('change'))).toBeNull();
  });

  it('ignores a non-event payload that is not a UiChange', () => {
    const conditionConfig = {
      class: 'CraftCms\\Cms\\Asset\\Conditions\\AssetCondition',
      conditionRules: [],
    };

    expect(
      uiChangeFromEvent(conditionConfig as unknown as UiChange)
    ).toBeNull();
  });
});

describe('list row paths', () => {
  it('edits a cell without converting its list into an object or touching siblings', () => {
    const values = {rows: [{name: 'Ada', enabled: true}, {name: 'Grace'}]};
    setValue(values, ['rows', '1', 'name'], 'Katherine');
    expect(values.rows).toEqual([
      {name: 'Ada', enabled: true},
      {name: 'Katherine'},
    ]);
    expect(valueAt(values, ['rows', '1', 'name'])).toBe('Katherine');
    unsetValue(values, ['rows', '0', 'enabled']);
    expect(values.rows).toEqual([{name: 'Ada'}, {name: 'Katherine'}]);
  });
});
