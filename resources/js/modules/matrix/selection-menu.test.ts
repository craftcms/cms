import {afterEach, describe, expect, it} from 'vite-plus/test';
import {
  MATRIX_SELECTION_ACTION,
  selectionMenuItem,
  syncSelectionMenu,
  withoutStraySeparators,
} from './selection-menu';

const copy = {
  type: 'button',
  label: 'Copy all blocks',
  action: {
    type: 'event',
    name: 'craft:copy-nested-elements',
    detail: {selector: '[data-matrix-block]', fieldId: 4},
  },
};
const collapse = {
  type: 'button',
  label: 'Collapse selected blocks',
  hidden: true,
  action: {
    type: 'event',
    name: MATRIX_SELECTION_ACTION,
    detail: {action: 'collapse'},
  },
};
const disable = {
  type: 'button',
  label: 'Disable selected blocks',
  hidden: true,
  action: {
    type: 'event',
    name: MATRIX_SELECTION_ACTION,
    detail: {action: 'disable'},
  },
};
const nothingSelected = {
  count: 0,
  total: 3,
  anyCollapsed: false,
  anyExpanded: true,
  collapsed: false,
  disabled: false,
};

describe('selectionMenuItem', () => {
  it('leaves the menu as the server sent it with nothing selected', () => {
    expect(selectionMenuItem(copy, nothingSelected)).toBe(copy);
    expect(selectionMenuItem(collapse, nothingSelected).hidden).toBe(true);
    expect(selectionMenuItem(disable, nothingSelected).hidden).toBe(true);
  });

  it('copies just the selection once blocks are selected', () => {
    const item = selectionMenuItem(copy, {
      ...nothingSelected,
      count: 2,
      total: 3,
      anyCollapsed: false,
      anyExpanded: true,
    });

    expect(item.label).toBe('Copy selected blocks');
    expect(item.action.detail).toEqual({
      selector: '[data-matrix-block][data-selected]',
      fieldId: 4,
    });
  });

  it('shows the selection items, offering what the selection needs', () => {
    const some = {
      count: 2,
      total: 3,
      anyCollapsed: false,
      anyExpanded: true,
      collapsed: false,
      disabled: false,
    };
    const folded = {
      count: 2,
      total: 3,
      anyCollapsed: false,
      anyExpanded: true,
      collapsed: true,
      disabled: true,
    };

    expect(selectionMenuItem(collapse, some)).toMatchObject({
      hidden: false,
      label: 'Collapse selected blocks',
      action: {detail: {action: 'collapse'}},
    });
    expect(selectionMenuItem(collapse, folded)).toMatchObject({
      label: 'Expand selected blocks',
      action: {detail: {action: 'expand'}},
    });
    expect(selectionMenuItem(disable, some)).toMatchObject({
      hidden: false,
      label: 'Disable selected blocks',
      action: {detail: {action: 'disable'}},
    });
    expect(selectionMenuItem(disable, folded)).toMatchObject({
      label: 'Enable selected blocks',
      action: {detail: {action: 'enable'}},
    });
  });

  it('resolves site and global status actions independently', () => {
    const statusItem = (action: string) => ({
      label: 'Status',
      hidden: true,
      action: {
        type: 'event',
        name: MATRIX_SELECTION_ACTION,
        detail: {action, type: 'entries', site: 'English'},
      },
    });
    const mixed = {
      ...nothingSelected,
      count: 2,
      disabled: true,
      disabledForSite: true,
      globallyDisabled: false,
      anyGloballyDisabled: false,
    };

    expect(
      selectionMenuItem(statusItem('disableForSite'), mixed)
    ).toMatchObject({
      hidden: false,
      label: 'Enable selected entries for English',
      action: {detail: {action: 'enableForSite'}},
    });
    expect(
      selectionMenuItem(statusItem('disableGlobally'), mixed)
    ).toMatchObject({
      hidden: false,
      label: 'Disable selected entries globally',
      action: {detail: {action: 'disableGlobally'}},
    });

    const globallyDisabled = {
      ...mixed,
      globallyDisabled: true,
      anyGloballyDisabled: true,
    };

    expect(
      selectionMenuItem(statusItem('disableForSite'), globallyDisabled).hidden
    ).toBe(true);
    expect(
      selectionMenuItem(statusItem('disableGlobally'), globallyDisabled)
    ).toMatchObject({
      label: 'Enable selected entries globally',
      action: {detail: {action: 'enableGlobally'}},
    });

    expect(
      selectionMenuItem(statusItem('disableForSite'), {
        ...mixed,
        disabledForSite: false,
        anyGloballyDisabled: true,
      }).hidden
    ).toBe(true);
  });

  it('offers select all while any block is unselected, and deselect all while any is selected', () => {
    const item = (action: string, label: string) => ({
      type: 'button',
      label,
      hidden: action !== 'select',
      action: {type: 'event', name: MATRIX_SELECTION_ACTION, detail: {action}},
    });
    const select = item('select', 'Select all entries');
    const deselect = item('deselect', 'Deselect all entries');
    const state = {
      count: 0,
      total: 2,
      collapsed: false,
      disabled: false,
      anyCollapsed: false,
      anyExpanded: true,
    };
    const hidden = (count: number, total = 2) => [
      selectionMenuItem(select, {...state, count, total}).hidden,
      selectionMenuItem(deselect, {...state, count, total}).hidden,
    ];

    expect(hidden(0)).toEqual([false, true]);
    expect(hidden(1)).toEqual([false, false]);
    expect(hidden(2)).toEqual([true, false]);
    // Nothing to select in an empty field.
    expect(hidden(0, 0)).toEqual([true, true]);
    // The server's wording stands.
    expect(selectionMenuItem(deselect, {...state, count: 1}).label).toBe(
      'Deselect all entries'
    );
  });

  it('calls the blocks what the server calls them', () => {
    const entries = {
      ...copy,
      action: {
        ...copy.action,
        detail: {...copy.action.detail, type: 'entries'},
      },
    };

    expect(
      selectionMenuItem(entries, {...nothingSelected, count: 1}).label
    ).toBe('Copy selected entries');
  });

  it('offers to expand or collapse all only when there is something to do', () => {
    const toggle = (collapse: boolean) => ({
      type: 'button',
      label: collapse ? 'Collapse all blocks' : 'Expand all blocks',
      hidden: false,
      action: {
        type: 'event',
        name: 'craft:matrix-toggle-all',
        detail: {collapse},
      },
    });
    const allExpanded = {
      ...nothingSelected,
      anyCollapsed: false,
      anyExpanded: true,
    };
    const allCollapsed = {
      ...nothingSelected,
      anyCollapsed: true,
      anyExpanded: false,
    };

    expect(selectionMenuItem(toggle(false), allExpanded).hidden).toBe(true);
    expect(selectionMenuItem(toggle(true), allExpanded).hidden).toBe(false);
    expect(selectionMenuItem(toggle(false), allCollapsed).hidden).toBe(false);
    expect(selectionMenuItem(toggle(true), allCollapsed).hidden).toBe(true);
    // An empty field has nothing either way.
    const empty = {...nothingSelected, anyCollapsed: false, anyExpanded: false};
    expect(selectionMenuItem(toggle(false), empty).hidden).toBe(true);
    expect(selectionMenuItem(toggle(true), empty).hidden).toBe(true);
  });

  it('leaves other items alone', () => {
    const hr = {type: 'hr'};

    expect(
      selectionMenuItem(hr, {
        ...nothingSelected,
        count: 1,
        total: 3,
        anyCollapsed: false,
        anyExpanded: true,
      })
    ).toBe(hr);
  });
});

