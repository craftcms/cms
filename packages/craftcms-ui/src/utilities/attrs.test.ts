import {attrs} from './attrs.js';
import {describe, expect, test} from 'vite-plus/test';

describe('attrs', () => {
  test('returns an empty object for undefined input', () => {
    expect(attrs()).toEqual({});
    expect(attrs(undefined)).toEqual({});
  });

  test('passes plain scalars through (id, etc.)', () => {
    expect(attrs({id: 'card-123', tabindex: 0})).toEqual({
      id: 'card-123',
      tabindex: 0,
    });
  });

  test('passes an array class list through unchanged', () => {
    expect(attrs({class: ['card', 'error', 'thumb-end']})).toEqual({
      class: ['card', 'error', 'thumb-end'],
    });
  });

  test('normalizes an index-keyed class object back to a flat array', () => {
    // PHP `array_filter` can leave numeric-key gaps, which serialize as an
    // object rather than a JSON array.
    expect(attrs({class: {0: 'element', 2: 'card'}})).toEqual({
      class: ['element', 'card'],
    });
  });

  test('passes a string class through unchanged', () => {
    expect(attrs({class: 'card error'})).toEqual({class: 'card error'});
  });

  test('passes a style map through unchanged', () => {
    const style = {
      '--custom-border-color': 'var(--c-color-1-200)',
      '--custom-bg-color': 'var(--c-color-1-50)',
    };
    expect(attrs({style})).toEqual({style});
  });

  test('passes an empty-array style through unchanged', () => {
    // The server emits `[]` for `style` when no custom properties are set.
    expect(attrs({style: []})).toEqual({style: []});
  });

  test('flattens a nested data group into hyphenated data-* keys', () => {
    expect(
      attrs({
        data: {
          type: 'craft\\elements\\Entry',
          id: 5,
          editable: true,
        },
      })
    ).toEqual({
      'data-type': 'craft\\elements\\Entry',
      'data-id': 5,
      'data-editable': true,
    });
  });

  test('preserves already-hyphenated keys within a group', () => {
    expect(
      attrs({
        data: {
          'cp-url': '/admin/entries/5',
          'owner-is-canonical': true,
        },
      })
    ).toEqual({
      'data-cp-url': '/admin/entries/5',
      'data-owner-is-canonical': true,
    });
  });

  test('flattens a nested aria group into hyphenated aria-* keys', () => {
    expect(attrs({aria: {hidden: true, label: 'Card'}})).toEqual({
      'aria-hidden': true,
      'aria-label': 'Card',
    });
  });

  test('JSON-stringifies array/object values inside a nested group', () => {
    expect(
      attrs({
        data: {
          settings: {ui: 'card', selectable: false},
          ids: [1, 2, 3],
        },
      })
    ).toEqual({
      'data-settings': '{"ui":"card","selectable":false}',
      'data-ids': '[1,2,3]',
    });
  });

  test('skips null and false attributes, including inside groups', () => {
    expect(
      attrs({
        id: 'card-1',
        hidden: false,
        title: null,
        data: {
          id: 7,
          editable: false,
          'draft-id': null,
        },
      })
    ).toEqual({
      id: 'card-1',
      'data-id': 7,
    });
  });

  test('handles a full card-attributes payload end to end', () => {
    expect(
      attrs({
        id: 'card-42',
        class: ['element', 'card', 'thumb-end'],
        style: {'--custom-border-color': 'var(--c-color-1-200)'},
        data: {
          type: 'craft\\elements\\Entry',
          id: 42,
          'cp-url': '/admin/entries/42',
          editable: true,
          trashed: false,
          status: null,
        },
      })
    ).toEqual({
      id: 'card-42',
      class: ['element', 'card', 'thumb-end'],
      style: {'--custom-border-color': 'var(--c-color-1-200)'},
      'data-type': 'craft\\elements\\Entry',
      'data-id': 42,
      'data-cp-url': '/admin/entries/42',
      'data-editable': true,
    });
  });

  describe('exclude option', () => {
    test('excludes a simple top-level key (class)', () => {
      expect(
        attrs(
          {id: 'card-1', class: ['element', 'card'], title: 'Entry'},
          {exclude: ['class']}
        )
      ).toEqual({
        id: 'card-1',
        title: 'Entry',
      });
    });

    test('excluding a nested group key drops the entire data-* group', () => {
      expect(
        attrs(
          {
            id: 'card-1',
            data: {type: 'Entry', id: 5, 'cp-url': '/admin/entries/5'},
          },
          {exclude: ['data']}
        )
      ).toEqual({
        id: 'card-1',
      });
    });

    test('excluding a key that is not present is a no-op', () => {
      expect(
        attrs({id: 'card-1', class: ['element']}, {exclude: ['style', 'data']})
      ).toEqual({
        id: 'card-1',
        class: ['element'],
      });
    });

    test('non-excluded attributes still pass through', () => {
      expect(
        attrs(
          {
            id: 'card-1',
            class: ['element', 'card'],
            style: {'--custom-bg-color': 'var(--c-color-1-50)'},
            data: {id: 5, editable: true},
            aria: {hidden: true},
          },
          {exclude: ['class', 'aria']}
        )
      ).toEqual({
        id: 'card-1',
        style: {'--custom-bg-color': 'var(--c-color-1-50)'},
        'data-id': 5,
        'data-editable': true,
      });
    });

    test('omitting the options argument behaves exactly as before', () => {
      const input = {id: 'card-1', class: ['element'], data: {id: 5}};
      expect(attrs(input)).toEqual(attrs(input, {}));
      expect(attrs(input)).toEqual({
        id: 'card-1',
        class: ['element'],
        'data-id': 5,
      });
    });
  });
});
