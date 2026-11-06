import {
  afterEach,
  describe,
  expect,
  it,
  onTestFinished,
  vi,
} from 'vite-plus/test';
import {effectScope, nextTick, ref} from 'vue';
import {actionClient} from '@craftcms/ui';
import {broadcast} from '@/common/craft/broadcast';
import {
  useElementPresentations,
  type PresentationQuery,
  type PresentedElement,
} from './element-presentations';

const ENTRY = 'craft\\elements\\Entry';

/** Answers each request with the requested elements, labeled by request. */
function respond() {
  let request = 0;
  return vi
    .spyOn(actionClient, 'post')
    .mockImplementation(async (_url, body: any) => {
      request++;
      return {
        data: {
          elements: body.elements ?? [
            ...body.elementIds.map((id: number) => ({
              id,
              siteId: body.siteId,
              label: `${body.viewMode} ${id} #${request}`,
            })),
            // Elements the server adds, such as ancestors, aren't cached.
            {id: 999, label: 'Ancestor'},
          ],
        },
      } as never;
    });
}

function consume(
  query: PresentationQuery,
  elements: PresentedElement[],
  seed: Array<{id: number; label: string; siteId?: number}> = []
) {
  const scope = effectScope();
  const presentations = scope.run(() =>
    useElementPresentations(query, elements)
  )!;
  presentations.seed(seed, 1);
  onTestFinished(() => scope.stop());
  return {presentations};
}

async function settle(post: ReturnType<typeof respond>): Promise<void> {
  await vi.waitFor(() => expect(post).toHaveBeenCalled());
  await new Promise((resolve) => setTimeout(resolve, 0));
}

afterEach(() => {
  vi.restoreAllMocks();
});