describe('syncSelectionMenu', () => {
  afterEach(() => {
    document.body.innerHTML = '';
  });

  function item(action: object, label: string, hidden = false): string {
    return `<craft-action-item action='${JSON.stringify(action)}'${hidden ? ' hidden' : ''}>${label}</craft-action-item>`;
  }

  function build(): HTMLElement {
    document.body.innerHTML = `
      <craft-field id="field">
        <craft-action-menu>
          ${item(copy.action, copy.label)}
          ${item(collapse.action, collapse.label, true)}
        </craft-action-menu>
        <div data-matrix-block data-selected data-collapsed></div>
        <div data-matrix-block></div>
        <div data-matrix-block>
          <craft-field>
            <div data-matrix-block data-selected></div>
          </craft-field>
        </div>
      </craft-field>
    `;

    return document.querySelector<HTMLElement>('#field')!;
  }

  it('rewrites the rendered items for the field’s own selection', () => {
    const field = build();
    const [copyItem, collapseItem] =
      field.querySelectorAll('craft-action-item');

    syncSelectionMenu(field);
    // Rewrites start from the server's items, so a second pass doesn't stack.
    syncSelectionMenu(field);

    expect(copyItem!.textContent).toBe('Copy selected blocks');
    expect(JSON.parse(copyItem!.getAttribute('action')!).detail.selector).toBe(
      '[data-matrix-block][data-selected]'
    );
    // Only the field's own selected block counts, and it's collapsed.
    expect(collapseItem!.hasAttribute('hidden')).toBe(false);
    expect(collapseItem!.textContent).toBe('Expand selected blocks');
  });

  it('hides "Expand all blocks" until a block is collapsed', () => {
    document.body.innerHTML = `
      <craft-field id="field">
        <craft-action-menu>
          ${item({type: 'event', name: 'craft:matrix-toggle-all', detail: {collapse: false}}, 'Expand all blocks')}
          ${item({type: 'event', name: 'craft:matrix-toggle-all', detail: {collapse: true}}, 'Collapse all blocks')}
        </craft-action-menu>
        <div data-matrix-block></div>
      </craft-field>
    `;
    const field = document.querySelector<HTMLElement>('#field')!;
    const [expand, collapse] = field.querySelectorAll('craft-action-item');

    syncSelectionMenu(field);

    expect(expand!.hasAttribute('hidden')).toBe(true);
    expect(collapse!.hasAttribute('hidden')).toBe(false);

    field
      .querySelector('[data-matrix-block]')!
      .setAttribute('data-collapsed', '');
    syncSelectionMenu(field);

    expect(expand!.hasAttribute('hidden')).toBe(false);
    expect(collapse!.hasAttribute('hidden')).toBe(true);
  });

  it('hides the selection items again once nothing is selected', () => {
    const field = build();

    syncSelectionMenu(field);
    field
      .querySelector('[data-matrix-block][data-selected]')!
      .removeAttribute('data-selected');
    syncSelectionMenu(field);

    const [copyItem, collapseItem] =
      field.querySelectorAll('craft-action-item');

    expect(copyItem!.textContent).toBe('Copy all blocks');
    expect(collapseItem!.hasAttribute('hidden')).toBe(true);
  });
});

