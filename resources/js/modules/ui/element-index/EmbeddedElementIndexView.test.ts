import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import type {BulkActionItem} from '@/modules/elements/types/actions';
import {
  button,
  inlineTitleInput,
  nestedElement,
  nestedIndexPayload,
  postsTo,
  stubElementInternals,
} from '../nested-elements/nested-index.fixture';
import EmbeddedElementIndexView from './EmbeddedElementIndexView.vue';

const request = vi.hoisted(() => ({post: vi.fn()}));
const actions = vi.hoisted(() => ({run: vi.fn()}));
vi.mock('@craftcms/ui', async (original) => ({
  ...(await original<Record<string, unknown>>()),
  actionClient: request,
}));
vi.mock('@craftcms/ui/actions.mjs', () => ({runAction: actions.run}));
vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {readOnly: false}}),
}));

const archive: BulkActionItem = {
  key: 'plugin\\Archive',
  label: 'Archive selected',
  action: {
    type: 'http',
    method: 'POST',
    url: '/actions/element-indexes/perform-action',
    body: {elementAction: 'plugin\\Archive'},
  },
} as BulkActionItem;

describe('EmbeddedElementIndexView', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let root: HTMLElement | undefined;
  let restoreElementInternals = () => {};

  afterEach(() => {
    app?.unmount();
    root?.remove();
    request.post.mockReset();
    actions.run.mockReset();
    localStorage.clear();
    restoreElementInternals();
  });

  function mount(
    readOnly: boolean,
    rows = [nestedElement(11), nestedElement(12)]
  ): HTMLElement {
    restoreElementInternals = stubElementInternals();
    request.post.mockImplementation(async (url: string) =>
      url.includes('save-elements')
        ? {data: {message: 'Changes saved.'}}
        : {data: nestedIndexPayload(rows, {actions: [archive]})}
    );
    root = document.createElement('div');
    document.body.append(root);
    app = createApp({
      render: () =>
        h(EmbeddedElementIndexView, {
          elementType: 'Entry',
          initial: nestedIndexPayload(rows, {actions: [archive]}),
          params: {embeddedIndexToken: 'sealed'},
          storageKey: 'embeddedindex.test',
          readOnly,
        }),
    });
    app.mount(root);

    return root;
  }

  function selectRow(container: HTMLElement, label: string): void {
    [...container.querySelectorAll<HTMLElement>('tr')]
      .find((row) => row.textContent?.includes(label))!
      .dispatchEvent(new KeyboardEvent('keydown', {key: ' ', bubbles: true}));
  }

  function actionItem(
    container: HTMLElement,
    label: string
  ): HTMLElement | undefined {
    return [
      ...container.querySelectorAll<HTMLElement>('craft-action-item'),
    ].find((item) => item.textContent?.trim() === label);
  }

  it('sends bulk actions for the selected rows with its scope', async () => {
    const container = mount(false);
    await vi.waitFor(() =>
      expect(container.querySelector('tr')).not.toBeNull()
    );

    selectRow(container, 'Entry 11');
    await nextTick();
    await vi.waitFor(() =>
      expect(actionItem(container, 'Archive selected')).toBeDefined()
    );
    const item = actionItem(container, 'Archive selected') as HTMLElement & {
      action: unknown;
    };

    expect(item.action).toMatchObject({
      url: '/actions/element-indexes/perform-action',
      body: {
        elementAction: 'plugin\\Archive',
        elementIds: [11],
        elementType: 'Entry',
        context: 'embeddedIndex',
        source: '__IMP__',
        embeddedIndexToken: 'sealed',
      },
    });
  });

  it('keeps Set Status’s status apart from the listed status', async () => {
    const setStatus = {
      key: 'CraftCms\\Cms\\Element\\Actions\\SetStatus',
      label: 'Set Status',
      action: {
        type: 'http',
        method: 'POST',
        url: '/actions/element-indexes/perform-action',
        body: {elementAction: 'CraftCms\\Cms\\Element\\Actions\\SetStatus'},
      },
    } as BulkActionItem;
    restoreElementInternals = stubElementInternals();
    const payload = nestedIndexPayload([nestedElement(11)], {
      actions: [setStatus],
      statusMenuValue: 'live',
    });
    request.post.mockResolvedValue({data: payload});
    root = document.createElement('div');
    document.body.append(root);
    app = createApp({
      render: () =>
        h(EmbeddedElementIndexView, {
          elementType: 'Entry',
          initial: payload,
          params: {embeddedIndexToken: 'sealed'},
          storageKey: 'embeddedindex.test',
        }),
    });
    app.mount(root);
    await vi.waitFor(() => expect(root!.querySelector('tr')).not.toBeNull());

    selectRow(root, 'Entry 11');
    await vi.waitFor(() => expect(actionItem(root!, 'Disabled')).toBeDefined());
    actionItem(root, 'Disabled')!.click();

    await vi.waitFor(() => expect(actions.run).toHaveBeenCalled());
    expect(actions.run.mock.calls[0]?.[0].body).toMatchObject({
      status: 'disabled',
      criteria: {status: 'live'},
    });
  });

  it('sorts search results by relevance', async () => {
    const container = mount(false);
    await vi.waitFor(() =>
      expect(container.querySelector('tr')).not.toBeNull()
    );
    const search = container.querySelector<HTMLElement & {value: string}>(
      'craft-input-search[name="search"]'
    )!;

    search.value = 'needle';
    search.dispatchEvent(new Event('input', {bubbles: true}));
    search.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Enter', bubbles: true})
    );

    await vi.waitFor(() =>
      expect(postsTo(request.post, 'get-elements').at(-1)).toMatchObject({
        search: 'needle',
        sort: [{field: 'score', direction: 'desc'}],
      })
    );
  });

  it('asks for inline inputs when editing inline', async () => {
    const container = mount(false);
    await vi.waitFor(() =>
      expect(container.querySelector('tr')).not.toBeNull()
    );

    [...container.querySelectorAll<HTMLElement>('craft-button')]
      .find((button) => button.textContent?.trim() === 'Edit inline')!
      .click();

    await vi.waitFor(() =>
      expect(request.post).toHaveBeenCalledWith(
        expect.stringContaining('get-elements'),
        expect.objectContaining({editable: true, embeddedIndexToken: 'sealed'})
      )
    );
  });

  it('reloads its rows after saving inline edits', async () => {
    const container = mount(false, [
      nestedElement(11, {inlineInputHtml: inlineTitleInput(11)}),
    ]);
    await vi.waitFor(() =>
      expect(container.querySelector('tr')).not.toBeNull()
    );
    button(container, 'Edit inline').click();
    const input = await vi.waitFor(() => {
      const element = container.querySelector<HTMLInputElement>(
        '[data-inline-id="11"] input'
      );
      expect(element).not.toBeNull();

      return element!;
    });
    input.value = 'Edited';
    const readsBeforeSave = postsTo(request.post, 'get-elements').length;

    button(container, 'Save').click();

    await vi.waitFor(() =>
      expect(postsTo(request.post, 'get-elements')).toHaveLength(
        readsBeforeSave + 1
      )
    );
    expect(postsTo(request.post, 'get-elements').at(-1)).not.toHaveProperty(
      'editable'
    );
  });

  it('offers exports but no bulk actions when it’s read-only', async () => {
    const container = mount(true);
    await vi.waitFor(() =>
      expect(container.querySelector('tr')).not.toBeNull()
    );

    selectRow(container, 'Entry 11');
    await nextTick();

    expect(actionItem(container, 'Archive selected')).toBeUndefined();
    expect(
      [...container.querySelectorAll('craft-button')].some(
        (button) => button.textContent?.trim() === 'Export'
      )
    ).toBe(true);
  });
});
