import {describe, expect, it} from 'vitest';
import {
  elementId,
  uiChangeFromEvent,
  uiTabPanelId,
  ignoreModelValueInitialization,
  namespaceId,
  setValue,
  unsetValue,
  valueAt,
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
    // Request context in `detail` is not a UI change.
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

/**
 * These ids are also built server-side, by `Html::id()` and
 * `InputNamespace::namespaceId()`, and the client has to address the markup the
 * server rendered — so the expected values here are the PHP output, not a
 * restatement of the TypeScript.
 */
describe('element ids', () => {
  it('joins the namespace and id before normalizing, not after', () => {
    expect(namespaceId('form-tab-abc123', 'fields[address]')).toBe(
      'fields-address-form-tab-abc123'
    );
    expect(namespaceId('form-tab-a', 'a[b][c]')).toBe('a-b-c-form-tab-a');
  });

  it('leaves an id alone when there is no namespace', () => {
    expect(namespaceId('form-tab-abc')).toBe('form-tab-abc');
    expect(namespaceId('form-tab-abc', null)).toBe('form-tab-abc');
  });

  it('returns an empty id untouched', () => {
    expect(namespaceId('', 'fields[address]')).toBe('');
  });

  it('passes a placeholder through, but not once it has been joined', () => {
    expect(elementId('__NAMESPACE__-fieldId')).toBe('__NAMESPACE__-fieldId');
    expect(namespaceId('__NAMESPACE__-fieldId', 'fields[x]')).toBe(
      'fields-x-__NAMESPACE__-fieldId'
    );
  });

  it('keeps hyphens that are already valid', () => {
    expect(elementId('a--b')).toBe('a--b');
  });

  it('falls back to a random id when nothing valid is left', () => {
    expect(elementId('!!!')).toMatch(/^[A-Za-z0-9]{10}$/);
  });

  /**
   * Previously `Craft.namespaceId()`, which only exists in the legacy CP
   * bundle — rendering a tab threw wherever that bundle wasn't loaded.
   */
  it('namespaces a tab panel id without the legacy global', () => {
    expect(uiTabPanelId('abc123', [])).toBe('ui-tab-abc123');
    expect(uiTabPanelId('abc123', ['fields', 'address'])).toBe(
      'fields-address-ui-tab-abc123'
    );
  });
});
