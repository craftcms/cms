import {expect, it, vi} from 'vite-plus/test';
import {computed, reactive} from 'vue';
import useCraftData, {useHelpers} from './useCraftData';

const state = vi.hoisted(() => ({page: null as any}));

vi.mock('@inertiajs/vue3', () => ({usePage: () => state.page}));

it('updates primitive, nested, and nullable props when Inertia replaces page props', () => {
  state.page = reactive({
    props: {
      craft: {
        readOnly: false,
        site: {handle: 'english'},
        csrfTokenValue: null,
        csrfTokenName: null,
      },
    },
  });
  const {site, readOnly, csrfTokenValue, csrfTokenName} = useCraftData();
  const selectedSite = computed(() => site.value?.handle);

  expect(readOnly.value).toBe(false);
  expect(selectedSite.value).toBe('english');
  expect(csrfTokenValue.value).toBeNull();
  expect(csrfTokenName.value).toBeNull();

  state.page.props = {
    craft: {
      readOnly: true,
      site: {handle: 'french'},
      csrfTokenValue: 'replacement-token',
      csrfTokenName: '_token',
    },
  };

  expect(readOnly.value).toBe(true);
  expect(selectedSite.value).toBe('french');
  expect(csrfTokenValue.value).toBe('replacement-token');
  expect(csrfTokenName.value).toBe('_token');
});

it('builds URLs from the current Craft data after navigation', () => {
  state.page = reactive({
    props: {
      craft: {
        actionUrl: 'https://example.test/actions',
        cpUrl: 'https://example.test/admin/',
        baseApiUrl: 'https://example.test/api',
      },
    },
  });
  const helpers = useHelpers();

  expect(helpers.getCpUrl('entries')).toBe(
    'https://example.test/admin/entries'
  );

  state.page.props.craft = {
    actionUrl: 'https://other.test/actions',
    cpUrl: 'https://other.test/control/',
    baseApiUrl: 'https://other.test/api',
  };

  expect(helpers.getActionUrl('save')).toBe('https://other.test/actions/save');
  expect(helpers.getCpUrl('entries')).toBe(
    'https://other.test/control/entries'
  );
  expect(helpers.getApiUrl('entries')).toBe('https://other.test/api/entries');
});
