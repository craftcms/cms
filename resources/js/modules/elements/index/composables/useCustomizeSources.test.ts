import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {router} from '@inertiajs/vue3';
import {useCustomizeSources} from './useCustomizeSources';
import {appendIndexQuery} from './useElementIndexVisits';
import type {CustomizeSourcesModalOptions} from '@/modules/customize-sources';

vi.mock('@inertiajs/vue3', () => ({router: {visit: vi.fn()}}));
vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({
    currentUser: {value: {admin: true}},
    allowAdminChanges: {value: true},
  }),
}));

const route = {
  url: (query = {}) =>
    appendIndexQuery('/admin/commerce/products/shirts', query),
};

describe('useCustomizeSources', () => {
  const openModal = vi.fn();

  beforeEach(() => {
    vi.stubGlobal('Craft', {openCustomizeSourcesModal: openModal});
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  function save(
    target: Parameters<typeof useCustomizeSources>[0],
    sourceKey: string | null,
    url: string | null
  ): CustomizeSourcesModalOptions {
    useCustomizeSources(target).value[0]!.onClick!(new MouseEvent('click'));
    const options: CustomizeSourcesModalOptions = openModal.mock.calls[0]![0];
    options.onSaved?.(sourceKey, url);

    return options;
  }

  it('lands on the link the nav gives the source last edited', () => {
    save(
      () => ({elementType: 'Product', route}),
      'productType:a',
      '/admin/commerce/products/shirts'
    );

    expect(router.visit).toHaveBeenCalledWith(
      '/admin/commerce/products/shirts',
      expect.anything()
    );
  });

  it('names the source in the query when the nav has no link for it', () => {
    save(
      () => ({
        elementType: 'Product',
        route,
        sourceHref: '/admin/commerce/products',
      }),
      'custom:b',
      null
    );

    expect(router.visit).toHaveBeenCalledWith(
      '/admin/commerce/products?source=custom%3Ab',
      expect.anything()
    );
  });

  it('leaves the modal to reload the page without a route', () => {
    const options = save(() => ({elementType: 'Product'}), 'custom:b', null);

    expect(options.onSaved).toBeUndefined();
    expect(router.visit).not.toHaveBeenCalled();
  });
});
