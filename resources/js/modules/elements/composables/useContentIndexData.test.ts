import {expect, it, vi} from 'vite-plus/test';
import {ref} from 'vue';
import {
  useContentIndexData,
  type ContentIndexData,
} from './useContentIndexData';

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => {
    throw new Error('usePage() should not be reached when a source is given');
  },
}));

function payload(overrides: Partial<ContentIndexData> = {}): ContentIndexData {
  return {
    elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    context: 'modal',
    search: null,
    sources: [],
    source: null,
    data: [],
    ...overrides,
  } as unknown as ContentIndexData;
}

it('reads the payload from an explicit source instead of the Inertia page', () => {
  const source = ref(payload({search: 'first'}));
  const index = useContentIndexData(undefined, source);

  expect(index.context).toBe('modal');
  expect(index.search).toBe('first');
});

it('tracks the source, so an XHR-driven index updates like a page visit', () => {
  const source = ref(payload({search: 'first', data: []}));
  const index = useContentIndexData(undefined, source);

  source.value = payload({search: 'second', data: [{id: 1}]});

  expect(index.search).toBe('second');
  expect(index.data).toHaveLength(1);
});

it('still lets extra keys win over the payload', () => {
  const source = ref(payload({search: 'from-payload'}));
  const index = useContentIndexData({search: 'from-extra'}, source);

  expect(index.search).toBe('from-extra');
});