describe('useElementPresentations()', () => {
  it('refetches a saved element once for every component that draws it the same way', async () => {
    const post = respond();
    const list = {elementType: ENTRY, viewMode: 'list'};
    const a = consume(list, [{id: 7, siteId: 1}], [{id: 7, label: 'Old'}]);
    const b = consume(list, [
      {id: 7, siteId: 1},
      {id: 8, siteId: 1},
    ]);
    const cards = consume({elementType: ENTRY, viewMode: 'cards'}, [
      {id: 7, siteId: 1},
    ]);
    const otherSite = consume(list, [{id: 7, siteId: 2}]);

    expect(a.presentations.get(7, 1)?.label).toBe('Old');

    broadcast({event: 'saveElement', id: 7});
    await settle(post);

    expect(post).toHaveBeenCalledTimes(3);
    expect(
      post.mock.calls.map(([, body]: any) => [
        body.viewMode,
        body.siteId,
        body.elementIds,
      ])
    ).toEqual(
      expect.arrayContaining([
        ['list', 1, [7]],
        ['cards', 1, [7]],
        ['list', 2, [7]],
      ])
    );
    const listLabel = a.presentations.get(7, 1)?.label;
    expect(listLabel).toMatch(/^list 7 #/);
    expect(b.presentations.get(7, '1')?.label).toBe(listLabel);
    expect(b.presentations.get(8, 1)).toBeUndefined();
    expect(cards.presentations.get(7, 1)?.label).toMatch(/^cards 7 #/);
    expect(otherSite.presentations.get(7, 2)?.label).toMatch(/^list 7 #/);
    expect(a.presentations.get(999, null)).toBeUndefined();
  });

  it('refetches elements saved together in one request', async () => {
    const post = respond();
    consume({elementType: ENTRY, viewMode: 'list'}, [
      {id: 7, siteId: 1},
      {id: 8, siteId: 1},
      {id: 9, siteId: 1},
    ]);

    broadcast({event: 'saveElement', id: 7});
    broadcast({event: 'deleteDraft', canonicalId: 8, draftId: 3});
    await settle(post);

    expect(post).toHaveBeenCalledOnce();
    expect((post.mock.calls[0]![1] as any).elementIds).toEqual([7, 8]);
  });

  it('refetches everything components draw when a response left saves out', async () => {
    const post = respond();
    consume({elementType: ENTRY, viewMode: 'list'}, [
      {id: 7, siteId: 1},
      {id: 8, siteId: 1},
    ]);

    broadcast({event: 'broadcastOverflow'});
    await settle(post);

    expect(post).toHaveBeenCalledOnce();
    expect((post.mock.calls[0]![1] as any).elementIds).toEqual([7, 8]);
  });

  it('lets a later server render replace what was refetched', async () => {
    const post = respond();
    const list = {elementType: ENTRY, viewMode: 'list'};
    const field = consume(list, [{id: 7, siteId: 1}]);

    broadcast({event: 'saveElement', id: 7});
    await settle(post);
    field.presentations.seed([{id: 7, label: 'Rendered later'}], 1, {
      replace: true,
    });

    expect(field.presentations.get(7, 1)?.label).toBe('Rendered later');
  });

  it('follows the elements a component draws, and forgets them once nothing draws them', async () => {
    const post = respond();
    const list = {elementType: ENTRY, viewMode: 'list'};
    const elements = ref<PresentedElement[]>([]);
    const scope = effectScope();
    const field = scope.run(() => useElementPresentations(list, elements))!;
    onTestFinished(() => scope.stop());

    broadcast({event: 'saveElement', id: 7});
    await new Promise((resolve) => setTimeout(resolve, 100));
    expect(post).not.toHaveBeenCalled();

    elements.value = [{id: 7, siteId: 1}];
    broadcast({event: 'saveElement', id: 7});
    await settle(post);
    expect(field.get(7, 1)).toBeDefined();

    scope.stop();
    const later = consume(list, []);
    expect(later.presentations.get(7, 1)).toBeUndefined();
  });

  it('keeps what a component mounted with from replacing a newer entry', async () => {
    const post = respond();
    const list = {elementType: ENTRY, viewMode: 'list'};
    const field = consume(list, [{id: 7, siteId: 1}], [{id: 7, label: 'Old'}]);

    broadcast({event: 'saveElement', id: 7});
    await settle(post);
    const fresh = field.presentations.get(7, 1)?.label;
    const hiddenTab = consume(
      list,
      [{id: 7, siteId: 1}],
      [{id: 7, label: 'Old'}]
    );

    expect(fresh).toMatch(/^list 7 #/);
    expect(hiddenTab.presentations.get(7, 1)?.label).toBe(fresh);
  });

  it('ignores a refetch answered after a newer write', async () => {
    let answer!: () => void;
    const post = vi.spyOn(actionClient, 'post').mockImplementation(
      (_url, body: any) =>
        new Promise((resolve) => {
          answer = () =>
            resolve({
              data: {elements: [{id: 7, siteId: body.siteId, label: 'Stale'}]},
            } as never);
        })
    );
    const field = consume({elementType: ENTRY, viewMode: 'list'}, [
      {id: 7, siteId: 1},
    ]);

    broadcast({event: 'saveElement', id: 7});
    await vi.waitFor(() => expect(post).toHaveBeenCalled());
    field.presentations.seed([{id: 7, label: 'Rendered later'}], 1, {
      replace: true,
    });
    answer();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(field.presentations.get(7, 1)?.label).toBe('Rendered later');
  });

  it('drops an element as soon as no component draws it', async () => {
    const elements = ref<PresentedElement[]>([{id: 7, siteId: 1}]);
    const scope = effectScope();
    const field = scope.run(() =>
      useElementPresentations({elementType: ENTRY, viewMode: 'list'}, elements)
    )!;
    onTestFinished(() => scope.stop());
    field.seed([{id: 7, label: 'Related'}], 1);

    elements.value = [];
    await nextTick();

    expect(field.get(7, 1)).toBeUndefined();
  });
});