describe('withoutStraySeparators', () => {
  const hr = {type: 'hr'};
  const shown = (label: string) => ({type: 'button', label});
  const hidden = (label: string) => ({type: 'button', label, hidden: true});
  const labels = (items: Array<{type: string; label?: string}>) =>
    items.map((item) => (item.type === 'hr' ? '---' : item.label));

  it('keeps a separator between groups that both show something', () => {
    expect(
      labels(withoutStraySeparators([shown('a'), hr, shown('b')]))
    ).toEqual(['a', '---', 'b']);
  });

  it('drops the separator around a group with nothing showing', () => {
    expect(
      labels(
        withoutStraySeparators([
          shown('select'),
          hr,
          hidden('disable'),
          hr,
          shown('copy'),
        ])
      )
    ).toEqual(['select', 'disable', '---', 'copy']);
  });

  it('never leaves a separator at either end', () => {
    expect(
      labels(
        withoutStraySeparators([hidden('a'), hr, shown('b'), hr, hidden('c')])
      )
    ).toEqual(['a', 'b', 'c']);
  });
});

describe('syncSelectionMenu separators', () => {
  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('hides the separators around a group with nothing showing', () => {
    const action = (detail: object) =>
      JSON.stringify({type: 'event', name: MATRIX_SELECTION_ACTION, detail});
    document.body.innerHTML = `
      <craft-field id="field">
        <craft-action-menu>
          <div slot="content">
            <craft-action-item action='${action({action: 'select'})}'>Select all</craft-action-item>
            <hr id="first">
            <craft-action-item action='${action({action: 'disable'})}' hidden>Disable selected</craft-action-item>
            <hr id="second">
            <craft-action-item>Field settings</craft-action-item>
          </div>
        </craft-action-menu>
        <div data-matrix-block></div>
        <div data-matrix-block></div>
      </craft-field>
    `;
    const field = document.querySelector('#field')!;

    syncSelectionMenu(field);

    // Nothing is selected, so "Disable selected" stays hidden: one separator.
    expect([
      field.querySelector<HTMLElement>('#first')!.hidden,
      field.querySelector<HTMLElement>('#second')!.hidden,
    ]).toEqual([true, false]);

    // One of two selected: "Select all" and "Disable selected" both apply.
    field
      .querySelector('[data-matrix-block]')!
      .setAttribute('data-selected', '');
    syncSelectionMenu(field);

    expect([
      field.querySelector<HTMLElement>('#first')!.hidden,
      field.querySelector<HTMLElement>('#second')!.hidden,
    ]).toEqual([false, false]);
  });
});
