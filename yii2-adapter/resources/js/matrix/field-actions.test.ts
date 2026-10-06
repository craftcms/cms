import {beforeEach, afterEach, describe, it, expect, vi} from 'vite-plus/test';
const forContainer = vi.hoisted(() => vi.fn());
vi.mock('./matrix-entry', () => ({MatrixEntry: {forContainer}}));

describe('legacy Matrix field actions', () => {
  /**
   * The DOM both render paths produce: the menu item stays a descendant of the
   * `craft-field` it was rendered into, and a nested field's own blocks sit
   * inside a `craft-field` of their own.
   */
  function buildMatrixField(): {trigger: HTMLElement; blocks: HTMLElement[]} {
    document.body.innerHTML = `
      <craft-field>
        <craft-action-menu>
          <craft-action-item id="trigger"></craft-action-item>
        </craft-action-menu>
        <div data-matrix-field>
          <div data-matrix-block data-id="1"></div>
          <div data-matrix-block data-id="2">
            <craft-field>
              <div data-matrix-block data-id="3"></div>
            </craft-field>
          </div>
        </div>
      </craft-field>
    `;

    return {
      trigger: document.querySelector<HTMLElement>('#trigger')!,
      blocks: [
        ...document.querySelectorAll<HTMLElement>('[data-matrix-block]'),
      ],
    };
  }

  beforeEach(async () => {
    forContainer.mockReset();
    await import('./field-actions');
  });
  afterEach(() => {
    document.body.innerHTML = '';
  });
  it('collapses only the blocks belonging to the invoking field', () => {
    const {trigger, blocks} = buildMatrixField();
    const entries = blocks.map(() => ({collapse: vi.fn(), expand: vi.fn()}));
    forContainer.mockImplementation(
      (el: Element) => entries[blocks.indexOf(el as HTMLElement)]
    );

    window.dispatchEvent(
      new CustomEvent('craft:matrix-toggle-all', {
        detail: {collapse: true, trigger},
      })
    );

    expect(entries[0]!.collapse).toHaveBeenCalled();
    expect(entries[1]!.collapse).toHaveBeenCalled();
    // The third block belongs to a nested field, not this one.
    expect(entries[2]!.collapse).not.toHaveBeenCalled();
  });

  it('expands when collapse is false', () => {
    const {trigger, blocks} = buildMatrixField();
    const entry = {collapse: vi.fn(), expand: vi.fn()};
    forContainer.mockImplementation((el: Element) =>
      el === blocks[0] ? entry : undefined
    );

    window.dispatchEvent(
      new CustomEvent('craft:matrix-toggle-all', {
        detail: {collapse: false, trigger},
      })
    );

    expect(entry.expand).toHaveBeenCalled();
    expect(entry.collapse).not.toHaveBeenCalled();
  });

  it('applies a selection item to the field’s selected blocks only', () => {
    document.body.innerHTML = `
      <craft-field>
        <craft-action-menu><craft-action-item id="trigger"></craft-action-item></craft-action-menu>
        <div data-matrix-block data-selected data-id="1"></div>
        <div data-matrix-block data-id="2"></div>
      </craft-field>
    `;
    const blocks = [
      ...document.querySelectorAll<HTMLElement>('[data-matrix-block]'),
    ];
    const entries = blocks.map(() => ({disableForSite: vi.fn()}));
    forContainer.mockImplementation(
      (el: Element) => entries[blocks.indexOf(el as HTMLElement)]
    );
    const trigger = document.querySelector('#trigger');

    window.dispatchEvent(
      new CustomEvent('craft:matrix-selection-action', {
        detail: {action: 'disableForSite', trigger},
      })
    );

    expect(entries[0]!.disableForSite).toHaveBeenCalledOnce();
    expect(entries[1]!.disableForSite).not.toHaveBeenCalled();
  });

  it('selects and deselects the field’s blocks through its Matrix input', () => {
    document.body.innerHTML = `
      <craft-field>
        <craft-action-menu><craft-action-item id="trigger"></craft-action-item></craft-action-menu>
        <div data-matrix-block data-id="1"></div>
      </craft-field>
    `;
    const entrySelect = {selectAll: vi.fn(), deselectAll: vi.fn()};
    forContainer.mockReturnValue({matrix: {entrySelect}});
    const trigger = document.querySelector('#trigger');

    window.dispatchEvent(
      new CustomEvent('craft:matrix-selection-action', {
        detail: {action: 'select', trigger},
      })
    );

    expect(entrySelect.selectAll).toHaveBeenCalled();

    window.dispatchEvent(
      new CustomEvent('craft:matrix-selection-action', {
        detail: {action: 'deselect', trigger},
      })
    );

    expect(entrySelect.deselectAll).toHaveBeenCalled();
  });
});
