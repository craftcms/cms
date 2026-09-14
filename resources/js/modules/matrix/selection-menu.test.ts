import {afterEach, describe, expect, it} from 'vite-plus/test';
import {
  MATRIX_SELECTION_ACTION,
  selectionMenuItem,
  syncSelectionMenu,
} from './selection-menu';

const copy = {
  type: 'button',
  label: 'Copy all blocks',
  action: {
    type: 'event',
    name: 'craft:copy-nested-elements',
    detail: {selector: '.matrixblock', fieldId: 4},
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
const nothingSelected = {count: 0, total: 3, collapsed: false, disabled: false};

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
    });

    expect(item.label).toBe('Copy selected blocks');
    expect(item.action.detail).toEqual({
      selector: '.matrixblock.sel',
      fieldId: 4,
    });
  });

  it('shows the selection items, offering what the selection needs', () => {
    const some = {count: 2, total: 3, collapsed: false, disabled: false};
    const folded = {count: 2, total: 3, collapsed: true, disabled: true};

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

  it('selects every block, or deselects them once all are', () => {
    const select = {
      type: 'button',
      label: 'Select all blocks',
      hidden: false,
      action: {
        type: 'event',
        name: MATRIX_SELECTION_ACTION,
        detail: {action: 'select'},
      },
    };
    const state = {count: 0, total: 2, collapsed: false, disabled: false};

    expect(selectionMenuItem(select, state)).toMatchObject({
      hidden: false,
      label: 'Select all blocks',
      action: {detail: {action: 'select'}},
    });
    expect(selectionMenuItem(select, {...state, count: 2})).toMatchObject({
      label: 'Deselect all blocks',
      action: {detail: {action: 'deselect'}},
    });
    // Nothing to select in an empty field.
    expect(selectionMenuItem(select, {...state, total: 0}).hidden).toBe(true);
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

  it('leaves other items alone', () => {
    const hr = {type: 'hr'};

    expect(
      selectionMenuItem(hr, {...nothingSelected, count: 1, total: 3})
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
        <div class="matrixblock sel collapsed"></div>
        <div class="matrixblock"></div>
        <div class="matrixblock">
          <craft-field>
            <div class="matrixblock sel"></div>
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
      '.matrixblock.sel'
    );
    // Only the field's own selected block counts, and it's collapsed.
    expect(collapseItem!.hasAttribute('hidden')).toBe(false);
    expect(collapseItem!.textContent).toBe('Expand selected blocks');
  });

  it('hides the selection items again once nothing is selected', () => {
    const field = build();

    syncSelectionMenu(field);
    field.querySelector('.matrixblock.sel')!.classList.remove('sel');
    syncSelectionMenu(field);

    const [copyItem, collapseItem] =
      field.querySelectorAll('craft-action-item');

    expect(copyItem!.textContent).toBe('Copy all blocks');
    expect(collapseItem!.hasAttribute('hidden')).toBe(true);
  });
});
