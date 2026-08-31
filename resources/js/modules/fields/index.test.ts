import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

const openSlideout = vi.hoisted(() => vi.fn());
const createCopyTextPrompt = vi.hoisted(() => vi.fn());
const forContainer = vi.hoisted(() => vi.fn());
const copyElements = vi.hoisted(() => vi.fn());

vi.mock('@/common/slideouts', () => ({openSlideout}));
vi.mock('@craftcms/ui/factory', () => ({createCopyTextPrompt}));
vi.mock('@/modules/matrix/matrix-entry', () => ({
  MatrixEntry: {forContainer},
}));

describe('field action listeners', () => {
  beforeEach(async () => {
    openSlideout.mockReset();
    createCopyTextPrompt.mockReset();
    forContainer.mockReset();
    copyElements.mockReset();
    document.body.innerHTML = '';

    window.Craft = {
      cp: {copyElements},
      openSlideout,
      getCpUrl: (path: string, params?: Record<string, unknown>) =>
        `/admin/${path}?${new URLSearchParams(params as never).toString()}`,
    } as never;

    await import('./index');
  });

  afterEach(() => {
    delete (window as {Craft?: unknown}).Craft;
  });

  it('opens the field settings slideout and re-announces the save', async () => {
    const trigger = document.createElement('button');
    document.body.append(trigger);
    const saved = vi.fn();
    trigger.addEventListener('field-saved', saved);

    window.dispatchEvent(
      new CustomEvent('craft:edit-field', {detail: {fieldId: 7, trigger}})
    );

    expect(openSlideout).toHaveBeenCalledTimes(1);
    const [url, options] = openSlideout.mock.calls[0]!;
    expect(url).toContain('settings/fields/edit');
    expect(url).toContain('fieldId=7');
    expect(options.opener).toBe(trigger);

    // A finished save bubbles `field-saved`; an autosaved draft does not.
    options.onSaved({draft: true, data: {a: 1}});
    expect(saved).not.toHaveBeenCalled();

    options.onSaved({data: {a: 1}});
    expect(saved).toHaveBeenCalledTimes(1);
    expect((saved.mock.calls[0]![0] as CustomEvent).detail).toEqual({a: 1});

    trigger.remove();
  });

  it('ignores an edit-field event with no field', () => {
    window.dispatchEvent(new CustomEvent('craft:edit-field', {detail: {}}));

    expect(openSlideout).not.toHaveBeenCalled();
  });

  it('shows the copy prompt for a handle', () => {
    window.dispatchEvent(
      new CustomEvent('craft:copy-text-prompt', {
        detail: {label: 'Field Handle', value: 'body'},
      })
    );

    expect(createCopyTextPrompt).toHaveBeenCalledWith({
      label: 'Field Handle',
      value: 'body',
    });
  });

  it('ignores a copy prompt with no value', () => {
    window.dispatchEvent(
      new CustomEvent('craft:copy-text-prompt', {detail: {label: 'Nope'}})
    );

    expect(createCopyTextPrompt).not.toHaveBeenCalled();
  });
});

describe('field input action listeners', () => {
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
        <div class="matrix matrix-field">
          <div class="matrixblock" data-id="1"></div>
          <div class="matrixblock" data-id="2">
            <craft-field>
              <div class="matrixblock" data-id="3"></div>
            </craft-field>
          </div>
        </div>
      </craft-field>
    `;

    return {
      trigger: document.querySelector<HTMLElement>('#trigger')!,
      blocks: [...document.querySelectorAll<HTMLElement>('.matrixblock')],
    };
  }

  beforeEach(async () => {
    openSlideout.mockReset();
    createCopyTextPrompt.mockReset();
    forContainer.mockReset();
    copyElements.mockReset();
    window.Craft = {cp: {copyElements}, getCpUrl: () => ''} as never;
    await import('./index');
  });

  afterEach(() => {
    document.body.innerHTML = '';
    delete (window as {Craft?: unknown}).Craft;
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

  it('copies the field’s own cards to the CP clipboard', () => {
    document.body.innerHTML = `
      <craft-field>
        <craft-action-menu><craft-action-item id="trigger"></craft-action-item></craft-action-menu>
        <div class="nested-element-cards">
          <ul class="elements">
            <li><div class="element" data-id="5" data-site-id="2" data-owner-id="9"></div></li>
          </ul>
        </div>
      </craft-field>
    `;

    window.dispatchEvent(
      new CustomEvent('craft:copy-nested-elements', {
        detail: {
          selector: '.nested-element-cards .elements > li > .element',
          elementType: 'craft\\elements\\Address',
          fieldId: 4,
          trigger: document.querySelector('#trigger'),
        },
      })
    );

    expect(copyElements).toHaveBeenCalledWith([
      {
        type: 'craft\\elements\\Address',
        fieldId: 4,
        id: '5',
        draftId: null,
        revisionId: null,
        ownerId: 9,
        siteId: 2,
      },
    ]);
  });

  it('does not touch the clipboard when there is nothing to copy', () => {
    document.body.innerHTML = `
      <craft-field>
        <craft-action-menu><craft-action-item id="trigger"></craft-action-item></craft-action-menu>
      </craft-field>
    `;

    window.dispatchEvent(
      new CustomEvent('craft:copy-nested-elements', {
        detail: {
          selector: '.element',
          trigger: document.querySelector('#trigger'),
        },
      })
    );

    expect(copyElements).not.toHaveBeenCalled();
  });
});
