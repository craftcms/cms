import {effectScope, nextTick, reactive} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {useUnderlineLinks} from './useUnderlineLinks';

const page = reactive<{props: Record<string, any>}>({props: {}});

vi.mock('@inertiajs/vue3', () => ({usePage: () => page}));

const underlined = () => document.body.classList.contains('underline-links');

afterEach(() => {
  document.body.classList.remove('underline-links');
  page.props = {};
});

it('follows the current user’s preference from page to page', async () => {
  page.props = {craft: {currentUser: {underlineLinks: true}}};
  const scope = effectScope();
  scope.run(() => useUnderlineLinks());

  expect(underlined()).toBe(true);

  page.props = {craft: {currentUser: {underlineLinks: false}}};
  await nextTick();

  expect(underlined()).toBe(false);

  scope.stop();
});

it('leaves links plain without a signed-in user', () => {
  page.props = {craft: {currentUser: null}};
  const scope = effectScope();
  scope.run(() => useUnderlineLinks());

  expect(underlined()).toBe(false);

  scope.stop();
});
