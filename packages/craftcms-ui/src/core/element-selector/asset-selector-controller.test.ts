import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {AssetSelectorController} from './asset-selector-controller.js';
import type {AssetSelectorOptions} from './asset-selector-controller.js';
import type {ElementInfo} from './types.js';

const ASSET = 'CraftCms\\Cms\\Asset\\Elements\\Asset';

const TRANSFORMS = [
  {handle: 'thumb', name: 'Thumbnail'},
  {handle: 'large', name: 'Large'},
];

function asset(id: number): ElementInfo {
  return {
    id,
    siteId: 1,
    label: `Asset ${id}`,
    status: null,
    url: `/uploads/asset-${id}.jpg`,
    hasThumb: true,
  };
}

function create(options: Partial<AssetSelectorOptions> = {}) {
  return new AssetSelectorController({
    elementType: ASSET,
    transforms: TRANSFORMS,
    hideOnSelect: false,
    loadIndexBody: async () => ({html: '', props: {}}),
    fetchTransformUrl: async (id, handle) => `/transformed/${handle}/${id}.jpg`,
    ...options,
  });
}

beforeEach(() => {
  // Static by design (transform URLs are stable per asset and modals are built
  // per relation field), so it has to be reset between tests.
  AssetSelectorController.transformUrls = {};
});

describe('canApplyTransform', () => {
  it('is false with nothing selected', () => {
    expect(create().canApplyTransform).toBe(false);
  });

  it('is true once assets are selected', () => {
    const controller = create();
    controller.setSelection([asset(1)]);

    expect(controller.canApplyTransform).toBe(true);
  });

  it('is false when no transforms are configured', () => {
    const controller = create({transforms: []});
    controller.setSelection([asset(1)]);

    expect(controller.canApplyTransform).toBe(false);
  });
});

describe('selectWithTransform', () => {
  it('splices the transformed URL into the payload', async () => {
    const onSelect = vi.fn();
    const controller = create({onSelect});
    controller.setSelection([asset(1), asset(2)]);

    await controller.selectWithTransform('thumb');

    expect(onSelect.mock.calls[0]![0].map((e: ElementInfo) => e.url)).toEqual([
      '/transformed/thumb/1.jpg',
      '/transformed/thumb/2.jpg',
    ]);
  });

  it('reports the transform in the select meta', async () => {
    const onSelect = vi.fn();
    const controller = create({onSelect});
    controller.setSelection([asset(1)]);

    await controller.selectWithTransform('large');

    expect(onSelect.mock.calls[0]![1]).toEqual({transform: 'large'});
  });

  it('leaves the transform unset for a plain submit', async () => {
    const onSelect = vi.fn();
    const controller = create({onSelect});
    controller.setSelection([asset(1)]);

    await controller.submit();

    expect(onSelect.mock.calls[0]![1]).toEqual({transform: null});
    expect(onSelect.mock.calls[0]![0][0].url).toBe('/uploads/asset-1.jpg');
  });

  it('resets the transform after submitting', async () => {
    const onSelect = vi.fn();
    const controller = create({onSelect});
    controller.setSelection([asset(1)]);

    await controller.selectWithTransform('thumb');
    await controller.submit();

    expect(onSelect.mock.calls[1]![1]).toEqual({transform: null});
  });

  it('holds busy across the fetches', async () => {
    let release: (url: string) => void = () => {};
    const controller = create({
      fetchTransformUrl: () =>
        new Promise<string>((resolve) => {
          release = resolve;
        }),
    });
    controller.setSelection([asset(1)]);

    const pending = controller.selectWithTransform('thumb');
    expect(controller.state.busy).toBe(true);

    release('/transformed/thumb/1.jpg');
    await pending;

    expect(controller.state.busy).toBe(false);
  });
});

describe('the URL cache', () => {
  it('fetches each asset once, sequentially', async () => {
    const order: number[] = [];
    const fetchTransformUrl = vi.fn(async (id: number, handle: string) => {
      order.push(id);
      return `/transformed/${handle}/${id}.jpg`;
    });
    const controller = create({fetchTransformUrl});
    controller.setSelection([asset(1), asset(2)]);

    await controller.selectWithTransform('thumb');
    await controller.selectWithTransform('thumb');

    expect(fetchTransformUrl).toHaveBeenCalledTimes(2);
    expect(order).toEqual([1, 2]);
  });

  it('only fetches the assets it is missing', async () => {
    const fetchTransformUrl = vi.fn(
      async (id: number, handle: string) => `/transformed/${handle}/${id}.jpg`
    );
    const controller = create({fetchTransformUrl});

    controller.setSelection([asset(1)]);
    await controller.selectWithTransform('thumb');
    fetchTransformUrl.mockClear();

    controller.setSelection([asset(1), asset(2)]);
    await controller.selectWithTransform('thumb');

    expect(fetchTransformUrl.mock.calls.map(([id]) => id)).toEqual([2]);
  });

  it('keys the cache per transform', async () => {
    const fetchTransformUrl = vi.fn(
      async (id: number, handle: string) => `/transformed/${handle}/${id}.jpg`
    );
    const controller = create({fetchTransformUrl});
    controller.setSelection([asset(1)]);

    await controller.selectWithTransform('thumb');
    await controller.selectWithTransform('large');

    expect(fetchTransformUrl).toHaveBeenCalledTimes(2);
    expect(Object.keys(AssetSelectorController.transformUrls)).toEqual([
      'thumb',
      'large',
    ]);
  });

  it('shares the cache across instances', async () => {
    const fetchTransformUrl = vi.fn(
      async (id: number, handle: string) => `/transformed/${handle}/${id}.jpg`
    );

    const first = create({fetchTransformUrl});
    first.setSelection([asset(1)]);
    await first.selectWithTransform('thumb');

    const second = create({fetchTransformUrl});
    second.setSelection([asset(1)]);
    await second.selectWithTransform('thumb');

    expect(fetchTransformUrl).toHaveBeenCalledTimes(1);
  });

  it('falls back to the untransformed URL when a transform fails', async () => {
    const onSelect = vi.fn();
    const controller = create({
      onSelect,
      fetchTransformUrl: async () => {
        throw new Error('generate-transform blew up');
      },
    });
    controller.setSelection([asset(1)]);

    await controller.selectWithTransform('thumb');

    expect(controller.state.busy).toBe(false);
    expect(onSelect.mock.calls[0]![0][0].url).toBe('/uploads/asset-1.jpg');
  });

  it('caches a false result so a failed transform is not retried', async () => {
    const fetchTransformUrl = vi.fn(async () => false as const);
    const controller = create({fetchTransformUrl});
    controller.setSelection([asset(1)]);

    await controller.selectWithTransform('thumb');
    await controller.selectWithTransform('thumb');

    expect(fetchTransformUrl).toHaveBeenCalledTimes(1);
  });
});
