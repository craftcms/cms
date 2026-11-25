import {effectScope, nextTick, reactive} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {useAccessibilityClasses} from './useAccessibilityClasses';

const page = reactive<{props: Record<string, any>}>({props: {}});

vi.mock('@inertiajs/vue3', () => ({usePage: () => page}));

const hasClass = (name: string) => document.body.classList.contains(name);

afterEach(() => {
  document.body.classList.remove('underline-links', 'use-shapes');
  page.props = {};
});

it('follows the current user’s preferences from page to page', async () => {
  page.props = {
    craft: {currentUser: {underlineLinks: true, useShapes: false}},
  };
  const scope = effectScope();
  scope.run(() => useAccessibilityClasses());

  expect(hasClass('underline-links')).toBe(true);
  expect(hasClass('use-shapes')).toBe(false);

  page.props = {
    craft: {currentUser: {underlineLinks: false, useShapes: true}},
  };
  await nextTick();

  expect(hasClass('underline-links')).toBe(false);
  expect(hasClass('use-shapes')).toBe(true);

  scope.stop();
});

it('leaves links plain and dots round without a signed-in user', () => {
  page.props = {craft: {currentUser: null}};
  const scope = effectScope();
  scope.run(() => useAccessibilityClasses());

  expect(hasClass('underline-links')).toBe(false);
  expect(hasClass('use-shapes')).toBe(false);

  scope.stop();
});
