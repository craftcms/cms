import {expect, it, vi} from 'vite-plus/test';
import {effectScope, nextTick, reactive} from 'vue';
import {useNavBadges} from './useNavBadges';

const state = vi.hoisted(() => ({page: null as any}));

vi.mock('@inertiajs/vue3', () => ({usePage: () => state.page}));

it('takes refreshed counts from a craft:nav-badges event until the next response', async () => {
  state.page = reactive({
    props: {craft: {navBadges: {'nav-utilities': 18}}},
  });
  const scope = effectScope();
  const badges = scope.run(() => useNavBadges())!;

  expect(badges.value).toEqual({'nav-utilities': 18});

  window.dispatchEvent(
    new CustomEvent('craft:nav-badges', {
      detail: {'nav-utilities': 20, 'nav-utilities-updates': 2},
    })
  );

  expect(badges.value).toEqual({
    'nav-utilities': 20,
    'nav-utilities-updates': 2,
  });

  state.page.props = {craft: {navBadges: {}}};
  await nextTick();

  expect(badges.value).toEqual({});

  scope.stop();
});
