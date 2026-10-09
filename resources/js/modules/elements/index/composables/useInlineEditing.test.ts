import {ref} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import type {InlineAttributeUiHost} from '@/modules/ui/inline-attribute-ui-host';
import {
  useInlineEditing,
  type InlineEditingSaveResult,
} from './useInlineEditing';

describe('useInlineEditing', () => {
  let container: HTMLElement | undefined;

  afterEach(() => {
    container?.remove();
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
  });

  function setup(
    save?: (body: URLSearchParams) => Promise<InlineEditingSaveResult | false>
  ) {
    const saveInline = save ?? vi.fn(async () => ({message: 'Changes saved.'}));
    container = document.createElement('div');
    container.innerHTML = `
      <div data-inline-id="11">
        <craft-inline-attribute-ui>
          <input name="inline[element-11][title]" value="Original">
        </craft-inline-attribute-ui>
      </div>
    `;
    document.body.append(container);
    const host = container.querySelector<InlineAttributeUiHost>(
      'craft-inline-attribute-ui'
    )!;
    Object.assign(host, {
      ready: Promise.resolve(),
      focusFirst: vi.fn(() => false),
      canSubmit: vi.fn(() => true),
      errors: {},
    });
    const load = vi.fn(async () => {});
    const displayNotice = vi.fn();
    const displayError = vi.fn();
    vi.stubGlobal('Craft', {
      findDeltaData: vi.fn(
        (_before: string, after: string) =>
          `${after}&modifiedDeltaNames[]=inline%5Belement-11%5D`
      ),
      cp: {displayNotice, displayError},
    });
    const state = useInlineEditing({
      container: ref(container),
      editableRows: [
        {
          id: 11,
          title: 'Original',
          inlineInputHtml: {
            title: '<input name="inline[element-11][title]" value="Original">',
          },
        },
      ],
      busy: false,
      loading: false,
      load,
      save: saveInline,
    });

    return {
      state,
      load,
      save: saveInline,
      host,
      input: container.querySelector<HTMLInputElement>('input')!,
      displayNotice,
    };
  }

  it('closes without a request when no inputs changed', async () => {
    const {state, save, displayNotice} = setup();
    await state.start();
    window.Craft.findDeltaData = vi.fn(() => '');

    await state.save();

    expect(save).not.toHaveBeenCalled();
    expect(state.editingIds.value).toEqual([]);
    expect(displayNotice).toHaveBeenCalledWith('No changes to save.');
  });

  it('keeps values and maps validation errors back to their form host', async () => {
    const save = vi.fn(async () => ({
      errors: {11: {title: ['Title is not allowed.']}},
    }));
    const {state, input, host} = setup(save);
    await state.start();
    input.value = 'Edited';

    await state.save();

    expect(state.editingIds.value).toEqual([11]);
    expect(state.errors.value).toEqual({
      11: {title: ['Title is not allowed.']},
    });
    expect(host.errors).toEqual({title: ['Title is not allowed.']});
    expect(input.value).toBe('Edited');
  });

  it('requires confirmation before discarding changed inputs', async () => {
    const {state, input} = setup();
    await state.start();
    input.value = 'Edited';
    const confirm = vi
      .fn()
      .mockReturnValueOnce(false)
      .mockReturnValueOnce(true);
    vi.stubGlobal('confirm', confirm);

    state.cancel();
    expect(state.editingIds.value).toEqual([11]);

    state.cancel();
    expect(state.editingIds.value).toEqual([]);
    expect(confirm).toHaveBeenCalledTimes(2);
  });

  it('saves with Enter and Ctrl+S but leaves plain textarea Enter alone', async () => {
    const {state, input, save} = setup();
    await state.start();
    input.value = 'Edited';

    const textarea = document.createElement('textarea');
    container!.append(textarea);
    const textareaEnter = new KeyboardEvent('keydown', {
      key: 'Enter',
      bubbles: true,
      cancelable: true,
    });
    Object.defineProperty(textareaEnter, 'target', {value: textarea});
    state.onKeydown(textareaEnter);
    expect(textareaEnter.defaultPrevented).toBe(false);

    const saveShortcut = new KeyboardEvent('keydown', {
      key: 's',
      ctrlKey: true,
      bubbles: true,
      cancelable: true,
    });
    Object.defineProperty(saveShortcut, 'target', {value: input});
    state.onKeydown(saveShortcut);
    await vi.waitFor(() => expect(save).toHaveBeenCalledOnce());

    expect(saveShortcut.defaultPrevented).toBe(true);
  });
});
