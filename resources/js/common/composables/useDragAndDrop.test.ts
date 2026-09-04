import {beforeEach, expect, it, vi} from 'vite-plus/test';
import {type DragData, useDragAndDrop} from './useDragAndDrop';

type Config = Record<string, any>;

const registry = vi.hoisted(() => ({
  draggables: [] as Config[],
  dropTargets: [] as Config[],
  monitors: [] as Config[],
}));

vi.mock('@atlaskit/pragmatic-drag-and-drop/element/adapter', () => ({
  draggable: (config: Config) => {
    registry.draggables.push(config);

    return () => undefined;
  },
  dropTargetForElements: (config: Config) => {
    registry.dropTargets.push(config);

    return () => undefined;
  },
  monitorForElements: (config: Config) => {
    registry.monitors.push(config);

    return () => undefined;
  },
}));

vi.mock('@atlaskit/pragmatic-drag-and-drop/combine', () => ({
  combine:
    (...cleanups: Array<() => void>) =>
    () =>
      cleanups.forEach((cleanup) => cleanup()),
}));

// The hitbox helpers need real geometry, which happy-dom doesn't have. Carry
// the edge on the data instead, so a test can say which one it means.
vi.mock('@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge', () => ({
  attachClosestEdge: (data: Config) => data,
  extractClosestEdge: (data: Config) => data.closestEdge ?? 'bottom',
}));

vi.mock(
  '@atlaskit/pragmatic-drag-and-drop-hitbox/util/get-reorder-destination-index',
  () => ({
    getReorderDestinationIndex: ({indexOfTarget}: {indexOfTarget: number}) =>
      indexOfTarget,
  })
);

vi.mock(
  '@atlaskit/pragmatic-drag-and-drop/element/preserve-offset-on-source',
  () => ({preserveOffsetOnSource: () => () => ({x: 0, y: 0})})
);

vi.mock(
  '@atlaskit/pragmatic-drag-and-drop/element/set-custom-native-drag-preview',
  () => ({setCustomNativeDragPreview: () => undefined})
);

/**
 * A list of rows registered with the composable, with the pieces a drag would
 * otherwise reach through the DOM exposed for a test to drive.
 */
function list(
  ids: string[],
  options: Partial<Parameters<typeof useDragAndDrop>[0]> = {}
) {
  const onReorder = vi.fn();
  const dnd = useDragAndDrop({onReorder, ...options});

  const rows = ids.map((id, index) => {
    const before = registry.dropTargets.length;
    dnd.registerItem(document.createElement('div'), null, id, index);

    const dropTarget = registry.dropTargets[before]!;
    const draggable = registry.draggables[before]!;

    return {
      id,
      /** What this row puts on the wire when dragged. */
      payload: (): DragData => draggable.getInitialData(),
      /** What a drop on this row reports back. */
      data: (): DragData => dropTarget.getData({input: {}}),
      canDrop: (data: DragData) => dropTarget.canDrop({source: {data}}),
      dragEnter: (data: DragData, edge: string = 'bottom') =>
        dropTarget.onDragEnter({
          source: {data},
          self: {data: {closestEdge: edge}},
        }),
      dropState: () => dnd.getDropState(id),
    };
  });

  dnd.setupMonitor();
  const monitor = registry.monitors[registry.monitors.length - 1]!;

  return {
    rows,
    onReorder,
    canMonitor: (data: DragData) => monitor.canMonitor({source: {data}}),
    drop: (data: DragData, target: DragData) =>
      monitor.onDrop({
        source: {data},
        location: {current: {dropTargets: [{data: target}]}},
      }),
  };
}

/** A sources list, whose rows can be dropped on a pages list. */
function sourcesList(ids: string[]) {
  return list(ids, {dragData: (id) => ({sourceKey: id})});
}

function pagesList(ids: string[], onForeignDrop = vi.fn()) {
  return {
    ...list(ids, {
      canDropForeign: (data) => typeof data.sourceKey === 'string',
      onForeignDrop,
    }),
    onForeignDrop,
  };
}

beforeEach(() => {
  registry.draggables.length = 0;
  registry.dropTargets.length = 0;
  registry.monitors.length = 0;
});

it('reorders within a list', () => {
  const sources = sourcesList(['a', 'b', 'c']);

  sources.drop(sources.rows[0]!.payload(), sources.rows[2]!.data());

  expect(sources.onReorder).toHaveBeenCalledWith(0, 2);
});

it('reports a row dropped on another list instead of reordering', () => {
  const sources = sourcesList(['section:a', 'section:b']);
  const pages = pagesList(['Entries', 'Archive']);

  const dragged = sources.rows[1]!.payload();
  const page = pages.rows[1]!;

  expect(page.canDrop(dragged)).toBe(true);
  expect(pages.canMonitor(dragged)).toBe(true);

  pages.drop(dragged, page.data());

  // Where it landed, so the receiving list can insert rather than guess.
  expect(pages.onForeignDrop).toHaveBeenCalledWith(dragged, {
    id: 'Archive',
    index: 1,
    edge: 'bottom',
  });
  // The page list owns the meaning of the drop; the source list stays out of it.
  expect(sources.onReorder).not.toHaveBeenCalled();
  expect(pages.onReorder).not.toHaveBeenCalled();
});

it('leaves its own rows alone when they land on another list', () => {
  const sources = sourcesList(['section:a', 'section:b']);
  const pages = pagesList(['Entries', 'Archive']);

  sources.drop(sources.rows[0]!.payload(), pages.rows[1]!.data());

  expect(sources.onReorder).not.toHaveBeenCalled();
});

it('turns away a foreign row that the list does not accept', () => {
  const sources = sourcesList(['section:a']);
  const pages = pagesList(['Entries']);
  // A page carries no source key, so the sources list won't take one, and the
  // pages list won't take one from another pages list either.
  const otherPages = pagesList(['Drafts']);

  expect(sources.rows[0]!.canDrop(pages.rows[0]!.payload())).toBe(false);
  expect(sources.canMonitor(pages.rows[0]!.payload())).toBe(false);
  expect(otherPages.rows[0]!.canDrop(pages.rows[0]!.payload())).toBe(false);
});

it('marks where a foreign drag would land', () => {
  const sources = sourcesList(['section:a']);
  const pages = pagesList(['Entries', 'Archive']);

  pages.rows[0]!.dragEnter(sources.rows[0]!.payload(), 'top');

  expect(pages.rows[0]!.dropState()).toEqual({
    type: 'is-over-foreign',
    closestEdge: 'top',
  });
  // A row of its own list still gets the between-rows treatment.
  pages.rows[0]!.dragEnter(pages.rows[1]!.payload());
  expect(pages.rows[0]!.dropState().type).toBe('is-over');
});
